<?php
/**
 * @author eugene
 * @date 12/27/13
 * @time 12:00 PM
 */

namespace lib;

class Image
{
    const DIR = 'img';
    const EXT_PNG = '.png';
    const EXT_GIF = '.gif';

    // image file names
    const START = 'start';
    const RESTART = 'restart';
    const STOP = 'stop';
    const UPDATE = 'update';
    const EYE = 'eye-open';
    const EYE_CLOSE = 'eye-close';
    const INITIALIZATION = 'wait';
    const WARNING = 'warning-message';
    const HOME = 'home24';

    public static function getAbsPath($file, $ext = self::EXT_PNG)
    {
        return DIRECTORY_SEPARATOR . self::DIR . DIRECTORY_SEPARATOR . $file . $ext;
    }

} 