<?php

namespace modules\FirstPage\Frontend\controllers;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Page\Frontend\controllers\Page;
use Symfony\Component\HttpFoundation\RedirectResponse;
use PDO;
use Util\Util;

class FirstPage extends Page
{
    protected $article_info;

    private $errors = array();

    private $dir;

    function __construct($article_id, Application $app)
    {
        parent::__construct($article_id, $app);
        $this->app = $app;
        $this->article_id = $article_id;
    }

    public function handle(Request $request)
    {
        if ($request->request->has('Send')) {
            $this->handleOrder($request);
        }
        $content = $this->app['twig']->render('modules/FirstPage/Frontend/templates/FirstPage.html.twig', array(
            'article'       => $this->article_info,
            'feedbacks'     => $this->feedbackList(),
            'lastphotos'    => $this->getLastPhotos(),
            'services'      => $this->getServices(),
            'errors'        => $this->errors
        ));
        return $this->showAction($content);
    }

    private function feedbackList()
    {
        $sql = "SELECT 
                  Feedback.question, 
                  Feedback.name 
                FROM 
                  Page, 
                  Feedback
                WHERE 
                  Page.publish='yes' 
                AND 
                  Feedback.id=Page.id
                ORDER BY 
                  Feedback.date DESC
                LIMIT 10";

        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();

    }

    private function getLastPhotos()
    {
        $sql="SELECT gallery_id, filename, title FROM GalleryPhoto ORDER BY id DESC LIMIT 10";
        $stmt = $this->app['db']->query($sql);
        return $stmt->fetchAll();
    }

    private function getServices()
    {
        $sql="SELECT id, title FROM RefServices WHERE publish='yes'";
        $stmt = $this->app['db']->query($sql);
        return $stmt->fetchAll();
    }

    private function handleOrder(Request $request)
    {
        if ($this->checkForm($request)) {
            $this->addOrder($request);
            $this->sendEmail($request);
            $this->app['session']->getFlashBag()->add('message', array('type' => 'success', 'content' => '<p><b>Спасибо за заказ!</b></p><p>В ближайшее время наш менеджер свяжется с Вами!</p>'));
            return new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null) . '/');
        }
        else {
            $err_str = '';
            foreach ($this->errors as $error) {
                $err_str .= $error.'<br />';
            }
            $this->app['session']->getFlashBag()->add('message', array('type' => 'danger', 'content' => '<p><b>При заполнении формы произошли следующие ошибки:</b></p><p>'.$err_str.'</p>'));
//            return new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null) . '/');
        }
    }

    private function addOrder(Request $request)
    {
        $sql = 'INSERT INTO Orders (name, email, phone, date, service, description) VALUES (:name, :email, :phone, NOW(), :service, :description)';


        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':name', $request->request->get('name'), PDO::PARAM_STR);
        $stmt->bindValue(':email', $request->request->get('email'), PDO::PARAM_STR);
        $stmt->bindValue(':phone', $request->request->get('phone'), PDO::PARAM_STR);
        $stmt->bindValue(':service', $request->request->get("service"), PDO::PARAM_STR);
        $stmt->bindValue(':description', $request->request->get('description'), PDO::PARAM_STR);
        $stmt->execute();

        $id = $this->app['db']->lastInsertId();

        $this->addPhoto($request, $id);

    }

    private function checkForm(Request $request)
    {
        if (!$request->request->has('name') or strlen(strip_tags($request->request->get('name'))) == 0) {
            $this->errors[] = '"Имя" - обязательное поле. Введите значение';

        }
        if ((!$request->request->has('email') or strlen(strip_tags($request->request->get('email'))) == 0) && (!$request->request->has('phone') or strlen(strip_tags($request->request->get('phone'))) == 0) ) {
            $this->errors[] = 'Введите значение в поля "E-mail" или "Телефон"';

        }
        if ((!$request->request->has('agree') or $request->request->get('agree') !== 'yes')) {
            $this->errors[] = 'Необходимо согласиться с Условиями предоставления персональных данных';

        }
        if (count($this->errors) > 0) {
            return false;
        }

        return true;
    }

    private function sendEmail(Request $request)
    {
        $to = $this->getAdminEmail();
        $email          = $request->request->get('email');
        $name           = $request->request->get('name');
        $phone          = $request->request->get('phone');
        $description    = $request->request->get('description');
        $service        = $request->request->get('service');
        $subject = "Заявка с сайта";
        $headers = "From:" . $name . "<" . $email . ">\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8";
        $headers .= "Content-Transfer-Encoding: 8bit\n";
        $msg = "Посетитель сайта $name ($email) заказал работы:\n\n";
        $msg .= 'Имя: '.$name."\n";
        $msg .= 'E-mail: '.$email."\n";
        $msg .= 'Телефон: '.$phone."\n";
        $msg .= 'Работы: '.$service."\n";
        $msg .= 'Описание: '.$description."\n\n";
        $msg .= "Дата: " . date("d-m-Y, H:i:s", time()) . "\n";
        mail($to,$subject,$msg,$headers);

    }

    private function addPhoto(Request $request, $id)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'].'/uplds/orderfiles/'.$id.'/';

        if (!file_exists($dir)) mkdir($dir);

        for ($i=0; $i<count($request->files->get('photo')); $i++) {
            $uploaded_file = $request->files->get('photo')[$i];
            if  (file_exists($uploaded_file->getRealPath())) {


                $photo_name     = md5($uploaded_file->getClientOriginalName().time()).'.'.$uploaded_file->getClientOriginalExtension();
                if ($uploaded_file->move($dir, $photo_name)) {
                    $sql = "INSERT INTO OrdersFile values (
                        NULL,
                        :photo_name,
                        :order_id
                        )";

                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':photo_name', $photo_name, PDO::PARAM_STR);
                    $stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
                    $stmt->execute();

                    Util::resizeAndCrop($dir, $photo_name, 900, 700, false);

                }
            }
        }
    }



    public function getPageTitle()
    {
        return 'Валка деревьев сложных и не очень';
    }
}