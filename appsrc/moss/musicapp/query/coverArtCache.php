<?php
namespace moss\musicapp\query;
use moss\musicapp\query\connect;
//use moss\musicapp\logger;
//use moss\musicapp;
/**
 * coverArtCache
 *
 * @author mosspence
 * 
 * check iTunes for artwork and save it in a local cache (database)
 * 
 */
//require_once 'SQLConnection.class.php';
//require_once 'musica.class.php';

class coverArtCache extends connect\SQLConnection{
    
    private $DB__TABLE_itc = "cover_art_cache";
    private $limit;
    private $db;
    
    public function __construct() {
        
        try{
            parent::__construct();
            $this->db = parent::getDBConn();
            
        }catch(\PDOException $e)
        {
            echo 'there was some kind of ' . __CLASS__ . ' child connection problem: '. $e->getMessage();
        }
    }
    //get the imageUrl from the local database
     /**
     * @assert ('nas', 'life is good') == 'http://a5.mzstatic.com/us/r30/Music/v4/05/9d/b1/059db16f-c11d-461e-7297-5268ca037267/UMG_cvrart_00602537109531_01_RGB72_1200x1200_12UMGIM31296.60x60-50.jpg'
     */    
    public function getAlbumArt($artist, $albumTitle)
    {
        $imgUrl = NULL;
            $sql = 'SELECT imgUrl 
                        FROM ' . $this->DB__TABLE_itc. '
                        WHERE artist = :artist
                        AND albumTitle = :albumTitle limit 1';
        try {

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':artist', $artist);
            $stmt->bindParam(':albumTitle', $albumTitle);
            $stmt->execute();
            
            $stmt->bindColumn('imgUrl', $imgUrl);
            $stmt->fetch();
                
        }catch (\PDOException $e) {
                /* Error */
                echo 'Prepared Statement Error: '. $e->getMessage();
            }
       return $imgUrl;
    }
     /**
          * @assert ('nas', 'Cherry Wine ') == 'http://a5.mzstatic.com/us/r30/Music/v4/05/9d/b1/059db16f-c11d-461e-7297-5268ca037267/UMG_cvrart_00602537109531_01_RGB72_1200x1200_12UMGIM31296.60x60-50.jpg'
     */    
    public function getSongArt($artist, $title)
    {
        $imgUrl = NULL;
            $sql = 'SELECT imgUrl 
                        FROM ' . $this->DB__TABLE_itc. '
                        WHERE artist =  :artist
                        AND songTitle =  :songTitle limit 1';
        try {

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':artist', $artist);
            $stmt->bindParam(':songTitle', $title);
            $stmt->execute();
            
            $stmt->bindColumn('imgUrl', $imgUrl);
            $stmt->fetch();
                
        }catch (\PDOException $e) {
                /* Error */
                echo 'Prepared Statement Error: '. $e->getMessage();
            }
       return $imgUrl;    
    }
     /**
     * @assert ('Cherry Wine ', 'nas', 'life is good') == 'http://a5.mzstatic.com/us/r30/Music/v4/05/9d/b1/059db16f-c11d-461e-7297-5268ca037267/UMG_cvrart_00602537109531_01_RGB72_1200x1200_12UMGIM31296.60x60-50.jpg'
     */    
    public function getCache($title, $artist, $albumTitle)
    {
        $art = $this->getAlbumArt($artist, $albumTitle);

        if(empty($art))
        {
            $art = $this->getSongArt($artist, $title);
            if(empty($art))
            {
                $art = $this->find($title, $artist, $albumTitle);
            }
        }
        return $art;
        // this only works in PHP 5.5+
        /*
        return (empty($this->getAlbumArt($artist, $albumTitle)))
                ? ( empty($this->getSongArt($artist, $title))
                        ? $this->find($title, $artist, $albumTitle)
                        : $this->getSongArt($artist, $title)
                  )
                : $this->getAlbumArt($artist, $albumTitle);
        */
    }
    //save the imgUrl to the database
    private function set($title, $artist, $albumTitle, $imgURL, $searchTerm)
    {
        $retVal = FALSE;
        $sql = 'insert into ' . $this->DB__TABLE_itc . 
                ' (imgUrl, artist, albumTitle, songTitle, searchTerm)  
                 VALUES (:imgUrl, :artist, :albumTitle, :songTitle, :searchTerm)';

        try{
            $stmt = $this->db->prepare($sql);
            // Bind your variables to replace the ?s
            $stmt->bindParam(':imgUrl', $imgURL);
            $stmt->bindParam(':artist', $artist);
            $stmt->bindParam(':albumTitle', $albumTitle);
            $stmt->bindParam(':songTitle', $title);
            $stmt->bindParam(':searchTerm', $searchTerm);

            // Execute query
            $retVal = $stmt->execute();
        }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
        return $retVal;
    }
    /*
    // One *should* do a _get_ before a _find_
    // find WILL update the local cache if it finds artwork.
    //  That means that if one does not check the cache first, 
     *  this method WILL insert duplicate data 
     * 
     */
     /**
     * @assert ('Cherry Wine ', 'nas', 'life is good') != NULL
     */
    public function find($title, $artist, $albumTitle)
    {
        $artwork = NULL;
        $gotSomethingFlag = FALSE;
        
        // It really is just crazy to perform searches like this
        // crazy crazy crazy to try like 4 times
        $searchTerms = array($title. ' ' . $artist,
            $albumTitle. ' ' . $artist,
            $title. ' ' . $albumTitle,
            $title. ' ' . $artist . ' ' . $albumTitle);

        foreach($searchTerms as $searchTerm)
        {
            //  (have not tried using US as country)
            //  the below attributes stopped working one day in April 2014
            //  'entity' => 'song,musicArtist,album',
            //  'attribute' => 'artistTerm, albumTerm, songTerm',
            
            $terms = array(
                'term' => $searchTerm, 'country' => 'CA', 
                'media' => 'music','limit' => '5'
            );
            
            $iTunesResults = json_decode(file_get_contents(
                "https://itunes.apple.com/search" . "?" . 
                    http_build_query($terms)
                ));
        
            if($iTunesResults->resultCount > 0)
            {
                // I care about 'artworkUrl60' or 'artworkUrl30'
                 foreach($iTunesResults->results as $record)
                 {
                     if($record->artworkUrl60)
                     {
                         $artwork = $record->artworkUrl60; 
                         $gotSomethingFlag = TRUE;
                         break;
                     }
                     if($record->artworkUrl30)
                     {
                         $artwork = $record->artworkUrl30; 
                         $gotSomethingFlag = TRUE;
                         break;
                     }
                 }
            }
            if($gotSomethingFlag) {break;}
        }

        // this method really should not do this part
        if(NULL !== $artwork)
        {
            $this->set($title, $artist, $albumTitle, $artwork, $searchTerm);
        }
        return $artwork;
    }
}