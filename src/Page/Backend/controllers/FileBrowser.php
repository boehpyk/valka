<?php
/**
 * Created by PhpStorm.
 * User: programmer
 * Date: 21/11/2017
 * Time: 16:34
 */

namespace Page\Backend\controllers;

use Symfony\Component\HttpFoundation\Request;
use Util\Util;
use PDO;

class FileBrowser
{
    private $filepath = 'uplds';

    function __construct($app)
    {
        $this->app = $app;
    }

    function handle(Request $request) {

        $this->article_id 	= ((strlen($request->get("article_id") > 0)) ? $request->get("article_id") : null);

        if (!$this->article_id) return;

        $this->type		= $request->get("type");
        $this->dir		= $this->getDir();

        if ($request->request->has('Upload')) {
            $this->uploadFiles();
        }
        elseif ($request->request->has("delImg") && (int)$request->get("delImg") > 0) {
            $this->deleteFile($request->get("selected_image"), $request->get("delImg"));
        }

        return $this->showMenu();
    }


    function showMenu()
    {

        $article_title = $this->getPageTitle();

        if ($this->type == 'image') {
            $images_list = $this->makeImagesList();

            return $this->app['twig']->render('Page/Backend/templates/filebrowser/image.html.twig', array(
                'thispageimages'    => $images_list['thispageimages'],
                'galleryimages'     => $images_list['galleryimages'],
                'otherimages'       => $images_list['otherimages'],
                'article_title'     => $article_title
            ));
        }
        elseif ($this->type == 'file') {
            $files_list = $this->makeFilesList();
            $pages_list = $this->makePagesList();
            return $this->app['twig']->render('Page/Backend/templates/filebrowser/file.html.twig', array(
                'pages_list'        => $pages_list,
                'thispagefiles'     => $files_list['thispagefiles'],
                'galleryfiles'      => $files_list['galleryfiles'],
                'otherfiles'        => $files_list['otherfiles'],
                'article_title'     => $article_title
            ));

        }
        elseif ($this->type == 'media') {
            $this->templ->setVariable("media_list", $this->makeMediaList());
            $this->templ->setVariable("global_title", "Выбор файла медиа");
            $this->templ->hideBlock("image_block");
            $this->templ->hideBlock("file_block");
        }
    }

    function makeImagesList()
    {
        $images_list = array();
        $sql = "select * from PageFiles WHERE article_id=:article_id AND type='image'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $images_list['thispageimages'] = array();
        while ($row = $stmt->fetch()) {
            $images_list['thispageimages'][] = array(
                'id'			=> $row["id"],
                'url'			=> "/".$this->filepath."/".$this->article_id."/".$row["filename"],
            );
        }
        // файлы галереи
        $images_list['galleryimages'] = array();
        if ($handle = opendir($_SERVER["DOCUMENT_ROOT"]."/".$this->filepath."/".$this->article_id."/gallery/")) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' or $file == '..') continue;
                $images_list['galleryimages'][] = array(
                    'id'			=> null,
                    'url'			=> "/".$this->filepath."/".$this->article_id."/gallery/".$file,
                );
            }
            closedir($handle);
        }

        //другие разделы
        $sql = "select * from PageFiles WHERE article_id!=:article_id AND type='image'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $images_list['otherimages'] = array();
        while ($row = $stmt->fetch()) {
            $images_list['otherimages'][] = array(
                'id'			=> $row["id"],
                'url'			=> "/".$this->filepath."/".$this->article_id."/".$row["filename"],
            );
        }
        return $images_list;
    }

    function makeFilesList()
    {

        $files_list = array();
        $sql = "select * from PageFiles WHERE article_id=:article_id AND type IN ('image', 'file')";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $files_list['thispagefiles'] = array();
        while ($row = $stmt->fetch()) {
            $files_list['thispagefiles'][] = array(
                'article_id'		=> $this->article_id,
                'id'			=> $row["id"],
                'title'			=> $row["title"],
                'filename'		=> $row["filename"],
                'url'			=> "/".$this->filepath."/".$row["article_id"]."/".$row["filename"]
            );
        }
        // файлы галереи
        $sql = "select * from GalleryPhoto WHERE gallery_id=:article_id AND publish='yes'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $files_list['galleryfiles'] = array();
        while ($row = $stmt->fetch()) {
            $files_list['galleryfiles'][] = array(
                'title'			=> $row["title"],
                'url'			=> "/".$this->filepath."/".$this->article_id."/".$row["filename"]
            );
        }

        //другие разделы
        $sql = "select * from PageFiles WHERE article_id!=:article_id AND type IN ('image', 'file')";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $files_list['otherfiles'] = array();
        while ($row = $stmt->fetch()) {
            $images_list['otherfiles'][] = array(
                'id'			=> $row["id"],
                'url'			=> "/".$this->filepath."/".$this->article_id."/".$row["filename"],
            );
        }
        return $files_list;
    }


    function makePagesList($id=0, &$result=null, $margin=0)
    {
        $margin += 25;
        if ($id > 0) {
            $result .= "<div id=\"block".$id."\" style=\"margin-left: ".$margin."px;margin-bottom:3px;marign-top:3px;\">\n";
        }
        $sql_count = "SELECT COUNT(*) FROM Page WHERE parent_id=:id AND subtype!='news' AND subtype!='users' AND subtype!='first_page' ORDER BY position";
        $stmt = $this->app['db']->prepare($sql_count);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $sql = "SELECT id, type, subtype, title, url FROM Page WHERE parent_id=:id AND subtype!='news' AND subtype!='users' AND subtype!='first_page' ORDER BY position";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue("id", $id, PDO::PARAM_INT);
            $stmt->execute();
                while ($row = $stmt->fetch()){
                    $result .= "<div style=\"float:left; margin: 0px 6px 0px 6px;\"><input type=\"radio\" name=\"url\" value=\"".$row["url"]."\"></div>" . $row['title'] . "<div style=\"margin: 0px; clear: both;\"></div>\n";
                    $this->makePagesList($row['id'], $result);
                    $margin -= 25;
                }
        }
        if ($id > 0) {
            $result .= "</div>\n";
        }
        return $result;
    }

    function makeMediaList()
    {
        $this_page_title = $this->getPageTitle();
        $query = mysql_query("select * from content_files WHERE article_id='".$this->article_id."'") ;//or die(mysql_error());
        while ($row = mysql_fetch_array($query)) {
            $info = explode('.', $row["filename"]);
            $ext = $info[(count($info)-1)];
            //if ($ext == 'avi' or $ext == 'swf' or $ext == 'mov' or $ext == 'rm' or  $ext == 'wma' or  $ext == 'wmv' or $ext == 'mpg') {
            $this->templ->setVariable(array(
                'id'			=> $row["id"],
                'title'			=> $row["title"],
                'filename'			=> $row["filename"],
                'url'			=> "/".$this->filepath."/".$this->article_id."/".$row["filename"],
                'width'			=> $row["width"] + 20,
                'height'       		=> $row["height"] + 20,
                'page_title'       	=> $this_page_title,
                'this_page_title'       => $this_page_title,
            ));
            $this->templ->parse("this_page_media_line");
            //}
        }
        $query = mysql_query("select * from content_files WHERE article_id!='".$this->article_id."' AND type='media'") ;//or die(mysql_error());
        while ($row = mysql_fetch_array($query)) {
            $info = explode('.', $row["filename"]);
            $ext = $info[(count($info)-1)];
            //if ($ext == 'avi' or $ext == 'swf' or $ext == 'mov' or $ext == 'rm' or  $ext == 'wma' or  $ext == 'wmv' or $ext == 'mpg') {
            $this->templ->setVariable(array(
                'id'			=> $row["id"],
                'title'			=> $row["title"],
                'filename'			=> $row["filename"],
                'url'			=> "/".$this->filepath."/".$row["article_id"]."/".$row["filename"],
                'width'			=> $row["width"] + 20,
                'height'       		=> $row["height"] + 20,
                'page_title'       	=> $this->getPageTitle($row["article_id"]),
            ));
            $this->templ->parse("media_line");
            //}
        }
    }



    function getPageTitle($id = null)
    {
        $sql = "select title from Page WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", (($id == null) ? $this->article_id : $id));
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $title = $row["title"];
        }
        return $title;
    }

    private function uploadFiles()
    {
        if (!isset($_FILES["photo"]) or count($_FILES["photo"]) == 0) return;

        for ($i=0; $i<(count($_FILES["photo"]["name"])); $i++) {
            if (file_exists($_FILES["photo"]["tmp_name"][$i])) {
                $filename = $this->article_id."_".Util::rus2trans($_FILES["photo"]["name"][$i]);
                //echo "<b>" . $image["tmp_name"], $_SERVER["DOCUMENT_ROOT"].IMAGES_DIR."/".$this->article_id."/".$filename . "</b><br>";
                if (move_uploaded_file($_FILES["photo"]["tmp_name"][$i], $this->dir."/".$filename)) {
                    $sql = "INSERT INTO PageFiles "
                        . "(article_id, filename, filetype, filesize, type) "
                        . "VALUES "
                        . "(:article_id, :filename, '".$_FILES["photo"]["type"][$i]."', '".$_FILES["photo"]["size"][$i]."', '".$this->type."')";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
                    $stmt->bindValue("filename", $filename, PDO::PARAM_STR);
                    $stmt->execute();
                }
            }
        }
    }

    private function deleteFile($file, $id) {

        $filename = $_SERVER["DOCUMENT_ROOT"].$file;

        if (file_exists($filename)) {

            unlink($filename);

            $sql = "DELETE FROM  content_files WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue("id", $id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    private function getDir()
    {
        if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id)) {
            mkdir($_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id, 0755);
            mkdir($_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/gallery", 0755);
        }
        return $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id;
    }

}