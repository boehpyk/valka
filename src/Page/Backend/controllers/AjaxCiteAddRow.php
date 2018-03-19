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

class AjaxCiteAddRow extends Page
{
    function __construct($app)
    {
        parent::__construct($app);
    }

    function handle(Request $request) {

        if (!$this->is_admin) {
            throw new AccessDeniedException('Access denied');
        }

        return $this->showForm();
    }


    function showForm()
    {
        $sql = "INSERT INTO Cite (id) VALUES (NULL)";
        $stmt = $this->app['db']->query($sql);
        $id = $this->app['db']->lastInsertId();

        $content = $this->app['twig']->render('Page/Backend/templates/AjaxCiteAddRow.html.twig', array(
            'id'  => $id
        ));

        return $content;

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