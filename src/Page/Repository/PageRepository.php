<?php
/**
 * Created by PhpStorm.
 * User: programmer
 * Date: 21/11/2017
 * Time: 14:15
 */

namespace Page\Repository;

use PDO;


class PageRepository
{
    function __construct($app, int $id)
    {
        $this->article_id = $id;
        $this->app = $app;
    }

    public function getPageInfo()
    {
        $sql = "SELECT * from Page WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $row1 = $stmt->fetch();

        $sql = "SELECT * FROM " . $row1["subtype"] . " WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $row2 = $stmt->fetch();

        $sql = "SELECT * FROM SEOMeta WHERE article_id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        $row_meta = ($res !== false) ? $res : array(
            'meta_keywords' => '',
            'meta_description' => ''
        );

        $right_col = $this->getRightCol();
        $res = array_merge($row2, $row1, $row_meta);
        $res["root_id"]             = $this->findRoot($this->article_id);
        $res['right_col_text']      = $right_col['text'];
        $res['right_col_seealso']   = $right_col['seealso'];
        $res['right_col_subtitle']   = $right_col['subtitle'];

        return $res;
    }

    function findRoot($id)
    {
        if ($id == 'map' or $id == 'search' or $id == 'main_page' or $id == 'user_login') {
            return false;
        }
        $stmt = $this->app['db']->prepare("select id, parent_id from Page WHERE id=:id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        while ($row2 = $stmt->fetch()) {
            if ($row2["parent_id"] == 0) {
                return $row2["id"];
            }
            else {
                $stmt = $this->app['db']->prepare("select id from Page WHERE id=:parent_id");
                $stmt->bindValue(':parent_id', $row2["parent_id"], PDO::PARAM_INT);
                $stmt->execute();
                while ($row = $stmt->fetch()) {
                    return $this->findRoot($row['id']);
                }
            }
        }
    }

    private function getRightCol()
    {
        $sql = "SELECT * FROM RightColContent WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res;
    }

}