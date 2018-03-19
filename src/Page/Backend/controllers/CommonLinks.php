<?php
/**
 * Created by PhpStorm.
 * User: programmer
 * Date: 05/12/2017
 * Time: 19:56
 */

namespace Page\Backend\controllers;

use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CommonLinks extends Page
{
    function __construct($app)
    {
        parent::__construct($app);
    }

    function handle(Request $request) {

        if (!$this->is_admin) {
            throw new AccessDeniedException('Access denied');
        }

        if ($request->request->has('Update')) {
            $this->updateCommonLinks($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/common_links/');
        }
        return $this->showForm();
    }


    function showForm()
    {
        $sql="SELECT * FROM CommonLinks";
        $stmt = $this->app['db']->query($sql);
        $links = $stmt->fetchAll();

        $content = $this->app['twig']->render('Page/Backend/templates/CommonLinks.html.twig', array(
            'links'  => $links
        ));

        return $this->formAction($content);

    }

    function updateCommonLinks(Request $request)
    {
        $stmt = $this->app['db']->prepare("UPDATE CommonLinks SET value=:value WHERE name=:name");
        foreach($request->request->get("fields") as $name=>$value) {
            $stmt->bindValue("name", $name);
            $stmt->bindValue("value", $value);
            $stmt->execute();
        }
    }
}