<?php
namespace moss\musicapp;
/**
 * Description of musica
 *
 * @author mosspence
 */

class mp3SongFileInfo
{
    private $filename;
    private $location;
    private $id;
    
    private $bitrate;
    private $length;
    
    private $title;
    private $artist;
    private $album;    
    private $genre;
    private $comment;
    private $track_number;
    private $year;
    
    private $waveform;
    private $coverImage;
    
    private $bpm;
    private $bpm_start;
    private $fBPM;
    
    private $key_start;
    private $key_end;
    private $initial_key;
    private $content_group_description;
    
    
    private $coverDir;

    public function __construct($id)
    {
        if(isset($id) && !empty($id))
        {    $this->id = $id;   }
        else
        {    $this->__destruct();   }
    }
    
    public function __set($name, $value)
    {
        if(isset($name) && !empty($name))
        {
            $this->$name = (isset($value) && !empty($value))?$value:"";
        }
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    function __destruct()
    {
        //$this = NULL;
    }
    protected function __toJSON()
    {
        $string = json_encode($this->__toArray());
        return $string;
    }

    protected function __toYAML()
    {
        
    }
    public function __toString() {
        $string = $this->__toJSON();
        return $string;
    }
    
    public function checkForCoverArt()
    {
        $coverDir = $this->coverDir;
        $cover = NULL;

        /* check local files for art */
        // to be deprecated when I move solely to the cloud (the cloud, the cloud)
        // 
        // test for cover (3) and waveform (17)
        //test for covers
        $coverTypes = array(17, 3, 0, 20);

        foreach($coverTypes as $num)
        {
            $filename =  DIRECTORY_SEPARATOR . $coverDir . DIRECTORY_SEPARATOR
                            . $this->id . '_' . $num . '.png';
            $fullFilename = $_SERVER['DOCUMENT_ROOT'] . $filename;
            if(file_exists($fullFilename))
            {
                if(17 == $num)  //test for waveforms (bright colored fish)
                {
                    $this->waveform = $filename;
                }else
                 {
                    $cover = $filename;
                    break; // emphasis on '3'
                 }
            }
        }
        
        /* sets cover art */
        
        $this->coverImage = (empty($cover))
                ? DIRECTORY_SEPARATOR . $coverDir
                        . DIRECTORY_SEPARATOR . 'default.png'
                : $cover;
    }
            
    protected function __toXML()
    {
        
    }
    
    // this function is like totally unecessary. I was thinking I was going to 
    // do more before outputting this information
    // do more:
    //  check userID (isAdmin? isSpecial?)
    public function __toArray()
    {
       // check for local image 
       $this->checkForCoverArt();

       $tempArray = array('ID' => $this->id, 
                            'filename' => $this->filename,
                            'location' => $this->location,
                            'title' => $this->title,
                            'artist' => $this->artist,
                            'album' => $this->album,
                            'genre' => $this->genre,           
                            'track_number' => $this->track_number,
                            'year' => $this->year,
                            'bpm' => $this->bpm, 
                            'fBPM' => $this->fBPM, 
                            'bpm_start' => $this->bpm_start, 
                            'initial_key' => $this->initial_key, 
                            'key_start' => $this->key_start, 
                            'key_end' => $this->key_end, 
                            'content_group_description' => $this->content_group_description, 
                            'playtime' => $this->length, 
                            'bitrate' => $this->bitrate,
                            'comment' => $this->comment,
                            'coverImage' => $this->coverImage,
                            'waveform' => $this->waveform);

       /*
        * this tempArray below includes an array for BPMs and KEYs
        * 
       
       $bpms = array('bpm' => $this->bpm, 
                    'fBPM' => $this->fBPM, 
                    'bpm_start' => $this->bpm_start);
       
       $keys = array('initial_key' => $this->initial_key, 
                    'key_start' => $this->key_start, 
                    'key_end' => $this->key_end, 
                    'content_group_description' => $this->content_group_description);

       $tempArray = array('ID' => $this->id, 
                            'filename' => $this->filename,
                            'title' => $this->title,
                            'location' => $this->location,
                            'artist' => $this->artist,
                            'album' => $this->album,
                            'genre' => $this->genre,
                            'track_number' => $this->track_number,
                            'year' => $this->year,
                            'bpm_info' => $bpms, 
                            'key_info' => $keys, 
                            'playtime' => $this->length, 
                            'bitrate' => $this->bitrate,
                            'comment' => $this->comment);
        * 
        */
       
        return $tempArray;
    }
}

?>
