<?php
namespace moss\musicapp\logger;
/*
 * SQLite stuff for the ID3 database
 * 
 */
// this is what used to be in defaults.php
/*
 * Contains DEFAULT Information for
 * for all php files/classes in the party
 */
//error_reporting(E_ALL);
ini_set("memory_limit", "50M");
const SQLite_DB_Name = 'ID3_DB_Table.sqlite.db';
/*       
const MAX_EXECUTION_TIME_PER_SONG = 30;     // seconds
const coversFolderName = 'imglib/covers';
const thumbSize = 40;       // pixels
const MAX_ACCEPTED_FILE_SIZE = 41943040; // 40M * 1024K * 1024b
//$baseDir = "/media/Home/MUSICA/";
*/

/* * ************************************
 * Create databases and                *
 * open connections                    *
 * *********************************** */

class SQLLiteMemoryHelper {

    private $baseDir  = "/dev/null";
    private $accepted = 41943040;
    private $maxtime  = 30;
    private $cover    = 'imglib/covers'; 
    private $thumb    = 40;
    
    
    private $file_db = NULL;
    private $tables_in_use = array("songFiles", "completedSongFiles", "currentSongFile", "settings");
    
    public function __construct() {
        try {
            // Create (connect to) SQLite database in file
            $file_db = new \PDO('sqlite:' . sys_get_temp_dir()  . DIRECTORY_SEPARATOR . SQLite_DB_Name);
            // Set errormode to exceptions
            $file_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create table songFiles
            //$file_db->exec("DROP TABLE songFiles");
            $file_db->exec("CREATE TABLE IF NOT EXISTS songFiles (
                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                filename TEXT NOT NULL)");
            $file_db->exec("CREATE TABLE IF NOT EXISTS completedSongFiles (
                                id INTEGER NOT NULL, 
                                filename TEXT NOT NULL,
                                message TEXT, 
                                timedate TEXT DEFAULT (DateTime('now')))");
            $file_db->exec("CREATE TABLE IF NOT EXISTS currentSongFile (
                                id INTEGER NOT NULL, 
                                filename TEXT NOT NULL)");
            $file_db->exec("CREATE TABLE IF NOT EXISTS settings (
                                baseDir TEXT NOT NULL, 
                                coverDir TEXT NOT NULL, 
                                thumbSize INTEGER NOT NULL, 
                                maxFileSize INTEGER NOT NULL,
                                maxSeconds INTEGER NOT NULL,
                                textBatchFile TEXT)");
            $this->file_db = $file_db;
        } catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }
            //usleep(500000); // sleep half a second to wait for database, since I suck.
            $settings = $this->fetchSettings();
            if(!isset($settings['baseDir'])){$this->setupSettings($this->baseDir);}
    }    
    /**
     * @assert (array(NULL)) 
     * 
     */
    function Load($file_array) {
        foreach ($file_array as $file) {
            $this->insertSong($file);
        }
    }
    private function setupSettings($baseDir,
            $cover = 'imglib/covers', $thumb = 40,
            $accepted = 41943040, $maxtime = 30, $textBatchFile = NULL)
    {
        try {
            $insert = "INSERT INTO settings (baseDir, coverDir, thumbSize, "
                    . "maxFileSize, maxSeconds, textBatchFile)"
                    . " VALUES (:baseDir, :coverDir, :thumbSize, :maxFileSize, "
                    . ":maxSeconds, :textBatchFile)";
            $stmt = $this->file_db->prepare($insert);
            $stmt->bindParam(':baseDir', $baseDir);
            //$cover = coversFolderName;
            $stmt->bindParam(':coverDir', $cover);
            //$thumb = thumbSize;
            $stmt->bindParam(':thumbSize', $thumb);
            //$accepted = MAX_ACCEPTED_FILE_SIZE;
            $stmt->bindParam(':maxFileSize', $accepted);
            //$maxtime = MAX_EXECUTION_TIME_PER_SONG;
            $stmt->bindParam(':maxSeconds', $maxtime);
            //$textBatchFile = full filename of csv file to process;
            $stmt->bindParam(':textBatchFile', $textBatchFile);
            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }  
    }
    public function newBaseDir($baseDir, $cover, $thumb, $accepted, $maxtime, $textBatchFile = NULL)
    {
        $retVal = FALSE;
        if(isset($baseDir) && !empty($baseDir))
        {
            $stmt = $this->file_db->prepare("delete from settings");
            $stmt->execute();
            $this->setupSettings($baseDir, $cover, $thumb, $accepted, $maxtime, $textBatchFile);
            $retVal = TRUE;
        }
        return $retVal;
    }
    public function fetchSettings()
    {
        try {
            $stmt = $this->file_db->prepare("SELECT * FROM settings");
            $stmt->execute();            
            $results = $stmt->fetch(\PDO::FETCH_ASSOC); // there CAN ONLY BE ONE row
        } catch (PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }
        return $results;
    }
    public function getErrorLog()
    {
        try {
            $stmt = $this->file_db->prepare("SELECT * FROM completedSongFiles where message !=''");
            $stmt->execute();
            //$stmt->bindColumn('id', $ID);
            //$stmt->bindColumn('filename', $filename);
            //$stmt->bindColumn('message', $message);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }
        return $results;        
    }
    public function insertCompleted($id, $filename, $message = '')
    {
        try {
            $insert = "INSERT INTO completedSongFiles (id, filename, message)"
                    . " VALUES (:id, :filename, :message)";
            $stmt = $this->file_db->prepare($insert);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':message', $message);
            $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }        
    }
    private function insertCurrent($id, $filename)
    {
        try {
            $insert = "INSERT INTO currentSongFile (id, filename) 
                VALUES (:id, :filename)";
            $stmt = $this->file_db->prepare($insert);

            // Bind parameters to statement variables
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':filename', $filename);
            $stmt->execute();
        } catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }        
    }
    public function putStatus($id, $previousID, $file, $folder, $startTime, $numFilesProcessed, $message)
    {
        $elapsedTime = time() - $startTime;
        $memory = memory_get_peak_usage();
        //$this->insertCompleted($id, $file);
        $this->deleteCompletedFromCurrent($previousID);
        $this->insertCurrent($id, $file);
        // when I'm ready ...
        //$this->deleteSong($id);
    }
    public function insertSong($filename) {
        try {
            $insert = "INSERT INTO songFiles (filename) 
                VALUES (:filename)";
            $stmt = $this->file_db->prepare($insert);

            // Bind parameters to statement variables
            $stmt->bindParam(':filename', $filename);
            $stmt->execute();
        } catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }
    }

    private function deleteCompletedFromCurrent($ID)
    {
        try {
            $delete = "delete from currentSongFile";
            $stmt = $this->file_db->prepare($delete);
            $stmt->execute();
        } catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }        
    }
    public function deleteSong($id) {
        try {
            $delete = "delete from songFiles where id = :id";
            $stmt = $this->file_db->prepare($delete);

            // Bind parameters to statement variables
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        } catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }
    }
    public function getCount($table = "songFiles")
    {
        if(!in_array($table, $this->tables_in_use)){return 0;}
        return intval(current($this->file_db->query("select count(*) from $table")->fetch()));
    }
    public function fetchAll($table = "songFiles")
    {
        if(!in_array($table, $this->tables_in_use)){return NULL;}
        $results = array();
        try {
            $stmt = $this->file_db->prepare("SELECT * FROM $table");
            $stmt->execute();
            $stmt->bindColumn('id', $ID);
            $stmt->bindColumn('filename', $filename);

            while ($stmt->fetch()) {
                $results[$ID] = $filename;
            }
        } catch (\PDOException $e) {
            // Print PDOException message
            echo $e->getMessage();
        }
        return $results;
    }

    public function startFresh() {
        $this->file_db->exec("DROP TABLE songFiles");
        $this->file_db->exec("DROP TABLE completedSongFiles");
        $this->file_db->exec("DROP TABLE currentSongFile");
        return TRUE;
    }
    
    private function __destructatsomePoint() {
        // export completedSongFiles
        $this->file_db->exec("DROP TABLE songFiles");
        $this->file_db->exec("DROP TABLE completedSongFiles");
        $this->file_db->exec("DROP TABLE currentSongFile");
        //@unlink(SQLite_DB_Name);
    }
}