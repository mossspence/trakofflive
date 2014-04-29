<?php

$loader = require __DIR__.'/vendor/autoload.php';
$app = new Silex\Application();
Twig_Autoloader::register();

$app['debug'] = true;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// test this shit out

$app->get('/test/', function (Silex\Application $app)
{
    //$cat = new \Symfony\Component\HttpKernel\Event\GetResponseEvent\onKernelRequest($event);
    $request = new \Symfony\Component\HttpFoundation\Request();
    $request = $app['request'];

    echo '<pre>';
    //print_r($request);
    echo '</pre>';
    echo ($app['request']->isXmlHttpRequest())?'true':'false';
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    ?
        'true':'false';
});
// */


$app->run();