<?php

namespace modules\Text\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;

class Text extends Page
{
    protected $article_id;
    protected $app;
    protected $article_info = null;

    function __construct(Application $app, $id = null)
    {
        parent::__construct($app);

        $this->article_id = $id;
        $this->app = $app;
    }

    public function handle(Request $request)
    {
        if ($this->article_id) {
            $repo = new PageRepository($this->app, $this->article_id);
            $this->article_info = $repo->getPageInfo();
        }

        if ($request->request->has('Update')) {
            $this->updateAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$this->article_id.'/');
        }
        elseif ($request->request->has('Add')) {
            $inserted_id = $this->addAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$inserted_id.'/');
        }
        elseif ($request->request->has('deleteArticle')) {
            $this->deleteAction($request);
            $parent_id = $request->request->get('parent_id');
            if ($parent_id !== null && $parent_id > 0) {
                return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$parent_id.'/');
            }
            else {
                return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/');
            }

        }
        else {
            return $this->showForm();
        }
    }

    public function indexAction(Request $request)
    {
        if ($request->request->has('Add')) {
            $inserted_id = $this->addAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/article/'.$inserted_id.'/');
        }
        elseif ($request->request->has('Update')) {
            $this->updateAction($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/');
        }

        else {
            $content = $this->app['twig']->render('modules/Text/Backend/templates/index.html.twig', array(
                'subdeps'   => $this->getSubdeps(0),
            ));
            return $this->formAction($content);
        }
    }

    private function showForm()
    {
        $content = $this->app['twig']->render('modules/Text/Backend/templates/'.$this->article_info['subtype'].'.html.twig', array(
            'article'   => $this->article_info,
            'subdeps'   => $this->getSubdeps($this->article_id, (($this->article_info['subtype'] == 'Endpoint') ? true : false)),
            'path'      => $this->getPath($this->article_id),
        ));
        return $this->formAction($content);
    }

    private function updateAction($request)
    {
        $this->globalUpdate($request);
        $this->updateSubDeps($request);
        $this->sortPages($request);
        if ($this->article_info['parent_id'] == 0) {
            $this->manageDropdownImage($request);
        }
//        header("location: /admin/text_parts/?article_id=".$this->article_id);
    }

    private function addAction(Request $request)
    {
        $subtype = $request->request->get("subtype");
        if ($subtype == 'NewsList') {
            $type = 'News';
        }
        else {
            $type = 'Text';
        }
        $sql = "INSERT INTO Page ("
            . "title, "
            . "service_name, "
            . "parent_id, "
            . "date_add, "
            . "date_update, "
            . "type, "
            . "subtype"
            . ") "
            . "VALUES ("
            . ":title, "
            . ":service_name, "
            . ":parent_id, "
            . "".time().", "
            . "".time().", "
            . "'".$type."', "
            . "'".$subtype."'"
            . ")";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':title', $request->request->get("title"), PDO::PARAM_STR);
        $stmt->bindValue(':service_name', Util::rus2trans($request->request->get("service_name")), PDO::PARAM_STR);
        $stmt->bindValue(':parent_id', $request->request->get("parent_id"), PDO::PARAM_STR);
        $stmt->execute();
        $id = $this->app['db']->lastInsertId();

        $sql = "INSERT INTO ".$subtype." (id) VALUES (".$id.")";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();

        $sql = "UPDATE Page set "
            . "url='".$this->makeUrl($id)."', "
            . "position=".($this->countPositions($id) + 1)." "
            . "WHERE id=".$id;
        $stmt = $this->app['db']->prepare($sql);
        $stmt->execute();

        if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/uplds/".$id)) {
            mkdir($_SERVER["DOCUMENT_ROOT"]."/uplds/".$id, 0755);
            mkdir($_SERVER["DOCUMENT_ROOT"]."/uplds/".$id."/gallery", 0755);
        }
        return $id;
    }

    public function deleteAction()
    {
        $this->deleteArticle($this->article_id);
    }


    private function getSubdeps($parent_id, $is_endpoint = false)
    {
        $sql = "SELECT id, title, publish, type, subtype, menu, main, special, no_delete FROM Page WHERE parent_id = :article_id AND type != 'FirstPage' ORDER BY position, title";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $parent_id, PDO::PARAM_INT);

        $stmt->execute();
        $i = 1;
        $subdeps = array();

        while($row = $stmt->fetch()) {

            if ($row["subtype"] == 'users' or $row["subtype"] == 'first_page') {
                continue;
            }
            $subdeps[] = $row;
        }
        return $this->app['twig']->render('modules/Text/Backend/templates/subdeps.html.twig', array(
            'subdeps'       => $subdeps,
            'is_endpoint'   => $is_endpoint
        ));
    }

}