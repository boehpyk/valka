<?php

namespace Page\Backend\controllers;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use PDO;
use Util\Util;

class Page
{
    protected $contents;
    protected $forbidden_types = array('Event', 'FirstPage', 'News', 'Feedback');
    protected $images_mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');
    protected $is_admin = false;
    protected $article_id;
    protected $article_info;
    protected $user;
    protected $common_links;

    protected $dropdown_image_width = 250;
    protected $dropdown_image_height = 250;

    function __construct(Application $app)
    {
        $this->is_admin = $app['security.authorization_checker']->isGranted('ROLE_ADMIN');
        $this->app = $app;
        $this->user = $app['security.token_storage']->getToken()->getUser();
    }

    public function sidebarAction()
    {
        $this->contents = $this->getContents();

        return $sidebar = array(
            'parents_arr'   => implode(',',$this->makeParentArticlesArr($this->article_id)),
            'pages_tree'    => $this->makeMenuTree(),
            'article_id'    => $this->article_id,
            'login'         => $this->user->getUsername()
        );
    }

    protected function formAction($content)
    {
        $this->contents = $this->getContents();

        return $this->app['twig']->render('Page/Backend/templates/layout.html.twig', array(
            'parents_arr'   => implode(',',$this->makeParentArticlesArr($this->article_id)),
            'pages_tree'    => $this->makeMenuTree(),
            'article_id'    => $this->article_id,
            'login'         => $this->user->getUsername(),
            'content'       => $content
        ));
    }

    protected function globalUpdate(Request $request)
    {
        if (null !== $this->article_info && count($this->article_info) > 0) {

            $forbidden_fields = array("id", "parent_id", "url", "type", "subtype");
            $article_fields = array();
            $contents_fields = array();

            $sql = "DESC ".$this->article_info['subtype'];
            $stmt = $this->app['db']->query($sql);
            while ($row = $stmt->fetch()) {
                if (!in_array($row["Field"], $forbidden_fields))
                    $article_fields[] = $row["Field"];
            }

            $sql = "DESC Page";
            $stmt = $this->app['db']->query($sql);
            while ($row = $stmt->fetch()) {
                if (!in_array($row["Field"], $forbidden_fields))
                    $contents_fields[] = $row["Field"];
            }

            if (count($article_fields) > 0) {
                $sql = "UPDATE ".$this->article_info['subtype']." SET ".$this->pdoSet($article_fields,$values)." WHERE id = :id";
                $stmt = $this->app['db']->prepare($sql);
                $values["id"] = $this->article_id;
                $stmt->execute($values);
            }

            if (count($contents_fields) > 0) {
                $sql = "UPDATE Page SET ".$this->pdoSet($contents_fields,$values)." WHERE id = :id";
                $stmt = $this->app['db']->prepare($sql);
                $values["id"] = $this->article_id;
                $stmt->execute($values);
            }
            if (strlen($request->request->get("meta_keywords")) > 0 or strlen($request->request->get("meta_description")) > 0) {
                $sql = "SELECT * FROM SEOMeta WHERE article_id=:article_id";
                $stmt = $this->app['db']->prepare($sql);
                $stmt->bindValue("article_id", $this->article_id);
                $stmt->execute();
                if ($stmt->rowCount() == 0) {
                    $sql = "INSERT INTO SEOMeta (article_id) VALUES (:article_id)";
                    $stmt = $this->app['db']->prepare($sql);
                    $stmt->bindValue("article_id", $this->article_id);
                    $stmt->execute();
                }
            }
            else {
                $sql = "DELETE FROM SEOMeta WHERE article_id=:article_id";
                $stmt = $this->app['db']->prepare($sql);
                $stmt->bindValue("article_id", $this->article_id);
                $stmt->execute();
            }

            if (strlen($request->request->get("meta_keywords")) > 0) {
                $sql = "UPDATE SEOMeta SET meta_keywords=:meta_keywords WHERE article_id=:article_id";
                $stmt = $this->app['db']->prepare($sql);
                $stmt->bindValue("article_id", $this->article_id);
                $stmt->bindValue("meta_keywords", $request->request->get("meta_keywords"));
                $stmt->execute();

            }
            if (strlen($request->request->get("meta_description")) > 0) {
                $sql = "UPDATE SEOMeta SET meta_description=:meta_description WHERE article_id=:article_id";
                $stmt = $this->app['db']->prepare($sql);
                $stmt->bindValue("article_id", $this->article_id);
                $stmt->bindValue("meta_description", $request->request->get("meta_description"));
                $stmt->execute();
            }

            $sql_ = "UPDATE Page SET date_update='".time()."' WHERE id = :id";
            $stmt_ = $this->app['db']->prepare($sql_);
            $stmt_->bindValue("id", $this->article_id);
            $stmt_->execute();
        }
    }

    private function pdoSet($fields, &$values, $source = array()) {
        $set = '';
        $values = array();
        if (!$source) $source = &$_POST;
        foreach ($fields as $field) {
            if (isset($source[$field])) {
                $set.="`".str_replace("`","``",$field)."`". "=:$field, ";
                $values[$field] = $source[$field];
            }
        }
        return substr($set, 0, -2);
    }

    protected function makeUrl($id=0, &$path='', $with_url = false)
    {
        if ($id < 1) {
            $id = $this->article_id;
        }

        if ($id > 0) {
            $sql = "select parent_id from Page WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            while ($row2 = $stmt->fetch()) {
                $parent = $row2['parent_id'];
            }

            $sql = "select service_name from Page WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            while ($row2 = $stmt->fetch()) {
                $service_name = $row2['service_name'];
            }
        }
        else {
            $parent = 0;
        }

        $sql = "select id, service_name, parent_id from Page WHERE id=:parent";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':parent', $parent, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                $path = $row['service_name']."/".$path;
                $this->makeUrl($row['id'], $path);
            }

        }

        $result = "/".$path.$service_name."/";
        return $result;
    }

    protected function countPositions($id)
    {
        $sql = "SELECT MAX(position) as max FROM Page WHERE parent_id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row["max"];
    }

    protected function sortPages(Request $request, $table = 'Page')
    {
        if (!$request->request->has('sortdata') or strlen($request->request->get('sortdata')) == 0) return;
        $sort_arr = explode(',', $request->request->get('sortdata'));
        $sql = "UPDATE ".$table." SET position=:position WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);

        $i = 1;
        foreach ($sort_arr as $value) {
            if ((int)$value > 0) {
                $stmt->bindValue(':id', $value, PDO::PARAM_INT);
                $stmt->bindValue(':position', $i, PDO::PARAM_INT);
                $stmt->execute();
                $i++;
            }
        }
    }


    function updateSubDeps(Request $request)
    {
        if (null !== $request->request->get('exists') && count($request->request->get("exists")) > 0) {
            $in  = str_repeat('?,', count($request->request->get("exists")) - 1) . '?';
            $sql = "UPDATE Page set publish='no' WHERE id IN (".$in.")";
            $stmt = $this->app['db']->prepare($sql);
            $c = 1;
            foreach ($request->request->get("exists") as $value) {
                $stmt->bindValue($c, $value, PDO::PARAM_INT);
                $c++;
            }
            $stmt->execute();
        }

        $this->app['db']->query("UPDATE Page set publish='yes' WHERE special='yes'");

        if (null !== $request->request->get("sub_publish")) {
            foreach($request->request->get("sub_publish") as $key=>$value) {
                if ($value == 'yes') {
                    $stmt = $this->app['db']->prepare("UPDATE Page set publish='yes' WHERE id=:key");
                    $stmt->bindValue("key", $key, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
        $sql = "UPDATE Page set menu='no' WHERE parent_id=:article_id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();

        if (null !== $request->request->get("sub_menu")) {
            foreach($request->request->get("sub_menu") as $key=>$value) {
                if ($value == 'yes') {
                    $stmt = $this->app['db']->prepare("UPDATE Page set menu='yes' WHERE id=:key");
                    $stmt->bindValue("key", $key, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }
        $sql = "UPDATE Page set main='no' WHERE parent_id=:article_id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("article_id", $this->article_id, PDO::PARAM_INT);
        $stmt->execute();

        if (null !== $request->request->get("sub_main") && $request->request->get("sub_main") > 0) {
            $sql = "UPDATE Page set main='yes' WHERE id=:sub_main";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue("sub_main", $request->request->get("sub_main"), PDO::PARAM_INT);
            $stmt->execute();
        }

        if (null !== $request->request->get("delete")) {
            foreach($request->request->get("delete") as $key=>$value) {
                if ($value == '1') {
                    $this->deleteArticle($key);
                }
            }
        }


    }

    function deleteArticle($id)
    {
        $sql = "SELECT type, subtype, parent_id FROM Page WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $parent_id = $row["parent_id"];
            $type = $row["type"];
            $subtype = $row["subtype"];
        }

        $sql = "DELETE FROM ".$subtype." WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "DELETE FROM Page WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();


        $sql = "DELETE FROM GalleryPhoto WHERE gallery_id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "DELETE FROM SEOMeta WHERE article_id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();


        $del = array();
        $sql = "SELECT * FROM PageFiles WHERE article_id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $del[] = $row["id"];
        }
//        new deleteContentFiles($del);
        $dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$id;
        $this->deleteArticleFiles($dir);

        $sql = "SELECT id FROM Page WHERE parent_id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $this->deleteArticle($row["id"]);
        }
    }

    function deleteArticleFiles($dirname)
    {
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
        return true;
    }

    function getPath($id, &$path='')
    {

        if ($id > 0) {
            $stmt = $this->app['db']->prepare("select parent_id from Page WHERE id=:id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            while ($row2 = $stmt->fetch()) {
                $parent = $row2['parent_id'];
            }

            $stmt = $this->app['db']->prepare("select title from Page WHERE id=:id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            while ($row2 = $stmt->fetch()) {
                $title = $row2['title'];
            }
        }
        else {
            $parent = 0;
        }

        $stmt = $this->app['db']->prepare("select id, title, parent_id, type, subtype from Page WHERE id=:parent");
        $stmt->bindValue(':parent', $parent, PDO::PARAM_INT);
        $stmt->execute();

        if (($numrows=$stmt->rowCount()) > 0) {
            while ($row = $stmt->fetch()) {
                $path = '<li><a href="/'.(($this->app['debug'] === true) ? $this->app['dev.handler'].'/' : '').'admin/article/'.$row['id'].'/">'.$row["title"].'</a></li>'."\n ".$path;
                $this->getPath($row['id'], $path);
            }
        }
        $result = '<li><a href="/'.(($this->app['debug'] === true) ? $this->app['dev.handler'].'/' : '').'admin/">Главная</a></li>'.$path.'<li class="active">'.$title.'</li>'."\n";
        return $result;
    }

    function makeMenuTree($parent=0, $has_children = false)
    {
        $result = ($parent > 0) ? "\n<ul".(($has_children) ? " class=\"nav nav-second-level collapse\" id=\"dep".$parent."\"" : null).">\n" : '';
        foreach ($this->contents as $row) {
            if ($row['parent_id'] == $parent){
                if ($this->has_children($row['id'])) {
                    $result .= "\n\t<li><button class=\"expand-btn btn btn-default\" data-toggle=\"collapse\" data-target=\"#dep".$row["id"]."\" id=\"btn".$row["id"]."\">+</button><a href=\"/".(($this->app['debug'] === true) ? $this->app['dev.handler'].'/' : '')."admin/article/".$row["id"]."/\">".$row["title"]."</a>";
                }
                else {
                    $result .= "\n\t<li><a href=\"/".(($this->app['debug'] === true) ? $this->app['dev.handler'].'/' : '')."admin/article/".$row["id"]."/\">".$row["title"]."</a>";
                }
                if ($this->has_children($row['id'])) {
                    $result.= $this->makeMenuTree($row['id'], true);
                }
                $result.= "</li>";
            }
        }
        $result.= "\n\t</ul>\n";

        return $result;
    }

    function makeParentArticlesArr($id = 0, &$arr=array())
    {
        $sql = "SELECT id, parent_id FROM Page WHERE id=:id AND subtype!='first_page' AND subtype!='users'";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $num = $stmt->rowCount();
        if ($num > 0){
            while ($row = $stmt->fetch()){
                $arr[] = $row["id"];
                $this->makeParentArticlesArr($row["parent_id"], $arr);
            }
        }
        return $arr;
    }

    function getContents()
    {
        $res = array();
        $editor_rights = array();
        $forbidden_types_in  = str_repeat('?,', count($this->forbidden_types) - 1) . '?';
        if ($this->is_admin) {
            $sql = "SELECT id, title, parent_id, type FROM Page WHERE subtype NOT IN (".$forbidden_types_in.") AND special!='yes' ORDER BY position";
            $stmt = $this->app['db']->prepare($sql);
            $c = 1;
            foreach ($this->forbidden_types as $value) {
                $stmt->bindValue($c, $value, PDO::PARAM_INT);
                $c++;
            }
        }
        else {
            foreach ($this->editor_rights["rights"] as $key => $value) {
                $editor_rights[] = $key;
            }
            $editor_rights_in  = str_repeat('?,', count($editor_rights) - 1) . '?';
            $forbidden_types_in  = str_repeat('?,', count($this->forbidden_types) - 1) . '?';
            $sql = "SELECT id, title, parent_id, type FROM Page, rights WHERE contents.subtype NOT IN (".$forbidden_types_in.") AND contents.special!='yes' AND rights.article_id IN (".$editor_rights_in.") AND rights.user_id=? AND contents.id=rights.article_id  ORDER BY contents.position";
            $stmt = $this->app['db']->prepare($sql);
            $c = 1;
            foreach ($this->forbidden_types as $value) {
                $stmt->bindValue($c, $value, PDO::PARAM_INT);
                $c++;
            }
            foreach ($editor_rights as $value) {
                $stmt->bindValue($c, $value, PDO::PARAM_INT);
                $c++;
            }
            $stmt->bindValue($c+1, $this->user_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        while ($row = $stmt->fetch()){
            $res[] = array('id'=>$row["id"], 'parent_id'=>$row["parent_id"], 'title'=>$row["title"], 'type'=>$row["type"]);
        }
        return $res;
    }

    private function has_children($id) {
        foreach ($this->contents as $row) {
            if ($row['parent_id'] == $id)
                return true;
        }
        return false;
    }

    protected function manageDropdownImage(Request $request)
    {
        $uploaded_file = $request->files->get('dropdown_image');

        if ($uploaded_file === null) return;

        $dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/";
        $photo_name = 'dropdown_image_'.$this->article_id.'.jpg';
        if (in_array($uploaded_file->getMimeType(), $this->images_mimetypes)) {
            $uploaded_file->move($dir, $photo_name);
            Util::resizeAndCrop($dir, $photo_name, 1500, null, false);
        }
    }

    function drawPages($num, $in_page = 5)
    {
        $result = "<ul class=\"pagination\">\n";
        if($in_page == 0) $in_page = 1;

        if(isset($_REQUEST["page"]) && $_REQUEST["page"] > 1) {
            $page = $_REQUEST["page"];
        }
        else {
            $page = 1;
        }

        $url_params = "";
        foreach($_REQUEST as $key => $value){
            if ($key == 'page' || $key == 'update' || $key == 'phpbb2mysql_sid' || $key == 'phpbb2mysql_data' || $key == 'econ_site') continue;
            $url_params .= $key . "=" . $value . "&";
        }
        $url_params = str_replace(" ", "%", $url_params);

        $query_string = preg_replace("/handler=[a-z]+/","",$_SERVER["QUERY_STRING"]);
        $query_string = preg_replace("/&(id=[0-9]+)?([&|\?]page=[0-9]+)?/","",$query_string);
        $query_string = preg_replace("/page=[0-9]+/","",$query_string);
        //var_dump($query_string);
        if (strlen($query_string) == 0) {
            $delimiter = "?";
        }
        else {
            $delimiter = "&";
        }

        for ($i = 1; $i <= ceil($num/$in_page); $i++) {

            if ($i == $page) {
                $result .= " <li class=\"active\"><a href=\"".$_SERVER['REQUEST_URI'].$delimiter."page=".$i."\">".$i."</a></li> ";
            }
            else {
                $result .= " <li><a href=\"".$_SERVER['REQUEST_URI'].$delimiter."page=".$i."\">".$i."</a></li> ";
            }

            if ($i < ceil($num/$in_page)) {
                $result .= " ";
            }
        }

        /* if($page < ceil($num/$in_page)) {
            $result .= " <a href=\"".$_SERVER['REQUEST_URI'].$delimiter."page=".($page+1)."\">Вперед</a> ";
        }
        */
        return $result."\n</ul>";
    }

    function getLimit($page, $in_page = 20)
    {
        if(!isset($page) or $page == 1) {
            $limit = " LIMIT ".$in_page;
        }
        else {
            $limit = " LIMIT ".(($page-1)*$in_page).", ".$in_page;
        }
        return $limit;
    }


}