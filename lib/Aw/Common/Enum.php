<?php

namespace Aw\Common;

use \ReflectionClass;

/**
 * Enum base class
 * @author Jerry Sietsma
 */
abstract class Enum
{
	protected static $_enumArr;
	
	public static function toArray()
	{
		if (!self::$_enumArr)
		{
			$reflectionClass = new ReflectionClass(get_called_class());
			self::$_enumArr = $reflectionClass->getConstants();
		}
		
		return self::$_enumArr;
	}
	
	public static function get($const)
	{
		$enum = self::toArray();
		return isset($enum[$const]) ? $enum[$const] : null; 
	}
}