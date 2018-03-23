<?php

namespace modules\Feedback\Frontend\controllers;

use Page\Frontend\controllers\Page;
use Symfony\Component\HttpFoundation\Request;
use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Feedback extends Page
{
    protected $article_info;

    function __construct($article_id, Application $app)
    {
        parent::__construct($article_id, $app);
        $this->app = $app;
        $this->article_id = $article_id;
    }

    public function handle(Request $request)
    {

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


    public function getPageTitle()
    {
        return $this->article_info['title'];
    }

}