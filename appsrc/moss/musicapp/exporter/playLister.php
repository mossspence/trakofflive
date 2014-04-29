<?php

namespace moss\musicapp\exporter;
use moss\standard;
use moss\musicapp\query;
use moss\musicapp;

/**
 * Description of playLister
 *
 * @author mosspence
 */
class playLister
{    
    private $playlist;
    private $linuxFolderPrefix;
    private $windowsDrive;
    private $signedIN;
    private $userIsWindowsUser;
    private $windowsDriveLetter;
    private $m3u8list;
    private $songsArray;
       
    public function __construct($playlist, $signedIN = FALSE, 
            $isWindowsUser = FALSE, $driveLetter = 'E')
    {
        $this->linuxFolderPrefix = "/media/Home";
        $this->windowsDrive = standard\sanitizeValues::sanitizeString($driveLetter);
        $this->playlist = array();
        $this->signedIN = $signedIN;
        $this->userIsWindowsUser = $isWindowsUser;
        $this->windowsDriveLetter = ($this->userIsWindowsUser)
                                            ? $this->windowsDrive : '';
        if(!empty($playlist))
        {
            foreach ($playlist as $item)
            {
                $this->playlist[] = standard\sanitizeValues::sanitizeINT($item);
            }   
        }
        $this->songsArray = self::convertPlaylistToSongList();
    }
    public function setWindowsUserStatus($status = FALSE)
    {
        $this->userIsWindowsUser = $status;
    }
    public function setSignedInStatus($status = FALSE)
    {
        $this->signedIN = $status;
    }    
    /*
     * returns a M3U8 string based on playlist (in constructor)
     */
     /**
      * assertNotEmpty
     */ 
    public function getM3U8List()
    {
        $expo = new ExportM3U8();

        $this->m3u8list = $expo->makePlaylist($this->songsArray, 
                $this->windowsDriveLetter, $this->linuxFolderPrefix, 
                $this->signedIN);

        return $this->m3u8list;
    }
     /**
      * assertNotEmpty
     */
    public function getPlayList()
    {
        return $this->playlist;
    }
    public function getSongsArray()
    {
        return $this->songsArray;
    }
    /**
      * assertNotEmpty
     */
    public function convertPlaylistToSongList()
    {
        $songsArray = NULL;
        $getMusic = new query\retrieveMusica();
        $myMusicList = new musicapp\MP3SongList();
        
        //
        $list = $this->playlist;
        
        foreach ($list as $item)
        {
            $songObject = $getMusic->getSong($item);
            $song = new musicapp\mp3SongFileInfo($item);
            $song->__set('title', $songObject->title);
            $song->__set('artist', $songObject->artist);
            $song->__set('album', $songObject->album);
            $song->__set('filename', $songObject->filename);
            $song->__set('length', $songObject->playtime);
            $song->__set('location', $songObject->location);           
            musicapp\MP3SongList::addMP3($song);
        }
        // */
        
        /*
        // below doesn't appear to work on goDaddy's version of PHP
        //
        $retVal = $getMusic->selectPlaylist($this->playlist);
        if ($retVal)
        {
            $myMusicList = new musicapp\MP3SongList();
            //$numResults = intval($myMusicList->getCount());
            $this->songsArray = $myMusicList->getSongList();
        }
        // */
        
        
        $this->songsArray = $myMusicList->getSongList();
        return $this->songsArray;
    }
}