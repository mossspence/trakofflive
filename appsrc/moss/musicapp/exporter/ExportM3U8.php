<?php
namespace moss\musicapp\exporter;
/**
 * Description of ExportM3U8
 *
 * @author mosspence
 * 
 * make a playlist based on list of songIDs
 * 
 */
class ExportM3U8
{
    private $linuxFolderPrefix = "/media/Home";
    private $signedIN = FALSE;
    private $userIsWindowsUser = FALSE;
    private $windowDrive = 'E';
    private $playList;

    public function __construct() {
        $this->linuxFolderPrefix = "/media/Home";
        $this->signedIN = \FALSE;
        $this->userIsWindowsUser = \FALSE;
        $this->windowDrive = 'E';
        $this->playList = NULL;
    }
    public function savePlaylist()
    {
        //require_once 'saveUserMusica.class.php';
        //$saveMe = new saveUserMusica();
        //if($this->signedIN && $this->playList )
        //$saveMe->saveUserPlaylist($user, $this->playList);
    }
    /*
     * I can't save NULL or FALSE with this dumbass function
     */
    public function __set($name, $value) {
        if(isset($value) && !empty($value))
        {
            $this->$name = $value;
        }
    }
    /**
     * 
     * @param type $name
     * @return type
     */
    /**
     * 
     * @assert ('linuxFolderPrefix') == "/media/Home"; 
     */
    public function __get($name) {
        return $this->$name;
    }
    
 // output the M3U
/*
 * loop through the song array and output M3U8
 * only output complete song URI (or complete unix path) if user signed in
 *
 * #EXTM3U
 * 
 * #EXTINF:123, Sample artist - Sample title
 * C:\Documents and Settings\I\My Music\Sample.mp3
     * @param type $songsArray
     * @param type $windowsDrive
     * @param type $linuxFolderPrefix
     * @param type $signedIn
     * @return UTF-8 string
     */
    public function makePlaylist($songsArray, $windowsDrive = NULL, $linuxFolderPrefix = '', $signedIn = FALSE)
    {
        $folderSeperator = (isset($windowsDrive) && !empty($windowsDrive))
                            ? '\\' 
                            : DIRECTORY_SEPARATOR;

        if(count($songsArray) <= 0){ return NULL;}

        $playlist = '#EXTM3U' . PHP_EOL . PHP_EOL;
        foreach ($songsArray as $song)
        {
            $playlist .= '#EXTINF:' . self::convertMinutesToSeconds($song['playtime']) .', ';
            $playlist .= $song['artist'] . ' - ' . $song['title'] . PHP_EOL;

            $location = ((isset($windowsDrive) && !empty($windowsDrive)) || $signedIn)
                    ? $windowsDrive . ':' . strtr(substr($song['location'], strlen($linuxFolderPrefix)), '/', '\\')
                    : $song['location'];

            $playlist .= ($signedIn) 
                    ? 
                $location . $folderSeperator . $song['filename']
                    :
                $song['filename'];
            $playlist .= PHP_EOL . PHP_EOL;
        }

        return utf8_encode($playlist);
    }

/*
 * does not do hours
 */
    private function convertMinutesToSeconds($time)
    {
        list($minutes,$seconds) = explode(':', $time);
        return intval($seconds) + (intval($minutes) * 60);
    }   
}

