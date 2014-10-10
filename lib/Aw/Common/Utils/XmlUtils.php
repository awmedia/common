<?php

namespace Aw\Common\Utils;

/**
 * XmlUtils
 * @author Jerry Sietsma
 */
class XmlUtils
{
    /**
     * File to array
     * @param   string  Filename
     * @return  array   Xml data as array
     */
    public static function fileToArray($filename)
    {
        return static::toArray(simplexml_load_file($filename, null, LIBXML_NOCDATA));
    }
    
    /**
     * String to array
     * @param   string  Xml data as string
     * @return  array   Xml data as array
     */
    public static function stringToArray($string)
    {
         return static::toArray(simplexml_load_string($string, null, LIBXML_NOCDATA));
    }
    
    /**
     * Private/protected methods
     */
    
    protected static function toArray(SimpleXMLElement $xml)
    {
        return json_decode(json_encode($xml), true);
    }
}