<?php

namespace modules\RightCol\Backend\controllers;

use App\Application;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Page\Repository\PageRepository;
use Page\Backend\controllers\Page;
use Util\Util;


class RightCol extends Page
{
    protected $article_id;
    protected $dir;
    protected $article_info;

    private $right_col_image_width = 340;
    private $right_col_image_height = 200;

    function __construct(Application $app, $id = null)
    {
        parent::__construct($app);

        $this->article_id = $id;
        $this->app = $app;
        $repo = new PageRepository($this->app, $this->article_id);
        $this->article_info = $repo->getPageInfo();
//        $this->dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/gallery/";
//        if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id)) mkdir($_SERVER["DOCUMENT_ROOT"]."/files/".$this->article_id, 755);
//        if (!file_exists($this->dir)) mkdir($this->dir);

    }


    function handle(Request $request){

        if ($request->request->has('Update')){
            $this->updateForm($request);
            return  new RedirectResponse((($this->app['debug']) ? '/index_dev.php' : null).'/admin/rightcol/'.$this->article_id.'/');
        }
        else {
            return $res = $this->showArticleForm($request);
        }

        return $res;

    }



    function updateForm(Request $request)
    {
        $this->manageRightColText($request);
        $this->manageRightColImage($request);
    }




    function showArticleForm(Request $request)
    {
        $content = $this->app['twig']->render('modules/RightCol/Backend/templates/RightCol.html.twig', array(
            'article'   => $this->article_info,
            'path'      => $this->getPath($this->article_id)
        ));
        return $this->formAction($content);

    }

    private function manageRightColImage(Request $request)
    {
        $dir = $_SERVER["DOCUMENT_ROOT"]."/uplds/".$this->article_id."/";
        $photo_name = 'rightcolimage'.$this->article_id.'.jpg';


        if ($request->request->has('del_image') && $request->request->get('del_image') == 'yes') {
            if (file_exists($dir.$photo_name)) {
                unlink($dir.$photo_name);
            }
        }

        $uploaded_file = $request->files->get('right_col_image');

        if ($uploaded_file === null) return;

        if (in_array($uploaded_file->getMimeType(), $this->images_mimetypes)) {
            $uploaded_file->move($dir, $photo_name);
            Util::resizeAndCrop($dir, $photo_name, $this->right_col_image_width, $this->right_col_image_height, true);
        }

    }
    private function manageRightColtext(Request $request)
    {
        $right_col_text     = ($request->request->has('right_col_text') && strlen(strip_tags($request->request->get('right_col_text'))) > 0) ? $request->request->get('right_col_text') : '';
        $right_col_seealso  = ($request->request->has('right_col_seealso') && strlen(strip_tags($request->request->get('right_col_seealso'))) > 0) ? $request->request->get('right_col_seealso') : '';
        $right_col_subtitle = ($request->request->has('right_col_subtitle') && strlen(strip_tags($request->request->get('right_col_subtitle'))) > 0) ? $request->request->get('right_col_subtitle') : '';
        $sql = 'REPLACE INTO RightColContent (id, text, seealso, subtitle) VALUES (:article_id, :text, :seealso, :subtitle)';
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':article_id', $this->article_id, PDO::PARAM_INT);
        $stmt->bindValue(':text', $right_col_text, PDO::PARAM_STR);
        $stmt->bindValue(':seealso', $right_col_seealso, PDO::PARAM_STR);
        $stmt->bindValue(':subtitle', $right_col_subtitle, PDO::PARAM_STR);
        $stmt->execute();
    }

}

