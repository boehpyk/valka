<?php

namespace modules\Feedback\Frontend\controllers;

use Page\Frontend\controllers\Page;
use Symfony\Component\HttpFoundation\Request;
use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Util\Validators;
use Util\Util;

class Feedback extends Page
{
    protected $article_info;
    private $errors = array();

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

        $content = $this->app['twig']->render('modules/Feedback/Frontend/templates/FeedbackLists.html.twig', array(
            'article'               => $this->article_info,
            'feedbacks'             => $this->feedbackList()
        ));
        return $this->showAction($content, $this->article_info);
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

    private function handleOrder(Request $request)
    {
        if ($this->checkForm($request)) {
            $this->addOrder($request);
            $this->sendEmail($request);
            $this->app['session']->getFlashBag()->add('message', array('type' => 'success', 'content' => '<p><b>Спасибо за отзыв!</b></p><p>После одобрения модератором он появится на сайте</p>'));
            return new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null) . '/otzyvy/');
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
        $sql = "INSERT INTO Page (
                                    title, 
                                    parent_id, 
                                    date_add, 
                                    date_update, 
                                    type, 
                                    subtype
                                  ) 
                                  VALUES 
                                  (
                                    :title, 
                                    :parent_id, 
                                    :time, 
                                    :time, 
                                    'Feedback', 
                                    'Feedback'
                                  )";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':title', Util::makeTitle($request->request->get('question')), PDO::PARAM_STR);
        $stmt->bindValue(':parent_id', $this->article_id, PDO::PARAM_STR);
        $stmt->bindValue(':time', time(), PDO::PARAM_INT);
        $stmt->execute();

        $id = $this->app['db']->lastInsertId();


        $sql = 'INSERT INTO Feedback (id, name, email, question, date) VALUES (:id, :name, :email, :question, NOW())';

        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $request->request->get('name'), PDO::PARAM_STR);
        $stmt->bindValue(':email', $request->request->get('email'), PDO::PARAM_STR);
        $stmt->bindValue(':question', $request->request->get('question'), PDO::PARAM_STR);
        $stmt->execute();
    }

    private function checkForm(Request $request)
    {
        if (!$request->request->has('name') or strlen(strip_tags($request->request->get('name'))) == 0) {
            $this->errors[] = '"Имя" - обязательное поле. Введите значение';

        }
//        if (!$request->request->has('email') or strlen(strip_tags($request->request->get('email'))) == 0 or !Validators::validEmail($request->request->get('email'))) {
//            $this->errors[] = 'Неверное значение в поля "E-mail"';
//
//        }
        if (!$request->request->has('question') or strlen(strip_tags($request->request->get('question'))) == 0) {
            $this->errors[] = 'Введите текст отзыва';

        }

        if (!$this->captchaverify($request->get('g-recaptcha-response'))) {
            $this->errors[] = 'Похоже, что Вы робот';

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
        $question       = $request->request->get('question');
        $subject = "Новый отзыв на сайте";
        $headers = "From:" . $name . "<" . $email . ">\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8";
        $headers .= "Content-Transfer-Encoding: 8bit\n";
        $msg = "Посетитель сайта $name ($email) оставил отзыв:\n\n";
        $msg .= 'Имя: '.$name."\n";
        $msg .= 'E-mail: '.$email."\n";
        $msg .= 'Отзыв: '.$question."\n\n";
        $msg .= "Дата: " . date("d-m-Y, H:i:s", time()) . "\n";
        mail($to,$subject,$msg,$headers);

    }



    public function getPageTitle()
    {
        return $this->article_info['title'];
    }

}