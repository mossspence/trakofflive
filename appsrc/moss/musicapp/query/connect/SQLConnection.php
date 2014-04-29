<?php
namespace moss\musicapp\query\connect;
use moss\standard;
/**
 * Description of SQLConnection
 * - connects, queries and shows results of database
 *
 * @category   Database Manipulation Engine
 * @author     Mark Spence mosspence at gmail dot com
 * @copyright  none
 * @deprecated NOT
 * @method constructor
 */
define('DB_PORT', 3306);

class SQLConnection{

    private $db;
    private $limit;
    var $offset;
    var $next;
    var $previous;
    var $numRecords;
    var $previousButton;
    var $nextButton;
    
    private $DB_ADMIN_NAME;
    private $DB_ADMIN_PW;
    private $DB_HOST;
    private $DB__NAME;
    private $DB__TABLEs = "songs";
    private $DB__TABLEb = "BPMs";
    private $DB__TABLEk = "keycodes";
    private $DB__TABLEgi = "genre_index";
    private $DB__TABLEgt = "genres_table";
    
    public function __construct() {

        $this->limit = 100;
        $this->offset = 0;
        $this->next = 0;
        $this->previous = 0;
        $this->numRecords = 0;
        $this->maxChars = 255;

        $ini_array = parse_ini_file("musica.ini", true);
    
        $this->DB_ADMIN_NAME = $ini_array['sql_conn']['DB_ADMIN_NAME'];
        $this->DB_ADMIN_PW = $ini_array['sql_conn']['DB_ADMIN_PW'];
        $this->DB_HOST = $ini_array['sql_conn']['DB_HOST'];
        $this->DB__NAME = $ini_array['sql_conn']['DB__NAME'];

        try{
        $this->db = new \PDO('mysql:host=' . $ini_array['sql_conn']['DB_HOST']
                          . ';dbname=' . $ini_array['sql_conn']['DB__NAME'],
                        $ini_array['sql_conn']['DB_ADMIN_NAME'], 
                        $ini_array['sql_conn']['DB_ADMIN_PW']);
        }catch (Exception $conn_trial)
        {
            echo "Can't connect: $conn_trial";
        }
        
        $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        unset($ini_array);
        if (!$this->db) {
            standard\Messages::addError("Something really bad happened so that I could not even link to the DB.");
            echo "Something really bad happened so that I could not even link to the DB.";
        }
    }
    /*
     * 
     * @return database resource
     */
     /**
     * @assert () != NULL
     */
    public function getDBConn()
    {
        return $this->db;
    }

    /*
     * delete an image from the DB
     *
     *  @param int imageID
     *  @return string filename of image just deleted
     */
    public function deleteSong($ID) {
       
        try {
            $sql = "DELETE from " . $this->DB__TABLEs . " WHERE ID = :id LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', intval ($ID));
            $stmt->execute();
            
        } catch (\PDOException $e) {
            /* Error */
            standard\Messages::addError("Prepared Statement Error: %s\n", $e->getMessage());
            //echo $e->getMessage();
        }
    }
    /*
     * get one (1) image from the DB based on imageID
     *
     *  @param int songID
     *  @return string filename
     */
    /**
     * 
     * @assert ('DJ_Godfather_-_Bring_It_Back_Clean_.wl.mp3', '/media/Home/MUSICA/whitelabel/Breakbeat/DJ Godfather') != NULL
     */
    public function getSong($songID) {

        try {
            $sql = "SELECT title, album, artist, filename, location, playtime from " . $this->DB__TABLEs . " WHERE ID = :id LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', intval($songID));
            $stmt->execute();
            $results = $stmt->fetch(\PDO::FETCH_ASSOC); // there SHOULD ONLY BE ONE row
        } catch (\PDOException $e) {
            /* Error */
            standard\Messages::addError("Prepared Statement Error: %s\n", $e->getMessage());
            //echo $e->getMessage();
        }  
        return (object) $results;
    }
    /*
     * get one (1) image from the DB based on filename
     *
     *  @param string filename
     *  @return int songID
     */  
    public function getSongID($filename, $location)
    {
        try {
            $sql = "SELECT ID from " . $this->DB__TABLEs . 
                " WHERE filename = :filename AND location = :location LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':location', $location);
            $stmt->execute();
            $results = $stmt->fetch(\PDO::FETCH_ASSOC); // there SHOULD ONLY BE ONE row
        } catch (\PDOException $e) {
            /* Error */
            standard\Messages::addError("Prepared Statement Error: %s\n", $e->getMessage());
            //echo $e->getMessage();
        }
        return $results['ID'];
    }
}
?>
