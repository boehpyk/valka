<?php

namespace modules\Refs\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;

class EventType extends Page
{
    protected $article_id;
    protected $app;
    protected $article_info = null;

    function __construct(Application $app)
    {
        parent::__construct($app);

        $this->app = $app;
    }

    public function handle(Request $request)
    {
        if ($request->request->has('article_id') && (int)$request->request->get('article_id') > 0) {
            $this->article_id = $request->request->get('article_id');
            $repo = new EventTypeRepository($this->app, $this->article_id);
            $this->article_info = $repo->getPageInfo();
        }
        else {
            $this->article_id = null;
        }

        if ($request->request->has('Update')) {
            $this->updateAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/refs/event_type/');
        }
        elseif ($request->request->has('Add')) {
            $this->addAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/refs/event_type/');
        }
        elseif ($request->request->has('deleteArticle')) {
            $this->deleteAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/refs/event_type');
        }
        else {
            return $this->showForm();
        }
    }


    private function showForm()
    {
        if ($this->article_id) {

        }
        else {
            $content = $this->app['twig']->render('modules/Refs/Backend/templates/EventType/EventTypeList.html.twig', array(
                'eventtypes' => $this->eventTypesList()
            ));
        }
        return $this->formAction($content);
    }

    function eventTypesList()
    {
        $sql = "SELECT * FROM EventType";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();
        $eventtypes = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eventtypes[] = array(
                "id"                => $row["id"],
                "title"             => $row["title"],
                "publish_checked"   => (($row['publish'] == 'yes') ? ' checked' : null)
            );
        }
        return $eventtypes;
    }

    private function updateAction($request)
    {
        if ($this->article_id) {
            $this->updateEventType($request);
        }
        else {
            $this->updateEventTypes($request);
        }
    }

    function updateEventTypes(Request $request)
    {
        if ($request->request->has("exists") && count($request->request->get("exists")) > 0) {
            $sql = "UPDATE EventType SET publish='no' WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($_POST["exists"] as $key=>$value) {
                if ($value == 'yes') {
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("sub_publish") && count($request->request->get('sub_publish') > 0)) {
            $sql = "UPDATE EventType SET publish='yes' WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($request->request->get('sub_publish') as $key=>$value) {
                if ($value == 'yes') {
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }

        if ($request->request->has("sub_title") && count($request->request->get('sub_title') > 0)) {
            $sql = "UPDATE EventType SET title=:title WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($request->request->get('sub_title') as $key=>$value) {
                if (strlen(strip_tags($value)) > 0) {
                    $stmt->bindValue('title', strip_tags($value));
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }
        
        if ($request->request->has("delete") && count($request->request->get('delete')) > 0) {
            foreach ($request->request->get('delete') as $key=>$value) {
                if ($value == 'yes') {
                    $this->deleteEventType($key);
                }
            }
        }
    }    

    function addAction(Request $request)
    {

        $sql = "INSERT INTO 
                            EventType (
                              title 
                            ) 
                      VALUES (
                          :title 
                        )";

        $title = $request->request->get('event_type_title');

        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function deleteEventType($id)
    {
        $sql = "DELETE FROM EventType WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();
    }


}