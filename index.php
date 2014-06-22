<?php
error_reporting(0);

$loader = require __DIR__.'/vendor/autoload.php';
$app = new Silex\Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$app['appName'] = 'trakofflive';
$app['app version'] = 'beta 0.5.2';

Twig_Autoloader::register();

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

$app['debug'] = $_SERVER['REMOTE_ADDR'] == '127.0.0.1';

if($app['debug'])
{
    error_reporting(E_ALL | E_STRICT); 
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}
//$app->mount('/api/search/', new moss\musicapp\controllers\searchControllerProvider());
//$app->mount('/api/tools/', new moss\musicapp\controllers\toolsControllerProvider());

// if you want to logout, access to 'auth/logout'
// $app->mount('/auth', new moss\standard\BasicAuthControllerProvider());

$app->get('/api/coverCache/{string}', function (Silex\Application $app, $string)
{
    $artWorkCheck = new moss\musicapp\query\coverArtCache();

    // fake unserialize
    list($title,$artist,$albumTitle) = array_pad( explode( '==', $string ), 3, '' );

    // searching for a record without parentheses provides better results
    // but this is terribly slow (as it should be)
    // and it is not perfect
    //$regex = "/\((.*?)\)/";
    $regex = "/\((.*?)\)|\/|\[(.*?)\]/";
    $art = $artWorkCheck->getCache(
            preg_replace($regex, ' ', html_entity_decode($title)), 
            preg_replace($regex, ' ', html_entity_decode($artist)), 
            preg_replace($regex, ' ', html_entity_decode($albumTitle)));
        
    return new \Symfony\Component\HttpFoundation\Response($art, 200);
});

// save the exported m3u8 list to database

$app->post('/api/savelist/', function (Request $request) use ($app)
{
    // make the M3U8 list for the DJ who signs into a Windows computer
    // $signedIN = $userIsWindowsUser = TRUE;
    $makePlayLister = new moss\musicapp\exporter\playLister(
            $request->get('playlist'), TRUE, TRUE); // $signedIN, $userIsWindowsUser);
    $DJ_m3u8list = $makePlayLister->getM3U8List();
    
    // set up the saving of the list
    $saveMyList = new moss\musicapp\exporter\saveMyList(
                            $request->get('playlistTitle'), 
                            $request->get('email'), 
                            $request->get('eventHashtag'),
                            $request->get('playlistComments'));
    
    $sendMessage = new moss\musicapp\exporter\sendMessageTo(__DIR__.'/appsrc/templates');
    $saveMyList->attach($sendMessage);
    //$request->getHost();
    
    if($saveMyList->saveMe($DJ_m3u8list, $makePlayLister->getPlayList()))
    {
        // sends an email
        $saveMyList->notify(); $result = '1'; // flash message?
    }else
    {
        $result = '0'; // flash message?
    }
   
    return new \Symfony\Component\HttpFoundation\Response(
            json_encode(array('result'=>$result)), 200);
});

// post standard loading settings

$app->post('/api/loadersettings/', function (Request $request)
{
    $settings = new moss\musicapp\logger\SQLLiteMemoryHelper();
    if ($request->get('nomorefun')
            && ('kittenSOFT' == $request->get('nomorefun')) )
    {
        $retVal = $settings->startFresh();

    }elseif ($request->get('baseDir'))
     {
        $retVal = $settings->newBaseDir($request->get('baseDir'),
            $request->get('coverDir'), $request->get('thumbSize'),
            $request->get('residentDJ'), $request->get('DJSMSaddress'),
            $request->get('maxFileSize'), $request->get('maxSeconds'), 
            $request->get('textBatchFile'));
        //print_r(json_encode(array($request->get('baseDir'), 'it maybe worked')));
     }
     return new \Symfony\Component\HttpFoundation\Response(
            json_encode(array($request->get('baseDir'), 'it maybe worked')), 200);
});

// get logs

$app->get('/api/logs/', function (Silex\Application $app)
{
    $settings = new moss\musicapp\logger\SQLLiteMemoryHelper(); 
    $myLog = $settings->getErrorLog();

    return new \Symfony\Component\HttpFoundation\Response(
            json_encode($myLog), 200);
});

// get standard LOAD settings

$app->get('/api/loadersettings/', function (Silex\Application $app)
{
     $settings = new moss\musicapp\logger\SQLLiteMemoryHelper();
     
    //sleep(1); // anymore than this and I'll have a riot on my hands
    $mySettings = $settings->fetchSettings();

    return new \Symfony\Component\HttpFoundation\Response(
            json_encode($mySettings), 200);
});

// show the m3u8 list to the user
// and let the use enter comments, email, title

$app->post('/api/export/', function (Request $request)
{
    $playlist = $request->get('playlist');

    $signedIN = FALSE;
    $userIsWindowsUser = $signedIN;
    
    $makePlayLister = new moss\musicapp\exporter\playLister(
            $playlist, $signedIN, $userIsWindowsUser);
    
    $m3u8list = $makePlayLister->getM3U8List();

    return new \Symfony\Component\HttpFoundation\Response($m3u8list);
});

// admin section
// download a playlist by ID
//
$app->get('/api/playlists/{id}', function (Silex\Application $app, $id)
{
    $expo = new moss\musicapp\exporter\savePlaylist();
    
    $id = moss\standard\sanitizeValues::sanitizeINT($id);
    $m3u8Info = $expo->getPlaylist($id);
    if(!empty($m3u8Info))
    {
        $title = str_ireplace(' ', '-', $m3u8Info['title']);
        $response = new Response($m3u8Info['m3u8list']);
        $response->headers->set('Content-Type', 'text/m3u8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $title . '.m3u8"');
        $response->setCharset('UTF-8');
        return $response;
    }else
    {
        $app->abort(404, "No playlist with that identifier.");
        return false;
    }
});

// admin section
// view uploaded playlists and download them

$app->get('/api/playlists/', function (Silex\Application $app)
{
    $expo = new moss\musicapp\exporter\savePlaylist();
    $playlists = $expo->getPlaylists();

    return new \Symfony\Component\HttpFoundation\Response(json_encode($playlists), 201);
});

// view progress of any upload

$app->get('/api/uploadprogress/', function (Silex\Application $app)
{
    $app['session'] = new Session(); $app['session']->start();
    return new \Symfony\Component\HttpFoundation\Response(
                    json_encode($_SESSION['upload_progress_upload']), 200);
});

// receive JSON song uploads

$app->post('/api/upload/songs/', function (Request $request) use ($app)
{
    $app['session'] = new Session(); $app['session']->start();

    if($request->files->get('myfile'))
    {
        //print_r ($request->files->get('myfile'));
        //$actualFile = $request->files->get('myfile');

        $fileReader = new moss\musicapp\loader\batchImport();

        //read the file, parse the JSON and load to database
        $songList = $fileReader->import($request->files->get('myfile'));
        //$songList = moss\musicapp\MP3SongList::getSongList();
        $numRows = count($songList);
        $success = ($numRows == 0) ? 0 : 1;
    }

    $returnVal = json_encode(array("numRows"=> $numRows, 
                            "success" => $success));
    
    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
});

// get a specific playlist to display to the user
// I do this instead of emailing a copy cause goDaddy has some super low limit
// for sending emails or something

$app->get('/myplaylist/{string}', function (Silex\Application $app, $string)
{
    $expo = new moss\musicapp\exporter\savePlaylist();
    $string = moss\standard\sanitizeValues::sanitizeString($string);
    $m3u8Info = $expo->getPlaylistUrl($string);

    //print_r($m3u8Info);
    if(is_array($m3u8Info))
    {
        $makePlayLister = new moss\musicapp\exporter\playLister(
            explode(',', $m3u8Info['songlist'], -1), FALSE, FALSE); // $signedIN, $userIsWindowsUser);
        $m3u8Info['m3u8'] = $makePlayLister->getM3U8List();

        $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__.'/appsrc/templates'));
        return $twig->render('myplaylist.html.twig', $m3u8Info);        
    }else
     {
        $app->abort(404, "That playlist does not exist.");
        return false;
     }
    // return new \Symfony\Component\HttpFoundation\Response(json_encode($playlists));
});


// search title, artist, album with page

$app->get('/api/search/{string}/{page}', function (Silex\Application $app, $string, $page) {

    $sch = new moss\musicapp\songSearch($string, '', '', $page);
    $songs = $sch->search();

    $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
            "numRows"=> count($songs), 
            "numResults"=>$sch->__get('numResults'), 
            "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'), 
            "query"=>$string, "cleaned_query"=>$sch->__get('searchString'), 
            "songs"=> $songs));
    //json_encode($returnVal);

    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
})
->value('page', 1)
->assert('page', '\d+');

// search title, artist, album

$app->get('/api/search/{string}', function ($string) {

    $sch = new moss\musicapp\songSearch($string);
    $songs = $sch->search();

    $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
            "numRows"=> count($songs), 
            "numResults"=>$sch->__get('numResults'), 
            "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'), 
            "query"=>$string, "cleaned_query"=>$sch->__get('searchString'), 
            "songs"=> $songs));
    //json_encode($returnVal);

    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
});
// 

// search key bpm with movement

$app->get('/api/tools/{key}/{bpm}/{kmove}/{bmove}/{page}/', 
         function (Silex\Application $app, $key, $bpm, $page, $kmove, $bmove)
{
    $sch = new moss\musicapp\songSearch('', $bpm, $key, $page, $kmove, $bmove);
    $songs = $sch->search();

    $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
            "numRows"=> count($songs), 
            "numResults"=>$sch->__get('numResults'), 
            "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'),
            "bpm"=>$sch->__get('bpmString'), "key"=>$sch->__get('keySearch'),
            "bpmMovement"=>$sch->__get('bpmMovement'), "keyMovement"=>$sch->__get('keyMovement'), 
            "songs"=> $songs));

    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
});

// search key bpm with movement

$app->get('/api/tools/{key}/{bpm}/{kmove}/{bmove}/', 
         function (Silex\Application $app, $key, $bpm, $kmove, $bmove)
{
    $sch = new moss\musicapp\songSearch('', $bpm, $key, 1, $kmove, $bmove);
    $songs = $sch->search();

    $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
            "numRows"=> count($songs), 
            "numResults"=>$sch->__get('numResults'), 
            "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'),
            "bpm"=>$sch->__get('bpmString'), "key"=>$sch->__get('keySearch'),
            "bpmMovement"=>$sch->__get('bpmMovement'), "keyMovement"=>$sch->__get('keyMovement'), 
            "songs"=> $songs));

    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
});

// search key bpm with page

$app->get('/api/tools/{key}/{bpm}/{page}/', 
                    function (Silex\Application $app, $key, $bpm, $page)
{
    $sch = new moss\musicapp\songSearch('', $bpm, $key, $page);
    $songs = $sch->search();

    $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
            "numRows"=> count($songs), 
            "numResults"=>$sch->__get('numResults'),
            "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'), 
            "bpm"=>$sch->__get('bpmString'), "key"=>$sch->__get('keySearch'),
            "songs"=> $songs));
    //json_encode($returnVal);

    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
});

// search key bpm

$app->get('/api/tools/{key}/{bpm}/', 
                    function (Silex\Application $app, $key, $bpm)
{
    $sch = new moss\musicapp\songSearch('', $bpm, $key);
    $songs = $sch->search();

    $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
            "numRows"=> count($songs), 
            "numResults"=>$sch->__get('numResults'),
            "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'), 
            "bpm"=>$sch->__get('bpmString'), "key"=>$sch->__get('keySearch'),
            "songs"=> $songs));
    //json_encode($returnVal);

    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
});

//
// cheap way to various pages
// however i heard somewhere that this is a bad practice

$app->get('/{string}/', function (Silex\Application $app, $string)
{
    if('import' == $string){$app['session'] = new Session(); $app['session']->start(); }
    // standard output, without error
    if(!is_readable(__DIR__.'/appsrc/templates' . DIRECTORY_SEPARATOR . $string .'.html.twig')
            || $string == 'myplaylist'
      )
    {
        $app->abort(404, "That page does not exist.");
        return false;
    }
    // probably a bad idea but whatever ...
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__.'/appsrc/templates'));
    return $twig->render($string .'.html.twig', array('folder' => $string, 'sessionVar' => ini_get('session.upload_progress.name')));
    //return true;
});
// */
//homepage

$app->get('/', function (Silex\Application $app)
{
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__.'/appsrc/templates'));
    return $twig->render('home.html.twig');
    //return true;
})->bind('home');

$app->run();
