<?php

namespace modules\Gallery\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;


class Gallery extends Page
{
    protected $article_id;
    protected $dir;
    protected $article_info;
    private $size_big_width = 1080;
    private $size_big_height = 720;

    private $size_small_width = 320;
    private $size_small_height = 240;


    function __construct(Application $app, $id = null)
    {
        parent::__construct($app);

        $this->article_id = $id;
        $this->app = $app;
        $repo = new PageRepository($this->app, $this->article_id);
        $this->article_info = $repo->getPageInfo();
        $this->dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/gallery/";

        if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id)) mkdir($_SERVER["DOCUMENT_ROOT"]."/files/".$this->article_id, 755);
        if (!file_exists($this->dir)) mkdir($this->dir);

    }


    function handle(Request $request){

        if ($request->request->has('Update')){
            $this->updateForm($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/gallery/'.$this->article_id.'/');
        }
        elseif ($request->request->has('addPhoto')){
            $this->addPhoto($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/gallery/'.$this->article_id.'/');
        }
        else {
            return $res = $this->showArticleForm($request);
        }

        return $res;

    }



    function updateForm(Request $request)
    {
        $this->updatePhotos($request);
        $this->sortPages($request, 'GalleryPhoto');
    }




    function showArticleForm(Request $request)
    {
        $content = $this->app['twig']->render('modules/Gallery/Backend/templates/Gallery.html.twig', array(
            'article'   => $this->article_info,
            'photos'    => $this->photosList($request),
            'path'      => $this->getPath($this->article_id),
        ));
        return $this->formAction($content);

    }

    function checkFields()
    {
        return true;
    }


    function photosList(Request $request)
    {
        $limit = 120;

        $page = ($request->request->has("page") && (int)$request->request->get("page") > 0) ? $request->request->get("page") : 1;

        $sql = "SELECT COUNT(*) FROM GalleryPhoto WHERE gallery_id=:article_id ORDER BY position, id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $num = $stmt->fetchColumn();
        $count = $num;

        $sql = "SELECT * FROM GalleryPhoto WHERE gallery_id=:article_id ORDER BY position, id ".Util::getMySQLLimit($page, $limit);
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $photos = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $photos[] = array(
                "id"                => $row["id"],
                "filename"          => $row["filename"],
                "preview"           => $row["filename_sm"],
                "title"             => $row["title"],
                "description"       => $row["description"],
                "publish_checked"   => (($row['publish'] == 'yes') ? ' checked' : null)
            );
        }
        return $photos;
    }

    function updatePhotos(Request $request)
    {
        if ($request->request->has("exists") && count($request->request->get("exists")) > 0) {
            foreach ($request->request->get("exists") as $key=>$value) {
                if ($value == 'yes') {
                    $sql = "UPDATE GalleryPhoto SET publish='no' WHERE id=:key";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':key', $key, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("sub_publish") && count($request->request->get("sub_publish")) > 0) {
            foreach ($request->request->get("sub_publish") as $key=>$value) {
                if ($value == 'yes') {
                    $sql = "UPDATE GalleryPhoto SET publish='yes' WHERE id=:key";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':key', $key, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("p_title") && count($request->request->get("p_title")) > 0) {
            foreach ($request->request->get("p_title") as $key=>$value) {
                if (isset($key) && (int)$key > 0) {
                    $sql = "UPDATE GalleryPhoto SET title=:title WHERE id=:key";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':title', $value, PDO::PARAM_STR);
                    $stmt->bindValue(':key', $key, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
        if ($request->request->has("delete") && count($request->request->get("delete")) > 0) {
            foreach ($request->request->get("delete") as $key=>$value) {
                if ($value == 'yes') {
                    $sql = "SELECT * FROM GalleryPhoto WHERE id=:key";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':key', $key, PDO::PARAM_INT);
                    $stmt->execute();
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (file_exists($this->dir.$row["filename"])) {
                            unlink($this->dir . $row["filename"]);
                        }
                        if (file_exists($this->dir.$row["filename_sm"])) {
                            unlink($this->dir . $row["filename_sm"]);
                        }
                        if (file_exists($this->dir."sm_".$row["filename"])) {
                            unlink($this->dir."sm_".$row["filename"]);
                        }
                        if (file_exists($this->dir."pr_".$row["filename"])) {
                            unlink($this->dir."pr_".$row["filename"]);
                        }
                    }
                    $sql = "DELETE FROM GalleryPhoto WHERE id=:key";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':key', $key, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
    }

    function addPhoto(Request $request)
    {
        for ($i=0; $i<count($request->files->get('photo')); $i++) {
            $uploaded_file = $request->files->get('photo')[$i];
            if  (file_exists($uploaded_file->getRealPath())) {
                $photo_name     = Util::filenamefix($uploaded_file->getClientOriginalName());
                $preview_name = "sm_".$photo_name;
                $title = $request->request->get('title')[$i];
                $size = $this->getWidthHeight($uploaded_file->getRealPath());
                $weight = $uploaded_file->getSize();
                if ($uploaded_file->move($this->dir, $photo_name)) {
                    $sql = "INSERT INTO GalleryPhoto values (
                        NULL,
                        :article_id,
                        :photo_name,
                        :preview_name,
                        :title,
                        :width,
                        :height,
                        '',
                        :photo_size,
                        '0',
                        'yes'
                        )";

                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
                    $stmt->bindValue(':photo_name', $photo_name, PDO::PARAM_STR);
                    $stmt->bindValue(':preview_name', $preview_name, PDO::PARAM_STR);
                    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                    $stmt->bindValue(':width', $size["width"], PDO::PARAM_INT);
                    $stmt->bindValue(':height', $size["height"], PDO::PARAM_INT);
                    $stmt->bindValue(':photo_size', $weight, PDO::PARAM_INT);
                    $stmt->execute();

                    $file_id = $this->app['db']->lastInsertId();

//                        $this->resizeToBigImage($photo_name);

                    $f_sm = "sm_".$photo_name;
                    $f_pr = "pr_".$photo_name;

                    copy($this->dir.$photo_name, $this->dir.$f_pr);
                    copy($this->dir.$photo_name, $this->dir.$f_sm);

                    $this->resizeToBigImage($photo_name, $this->size_big_width, $this->size_big_height);
                    $sm_size = Util::resizeAndCrop($this->dir, $f_sm, $this->size_small_width, $this->size_small_height, true);

                    Util::resizeAndCropForSquareContainer($this->dir, $f_pr, 145, 145);

                    $sql2 = "UPDATE GalleryPhoto SET width=:width, height=:height WHERE id=:file_id";
                    $stmt = $this->app['db']->prepare($sql2);
                    $stmt->bindValue(':width', $sm_size[0], PDO::PARAM_INT);
                    $stmt->bindValue(':height', $sm_size[1], PDO::PARAM_INT);
                    $stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
    }

    function getWidthHeight($file) {
        $res = array();
        $size = GetImageSize($file);

        if (!$size) return false;

        $res["width"] = $size[0];
        $res["height"] = $size[1];
        return $res;
    }


    function resizeToBigImage($filename, $w_max=900, $h_max=700) {

        $f = $this->dir.$filename;
        $gis = GetImageSize($f);
        $w_src = $gis[0];
        $h_src = $gis[1];
        if ($w_src > $h_src) {
            if ($w_src <= $w_max) return;
            resize($f, $f, $w_max, null, false);
        }
        elseif ($w_src <= $h_src) {
            if ($h_src <= $h_max) return;
            resize($f, $f, null, $h_max, false);
        }
    }

    function resizeAndCrop($filename, $w_aim, $h_aim) {

        $f = $this->dir.$filename;
        $gis = GetImageSize($f);
        $w_src = $gis[0];
        $h_src = $gis[1];
        $ratio = $w_aim/$h_aim;
        if ($w_src/$h_src >= $ratio) {
            resize($f, $f, null, $h_aim, false);
        }
        else {
            resize($f, $f, $w_aim, null, false);
        }

        $gis_list = $final_size = GetImageSize($f);
        $w_list = $gis_list[0];
        $h_list = $gis_list[1];
        $x_list = ceil(($w_list-$w_aim)/4);
        $y_list = ceil(($h_list-$h_aim)/4);
        if ($w_src/$h_src >= $ratio) {
            crop($f, $f, array($x_list,0,$w_aim+$x_list,$h_aim));
        }
        else {
            crop($f, $f, array(0,$y_list,$w_aim,$h_aim+$y_list));
        }
        return $final_size;
    }




    private function sortPhotos()
    {
        if (isset($_POST["sortdata"]) && strlen($_POST["sortdata"]) > 0) {
            $sort_arr = preg_split("/[,]+/",$_POST["sortdata"]);

            $i = 1;

            foreach($sort_arr as $value) {
                $sql = "UPDATE galleries_photos SET position='".$i."' WHERE id='".$value."'";
                mysql_query($sql);
                $i++;
            }

        }
    }



}

