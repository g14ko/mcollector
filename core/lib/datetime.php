<?php
/**
 * User: eugene
 * Date: 4/8/14
 * Time: 3:14 PM
 */

namespace lib;


class DateTime
{
    private static $instance = null;
    private static $difference = null;

    public static function getInterval($from, $to = 'now')
    {
        self::initDateTime($from);
        self::setDifference($to);
        return self::interval();
    }

    private static function initDateTime($timestamp)
    {
        self::$instance = new \DateTime();
        self::$instance->setTimestamp($timestamp);
    }

    private static function setDifference($timestamp)
    {
        self::$difference = self::$instance->diff(new \DateTime($timestamp));
    }

    private static function interval()
    {
        $days = self::$difference->days;
        $hours = self::$difference->h;
        $minutes = self::$difference->i;
        $seconds = self::$difference->s;
        $interval = null;
        switch (true)
        {
            case !empty($days):
                $interval .= $days . 'd ';
            case !empty($hours):
                $interval .= $hours . 'h ';
            case !empty($minutes):
                $interval .= $minutes . 'm ';
            case !empty($seconds):
                $interval .= $seconds . 's ';
        }
        return !empty($interval) ? $interval . 'ago' : 'load data';
    }

} 