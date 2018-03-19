<?php
/**
 * Created by PhpStorm.
 * User: programmer
 * Date: 10/12/2017
 * Time: 20:37
 */

namespace App\Twig;

class boehpykExtension extends \Twig_Extension
{
    private $weekdays   = array('воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота');
    private $months     = array(
                                1  => 'января',
                                2  => 'февраля',
                                3  => 'марта',
                                4  => 'апреля',
                                5  => 'мая',
                                6  => 'июня',
                                7  => 'июля',
                                8  => 'августа',
                                9  => 'сентября',
                                10 => 'октября',
                                11 => 'ноября',
                                12 => 'декабря',
                            );

    public function getName() {
        return "boehpyk";
    }

    public function getFilters() {
        return array(
            "rusWeekdays"       => new \Twig_SimpleFilter("rusWeekdays", array($this, 'rusWeekdays')),
            "rusMonths"         => new \Twig_SimpleFilter("rusMonths", array($this, 'rusMonths')),
            "rusdate"           => new \Twig_SimpleFilter("rusdate", array($this, 'rusdate')),
        );
    }

    public function rusWeekdays($input)
    {
        return $this->weekdays[date('w', strtotime($input))];
    }

    public function rusMonths($input)
    {
        return $this->months[date('n', strtotime($input))];
    }


    public function rusdate($date, $weekday = false)
    {
        $time = strtotime($date);

        $result = date('j', $time). ' ' .$this->months[date('n', $time)] . (($weekday) ? ', ' . $this->weekdays[date('w', $time)] : null);
        return $result;
    }

}