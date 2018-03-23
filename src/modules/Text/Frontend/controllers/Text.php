<?php

namespace modules\Text\Frontend\controllers;

use Page\Frontend\controllers\Page;
use Symfony\Component\HttpFoundation\Request;
use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Text extends Page
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
        if ($this->article_info['subtype'] == 'Endpoint') {
            $stmt = $this->app['db']->prepare("select url from Page WHERE parent_id=:id AND main='yes'");
            $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).$row['url']);

        }
        else {
            if ($this->article_info['is_index'] == 'yes') {
                $subdeps = $this->getSubdeps();
            }
            else {
                $subdeps = false;
            }

            $content = $this->app['twig']->render('modules/Text/Frontend/templates/Text.html.twig', array(
                'article'               => $this->article_info,
                'photos'                => $this->getPhotos(),
                'subdeps'               => (($subdeps !== false && is_array($subdeps) && count($subdeps) > 0) ? $subdeps : false)
            ));
            return $this->showAction($content, $this->article_info);
        }
    }


    public function getPageTitle()
    {
        return $this->article_info['title'];
    }

}