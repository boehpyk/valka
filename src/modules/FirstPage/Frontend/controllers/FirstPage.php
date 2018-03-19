<?php

namespace modules\FirstPage\Frontend\controllers;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Page\Frontend\controllers\Page;

class FirstPage extends Page
{
    protected $article_info;

    function __construct($article_id, Application $app)
    {
        parent::__construct($article_id, $app);
        $this->app = $app;
        $this->article_id = $article_id;
    }

    public function handle()
    {
        $content = $this->app['twig']->render('modules/FirstPage/Frontend/templates/FirstPage.html.twig', array(
            'article'       => $this->article_info,
            'events'        => $this->eventList(),
            'cite'          => $this->getCite(),
            'common_links'  => $this->common_links
        ));
        return $this->showAction($content);
    }

    private function eventList()
    {
        $sql = "SELECT 
                  Page.id, 
                  Page.title, 
                  Page.url,
                  Event.datebegin, 
                  Event.lid, 
                  EventType.title as type_title 
                FROM 
                  Page, 
                  Event, 
                  EventType 
                WHERE 
                  Page.publish='yes' 
                AND 
                  Event.id=Page.id
                AND 
                  Event.type_id!=2 
                AND 
                  EventType.id=Event.type_id
                ORDER BY 
                  Event.datebegin DESC
                LIMIT ".(($this->article_info['events_num'] !== null) ? $this->article_info['events_num'] : 0);

        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();
        $events = array();
        while ($row = $stmt->fetch()) {
            $row['img_exists'] = file_exists($_SERVER['DOCUMENT_ROOT'].'/uplds/'.$row['id'].'/fp_image_'.$row['id'].'.jpg');
            $events[] = $row;
        }
        return $events;

        return $events;

    }

    private function getCite()
    {
        $sql="SELECT text FROM Cite ORDER BY RAND() LIMIT 1";
        $stmt = $this->app['db']->query($sql);
        $res =  $stmt->fetchAll();
        if (count($res) > 0) {
            $item = $res[0];
            return $item['text'];
        }
        return '';
    }


    public function getPageTitle()
    {
        return 'Литературно-мемориальный музей Ф.М. Достоевского';
    }
}