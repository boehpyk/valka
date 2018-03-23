<?php
/**
 * Created by PhpStorm.
 * User: programmer
 * Date: 21/11/2017
 * Time: 14:15
 */

namespace Page\Repository;

use PDO;


class ServicesRepository
{
    function __construct($app, int $id)
    {
        $this->article_id = $id;
        $this->app = $app;
    }

    public function getPageInfo()
    {
        $sql = "SELECT * from RefServices WHERE id=:id";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue(':id', $this->article_id, PDO::PARAM_INT);
        $stmt->execute();
        $row1 = $stmt->fetch();

        return $row1;
    }

}