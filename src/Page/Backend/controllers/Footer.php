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

class Footer extends Page
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
            $this->updateFooter($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/footer/');
        }
        return $this->showForm();
    }


    function showForm()
    {
        $sql="SELECT * FROM Footer";
        $stmt = $this->app['db']->query($sql);
        while ($row = $stmt->fetch()) {
            $settings['col1'] = $row["col1"];
            $settings['col2'] = $row["col2"];
            $settings['col3'] = $row["col3"];
        }


        $content = $this->app['twig']->render('Page/Backend/templates/footer.html.twig', array(
            'settings'  => $settings
        ));

        return $this->formAction($content);

    }

    function updateFooter(Request $request)
    {
        $stmt = $this->app['db']->prepare("UPDATE Footer SET col1=:col1, col2=:col2, col3=:col3");
        $stmt->bindValue("col1", $request->request->get("col1"));
        $stmt->bindValue("col2", $request->request->get("col2"));
        $stmt->bindValue("col3", $request->request->get("col3"));
        $stmt->execute();
    }
}