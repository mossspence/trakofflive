<?php

namespace moss\musicapp;

/**
 * Description of MP3SongList
 *
 * @author oneilstuart
 */
class MP3SongList implements \IteratorAggregate
{
    private static $songList = array();
    private $count;
    //private $searchCount;
    
    public function __construct() 
    {
        // probably really unnecessary
        $this->count = count(self::$songList);
        //$this->searchCount = (isset($count) && !empty($count))?intVal($count):0;
    }

    public static function getCount()
    {
        return $this->count;
    }
    public static function getSongList()
    {
        $tempArray = array();
        foreach (self::$songList as $song)
        {
            $tempArray[] = $song->__toArray();
        }
        return $tempArray;
    }  
  public static function addMP3(mp3SongFileInfo $song)
  {
      self::$songList[] = $song;
  }

    public function getIterator()
    {
        return new \ArrayIterator(MP3SongList::$songList);
    }
  
    public function __toString() {
        $result = "{\"songs\":" . json_encode($this->getSongList()) . "}";
        return $result;
    }
}
