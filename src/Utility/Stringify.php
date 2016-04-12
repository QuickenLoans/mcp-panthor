<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

/**
 * This utility provides helper functions to combine strings.
 *
 * It should only be used for Symfony DI configuration, as sometimes you have scalars as services
 * (or synthetic services) that need to be appended to other parameters (such as file paths).
 *
 * For example, Panthor uses this for building the full path of the twig template directory by combining
 * the app root (@root synthetic service) to the relative template path (by default "templates").
 */
class Stringify
{
    /**
     * @param string $template Valid printf template
     * @param array $parameters
     *
     * @return string
     */
    public static function template($template, array $parameters)
    {
        array_unshift($parameters, $template);
        return call_user_func_array('sprintf', $parameters);
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public static function combine(array $parameters)
    {
        return implode('', $parameters);
    }
}
