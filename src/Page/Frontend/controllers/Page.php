<?php

namespace Page\Frontend\controllers;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use PDO;
use Page\Repository\PageRepository;
use Util\Util;

abstract class Page
{
    protected $article_id;
    protected $article_info;
    protected $forbidden_types = array('Event', 'FirstPage', 'News', 'Feedback');
    protected $common_links;
    private $root_deps = array();

    function __construct(int $article_id, Application $app)
    {
        $this->app = $app;
        $this->article_id = $article_id;
        $repo = new PageRepository($this->app, $this->article_id);
        $this->article_info = $repo->getPageInfo();
    }

    protected function showAction($content, $article = null)
    {
        if ($this->article_id == 4) {
            $template = 'index_layout.html.twig';
        }
        else {
            $template = 'inner_layout.html.twig';
        }

        $this->makeMenuTree();

        return $this->app['twig']->render('Page/Frontend/templates/'.$template, array(
            'page_title'    => $this->getPageTitle(),
            'mainmenu'      => $this->root_deps,
            'path'          => $this->getPath($this->article_id),
            'content'       => $content,
            'article'       => $this->article_info,
        ));
    }

    protected function makeMenuTree()
    {
        $elements = $this->getContents();
        $tree = $this->buildTree($elements);
        return $tree;
    }

    function buildTree(array $elements, $parentId = 0, &$level = 1) {
        $branch = array();
        $level++;
        foreach ($elements as $element) {
            $element['level'] = $level;
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id'], $level);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        $level--;
        return $branch;
    }

    function getContents()
    {
        $res = array();
        $forbidden_types_in  = str_repeat('?,', count($this->forbidden_types) - 1) . '?';
        $sql = "SELECT id, title, parent_id, url FROM Page WHERE subtype NOT IN (".$forbidden_types_in.") AND special!='yes' AND publish='yes' AND menu='yes' ORDER BY position";
        $stmt = $this->app['db']->prepare($sql);
        $c = 1;
        foreach ($this->forbidden_types as $value) {
            $stmt->bindValue($c, $value, PDO::PARAM_INT);
            $c++;
        }
        $stmt->execute();
        while ($row = $stmt->fetch()){
            $res[] = array(
                'id'        => $row["id"],
                'parent_id' => $row["parent_id"],
                'title'     => $row["title"],
                'url'       => $row['url'],
            );
            if ($row['parent_id'] == 0) {
                $this->root_deps[] = array(
                    'id'        => $row["id"],
                    'parent_id' => $row["parent_id"],
                    'title'     => $row["title"],
                    'url'       => $row['url'],
                );
            }
        }
        return $res;
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

        $stmt = $this->app['db']->prepare("select id, title, parent_id, url from Page WHERE id=:parent");
        $stmt->bindValue(':parent', $parent, PDO::PARAM_INT);
        $stmt->execute();

        if (($numrows=$stmt->rowCount()) > 0) {
            while ($row = $stmt->fetch()) {
                $path = '<li><a href="'.(($this->app['debug'] === true) ? '/'.$this->app['dev.handler'] : '').$row['url'].'">'.$row["title"].'</a></li>'."\n ".$path;
                $this->getPath($row['id'], $path);
            }
        }
        $result = '<li><a href="'.(($this->app['debug'] === true) ? '/'.$this->app['dev.handler'].'/' : '/').'">Главная</a></li>'.$path.'<li class="active">'.$title.'</li>'."\n";
        return $result;
    }

    function getPhotos($limit = null, $pos = '')
    {
        if (strlen($pos) > 0) $pos = $pos."_";

        $page = (isset($_REQUEST["page"]) && $_REQUEST["page"] > 0) ? $_REQUEST["page"] : 1;
        $limit = ($limit) ? $limit : 120;

        $sql = "SELECT * FROM GalleryPhoto WHERE gallery_id=:article_id AND publish='yes' ORDER BY position, id ".Util::getMySQLLimit($page, $limit);
        $sth = $this->app['db']->prepare($sql);
        $sth->bindValue(":article_id", $this->article_id, PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetchAll();
        $count = $num = count($result);

        if ($count == 0) return;

        return $result;
    }

    protected function getSubdeps()
    {
        $res = array();
        $sql ="SELECT Page.id, Page.title, Page.url from Page WHERE Page.parent_id=:id AND Page.publish='yes' ORDER BY Page.position";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $row['listimage'] = $this->getFirstGalleryImage($row["id"]);
            $res[] = $row;
        }
        return $res;
    }

    private function getFirstGalleryImage($id)
    {
        $sql ="SELECT filename from GalleryPhoto WHERE gallery_id=:id AND publish='yes' ORDER BY position LIMIT 1";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();

    }

    function getAdminEmail()
    {
        $sql = "SELECT value FROM settings WHERE name='admin_email'";
        $stmt = $this->app['db']->query($sql);
        $row = $stmt->fetch();
        if(strlen($row['value']) > 0){
            return $row['value'];
        }
        else{
            return false;
        }
    }


    abstract function getPageTitle();

}