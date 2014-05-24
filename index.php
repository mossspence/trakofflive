<?php
error_reporting(0);
$loader = require __DIR__.'/vendor/autoload.php';
$app = new Silex\Application();
Twig_Autoloader::register();

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

$app['debug'] = false;

$app->get('/api/coverCache/{string}', 
            function (Silex\Application $app, $string)
{
    $artWorkCheck = new moss\musicapp\query\coverArtCache();

    // fake unserialize
    list($title,$artist,$albumTitle) = array_pad( explode( '==', $string ), 3, '' );

    // searching for a record without parentheses provides better results
    // but this is terribly slow (as it should be)
    // and it is not perfect
    //
    $art = $artWorkCheck->getCache(
            preg_replace("/\((.*?)\)/", '', html_entity_decode($title)), 
            preg_replace("/\((.*?)\)/", '', html_entity_decode($artist)), 
            preg_replace("/\((.*?)\)/", '', html_entity_decode($albumTitle)));
        
    return new \Symfony\Component\HttpFoundation\Response($art, 200);
});
$app->get('/itunes/{string}', function (Silex\Application $app, $string)
{
    $terms = array(
     'term' => $string, 'country' => 'CA', 'media' => 'music',  'limit' => '15'
    );
    
    $searchString = "https://itunes.apple.com/search" . "?" . http_build_query($terms);

    $json =  file_get_contents($searchString);
    return new \Symfony\Component\HttpFoundation\Response($json, 200);
    // */
});
$app->get('/sanitize/{string}', function (Silex\Application $app, $string)
{
    $result = '';
    $sch = new moss\musicapp\songSearch($string);
    $songs = $sch->search();
    foreach ($songs as $song)
    {
        $result .= $song['title'] . " : sanitized: " 
                . preg_replace("/\((.*?)\)/", '', html_entity_decode($song['title']))
                .  '<br />' . PHP_EOL;
    }
    //$result = $string . ": sanitized: " . moss\standard\sanitizeValues::sanitizeString($string);
    return new \Symfony\Component\HttpFoundation\Response($result, 200);
    // */
});
$app->get('/testmail/', function (Silex\Application $app)
{    
        $app->register(new Silex\Provider\SwiftmailerServiceProvider());
        
        $smtp_server = ini_get('SMTP');
        $smtp_port = ini_get('smtp_port');
        
        $transport = (!empty($smtp_server))
                ? \Swift_SmtpTransport::newInstance($smtp_server, $smtp_port)
                :NULL;
        if(!empty($transport))
        {
            $myMailer = new moss\musicapp\exporter\sendMailToAdmin(
                    \Swift_Mailer::newInstance($transport),
                    \Swift_Message::newInstance(),
                    array('oneilstuart@gmail.com' => 'Oneil Stuart'),  // recipient
                    array('mark@oneilstuart.com' => 'Resident DJ Oneil'),  // sender
                    'test title');

            $message = $myMailer->showObject('message');
            $mailer = $myMailer->showObject('mailer');

            echo '<h1>SMTP Object</h1><pre>';
            echo 'HOST: ' . $smtp_server . ' port: ' . $smtp_port;
            echo '</pre>';
            
            /*
            echo '<h1>Mailer Object</h1><pre>';
            echo var_dump($mailer);
            echo '</pre>'; 

            echo '<h1>Message Object</h1><pre>';
            echo var_dump($message);
            echo '</pre>';

            echo '<h1>Transport Object</h1><pre>';
            echo var_dump($transport);
            echo '</pre>';
             * 
             */

            $myMailer->makeMessage('test list', 
                    moss\standard\sanitizeValues::sanitizeString(ucfirst(substr('oneilstuart@gmail.com', 0, strpos('oneilstuart@gmail.com', '@')))));
        }
        //return true;
        return ($myMailer->sendEmail());
        
});

//homepage

$app->get('/', function (Silex\Application $app)
{
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__.'/appsrc/templates'));
    return $twig->render('home.html.twig');
    //return true;
});

// save the exported m3u8 list to database

$app->post('/api/savelist/', function (Request $request) use ($app)
{
    $numSent = 0;
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
    
    $result = $saveMyList->saveMe($DJ_m3u8list, $makePlayLister->getPlayList());
    
    if($result)
    {
        $subjectTitle = $saveMyList->getfield('title'); // sanitized title

        $smtp_server = ini_get('SMTP');
        $smtp_port = ini_get('smtp_port');

        $app->register(new Silex\Provider\SwiftmailerServiceProvider());        
        $transport = (!empty($smtp_server))
                ? \Swift_SmtpTransport::newInstance($smtp_server, $smtp_port)
                :NULL;
        if(!empty($transport))
        {
            $myMailer = new moss\musicapp\exporter\sendMailToAdmin(
                    Swift_Mailer::newInstance($transport),
                    Swift_Message::newInstance(),
                    array($saveMyList->getfield('email')),          // recipient
                    array('mark@oneilstuart.com' => 'Resident DJ Oneil'),  // sender
                    $subjectTitle);

            $myMailer->makeMessage( $saveMyList->getfield('urlTitle'),
                    ucfirst(substr($saveMyList->getfield('email'), 0, 
                            strpos($saveMyList->getfield('email'), '@'))));
            $numSent = $myMailer->sendEmail();
        }
        $result = $saveMyList->getfield('url');
    }else
    {
        echo 'Could not be saved.';
    }
    return new \Symfony\Component\HttpFoundation\Response($result, 200);
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
            $request->get('maxFileSize'), $request->get('maxSeconds'), 
                $request->get('textBatchFile'));
        //print_r(json_encode(array($_POST['baseDir'], 'it maybe worked')));
     }
     return $retVal;
});

// get logs

$app->get('/api/logs/', function (Silex\Application $app)
{
     $settings = new moss\musicapp\logger\SQLLiteMemoryHelper();
     
    $myLog = $settings->getErrorLog();
    
    $returnVal = json_encode($myLog);
    return new \Symfony\Component\HttpFoundation\Response($returnVal, 201);
});

// get standard LOAD settings

$app->get('/api/loadersettings/', function (Silex\Application $app)
{
     $settings = new moss\musicapp\logger\SQLLiteMemoryHelper();
     
        //sleep(1); // anymore than this and I'll have a riot on my hands
        $mySettings = $settings->fetchSettings();
        $returnVal = json_encode($mySettings);
    return new \Symfony\Component\HttpFoundation\Response($returnVal, 200);
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
                    json_encode($_SESSION['upload_progress_upload']), 201);
});

// receive JSON song uploads

$app->post('/api/upload/songs/', function (Request $request) use ($app)
{
    $app['session'] = new Session(); $app['session']->start();

    if($request->files->get('myfile'))
    {
        //print_r ($request->files->get('myfile'));
        $actualFile = $request->files->get('myfile');

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

$app->get('/api/search/{string}/{page}/', function (Silex\Application $app, $string, $page) {

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
});

// search title, artist, album

$app->get('/api/search/{string}/', function (Silex\Application $app, $string) {

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

$app->run();
