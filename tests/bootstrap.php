<?php

/**
 * @author oneilstuart
 */
// TODO: check include path
//ini_set('include_path', ini_get('include_path'));

require_once '/home/www/SplClassLoader.php';
$classLoader = new SplClassLoader('moss', '/home/www/silex-songs/appsrc');
$classLoader->register();
?>
