<?php
namespace moss\musicapp\loader;

/**
 * LoadMusica
 * load song data into the database
 * 
 * @author mosspence
 */

//require_once 'SQLConnection.class.php';
//require_once 'musica.class.php';
use moss\musicapp\query\connect;
use moss\musicapp;

class LoadMusica extends connect\SQLConnection{
    
    private $DB__TABLEs = "songs";
    private $DB__TABLEb = "BPMs";
    private $DB__TABLEk = "keycodes";
    private $DB__TABLEki = "keycode_index";
    private $DB__TABLEgi = "genre_index";
    private $DB__TABLEgt = "genres_table";
    
    private $db;
    
    public function __construct() {
        //$sqlconn = new SQLConnection();
        //$this->db = $sqlconn->getDBConn();
        parent::__construct();
        $this->db = parent::getDBConn();
    }
    /**
      * @assert ('5M') != NULL 
     */
    function getSongOpenKeyID($keycode)
    {
        // assert $keyID string should have already been checked to be a nonEmpty string
        try{
        $retVal = NULL;
        $sql = "SELECT keyID FROM " . $this->DB__TABLEki . " WHERE openKey_code = :sKey LIMIT 1";
        
        // Create a prepared statement
        $stmt=$this->db->prepare($sql);
        
        if ($stmt) {
            
            $stmt->bindParam(':sKey', $keycode);
            $retVal = $stmt->execute();
            /* Bind results to variables */
            $stmt->bindColumn('keyID', $keyID);
            $stmt->fetch(\PDO::FETCH_ASSOC);

            //echo "getSongGenreID: genreID = $genreID after checking for $genre<br />\n";
            
        } else {
            /* Error */
            \moss\standard\Messages::addError("Prepared Statement Error: %s\n", $stmt->error);
        }

        }catch (ErrorException $e){ echo "what happened: $e";}
        
        //if(!$keyID){$keyID = NULL;} // does this make sense? I think so.
        return (!$keyID) ? NULL : $keyID;
    }
    /**
      * @assert ('5A') != NULL
     */
    function getSongCamelotKeyID($keycode)
    {
        // assert $keyID string should have already been checked to be a nonEmpty string
        try{
        $retVal = NULL;

        $sql = "SELECT keyID FROM " . $this->DB__TABLEki . " WHERE camelot_code = :sKey LIMIT 1";
        $stmt = $this->db->prepare($sql);
        // Create a prepared statement
        if ($stmt) {
            
            $stmt->bindParam(':sKey', $keycode);

            $retVal = $stmt->execute();
            /* Bind results to variables */
            $stmt->bindColumn('keyID', $keyID);
            $stmt->fetch(\PDO::FETCH_ASSOC);

            //echo "getSongGenreID: genreID = $genreID after checking for $genre<br />\n";
            
        } else {
            /* Error */
            \moss\standard\Messages::addError("Prepared Statement Error: %s\n", $stmt->error);
        }

        }catch (ErrorException $e){ echo "what happened: $e";}
        
        //if(!$keyID){$keyID = NULL;} // does this make sense? I think so.
        return (!$keyID) ? NULL : $keyID;
    }
    /*
     * insert a song into the DB
     *
     * 
     * array('ID' => $song->id, 
                            'filename' => $song->filename,
                            'location' => $song->location,
                            'title' => $song->title,
                            'artist' => $song->artist,
                            'album' => $song->album,
                            'genre' => $song->genre,
                            'track_number' => $song->track_number,
                            'year' => $song->year,
                            'bpm' => $song->bpm, 
                            'fBPM' => $song->fBPM, 
                            'bpm_start' => $song->bpm_start, 
                            'initial_key' => $song->initial_key, 
                            'key_start' => $song->key_start, 
                            'key_end' => $song->key_end, 
                            'content_group_description' => $song->content_group_description, 
                            'playtime' => $song->length, 
                            'bitrate' => $song->bitrate,
                            'comment' => $song->comment); 
     * 
     *  @param mp3Song Object
     *  @return boolean
     */
    function insertSongKeys($myID, musicapp\mp3SongFileInfo $songInfo)
    {
        // assert that at least 1 key field is a non-empty string
        $song = (object) $songInfo->__toArray();
        
        $retVal = FALSE;

        $sql = "INSERT INTO " . $this->DB__TABLEk . 
                            " (songID, 
                                initial_key,
                                key_start,
                                key_end,
                                content_group_description) 
                            VALUES (:songID, :iKey, :keyStart, :keyEnd, :grouping)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':songID', intval($myID));
            $stmt->bindParam(':iKey', $song->initial_key);
            $stmt->bindParam(':keyStart', $song->key_start);
            $stmt->bindParam(':keyEnd', $song->key_end);
            $stmt->bindParam(':grouping', $song->content_group_description);
                          
            // Execute query
            $retVal = $stmt->execute();

         }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
        return $retVal;
    }
    /**
      * @assert ('Hip-Hop') != NULL
     *  @assert ('Hip Hop') != NULL
     */
    function getSongGenreID($genre)
    {
        // assert $genre string should have already been checked to be a nonEmpty string

        $retVal = NULL;
        
        $sql = "SELECT GID FROM " . $this->DB__TABLEgi . " WHERE genre = :genre LIMIT 1";
        
        // Create a prepared statement
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':genre', $genre);

            $retVal = $stmt->execute();
            /* Bind results to variables */
            $stmt->bindColumn('GID', $genreID);
            $stmt->fetch(\PDO::FETCH_ASSOC);

            //echo "getSongGenreID: genreID = $genreID after checking for $genre<br />\n";
            
        }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
        return $genreID;
    }
    function addSongGenreToIndex($genreString)
    {    
        // assert $genre string should have already been checked to be a nonEmpty string
        $last_Inserted_ID = NULL;
        
        $sql = "INSERT INTO " . $this->DB__TABLEgi . 
                            " (genre) 
                            VALUES (:genre)";
        
        try {
            $stmt = $this->db->prepare($sql);
            // Bind your variables to replace the ?s
            $stmt->bindParam(':genre', $genreString);        

            // Execute query
            $retVal = $stmt->execute();

            if($retVal) {$last_Inserted_ID = $this->db->lastInsertId(); }
         }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
         
        return $last_Inserted_ID;        
    }
    /**
      * @assert ('Electronica') != NULL
     */
    function checkSongGenre($genre)
    {
        $genreID = $this->getSongGenreID($genre);
        if(!$genreID)
        {
            $genreID = $this->addSongGenreToIndex($genre);
        }
        return $genreID;
    }
    function insertSongGenres($myID, musicapp\mp3SongFileInfo $songInfo)
    {
        $song = (object) $songInfo->__toArray();
        $genreID = $this->checkSongGenre($song->genre);
        
        $retVal = FALSE;
        
        $sql = "INSERT INTO " . $this->DB__TABLEgt . 
                            " (GID, songID)  
                            VALUES (:id, :genre)";
        
        try{
            $stmt = $this->db->prepare($sql);
            // Bind your variables to replace the ?s
            $stmt->bindParam(':id', intval($myID));
            $stmt->bindParam(':genre', intval($genreID));

            // Execute query
            $retVal = $stmt->execute();
        }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
        return $retVal;
    }
    function insertSongBPMs($myID, musicapp\mp3SongFileInfo $songInfo)
    {
        // assert that at least 1 BPM field is a non-empty string
        
        $song = (object) $songInfo->__toArray();
        
        $retVal = FALSE;

        $sql = "INSERT INTO " . $this->DB__TABLEb . 
                            " (songID, 
                                bpm,
                                fBPM,
                                bpm_start) 
                            VALUES (:songID, :bpm, :fBPM, :bpmStart)";
        try{
            $stmt = $this->db->prepare($sql);

            $stmt->bindParam(':songID', intval($myID));
            $stmt->bindParam(':bpm', $song->bpm);
            $stmt->bindParam(':fBPM', $song->fBPM);
            $stmt->bindParam(':bpmStart', $song->bpm_start);

            // Execute query
            $retVal = $stmt->execute();
         }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
        return $retVal;
    }
    function insertSongBasics(musicapp\mp3SongFileInfo $songInfo) {

        $song = (object) $songInfo->__toArray();
        
        $previouslyEnteredID = parent::getSongID($song->filename, $song->location);
        
        if(NULL != $previouslyEnteredID)
        {
            $this->updateSongID($previouslyEnteredID, $songInfo);
            $last_Inserted_ID = $previouslyEnteredID;
        }else
         {
            $last_Inserted_ID = $this->insertSong($songInfo);
         }
         
        if($last_Inserted_ID)
        {
             // insert/update into genre

             if($song->genre)
             {
                 $genreRetVal = $this->insertSongGenres($last_Inserted_ID, $songInfo);
             }

             // insert/update into Keys

             if(($song->initial_key) || (($song->key_start)) ||
                (($song->key_end)) || (($song->content_group_description))
               )
             {
                 $keyRetVal = $this->insertSongKeys($last_Inserted_ID, $songInfo);
             }

             // insert/update into BPMs

             if(($song->bpm) || (($song->fBPM)) || (($song->bpm_start)))
             {
                 $bpmRetVal = $this->insertSongBPMs($last_Inserted_ID, $songInfo);
             }
        }
        // this next line is useless because it can FAIL on successful insertion
        //$retVal = ($retVal && $genreRetVal && $keyRetVal && $bpmRetVal);  
        return $last_Inserted_ID;
    }
    
    /*
     * In the event of an UPDATE
     * I would like to find the ID of a song based on the following unique info
     * :filename
     * :location
     */
    function updateSongID($songIDToUpdate, musicapp\mp3SongFileInfo $songInfo)
    {
        $song = (object) $songInfo->__toArray();
                
        $retVal = FALSE;

        $sql = "UPDATE  $this->DB__TABLEs  SET 
                title = :title, artist = :artist, album = :album, 
                bitrate = :bitrate, playtime = :playtime, comment = :comment, 
                WHERE ID = :ID";
        
        try{
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':title', $song->title);
            $stmt->bindParam(':artist', $song->artist);
            $stmt->bindParam(':album', $song->album);
            $stmt->bindParam(':bitrate', $song->bitrate);
            $stmt->bindParam(':playtime', $song->playtime);
            $stmt->bindParam(':comment', $song->comment);
            $stmt->bindParam(':ID', intval($songIDToUpdate));

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
     * insert the song and return the songID
     * 
     */
    function insertSong(musicapp\mp3SongFileInfo $songInfo)
    {
        $song = (object) $songInfo->__toArray();
        
        $retVal = \FALSE;
        $last_Inserted_ID = \NULL;

        $sql = "INSERT INTO " . $this->DB__TABLEs . 
                    " (filename, location, title, artist, album, 
                     track_number, year, playtime, bitrate, comment) 
                        VALUES (
                            :filename,
                            :location, 
                            :title,
                            :artist,
                            :album,
                            :track_number,
                            :year,
                            :playtime,
                            :bitrate,
                            :comment
                            )";

        try{
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':filename', $song->filename);
            $stmt->bindParam(':location', $song->location);
            $stmt->bindParam(':title', $song->title);
            $stmt->bindParam(':artist', $song->artist);
            $stmt->bindParam(':album', $song->album);
            $stmt->bindParam(':track_number', $song->track_number);
            $stmt->bindParam(':year', $song->year);
            $stmt->bindParam(':playtime', $song->playtime);
            $stmt->bindParam(':bitrate', $song->bitrate);
            $stmt->bindParam(':comment', $song->comment);
            
            // Execute query
            $retVal = $stmt->execute();
            
         }catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
            \moss\standard\Messages::addError("Error: %s\n", $e->getMessage());
        }
         
         // get the lastInsertedID (songID) to be the foreign key
         if($retVal) {$last_Inserted_ID = $this->db->lastInsertId();}
         return $last_Inserted_ID;
    }
}