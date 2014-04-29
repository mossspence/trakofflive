<?php
namespace moss\musicapp\exporter;
use moss\musicapp\query\connect;
/**
 * Description of savePlaylist
 *
 * @author mosspence
 * 
 * - save a playlist and meta data
 * - get all playlists and meta data
 * - get a playlist from ID
 * 
 */
//require_once 'SQLConnection.class.php';

class savePlaylist extends connect\SQLConnection
{
    private $playlist;
    private $title;
    private $comments;
    
    public function __construct()
    {
        $sqlconn = new connect\SQLConnection();
        $this->db = $sqlconn->getDBConn();
        
        try
        {
            $this->db->exec("CREATE TABLE IF NOT EXISTS playlists (
                  id int(10) unsigned NOT NULL AUTO_INCREMENT,
                  username varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  title varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  urlTitle varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  hashTag varchar(255) COLLATE utf8_unicode_ci,
                  dateListed TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  comments tinytext COLLATE utf8_unicode_ci,
                  songlist tinytext COLLATE utf8_unicode_ci,
                  m3u8list mediumtext COLLATE utf8_unicode_ci,
                  ip_address varchar(40) COLLATE utf8_unicode_ci NOT NULL,
                  mix_date datetime DEFAULT NULL,
                  mix_url varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (id)
                )");            
        } catch (\PDOException $ex) {
            var_dump($ex);
            echo $ex->getMessage();
        }
    }
    
    public function saveList($title, $username, $urlTitle, $hashTag, 
                               $ip_address, $songlist, $m3u8list, $comments = "")
    {
        $result = FALSE;
        try {
            $insert = "INSERT INTO playlists (title, comments, username, "
                    . "urlTitle, hashTag, ip_address, songlist, m3u8list)"
                    . " VALUES (:title, :comments, :username, :urlTitle, "
                    . ":hashTag, :ip_address, :songlist, :m3u8list)";
            
            $stmt = $this->db->prepare($insert);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':comments', $comments);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':urlTitle', $urlTitle);
            $stmt->bindParam(':hashTag', $hashTag);
            $stmt->bindParam(':songlist', implode(', ', $songlist));
            $stmt->bindParam(':m3u8list', $m3u8list);

            $result = $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    public function getPlaylists()
    {
        try{
        $sql = "select id, username, title, urlTitle, hashTag, "
                . "dateListed, comments, mix_date, mix_url from playlists";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        }catch (\PDOException $e){
            echo $e->getMessage();
        }
        return $results;
    }
    public function getPlaylist($id)
    {
        try{
            $sql = "select title, hashTag, dateListed, comments, "
                    . "mix_date, mix_url, m3u8list from playlists where id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
        
            $stmt->execute();
            $results = $stmt->fetch(\PDO::FETCH_ASSOC);
        }catch (\PDOException $e){
            echo $e->getMessage();
        }
        return $results;
    }
    public function getPlaylistUrl($urlTitle)
    {
        try{
            $sql = "select title, hashTag, dateListed, comments, mix_date, "
                    . "mix_url, songlist from playlists where urlTitle = :urlTitle";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':urlTitle', $urlTitle);
        
            $stmt->execute();
            $results = $stmt->fetch(\PDO::FETCH_ASSOC);
        }catch (\PDOException $e){
            echo $e->getMessage();
        }
        return $results;
    }    
}
