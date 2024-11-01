<?php
/**
 * Created by PhpStorm.
 * User: Enterprise
 * Date: 08/04/2016
 * Time: 23:46
 */


if (!function_exists('printPiwikScript')) {
    require_once ("script/piwik.php");
}

add_action("wp_head", "printPiwikScript");
