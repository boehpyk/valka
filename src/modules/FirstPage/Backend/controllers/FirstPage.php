<?php

namespace modules\FirstPage\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;

class FirstPage extends Page
{
    protected $article_id;
    protected $app;
    protected $article_info = null;

    function __construct(Application $app, $id = null)
    {
        parent::__construct(4, $app);

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
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/first_page/');
        }
        else {
            return $this->showForm($request);
        }
    }

    private function showForm(Request $request)
    {
        $content = $this->app['twig']->render('modules/FirstPage/Backend/templates/FirstPage.html.twig', array(
            'article'   => $this->article_info,
            'cites'     => $this->getCites(),
            'path'      => $this->getPath($this->article_id)
        ));
        return $this->formAction($content);
    }

    private function updateAction($request)
    {
        $this->globalUpdate($request);
        $this->updateCites($request);
        $this->manageAnnounceImage($request);
    }

    private function updateCites(Request $request)
    {
        if ($request->request->has("exists") && count($request->request->get("exists")) > 0) {
            $sql = "UPDATE Cite SET text=:text WHERE id=:id";
            $stmt = $this->app['db']->prepare($sql);
            foreach ($request->request->get('cite_text') as $key=>$value) {
                if (strlen(strip_tags($value)) > 0) {
                    $stmt->bindValue('text', strip_tags($value));
                    $stmt->bindValue("id", $key);
                    $stmt->execute();
                }
            }
        }

        if ($request->request->has("delete_cite") && count($request->request->get('delete_cite')) > 0) {
            foreach ($request->request->get('delete_cite') as $key=>$value) {
                if ($value == 'yes') {
                    $this->deleteCite($key);
                }
            }
        }
    }

    private function deleteCite($id)
    {
        $sql = "DELETE FROM Cite WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue("id", $id, PDO::PARAM_INT);
        $stmt->execute();
    }


    private function getCites()
    {
        $sql="SELECT * FROM Cite";
        $stmt = $this->app['db']->query($sql);
        return $stmt->fetchAll();
    }

    protected function manageAnnounceImage(Request $request)
    {
        $uploaded_file = $request->files->get('announce_img');

        if ($uploaded_file === null) return;

        $dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/";
        $photo_name     = Util::filenamefix($uploaded_file->getClientOriginalName());
        if (in_array($uploaded_file->getMimeType(), $this->images_mimetypes)) {
            $uploaded_file->move($dir, $photo_name);
            Util::resizeAndCrop($dir, $photo_name, 630, 200, true);
            $sql = "UPDATE FirstPage SET announce_img=:img";
            $stmt = $this->app['db']->prepare($sql);
            $stmt->bindValue("img", $photo_name, PDO::PARAM_STR);
            $stmt->execute();

        }
    }


}