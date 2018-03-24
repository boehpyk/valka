<?php

namespace modules\Order\Backend\controllers;

use App\Application;
use PDO;
use Page\Backend\controllers\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Util\Util;


class Order extends Page
{

    function __construct(Application $app, Request $request)
    {
        parent::__construct($app);

        $this->app = $app;
        $this->dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/orderfiles/";
    }


    function handle(Request $request){

        if ($request->request->has('Update')){
            $this->updateForm($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/orders/');
        }
        else {
            return $this->showArticleForm($request);
        }
    }



    function updateForm(Request $request)
    {
        $this->updateOrders($request);
    }

    function showArticleForm(Request $request)
    {
        $content = $this->app['twig']->render('modules/Order/Backend/templates/OrderList.html.twig', array(
            'orders'    => $this->getOrdersList($request)
        ));
        return $this->formAction($content);

    }

    private function getOrdersList(Request $request)
    {
        $sql = "SELECT * FROM Orders WHERE handled = '".(($request->query->has('archive') && $request->query->get('archive') == 'yes') ? 'yes' : 'no')."' ORDER BY date DESC";
        $stmt = $this->app['db']->query($sql);
        $res = array();
        while ($row = $stmt->fetch()) {
            $row['images'] = $this->getOrderImages($row["id"]);
            $res[] = $row;
        }
        return $res;
    }

    private function getOrderImages($id)
    {
        $sql = "SELECT * FROM OrdersFile WHERE order_id = :order_id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue('order_id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();

    }

    function updateOrders(Request $request)
    {
        if ($request->request->has("exists") && count($request->request->get("exists")) > 0) {
            $sql = "UPDATE Orders SET handled='no' WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($_POST["exists"] as $key=>$value) {
                if ($value == 'yes') {
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("sub_handled") && count($request->request->get('sub_handled') > 0)) {
            $sql = "UPDATE Orders SET handled='yes' WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($request->request->get('sub_handled') as $key=>$value) {
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

    function deleteArticle($id)
    {
        $sql = "DELETE FROM Orders WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();

        $dirname = $this->dir.'/'.$id;

        if (is_dir($dirname)) $dir_handle = opendir($dirname);
        else  return;

        if (!$dir_handle) return false;
        while($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file)) unlink($dirname."/".$file);
                else $this->deleteArticleFiles($dirname.'/'.$file);
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
    }

}

