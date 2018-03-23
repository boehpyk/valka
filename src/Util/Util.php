<?php

namespace Util;

include_once(dirname(__FILE__) . "/lib/Roman.php");
include(dirname(__FILE__). "/lib/resize_crop.php");

final class Util {

    static function intFormat($number){ //приводит число к виду 1.245.567
        return str_replace(',', '.', number_format($number));
    }

    static function intUnFormat($string){ //приводит число к виду 1245567
        return str_replace('.', '', $string);
    }

    static function getMysqlDateFormat($date)//конвертирует данные поля типа date mysql в стринг
    {
        if (($date + 0) > 0) {
            return date('d.m.Y H:i', strtotime($date));
        }
        return "-";
    }

    static function getMysqlDateWOTime($date, $add_time=false)//конвертирует данные поля типа date mysql в стринг
    {
        if (($date + 0) > 0) {
            if ($add_time) {
                return date('d.m.Y H:i', OtherUtil::fromMySQLDateTimeToTimestamp($date));
            }
            else {
                return date('d.m.Y', OtherUtil::fromMySQLDateTimeToTimestamp($date));
            }
        }
        return "-";
    }


    static function parseMySQLDate($date, $with_time=false, $say_month=false)
    {
        if (strlen($date) > 0)
        {
            $result = array();
            if ($with_time || $say_month)
            {
                list($_date, $_time) = explode(" ", $date);
            }
            else
            {
                $_date = $date;
            }
            $date_arr = explode("-",$_date);

            $result["year"] = $date_arr[0];
            $result["mon"] = $date_arr[1];
            $result["mday"] = $date_arr[2];

            if ($with_time)
            {
                list($result["hours"], $result["minutes"], $result["seconds"]) = explode(":",$_time);
            }
            else
            {
                $result["hours"] = 0;
                $result["minutes"] = 0;
                $result["seconds"] = 0;
            }

            if($say_month) {
                $months = array('', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
                $result["mon"] = $months[intval($date_arr[1])];
            }
            return $result;
        }

    }

    static function makeRecordtimeForm($date, $name, $add_time=true)
    {

        $months = array(
            1  => "января",
            2  => "февраля",
            3  => "марта",
            4  => "апреля",
            5  => "мая",
            6  => "июня",
            7  => "июля",
            8  => "августа",
            9  => "сентября",
            10 => "октября",
            11 => "ноября",
            12 => "декабря"
        );

        if (strlen($date) < 1) {
            $date = "0000-00-00 00:00:00";
        }

        $today = OtherUtil::parseMySQLDate($date, true);
        $form = "<input type=\"hidden\" name=\"".$name."\" value=\"1\">\n";
        // формируем input для дня месяца
        if (strlen($today["mday"]) > 1 && $today["mday"] < 10) {
            $today["mday"] = substr($today["mday"], 1);
        }
        $form .= "<input type=\"text\" name=\"".$name."_day\" value=\"".(($today["mday"] < 10) ? "0".$today["mday"] : $today["mday"])."\" size=3 maxlength=2 class=\"text\" style=\"width: 1.5em;\">\n";

        //формируем select для месяца

        $form .= "<select name=\"".$name."_month\">\n";
        for ($i = 1; $i<13; $i++) {
            if ($i == $today["mon"]) {
                $form .= "<option value=\"".$i."\" selected>".$months[$i]."</option>\n";
            }
            else {
                $form .= "<option value=\"".$i."\">".$months[$i]."</option>\n";
            }
        }
        $form .= "</select>\n";

        // формируем input для года
        $form .= "<input type=\"text\" name=\"".$name."_year\" value=\"".$today["year"]."\" size=3 maxlength=4 class=\"text\" style=\"width: 3em;\">\n";

        $form .= " ";

        if ($add_time) {
            // формируем input для часа
            $form .= "<input type=\"text\" name=\"".$name."_hour\" value=\"".(($today["hours"])<10 ? "0".$today["hours"] : $today["hours"])."\" size=3 maxlength=2 class=\"text\" style=\"width: 1.5em;\"><b>:</b>";

            // формируем input для минут
            $form .= "<input type=\"text\" name=\"".$name."_minutes\" value=\"".(($today["minutes"] < 10) ? "0".$today["minutes"] : $today["minutes"])."\" size=3 maxlength=2 class=\"text\" style=\"width: 1.5em;\">\n";
        }

        return $form;
    }

    function makeDate($recordtime)
    {
        return date("d.m.Y", $recordtime);
    }


    static function fromMySQLDateTimeToTimestamp($date) //конвертирует данные поля типа date mysql в timestamp
    {
        $today = OtherUtil::parseMySQLDate($date);
        $result = mktime($today["hours"], $today["minutes"], $today["seconds"], $today["mon"], $today["mday"], $today["year"]);
        return $result;
    }


    static function fromTimestampToMySQLDateTime($timestamp, $with_time = false)
    {
        if ($with_time) {
            return date("Y-m-d H:i:s", $timestamp);
        }
        else {
            return date("Y-m-d", $timestamp);
        }
    }

    static function drawPages($num, $in_page, $exclude_params_arr = array())
    {
        if($in_page == 0)
        {
            $in_page = 1;
        }
        if(isset($_POST["postMessage_x"]))
        {
            $page = ceil($num/$in_page);
        }
        elseif(isset($_REQUEST["page"]))
        {
            $page = $_REQUEST["page"];
        }
        else
        {
            $page = 1;
        }


        $before = 7;
        $after = 7;
        $url_params = "";
        $result = "";




        foreach($_REQUEST as $key => $value)		//правил Д. Аверков
        {
            if ($key == 'page' || $key == 'update' || $key == 'SelectAll' || $key == 'Approve' || $key == 'Block' || $key == 'Delete' || in_array($key, $exclude_params_arr))
            {
                continue;
            }
            if(is_array($value))
            {
                foreach($value as $key2 => $value2)
                {
                    $url_params .= $key . "[" . $key2 . "]=" . $value2 . "&";
                }
            }
            else
            {
                $url_params .= $key . "=" . $value . "&";
            }
        }
        $url_params = str_replace(" ", "%", $url_params);

        $page_url = ereg_replace("\?.*", "", $_SERVER['REQUEST_URI']);
        if($page > 1 + $after)
        {
            $result .= "<a href=\"".$page_url."?".$url_params."page=".($page - 1 - $before)."\" class=\"page_navigation\">Предыдущая</a>&nbsp;";
        }
        else
        {
            $result .= "Предыдущая&nbsp;";
        }

        $i = 1;
        $pages_num = ceil($num/$in_page);
        if($pages_num > 1){
            for ($i; $i <= $pages_num; $i++) {
                if ($i == $page) {
                    $result .= "&nbsp;<span class=\"page_navigation\">" .  $i . "</span>";
                }
                else {
                    if($i < $page && !($page > $before && ($page - $i) > $before)){
                        $result .= "&nbsp;<a href=\"".$page_url."?".$url_params."page=".$i."\" class=\"page_navigation\">".$i."</a>";
                    }
                    elseif($i > $page  && $i <= ($page + $after)){
                        $result .= "&nbsp;<a href=\"".$page_url."?".$url_params."page=".$i."\" class=\"page_navigation\">".$i."</a>";
                    }
                }
                if ($i < ceil($num/$in_page)) {
                    $result .= " ";
                }
            }
        }
        $page_num = ceil($num/$in_page);
        if($page + $after < $page_num) {
            $next_page = ($page + 1 + $after > $page_num) ? $page_num : $page + 1 + $after;
            if(preg_match("/\/offers_list.php/", $page_url))
            {
                $result .= "&nbsp;<a href=\"".$page_url."?".$url_params."page=".$next_page."\" class=\"page_navigation\">Следующая</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"".$page_url."?".$url_params."po300\" class=\"page_navigation\">по 300</a>";
            }
            else
            {
                $result .= "&nbsp;<a href=\"".$page_url."?".$url_params."page=".$next_page."\" class=\"page_navigation\">Следующая</a>&nbsp;";
            }
        }
        if($num < $in_page){
            return null;
        }
        else{
            return $result . "<br><br>";
        }
    }


    static function drawPagesForFrontend($num, $in_page){ //используется для фронтенда в списке
        $result = "";
        //var_dump($_SERVER["QUERY_STRING"]);
        if($in_page == 0) $in_page = 1;

        if(isset($_REQUEST["page"]) && $_REQUEST["page"] > 1) {
            $page = $_REQUEST["page"];
        }
        else {
            $page = 1;
        }

        $url_params = "";
        //foreach($_REQUEST as $key => $value){
        //	if ($key == 'page' || $key == 'update' || $key == 'phpbb2mysql_sid' || $key == 'phpbb2mysql_data' || $key == 'econ_site') continue;
        //   	$url_params .= $key . "=" . $value . "&";
        //}
        //$url_params = str_replace(" ", "%", $url_params);

        $request_uri = preg_replace("/[&|\?]page=[0-9]+/","",$_SERVER['REQUEST_URI']);
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


        if($page > 1) {
            $result .= " <a href=\"".$request_uri.$delimiter.$url_params."page=".($page-1)."\">Назад</a> ";
        }

        for ($i = 1; $i <= ceil($num/$in_page); $i++) {

            if ($i == $page) {
                $result .= "<b>".$i."</b> | ";
            }
            else {
                $result .= " <a href=\"".$request_uri.$delimiter.$url_params."page=".$i."\">".$i."</a> | ";
            }

            if ($i < ceil($num/$in_page)) {
                $result .= " ";
            }
        }

        if($page < ceil($num/$in_page)) {
            $result .= " <a href=\"".$request_uri.$delimiter.$url_params."page=".($page+1)."\">Вперед</a> ";
        }
        return $result;
    }


    static function getMySQLLimit($page, $in_page = 20)
    {

        if(!isset($page) or $page == 1) {
            $limit = " LIMIT ".$in_page;
        }
        else {
            $limit = " LIMIT ".(($page-1)*$in_page).", ".$in_page;
        }
        return $limit;
    }

    function dupper($s){ //переводит кирилистическую строку в верхний регистр
        return strtr($s,"абвгдеёжзийклмнопрстуфхцчшщъыьэюяabcdefghijklmnopqrstuvwxyz",
            "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯABCDEFGHIJKLMNOPQRSTUVWXYZ");

    }


    function dlower($s){ //переводит кирилистическую строку в нижний регистр
        return strtr($s,"АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯABCDEFGHIJKLMNOPQRSTUVWXYZ",
            "абвгдеёжзийклмнопрстуфхцчшщъыьэюяabcdefghijklmnopqrstuvwxyz");

    }


    public static function rus2trans($str)
    {
        $rus = array('щ','Щ','ш','Ш','ё','Ё','ж','Ж','ч','Ч','э','Э','ю','Ю','я','Я','а','б','в','г','д','е','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ъ','ы','ь','А','Б','В','Г','Д','Е','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ъ','Ы','Ь', ' ', '/', '\\', '?' );
        $trans = array('sch','SCH','sh','SH','yo','YO','zh','ZH','ch','CH','e','E','yu','YU','ya','YA','a','b','v','g','d','e','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c',"\"",'y',"",'A','B','V','G','D','E','Z','I','J','K','L','M','N','O','P','R','S','T','U','F','H','C',"_",'Y',"", "_", "_", "_", "_");
        $newstr = str_replace($rus,$trans,$str);
        //добавил Д. Аверков
        //$newstr = preg_replace("/\/|\\|\||\"|\'|\?|\*|\+|\&|\$|\,/", "_", $newstr);
        return $newstr;
    }

    static function filenamefix($str)
    {
        $str= trim($str);
        $rus = array('щ','Щ','ш','Ш','ё','Ё','ж','Ж','ч','Ч','э','Э','ю','Ю','я','Я','а','б','в','г','д','е','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ъ','ы','ь','А','Б','В','Г','Д','Е','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ъ','Ы','Ь', ' ', '/', '\\', '?' );
        $trans = array('sch','SCH','sh','SH','yo','YO','zh','ZH','ch','CH','e','E','yu','YU','ya','YA','a','b','v','g','d','e','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c',"\"",'y',"",'A','B','V','G','D','E','Z','I','J','K','L','M','N','O','P','R','S','T','U','F','H','C',"_",'Y',"", "_", "_", "_", "_");
        $newstr = str_replace($rus,$trans,$str);
        //добавил Д. Аверков
//        $newstr = preg_replace("/\/|\\|\||\"|\'|\?|\*|\+|\&|\$|\,/", "", $newstr);
        $newstr = preg_replace('/[^ \w]+/', "", $newstr);
        $newstr = preg_replace('/[\-\s]+/', "_", $newstr);
        return $newstr;
    }


    function clearMachine($res)	//мясорубка(чистит от хлама HTML)
    {
        //оставляем только  то что в <body></body>:
        $res =  preg_replace("/^.*?(<body).*?>{1}?/si", "", $res);
        $res =  preg_replace("/<\/[^t]?body.*/si", "", $res);

        //вырезаем все что не тегах <p>, <br>, <b>, <i>, <u>, <sub>, <sup>, <ul>, <ol>, <li>, <h1>, <h2>, <table>, <tr>, <td>, <tbody>, <thead>, <strong>, <em>
        $res = strip_tags($res,'<p><br><b><i><u><sub><sup><ul><ol><li><h1><h2><table><tr><td><tbody><thead><strong><em>');
        $res =  preg_replace("/(<\w+?)[\s]+.*?(>)/si", "\\1\\2", $res);

        //вырезаем пустые теги:
        $res =  preg_replace("/<(.+?)><\/\\1>/si", "", $res);

        //вставляем cellspacing="0"
        $res =  preg_replace("/<(table+?)>/si", "<table cellspacing=\"0\">", $res);

        return $res;
    }

    static function checkInput($res) {
        //вырезаем все что не тегах <p>, <br>, <b>, <i>, <u>, <sub>, <sup>, <ul>, <ol>, <li>, <h1>, <h2>, <table>, <tr>, <td>, <tbody>, <thead>, <strong>, <em>
        //$res = strip_tags($res,'<p><br><b><i><u><sub><sup><ul><ol><li><h1><h2><table><tr><td><tbody><thead><strong><em>');
        $res = strip_tags($res);
        $res =  preg_replace("/(<\w+?)[\s]+.*?(>)/si", "\\1\\2", $res);

        //вырезаем пустые теги:
        $res =  preg_replace("/<(.+?)><\/\\1>/si", "", $res);
        $res =  str_replace(array("«", "»"), array("&laquo;", "&raquo;"), $res);
        $res =  str_replace("'|`", "&#39;", $res);
        $res =  str_replace("\"", "&quot;", $res);

        return $res;
    }

    static function checkInputNoHTML($res) {
        $res = strip_tags($res);
        $res = str_replace(array("<", ">"), array("&laquo;", "&raquo;"), $res);
        $res = ereg_replace("'|`", "&#39;", $res);

        return $res;
    }

    static function clearBBQuotes($text)
    {
        $search = array ("'\[quote[^>]*?\].*?\[/quote\]'si",
            "'\[[\/\!]*?[^\[\]]*?\]'si");

        $replace = array ("",
            "");

        $res = preg_replace ($search, $replace, $text);
        return $res;
    }


    function fromRomanToArabic($num) {
        $res = Numbers_Roman::toNumber($num);
        return $res;
    }
    function fromArabicToRoman($num) {
        $res = Numbers_Roman::toNumeral($num,true,false);
        return $res;
    }

    function createAlphabetRusLinks(){
        $result = "";
        $alph = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Э', 'Ю', 'Я');
        foreach($alph as $litera){
            $result .=  ((isset($_GET['lit']) && $_GET['lit'] == $litera) ? "<b>" . $litera . "</b>" : "<a href=\"?lit=" . $litera . "\">" . $litera . "<a>") . "&nbsp;";
        }
        return $result;
    }

    function createAlphabetEngLinks(){
        $result = "";
        $alph = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        foreach($alph as $litera){
            $result .= "<a href=\"?lit=" . $litera . "\">" . $litera . "<a>&nbsp;";
        }
        return $result;
    }

    function createNumberLinks(){
        $result = "";
        $alph = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
        foreach($alph as $litera){
            $result .= "<a href=\"?lit=" . $litera . "\">" . $litera . "<a>&nbsp;";
        }
        return $result;
    }

    static function deleteLidImage($article_id)
    {
        if (file_exists($_SERVER["DOCUMENT_ROOT"]."/files/lid_image".$article_id.".jpg")) {
            unlink($_SERVER["DOCUMENT_ROOT"]."/files/lid_image".$article_id.".jpg");
        }
    }

    static function deleteMenuImage($article_id)
    {
        if (file_exists($_SERVER["DOCUMENT_ROOT"]."/files/menu_image".$article_id.".jpg")) {
            unlink($_SERVER["DOCUMENT_ROOT"]."/files/menu_image".$article_id.".jpg");
        }
    }

    static function showError($error) {
        echo "<script language=\"Javascript\">alert('".$error."'</script><noscript>Ошибка!<br>".$error."</noscript>";
    }

    static function generatePassword()
    {
        srand ((float) microtime() * 10000000);
        $letters_str = "q,w,e,r,t,y,u,i,o,p,a,s,d,f,g,h,j,k,l,z,x,c,v,b,n,m,Q,W,E,R,T,Y,U,I,O,P,L,K,J,H,G,F,D,S,A,Z,X,C,V,B,N,M,1,2,3,4,5,6,7,8,9,0";
        $letters = explode(",",$letters_str);
        $rand_keys = array_rand ($letters, 8);
        $pass = "";
        foreach($rand_keys as $value) {
            $pass .= $letters[$value];
        }
        return $pass;
    }

    static function removeAmps($value)
    {
        $src = array("&amp;quot;", "&amp;laquo;", "&amp;raquo;");
        $replace = array("&quot;", "&laquo;", "&raquo;");
        $value = str_replace($src, $replace, $value);
        return $value;
    }

    static function makeTitle($lid) {
        return substr(strip_tags($lid), 0, 30)."...";
    }

    static function makeLidByWords($str, $length = 7) {
        $res = '';
        $str = strip_tags($str);
        $arr = explode(" ",$str);
        for ($i=0; $i <= (count($arr) - 1); $i++) {
            if ($i < $length) {
                $res .= $arr[$i] . " ";
            }
        }

        return $res;
    }

    static function ostatok ($x, $y)
    {
        $a = floor($x/$y);
        $b = $x - $a*$y;
        return $b;
    }

    static function adjustImageSize($dir, $filename, $w_max, $h_max)
    {
        $f = $dir.$filename;
        $gis = GetImageSize($f);
        $w_src = $gis[0];
        $h_src = $gis[1];

        if ($w_src >= $h_src) {
            self::resizeAndCrop($dir, $filename, $w_max, $h_max, true);
        }
        else {
            $f = $dir.$filename;
            resize($f, $f, null, $h_max, false);
        }

    }

    static function resizeAndCrop($dir, $filename, $w_aim, $h_aim, $crop = false) {
        $f = $dir.$filename;
        $gis = GetImageSize($f);
        $_w_src = $gis[0];
        $_h_src = $gis[1];

        if ($crop && $_w_src == $w_aim && $_h_src == $h_aim) return;

        $exif = exif_read_data($f);
        if(!empty($exif['Orientation'])) {
            switch($exif['Orientation']) {
                case 8:
                    $w_src = $_h_src;
                    $h_src = $_w_src;
                    break;
                case 6:
                    $w_src = $_h_src;
                    $h_src = $_w_src;
                    break;
                default:
                    $w_src = $_w_src;
                    $h_src = $_h_src;
                    break;
            }
        }
        else {
            $w_src = $_w_src;
            $h_src = $_h_src;
        }


        if ($crop) {
            $ratio = $w_aim/$h_aim;
        }

        if (!$crop) {
            if ($h_aim && $h_src > $h_aim) {
                resize($f, $f, null, $h_aim, false);
            }
            elseif ($w_aim && $w_src > $w_aim) {
                resize($f, $f, $w_aim, null, false);
            }

            $final_size = GetImageSize($f);
        }
        else {
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

            $final_size = GetImageSize($f);
        }
        return $final_size;
    }

    static function resizeAndCropForSquareContainer($dir, $filename, $w_aim, $h_aim, $crop = false) {
        $f = $dir.$filename;
        $gis = GetImageSize($f);
        $_w_src = $gis[0];
        $_h_src = $gis[1];

        $exif = @exif_read_data($f);
        if(!empty($exif['Orientation'])) {
            switch($exif['Orientation']) {
                case 8:
                    $w_src = $_h_src;
                    $h_src = $_w_src;
                    break;
                case 6:
                    $w_src = $_h_src;
                    $h_src = $_w_src;
                    break;
                default:
                    $w_src = $_w_src;
                    $h_src = $_h_src;
                    break;
            }
        }
        else {
            $w_src = $_w_src;
            $h_src = $_h_src;
        }


        if ($crop) {
            $ratio = $w_aim/$h_aim;
        }


        if (!$crop) {
            if ($w_src >= $h_src) {
                resize($f, $f, $w_aim, null, false);
            }
            else {
                resize($f, $f, null, $h_aim, false);
            }
            $final_size = GetImageSize($f);
        }
        else {
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

            $final_size = GetImageSize($f);
        }
        return $final_size;
    }

    static function camelize($input, $separator = '_')
    {
//        return str_replace($separator, '', ucwords($input, $separator));
        return str_replace($separator, '', ucwords($input));
    }


}
?>