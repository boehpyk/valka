<?php

namespace modules\Feedback\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;

class Feedback extends Page
{
    protected $article_id;
    protected $app;
    protected $article_info = null;

    private $limit = 20;


    function __construct(Application $app, $id = null)
    {
        parent::__construct($app);

        $this->article_id = $id;
        $this->app = $app;
    }

    public function handle(Request $request)
    {
        if ($this->article_id) {
            $repo = new PageRepository($this->app, $this->article_id);
            $this->article_info = $repo->getPageInfo();
        }

        if ($request->request->has('Update')) {
            $this->updateAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$this->article_id.'/');
        }
        elseif ($request->request->has('Add')) {
            $inserted_id = $this->addAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$inserted_id.'/');
        }
        elseif ($request->request->has('deleteArticle')) {
            $this->deleteAction();
            $parent_id = $request->request->get('parent_id');
            if ($parent_id !== null && $parent_id > 0) {
                return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$parent_id.'/');
            }
            else {
                return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/');
            }

        }
        else {
            return $this->showForm($request);
        }
    }


    private function showForm(Request $request)
    {
        if ($this->article_info['subtype'] == 'FeedbackList') {

            [$itemslist, $navigation] = $this->getItemsList($request);

            $content = $this->app['twig']->render('modules/Feedback/Backend/templates/FeedbackList.html.twig', array(
                'article'       => $this->article_info,
                'path'          => $this->getPath($this->article_id),
                'itemslist'     => $itemslist,
                'navigation'    => $navigation
            ));
        }
        elseif ($this->article_info['subtype'] == 'Feedback') {

            $content = $this->app['twig']->render('modules/Feedback/Backend/templates/Feedback.html.twig', array(
                'article'       => $this->article_info,
                'path'          => $this->getPath($this->article_id)
            ));
        }

        return $this->formAction($content);
    }

    private function updateAction($request)
    {
        $this->globalUpdate($request);
        if ($this->article_info['subtype'] == 'FeedbackList') {
            $this->updateItems($request);
        }
        if ($this->article_info['subtype'] == 'Feedback') {
            $sql = "UPDATE Feedback SET date=:date WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue("date", date('Y-m-d', strtotime($request->request->get('date'))));
            $stmt->bindValue('id', $this->article_id);
            $stmt->execute();
        }

    }

    function updateItems(Request $request)
    {
        if ($request->request->has("exists") && count($request->request->get("exists")) > 0) {
            $sql = "UPDATE Page SET publish='no' WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($_POST["exists"] as $key=>$value) {
                if ($value == 'yes') {
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("sub_publish") && count($request->request->get('sub_publish') > 0)) {
            $sql = "UPDATE Page SET publish='yes' WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($request->request->get('sub_publish') as $key=>$value) {
                if ($value == 'yes') {
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("delete") && count($request->request->get('delete')) > 0) {
            foreach ($request->request->get('delete') as $key=>$value) {
                if ($value == 'yes') {
                    $this->deleteArticle($key);
                }
            }
        }
    }    

    function addAction(Request $request)
    {

        $sql = "INSERT INTO 
                            Page (
                              title, 
                              parent_id, 
                              date_add, 
                              date_update, 
                              type, 
                              subtype
                            ) 
                      VALUES (
                          :add_title, 
                          :parent_id, 
                          ".time().", 
                          ".time().", 
                          'Feedback', 
                          'Feedback'
                        )";

        $title = Util::makeLidByWords(strip_tags($request->request->get('question')));

        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':add_title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':parent_id', $request->request->get("parent_id"), PDO::PARAM_STR);
        $stmt->execute();

        $id = $this->app['db']->lastInsertId();

        $this->app['db']->query("UPDATE Page set service_name='".$id."' WHERE id=".$id);

        $sql = "UPDATE Page set "
            . "url='".$this->makeUrl($id)."' "
            . "WHERE id=".$id;
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();

        $sql = "INSERT INTO Feedback (id, date, question) VALUES (:id, '".date('Y-m-d')."', :question)";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':question', strip_tags($request->request->get('question')), PDO::PARAM_STR);
        $stmt->execute();


        if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/uplds/".$id)) {
            mkdir($_SERVER["DOCUMENT_ROOT"]."/uplds/".$id, 0755);
            mkdir($_SERVER["DOCUMENT_ROOT"]."/uplds/".$id."/gallery", 0755);
        }

        return $id;
    }

    public function deleteAction()
    {
        $this->deleteArticle($this->article_id);
    }


    private function getItemsList(Request $request)
    {
        $page = ($request->query->has('page') && (int)$request->query->get('page') > 0) ? (int)$request->query->get('page') : 1;

        $sql = "SELECT COUNT(*) FROM Page WHERE Page.parent_id = :article_id AND Page.type = 'Feedback'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $num = $stmt->fetchColumn();

        $sql = "SELECT Page.id, Page.title, Page.publish, Feedback.date FROM Page, Feedback WHERE Page.parent_id = :article_id AND Page.type = 'Feedback' AND Page.id=Feedback.id ORDER BY Feedback.date DESC".$this->getLimit($page, $this->limit);
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);

        $stmt->execute();
        $res = array();
        $newslist = $stmt->fetchAll();

        $navigation = $this->drawPages($num, $this->limit);

        $res[] = $newslist;
        $res[] = $navigation;

        return $res;

    }


}