<?php

declare(strict_types=1);

namespace Darryldecode\Cart\Helpers;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/15/2015
 * Time: 8:09 PM
 */
class Helpers
{
    /**
     * normalize price
     *
     * @return float
     */
    public static function normalizePrice($price)
    {
        return (is_string($price)) ? (float) $price : $price;
    }

    /**
     * check if array is multi dimensional array
     * This will only check the first element of the array if it is still an array
     * to decide that it is a multi dimensional, if you want to check the array strictly
     * with all on its element, flag the second argument as true
     *
     * @param  bool  $recursive
     * @return bool
     */
    public static function isMultiArray($array, $recursive = false)
    {
        if ($recursive) {
            return (count($array) === count($array, COUNT_RECURSIVE)) ? false : true;
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                return true;
            }

            return false;

        }

    }

    /**
     * check if variable is set and has value, return a default value
     *
     * @param  bool|mixed  $default
     * @return bool|mixed
     */
    public static function issetAndHasValueOrAssignDefault(&$var, $default = false)
    {
        if ((isset($var)) && ($var !== '')) {
            return $var;
        }

        return $default;
    }

    public static function formatValue($value, $format_numbers, $config)
    {
        if ($format_numbers && $config['format_numbers']) {
            return number_format($value, $config['decimals'], $config['dec_point'], $config['thousands_sep']);
        }

        return $value;

    }
}
