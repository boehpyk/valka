<?php

namespace modules\News\Frontend\controllers;

use Page\Frontend\controllers\Page;
use Symfony\Component\HttpFoundation\Request;
use App\Application;
use PDO;
use Util\Util;

class News extends Page
{
    protected $article_info;
    private $limit = 20;

    function __construct($article_id, Application $app)
    {
        parent::__construct($article_id, $app);
        $this->app = $app;
        $this->article_id = $article_id;
    }

    public function handle(Request $request)
    {
        if ($this->article_info['subtype'] == 'News') {
            $content = $this->app['twig']->render('modules/News/Frontend/templates/News.html.twig', array(
                'article'               => $this->article_info,
                'photos'                => $this->getPhotos(),
            ));
            return $this->showAction($content, $this->article_info);
        }
        elseif ($this->article_info['subtype'] == 'NewsList') {

            [$newslist, $navigation] = $this->newsList($request);

            $content = $this->app['twig']->render('modules/News/Frontend/templates/NewsList.html.twig', array(
                'article'       => $this->article_info,
                'newslist'      => $newslist,
                'navigation'    => $navigation
            ));

            return $this->showAction($content, $this->article_info);
        }
    }

    private function newsList(Request $request)
    {
        $page = ($request->query->has('page') && (int)$request->query->get('page') > 0) ? (int)$request->query->get('page') : 1;

        $sql = "SELECT COUNT(*) FROM Page WHERE Page.parent_id = :article_id AND Page.type = 'News' AND Page.publish = 'yes'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $num = $stmt->fetchColumn();

        $sql = "SELECT Page.id, Page.title, Page.publish, Page.url, News.date FROM Page, News WHERE Page.parent_id = :article_id AND Page.type = 'News' AND Page.id=News.id  AND Page.publish = 'yes' ORDER BY News.date DESC".Util::getMySQLLimit($page, $this->limit);
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);

        $stmt->execute();
        $res = array();
        $newslist = $stmt->fetchAll();

        $navigation = Util::drawPagesForFrontend($num, $this->limit);

        $res[] = $newslist;
        $res[] = $navigation;

        return $res;

    }


    public function getPageTitle()
    {
        return $this->article_info['title'];
    }

}