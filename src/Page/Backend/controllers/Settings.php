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

class Settings extends Page
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
            $this->updateSettings($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/settings/');
        }
        return $this->showSettingsForm();
    }


    function showSettingsForm()
    {
        $settings = array();
        $settings["meta_keywords"] = '';
        $settings["meta_description"] =  '';


        $sql="SELECT * FROM Settings";
        $stmt = $this->app['db']->query($sql);
        while ($row = $stmt->fetch()) {
            $settings[$row["name"]] = $row["value"];
        }

        $sql="SELECT * FROM SEOMeta WHERE article_id=0";
        $stmt = $this->app['db']->query($sql);
        while ($row = $stmt->fetch()) {
            $settings["meta_keywords"] = $row["meta_keywords"];
            $settings["meta_description"] =  $row["meta_description"];
        }

        $content = $this->app['twig']->render('Page/Backend/templates/settings.html.twig', array(
            'settings'  => $settings
        ));

        return $this->formAction($content);

    }

    function updateSettings(Request $request)
    {
        $admin_email = $request->request->get("admin_email");
        if (null !== $admin_email  && strlen($admin_email) > 0) {
            $stmt = $this->app['db']->prepare("UPDATE Settings SET value=:admin_email WHERE name='admin_email'");
            $stmt->bindValue("admin_email", $admin_email);
            $stmt->execute();
        }
        $admin_password = $request->request->get("admin_password");
        if (strlen($admin_password) > 0) {
            $encoded = $this->app['security.default_encoder']->encodePassword($admin_password, '');
            $stmt = $this->app['db']->prepare("UPDATE users SET password=:admin_password WHERE username='admin'");
            $stmt->bindValue("admin_password", $encoded);
            $stmt->execute();
        }
        if (strlen($_POST["meta_keywords"]) > 0) {
            $stmt = $this->app['db']->prepare("UPDATE SEOMeta SET meta_keywords=:meta_keywords WHERE article_id='0'");
            $stmt->bindValue("meta_keywords", $request->request->get("meta_keywords"));
            $stmt->execute();
        }
        if (strlen($_POST["meta_description"]) > 0) {
            $stmt = $this->app['db']->prepare("UPDATE SEOMeta SET meta_description=:meta_description WHERE article_id='0'");
            $stmt->bindValue("meta_description", $request->request->get("meta_description"));
            $stmt->execute();
        }
        //if (strlen($_POST["news_on_main"]) > 0) {
        //    mysql_query("UPDATE settings SET value='".$_POST["news_on_main"]."' WHERE name='news_on_main'");
        //}
        //if (strlen($_POST["news_on_left"]) > 0) {
        //    mysql_query("UPDATE settings SET value='".$_POST["news_on_left"]."' WHERE name='news_on_left'");
        //}

    }

}