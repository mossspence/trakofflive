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
class saveMyList
{
    private $title;
    private $email;
    private $hashTag;
    private $comments;
    private $urlTitle;
    
    public function __construct($title, $email, $hashTag = '', $comments = NULL)
    {
        //sanitize title, email and comments
        $this->title = standard\sanitizeValues::sanitizeString($title);
        $this->email = standard\sanitizeValues::sanitizeString($email);
        $this->hashTag = standard\sanitizeValues::sanitizeString($hashTag);
        $this->comments = standard\sanitizeValues::sanitizeString($comments);
        $this->urlTitle = urlencode(strtolower(str_ireplace(' ', '-', 
                            trim($this->title))));
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
            
            $retVal = $savedList->saveList($this->title, $this->email, 
                $this->urlTitle, $this->hashTag,  $_SERVER['REMOTE_ADDR'], 
                    $playlist, $m3u8list, $this->comments);
        }
        return $retVal;
    }
    public function getfield($field)
    {
        return $this->$field;
    }
}
