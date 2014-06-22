<?php

namespace moss\musicapp\controllers;

/**
 * Description of searchController
 *
 * @author mosspence
 * search controller
 */
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
 
class searchControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        //$controllers = $app['controllers_factory'];
        $controllers = new ControllerCollection($app['route_factory']);
        $controllers
                ->$app->get(  // this is where the problem occurs /* Catchable fatal error: Object of class Silex\Application could not be converted to string in /home/www/silex-songs/appsrc/moss/musicapp/controllers/searchControllerProvider.php on line 25  */
                '/{string}', 
                function ($string){
           
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
/*
        $controllers->$app->get('/{string}/{page}', function (Silex\Application $app, $string) {

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
        })->value('page', 1)->assert('page', '\d+');
        // */
        return $controllers;
    }
}



/*
{
    public function indexAction(Application $app, $string, $page)
    {
        $sch = new moss\musicapp\songSearch($string, '', '', $page);
        $songs = $sch->search();

        $returnVal = json_encode(array("offset"=>$sch->__get('offset'), 
                "numRows"=> count($songs), 
                "numResults"=>$sch->__get('numResults'), 
                "limit"=>$sch->__get('limit'), "page"=>$sch->__get('page'), 
                "query"=>$string, "cleaned_query"=>$sch->__get('searchString'), 
                "songs"=> $songs));
        
        return new Response($returnVal, 200);
    }
    
    public function toolAction(Application $app, $key, $bpm, $page, $kmove, $bmove)
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
        
        return new Response($returnVal, 200);
    }
}
// */