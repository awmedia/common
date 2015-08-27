<?php

namespace Aw\Common\Utils;

/**
 * UrlUtils
 * @author Jerry Sietsma
 */
class UrlUtils
{
    public static function exists($url)
    {
        $headers = get_headers($url, 1);
        return strpos($headers[0], '404') === false;
    }
}