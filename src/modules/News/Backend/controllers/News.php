<?php

namespace modules\News\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;

class News extends Page
{
    protected $article_id;
    protected $app;
    protected $article_info = null;

    private $fp_image_width = 327;
    private $fp_image_height = 316;

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
            $this->deleteAction($request);
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
        if ($this->article_info['subtype'] == 'NewsList') {

            [$newslist, $navigation] = $this->newsList($request);

            $content = $this->app['twig']->render('modules/News/Backend/templates/NewsList.html.twig', array(
                'article'       => $this->article_info,
                'path'          => $this->getPath($this->article_id),
                'newslist'      => $newslist,
                'navigation'    => $navigation
            ));
        }
        elseif ($this->article_info['subtype'] == 'News') {

            $content = $this->app['twig']->render('modules/News/Backend/templates/News.html.twig', array(
                'article'       => $this->article_info,
                'path'          => $this->getPath($this->article_id)
            ));
        }

        return $this->formAction($content);
    }

    private function updateAction($request)
    {
        $this->globalUpdate($request);
        if ($this->article_info['subtype'] == 'NewsList') {
            $this->updateNews($request);
        }
        if ($this->article_info['subtype'] == 'News') {
            $sql = "UPDATE News SET date=:date WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue("date", date('Y-m-d', strtotime($request->request->get('date'))));
            $stmt->bindValue('id', $this->article_id);
            $stmt->execute();
            $this->manageFPImage($request);
        }

    }

    function updateNews(Request $request)
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
                          :news_title, 
                          :parent_id, 
                          ".time().", 
                          ".time().", 
                          'News', 
                          'News'
                        )";

        $title = strip_tags($request->request->get('news_title'));

        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':news_title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':parent_id', $request->request->get("parent_id"), PDO::PARAM_STR);
        $stmt->execute();

        $id = $this->app['db']->lastInsertId();

        $this->app['db']->query("UPDATE Page set service_name='".Util::filenamefix($title)."_".$id."' WHERE id=".$id);

        $sql = "UPDATE Page set "
            . "url='".$this->makeUrl($id)."' "
            . "WHERE id=".$id;
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();

        $sql = "INSERT INTO News (id) VALUES (".$id.")";
        $this->app['db']->query($sql);


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


    private function newsList(Request $request)
    {
        $page = ($request->query->has('page') && (int)$request->query->get('page') > 0) ? (int)$request->query->get('page') : 1;

        $sql = "SELECT COUNT(*) FROM Page WHERE Page.parent_id = :article_id AND Page.type = 'News'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $num = $stmt->fetchColumn();

        $sql = "SELECT Page.id, Page.title, Page.publish, News.date FROM Page, News WHERE Page.parent_id = :article_id AND Page.type = 'News' AND Page.id=News.id ORDER BY News.date DESC".$this->getLimit($page, $this->limit);
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

    private function manageFPImage(Request $request)
    {
        $uploaded_file = $request->files->get('fp_image');

        if (null == $uploaded_file) return;

        $dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/";
        $photo_name = 'fp_image_'.$this->article_id.'.jpg';
        if (in_array($uploaded_file->getMimeType(), $this->images_mimetypes)) {
            $uploaded_file->move($dir, $photo_name);
            Util::resizeAndCrop($dir, $photo_name, $this->fp_image_width, $this->fp_image_height, true);
        }
    }


}