<?php

namespace Text\Frontend\controllers;

use Symfony\Component\HttpFoundation\Request;

class Text
{
    function __construct($id)
    {
        $this->article_id = $id;
    }

    public function TextAction()
    {
        return 'textAction!!!';
    }
}