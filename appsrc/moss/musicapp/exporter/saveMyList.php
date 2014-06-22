<?php

namespace moss\musicapp\exporter;
use moss\standard;
use moss\musicapp\query;
use moss\musicapp;
use moss\musicapp\exporter;

/**
 * Description of saveMyList
 *
 * @author oneilstuart
 */
class saveMyList implements \SplSubject
{
    // these should not all be public, I should make a public paramaters var.
    public $title;
    public $email;
    public $hashTag;
    private $comments;
    public $urlTitle;
    public $song_requested_info;
    
    private $observers = array();
    
    public function __construct($title, $email, $hashTag = '', $comments = NULL)
    {
        //sanitize title, email and comments
        $this->title = standard\sanitizeValues::sanitizeString($title);
        $this->email = standard\sanitizeValues::sanitizeEmail($email);
        $this->hashTag = standard\sanitizeValues::sanitizeString($hashTag);
        $this->comments = standard\sanitizeValues::sanitizeString($comments);
        $this->urlTitle = urlencode(strtolower(str_ireplace(' ', '-', 
                            trim($this->title))));
    }
    public function attach(\SplObserver $observer)
    {
        $this->observers[] = $observer;
    }
    public function detach(\SplObserver $observer)
    {
        if (($idx = array_search($observer, $this->observers, true)) !== false)
        {
            unset($this->observers[$idx]);
        }
    }
    /**
      Implement SplSubject method
     */
    public function notify()
    {
        foreach ($this->observers as $value)
        {
            $value->update($this);
        }
    }
    /*
     * $m3u8list, $playlist paramaters are created by exporter\
     */
    public function saveMe($m3u8list, $playlist)
    {
        $retVal = FALSE;
        
        if(!empty($this->title) && !empty($this->email))
        {
            $savedList = new exporter\savePlaylist();
            //save it
            
            // $_SERVER['REMOTE_ADDR'] should be $request->getClientIp();
            
            $retVal = $savedList->saveList($this->title, $this->email, 
                $this->urlTitle, $this->hashTag, $_SERVER['REMOTE_ADDR'], 
                    $playlist, $m3u8list, $this->comments);
        }
        $this->song_requested_info = $m3u8list;
        return $retVal;
    }
    public function getfield($field)
    {
        return (property_exists($this, $field))?$this->$field:NULL;
    }
    
    public function __destruct()
    {
        foreach($this->observers as $observer)
        {
            $this->detach($observer);
        }
    }
}
