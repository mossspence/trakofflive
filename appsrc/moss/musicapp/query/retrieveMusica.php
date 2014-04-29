<?php
namespace moss\musicapp\query;
use moss\musicapp\query\connect;
use moss\musicapp\logger;
use moss\musicapp;
/**
 * Description of retrieveMusica
 *
 * @author mosspence
 */
//require_once 'SQLConnection.class.php';
//require_once 'musica.class.php';

class retrieveMusica extends connect\SQLConnection{
    
    private $DB__TABLEs = "songs";
    private $DB__TABLEb = "BPMs";
    private $DB__TABLEk = "keycodes";
    private $DB__TABLEgi = "genre_index";
    private $DB__TABLEgt = "genres_table";
    private $limit;
    private $db;
    
    private $coverDir;
    
    public function __construct() {
        
        try{
            parent::__construct();
            $this->db = parent::getDBConn();
            $this->limit = 50;
        }catch(\PDOException $e)
        {
            echo 'there was some kind of ' . __CLASS__ . ' child connection problem: '. $e->getMessage();
        }
        
        try{
            // I wonder if this is the best place to do this?
            $settings = new logger\SQLLiteMemoryHelper();
            $mySettings = $settings->fetchSettings();
            // but honestly, the coverDir should be a static setting
            $this->coverDir = $mySettings['coverDir'];
        }catch(\PDOException $e)
        {
            echo 'there was a CHILD SQLITE connection problem: '. $e->getMessage();
        }
    }
    public function getLimit()
    {
        return $this->limit;
    }
    public function searchSongsBasic($subject, $offset = 0)
    {
        $retVal = NULL;
        
        try {
            $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS ID, filename, location, 
                    title, artist, album, bitrate, bpm, playtime, initial_key  
                FROM songs
                LEFT JOIN BPMs ON ID = BPMs.songID 
                LEFT JOIN keycodes ON ID = keycodes.songID 
                WHERE title LIKE CONCAT('%' , :string, '%') 
                OR artist LIKE CONCAT('%' , :string, '%') 
                OR album LIKE CONCAT('%' , :string, '%') 
                ORDER BY title LIMIT " . $this->limit;
            
                if ($offset) {
                    $sql = $sql . " OFFSET " . $offset;
                }
                $db = parent::getDBConn();
                $stmt = $db->prepare($sql);

                $stmt->bindParam(':string', $subject);
                $stmt->execute();
            
                $stmt->bindColumn('ID', $ID);
                $stmt->bindColumn('filename', $filename);
                $stmt->bindColumn('location', $location);
                $stmt->bindColumn('title', $title);
                $stmt->bindColumn('artist', $artist);
                $stmt->bindColumn('album', $album);
                $stmt->bindColumn('bitrate', $bitrate);
                $stmt->bindColumn('bpm', $bpm);
                $stmt->bindColumn('playtime', $playtime);
                $stmt->bindColumn('initial_key', $key);
            
                while ($stmt->fetch())
                {
                    $song = new musicapp\mp3SongFileInfo($ID);
                    $song->__set('title', $title);
                    $song->__set('artist', $artist);
                    $song->__set('album', $album);
                    $song->__set('bitrate', $bitrate);
                    $song->__set('filename', $filename);
                    $song->__set('bpm', $bpm);
                    $song->__set('length', $playtime);
                    $song->__set('location', $location);
                    $song->__set('initial_key', $key);
                    
                    $song->__set('coverDir', $this->coverDir);
                    
                    musicapp\MP3SongList::addMP3($song);
                }

                $sql2 = 'SELECT FOUND_ROWS()';
                $stmt = $this->db->prepare($sql2);
                $stmt->execute();
                $stmt->bindColumn('FOUND_ROWS()', $rows);
                $stmt->fetch();$retVal = $rows;
                
            } catch (\PDOException $e) {
                /* Error */
                echo 'Prepared Statement Error: '. $e->getMessage();
            }

        return $retVal;
    }
    public function selectPlaylist($songList)
    {
        $retVal = NULL;
        
        $arr_length = count($songList);
        
        if($arr_length <= 0){return $retVal;}
        
        $songListRef = array();
        foreach ($songList as $song) { $songListRef[] = $song; }
        
        $params = implode(', ', array_fill(0, $arr_length, '?'));
        $songListParam = array_merge($songList, $songList);

        try {
            $sql = 'SELECT ID, filename, location, title, artist, album, bitrate,
                        playtime
                        FROM songs 
                        WHERE id IN ( '
                        . $params . ' ) ' . 
                        'ORDER BY FIELD '
                        . '( id, ' . $params .' )'; // */
            /*
             *          bpm, playtime, initial_key 
                        FROM songs 
                        WHERE id IN ( '
                        LEFT JOIN BPMs ON ID = BPMs.songID 
                        LEFT JOIN keycodes ON ID = keycodes.songID             
            
            // */
                $db = parent::getDBConn();
                $stmt = $db->prepare($sql);
                //$stmt = $this->db->prepare($sql);
                $retVal = $stmt->execute($songListParam);
                
                $stmt->bindColumn('ID', $ID);
                $stmt->bindColumn('filename', $filename);
                $stmt->bindColumn('location', $location);
                $stmt->bindColumn('title', $title);
                $stmt->bindColumn('artist', $artist);
                $stmt->bindColumn('album', $album);
                $stmt->bindColumn('bitrate', $bitrate);
                //$stmt->bindColumn('bpm', $bpm);
                $stmt->bindColumn('playtime', $playtime);
                //$stmt->bindColumn('initial_key', $key);
                // */

                while ($stmt->fetch())
                {
                    $song = new musicapp\mp3SongFileInfo($ID);
                    $song->__set('title', $title);
                    $song->__set('artist', $artist);
                    $song->__set('album', $album);
                    $song->__set('bitrate', $bitrate);
                    $song->__set('filename', $filename);
                    //$song->__set('bpm', $bpm);
                    $song->__set('length', $playtime);
                    $song->__set('location', $location);
                    //$song->__set('initial_key', $key);
                    //  */
                    $song->__set('coverDir', $this->coverDir);
                    
                    musicapp\MP3SongList::addMP3($song);                
                }
            
        } catch (\PDOException $e) {
                /* Error */
                echo 'Prepared Statement Error: '. $e->getMessage();
            }
        return $retVal;
    }
    
    public function searchSongsBPMKeyMatch($givenKey, $bpmRange, $offset = 0)
    {
        $retVal = NULL;

        try {
            $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS ID, filename, location, title, 
                    artist, album, bitrate, bpm, playtime,  
                    TRIM(LEADING '0' FROM initial_key) AS initial_key  
                FROM songs
                LEFT JOIN BPMs ON ID = BPMs.songID 
                LEFT JOIN keycodes ON ID = keycodes.songID 
                WHERE BPMs.bpm BETWEEN :low AND :high 
                AND TRIM(LEADING '0' FROM initial_key) = :key 
                ORDER BY BPMs.bpm LIMIT " . $this->limit;

                if ($offset) {
                    $sql = $sql . " OFFSET " . $offset;
                }

                $stmt = $this->db->prepare($sql);
                
                $stmt->bindParam(':low', $bpmRange['lower']);
                $stmt->bindParam(':high', $bpmRange['upper']);
                $stmt->bindParam(':key', $givenKey);
                $retVal = $stmt->execute();
            
                $stmt->bindColumn('ID', $ID);
                $stmt->bindColumn('filename', $filename);
                $stmt->bindColumn('location', $location);
                $stmt->bindColumn('title', $title);
                $stmt->bindColumn('artist', $artist);
                $stmt->bindColumn('album', $album);
                $stmt->bindColumn('bitrate', $bitrate);
                $stmt->bindColumn('bpm', $bpm);
                $stmt->bindColumn('playtime', $playtime);
                $stmt->bindColumn('initial_key', $key);
            
                while ($stmt->fetch())
                {
                    $song = new musicapp\mp3SongFileInfo($ID);
                    $song->__set('title', $title);
                    $song->__set('artist', $artist);
                    $song->__set('album', $album);
                    $song->__set('bitrate', $bitrate);
                    $song->__set('filename', $filename);
                    $song->__set('bpm', $bpm);
                    $song->__set('length', $playtime);
                    $song->__set('location', $location);
                    $song->__set('initial_key', $key);
                    
                    $song->__set('coverDir', $this->coverDir);
                    
                    musicapp\MP3SongList::addMP3($song);                
                }

                $sql2 = 'SELECT FOUND_ROWS()';
                $stmt = $this->db->prepare($sql2);
                $stmt->execute();
                $stmt->bindColumn('FOUND_ROWS()', $rows);
                $stmt->fetch();$retVal = $rows;
                
            } catch (\PDOException $e) {
                /* Error */
                echo 'Prepared Statement Error: '. $e->getMessage();
            }
        return $retVal;
    }
}