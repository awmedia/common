<?php

namespace Aw\Common\Utils;

/**
 * ArrayUtils
 * @author Jerry Sietsma
 */
class ArrayUtils
{
    
    /**
     * Tests if an array is associative or or not.
     * @param   array   array to check
     * @return  boolean
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * Gets a value from an array using a dot separated path.
     *     // Get the value of $array['foo']['bar']
     *     $value = Arr::path($array, 'foo.bar');
     * @param   array   array to search
     * @param   string  key path, dot separated
     * @param   mixed   default value if the path is not set
     * @return  mixed
     */
    public static function path($array, $path, $default = NULL)
    {
        $keys = explode('.', $path);

        do
        {
            $key = array_shift($keys);

            if (ctype_digit($key))
            {
                $key = (int) $key;
            }

            if (isset($array[$key]))
            {
                if ($keys)
                {
                    if (is_array($array[$key]))
                    {
                        $array = $array[$key];
                    }
                    else
                    {
                        break;
                    }
                }
                else
                {
                    return $array[$key];
                }
            }
            else
            {
                break;
            }
        }
        while ($keys);

        return $default;
    }

    /**
     * Retreive a single key from an array. If the key does not exist in the
     * array, the default value will be returned instead.
     * @param   array   array to extract from
     * @param   string  key name
     * @param   mixed   default value
     * @param    string    the type to cast the value to (optional). Not applicable for default value.
     * @return  mixed
     */
    public static function get(array $array = null, $key, $default = null, $castToType = null)
    {
        if (isset($array[$key]))
        {
            $value = $array[$key];
            
            if ($castToType !== null)
            {
                settype($value, $castToType);
            }
            
            return $value;
        }
        
        return $default;
    }

    /**
     * Retrieves multiple keys from an array. If the key does not exist in the
     * array, the default value will be added instead.
     *
     * @param   array   array to extract keys from
     * @param   array   list of key names
     * @param   mixed   default value
     * @param	boolean	Preserve keys (default = false)
     * @param   boolean    true to ignore keys that are not set instead of using default
     * @return  array
     */
    public static function extract(array $array, array $keys, $default = null, $preserveKeys = false, $ignoreEmpty = false)
    {
        $found = array();
        foreach ($keys as $key)
        {
            $isset = isset($array[$key]);
            
            if (!$isset && $ignoreEmpty === true)
            {
                continue;
            }
            
            $value = $isset ? $array[$key] : $default;
            if ($preserveKeys)
            {
	            $found[$key] = $value;
            }
            else
            {
	        	$found[] = $value;   
            } 
        }

        return $found;
    }

    /**
     * Merges one or more arrays recursively and preserves all keys.
     * Note that this does not work the same the PHP function array_merge_recursive()!
     * @param   array  initial array
     * @param   array  array to merge
     * @param   array  ...
     * @return  array
     */
    public static function merge(array $a1)
    {
        $result = array();
        for ($i = 0, $total = func_num_args(); $i < $total; $i++)
        {
            foreach (func_get_arg($i) as $key => $val)
            {
                if (isset($result[$key]))
                {
                    if (is_array($val))
                    {
                        $result[$key] = self::merge($result[$key], $val);
                    }
                    elseif (is_int($key))
                    {
                        array_push($result, $val);
                    }
                    else
                    {
                        $result[$key] = $val;
                    }
                }
                else
                {
                    $result[$key] = $val;
                }
            }
        }

        return $result;
    }
}