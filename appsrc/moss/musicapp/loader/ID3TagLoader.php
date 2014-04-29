<?php
namespace moss\musicapp\loader;
//require_once 'defaults.php';
//require_once 'musica.class.php';
//require_once 'sqliteLog.php';
/**
 * Description of ID3TagLoader
 *
 * @author oneilstuart
 */
class ID3TagLoader {

    var $filename;
    static $filelength = 0;
    var $name;
    var $docRoot;
    var $location;
    var $ThisFileInfo; // contains id3 analysis, don't forget to load it, bitch!!
    var $myBPMs;
    var $myKeys;
    var $bitrate_quality_colour;
    var $colors;
    var $coverImage;
    var $waveform;
    private $search_fields;
    private $search_tags;
    var $BPM_strings;
    var $KEY_strings;

    function __construct()
    {
        // Why can't I use $_SERVER['DOCUMENT_ROOT'] (5.3.10-1ubuntu3.9)
        // I'm pretty sure because I'm calling this from the command line
        $this->docRoot = "/home/www"; 
        
        $this->colors = array('springgreen', 'yellow');

        $this->search_fields = array('TXXX', 'TIT1', 'TKEY', 'TBPM');
        $this->search_tags = array('framenameshort', 'description');
        $this->BPM_strings = array('bpm', 'fBPM', 'bpm_start');
        $this->KEY_strings = array('key_start', 'key_end', 'initial_key', 'content_group_description');

        $this->numIterations = 1;
        $this->myBPMs = array();
        $this->myKeys = array();
    }

    function getRelevantInfo($filename)
    {
        // Analyze file and store returned data in $ThisFileInfo
        $this->filename = $filename;
        $relevantINFO = pathinfo($filename);
        $this->name = $relevantINFO['basename']; // this is unnecessary
        $this->location = $relevantINFO['dirname']; // this is unnecessary 
        $length = mb_strlen($filename);
        ID3TagLoader::$filelength = ($length > ID3TagLoader::$filelength) ? $length : ID3TagLoader::$filelength;
    }

    function getKeysAndBPMs() {
        $search_fields = $this->search_fields;
        $search_tags = $this->search_tags;
        $BPM_strings = $this->BPM_strings;
        $KEY_strings = $this->KEY_strings;

        $this->myBPMs = $this->getBPMs($this->ThisFileInfo);
        $this->myKeys = $this->getKEYs($this->ThisFileInfo);

        /*
          //check the motherfuckin BPMs
          //txxx // description
          $this->id3v2TagSearch($search_fields[0], $search_tags[1], $BPM_strings, $this->myBPMs);

          //tbpm // framenameshort
          $this->id3v2TagSearch($search_fields[3], $search_tags[0], $BPM_strings, $this->myBPMs);

          //check the motherfuckin keys.
          //txxx  // description
          $this->id3v2TagSearch($search_fields[0], $search_tags[1], $KEY_strings, $this->myKeys);

          //tkey  // frame
          $this->id3v2TagSearch($search_fields[2], $search_tags[0], $KEY_strings, $this->myKeys);

          //tit1  // frame
          $this->id3v2TagSearch($search_fields[1], $search_tags[0], $KEY_strings, $this->myKeys);
         */
    }

    /* id3v2TagSearch()
     * generic algorithm for searching tags I want
     * loop through these search fields
     * 
     * 'TXXX', 'TIT1', 'TKEY', 'TBPM', 
     * 
     * using these search tags:
     * 'framenameshort', 'description'
     * 
     * with these search string arrays
     * 'key_start', 'key_end', 'initial_key', 'content_group_description'
     * 'bpm', 'fBPM', 'bpm_start'
     * 
     * @return array of string
     */
    function id3v2TagSearch($search_field, $search_tag, $search_string_array, &$result_array) {

        define('DEBUG', 0);

        //$ThisFileInfo = $this->ThisFileInfo;
        //$return_vals = array();

        if (isset($this->ThisFileInfo['id3v2'][$search_field]) && !empty($this->ThisFileInfo['id3v2'][$search_field])) {

            foreach ($this->ThisFileInfo['id3v2'][$search_field] as $element) {

                foreach ($search_string_array as $needle) {

                    if (strcasecmp($element[$search_tag], $needle) == 0) {
                        $encoding = (isset($element['encoding']) && !empty($element['encoding'])) ? $element['encoding'] : 'ISO-8859-1';
                        //$result_array[$needle] = htmlentities($element['data'], ENT_QUOTES|ENT_HTML5, $encoding);

                        $tempVAL = @htmlentities($element['data'], ENT_QUOTES | ENT_HTML5, $encoding);

                        $result_array[$needle] = $tempVAL;
                    }
                }
            }
        }
        //return $return_vals;
    }

    /* getID3Field
     * the content of these fields are in multiple places. 
     * the best location (that I care about) is:
     *  [tags_html][id3v2][$fieldname][0]
     * but it might also be in: 
     *  [tags_html][id3v1][$fieldname][0]
     * and if I use the helpful function CopyTagsToComments 
     * provided by this awesome ID3 lib. content will also be here:
     *  ['comments_html'][$fieldname][0]
     * 
     * useful for:
     * title
     * album
     * artist
     * genre
     * ?filename=%2Fhome%2Fwww%2Flib%2Fid3lib%2FStarted+From+The+Bottom.mp3
     *  @return string
     */
    function getID3Field($field_name) {
        $ThisFileInfo = $this->ThisFileInfo;

        //artist or bpm or genre or title or Album
        $field_content = "";

        if (isset($field_name) && !empty($field_name)) {
            if (isset($ThisFileInfo['tags_html']['id3v2'][$field_name][0]) && !empty($ThisFileInfo['tags_html']['id3v2'][$field_name][0])) {
                @$field_content = $ThisFileInfo['tags_html']['id3v2'][$field_name][0];
            } elseif (isset($ThisFileInfo['tags_html']['id3v1'][$field_name][0]) && !empty($ThisFileInfo['tags_html']['id3v1'][$field_name][0])) {
                @$field_content = $ThisFileInfo['tags_html']['id3v1'][$field_name][0];
            } elseif ((isset($ThisFileInfo['comments_html'][$field_name][0]) && !empty($ThisFileInfo['comments_html'][$field_name][0]))) {
                @$field_content = $ThisFileInfo['tags_html']['comments_html'][$field_name][0];
            }
        }
        //$this->ThisFileInfo = $ThisFileInfo;
        return $field_content;
    }

    /*
     * getBPMs()
     * BPM content is in multiple places.
     * 
     * BPM_Start
     * fBPM
     * BPM
     * 
     * I would like to get all of them
     * @return array of strings
     */
    function getBPMs($ThisFileInfo) {
        //$ThisFileInfo = $myMusicID3Info->ThisFileInfo;
        // these are the bpm tags I know (or actually care about)
        $BPMs = array();
        $BPM_strings = array('bpm', 'fBPM', 'bpm_start');

        // but i have to be careful since i can find 'bpm_accuracy' and overwrite a 'bpm'
        //['TXXX']
        if (isset($ThisFileInfo['id3v2']['TXXX']) && !empty($ThisFileInfo['id3v2']['TXXX'])) {
            foreach ($ThisFileInfo['id3v2']['TXXX'] as $element) {
                foreach ($BPM_strings as $needle) {
                    if (strcasecmp($element['description'], $needle) == 0) {
                        $encoding = (isset($element['encoding']) && !empty($element['encoding'])) ? $element['encoding'] : 'ISO-8859-1';
                        $BPMs[$needle] = @htmlentities($element['data'], ENT_QUOTES | ENT_HTML5, $encoding);

                        //$BPMs[$needle] = $element['data'];
                        //echo $element['description'] . " => " . $BPMs[$needle] . "<br />\n";
                    }
                }
            }
        }

        //['TBPM']
        if (isset($ThisFileInfo['id3v2']['TBPM']) && !empty($ThisFileInfo['id3v2']['TBPM'])) {
            foreach ($ThisFileInfo['id3v2']['TBPM'] as $element) {
                foreach ($BPM_strings as $needle) {
                    if (strcasecmp($element['framenameshort'], $needle) == 0) {
                        $encoding = (isset($element['encoding']) && !empty($element['encoding'])) ? $element['encoding'] : 'ISO-8859-1';
                        $BPMs[$needle] = @htmlentities($element['data'], ENT_QUOTES | ENT_HTML5, $encoding);

                        //$BPMs[$needle] = $element['data'];
                        //echo $element['framenameshort'] . " => " . $BPMs[$needle] . "<br />\n";
                    }
                }
            }
        }

        return $BPMs;
    }

    /*
     * getKEYs()
     * KEY content is in multiple places.
     * 
     * initial_key
     * key_start
     * key_end
     * grouping
     * 
     * I would like to get all of them
     * @return array of strings
     */
    function getKEYs($ThisFileInfo) {

        //$ThisFileInfo = $myMusicID3Info->ThisFileInfo;
        // these are the key tags I know (or actually care about)
        $KEYs = array();
        $KEY_strings = array('key_start', 'key_end', 'initial_key', 'content_group_description');

        //['TXXX']
        if (isset($ThisFileInfo['id3v2']['TXXX']) && !empty($ThisFileInfo['id3v2']['TXXX'])) {
            foreach ($ThisFileInfo['id3v2']['TXXX'] as $element) {
                foreach ($KEY_strings as $needle) {
                    if (strcasecmp($element['description'], $needle) == 0) {
                        $encoding = (isset($element['encoding']) && !empty($element['encoding'])) ? $element['encoding'] : 'ISO-8859-1';
                        $KEYs[$needle] = @htmlentities($element['data'], ENT_QUOTES | ENT_HTML5, $encoding);

                        //$KEYs[$needle] = $element['data'];
                        //echo $element['description'] . " => " . $KEYs[$needle] . "<br />\n";
                    }
                }
            }
        }

        //[id3v2][TKEY]
        if (isset($ThisFileInfo['id3v2']['TKEY']) && !empty($ThisFileInfo['id3v2']['TKEY'])) {
            foreach ($ThisFileInfo['id3v2']['TKEY'] as $element) {
                foreach ($KEY_strings as $needle) {
                    if (strcasecmp($element['framenameshort'], $needle) == 0) {
                        $encoding = (isset($element['encoding']) && !empty($element['encoding'])) ? $element['encoding'] : 'ISO-8859-1';
                        //$KEYs[$needle] = @htmlentities($element['data'], ENT_QUOTES|ENT_HTML5, $encoding);
                        $KEYs[$needle] = @htmlentities($element['data'], ENT_QUOTES | ENT_HTML5, $encoding);
                        //$KEYs[$needle] = $element['data'];
                        //echo $element['framenameshort'] . " => " . $KEYs[$needle] . "<br />\n";
                    }
                }
            }
        }

        //[TIT1] 
        if (isset($ThisFileInfo['id3v2']['TIT1']) && !empty($ThisFileInfo['id3v2']['TIT1'])) {
            foreach ($ThisFileInfo['id3v2']['TIT1'] as $element) {
                foreach ($KEY_strings as $needle) {
                    if (strcasecmp($element['framenameshort'], $needle) == 0) {
                        $encoding = (isset($element['encoding']) && !empty($element['encoding'])) ? $element['encoding'] : 'ISO-8859-1';
                        //$KEYs[$needle] = @htmlentities($element['data'], ENT_QUOTES|ENT_HTML5, $encoding);
                        $KEYs[$needle] = @htmlentities($element['data'], ENT_QUOTES | ENT_HTML5, $encoding);
                        //$KEYs[$needle] = $element['data'];
                        //echo $element['framenameshort'] . " => " . $KEYs[$needle] . "<br />\n";
                    }
                }
            }
        }
        return $KEYs;
    }

    /*
     * loopthroughPictures
     * get images info from ID3 tags
     *   I currently only get these
     *  - Cover (front)
     *  - waveform
     */
    function loopThroughPictures()
    {
        $imageArray = array();
        
        if(!isset($this->ThisFileInfo['id3v2']['APIC'])){return NULL;} // just to stop the warnings
        
        $maxSize = 512 * 1024;  // less than 512 kB
            
        foreach ($this->ThisFileInfo['id3v2']['APIC'] as $pictureInfo)
        {
            if (($maxSize > $pictureInfo['datalength']))
            {  
                // 3 == $pictureInfo['picturetypeid']
                $imageFilename = $this->writeID3Image($pictureInfo);
                if (NULL != $imageFilename)
                {
                    $imageArray[$pictureInfo['picturetypeid']] = $imageFilename;
                }
            }
        }
        return $imageArray;
    }
    /*
     * writeID3Image($picture)
     * 
     * @param pictureInfo from ID3 tag reader
     * @return string temp file name
     */
    function writeID3Image($picture)
    {
        $tmpfilename = NULL;
        $memoryHelper = new SQLLiteMemoryHelper();
        $settings = $memoryHelper->fetchSettings();
        //$thumbSize = \thumbSize;
        $thumbSize = $settings['thumbSize'];

        /* standard size for image thumbs is 40x40 (thumbSize from defaults.php) */
        //special case for waveforms (picturetypeid == 17) because I know about them
        $width = (17 == $picture['picturetypeid'])?($picture['image_width'] / 2):$thumbSize;
        $height = (17 == $picture['picturetypeid'])?($picture['image_height'] / 2):$thumbSize;
        
        if (function_exists('imagecreatefromstring'))
        {
            $image = imagecreatefromstring($picture['data']);
            if ($image !== false)
            {
                $thumb = imagecreatetruecolor($width, $height);
                if(FALSE !== imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $picture['image_width'], $picture['image_height']))
                {
                    $tmpfilename = tempnam(sys_get_temp_dir(), 'ID3');
                    // 0 - 9 doesn't seem to matter much at this size
                    if (FALSE !== imagepng($thumb, $tmpfilename, 9))
                    {
                        imagedestroy($image);
                        imagedestroy($thumb);
                    }
                }
            }
        }
        return $tmpfilename;
    }
    /*
     * moveID3ImageFile($id, $imageArray)
     * 
     * @param id of song file
     * @param array of images created
     */
    function setID3ImageFile($id)
    {
        // someone is going to have to make sure this folders exist and is writeable
        $memoryHelper = new SQLLiteMemoryHelper();
        $settings = $memoryHelper->fetchSettings();
        $coversFolderName = $settings['coverDir'];        
        //$coversFolderName = coversFolderName; // const from defaults.php
        
        $imageArray = $this->loopThroughPictures();
        if(NULL == $imageArray){return;} // just to stop the warnings
        
        foreach ($imageArray as $key=>$tmpfilename)
        {
            $dst_filename = $this->docRoot . DIRECTORY_SEPARATOR . $coversFolderName . DIRECTORY_SEPARATOR . $id . '_' . $key . '.png';
            try
            {
                if(FALSE == rename($tmpfilename, $dst_filename))
                {
                    //$memoryHelper->insertCompleted($id.$key.$id, $this->name, "Rename failed, trying copy image: $tmpfilename => $dst_filename");
                    if(TRUE == copy($tmpfilename, $dst_filename))
                    {
                        // bada bing "Wrote image: $tmpfilename => $dst_filename";
                    }
                }
                if(TRUE == unlink($tmpfilename))
                {  
                    // bada boop "Deleted image: $tmpfilename!";  /* outside for paranoid's sake */ 
                }
            }catch (ErrorException $moveImage)
             {
                echo 'Looky here, bwoy! Mi catch an exception: ',  $moveImage->getMessage(), "\n";
                $memoryHelper->insertCompleted($id.$key, $this->name, 'Exception: ' . $moveImage->getMessage());
             }
        }
    }
    function loadSongInfo()
    {
        // do some pre-processing
        
        $track_v1 = $this->getID3Field('track');
        $year = $this->getID3Field('year');
        $track_v2 = $this->getID3Field('track_number');

        $track = (isset($track_v2) && !empty($track_v2)) ? $track_v2 : ((isset($track_v1) && !empty($track_v1)) ? $track_v1 : "");

        // $year = (isset($year) && !empty($year))?substr($year, -4):"";  //01-01-2008
        $year = (isset($year) && !empty($year)) ? substr($year, 0, 4) : "";   // 2008-01-01
        // or should I search for four consecutive digits?
        //$picture = $this->getID3Picture();
        //$id = 1;
        //$this->setID3ImageFile($id, $this->loopThroughPictures());
        
        //$retVal = $this->writeID3Picture($id, $picture);

        $this->getKeysAndBPMs();
        
        // this constructs an ID that is ignored on entrance to DB
        $songInfo = new mp3SongFileInfo($this->numIterations);

        $songInfo->__set('title', $this->getID3Field('title'));
        @$songInfo->__set('year', intval($year));
        $songInfo->__set('location', $this->location);
        $songInfo->__set('filename', $this->name);

        @$songInfo->__set('artist', $this->getID3Field('artist'));

        @$songInfo->__set('genre', $this->getID3Field('genre'));

        @$songInfo->__set('album', $this->getID3Field('album'));

        @$songInfo->__set('comment', $this->getID3Field('comment'));
        @$songInfo->__set('bitrate', $this->ThisFileInfo['bitrate']);

        @$songInfo->__set('length', $this->ThisFileInfo['playtime_string']);
        @$songInfo->__set('track_number', $track);
        if (isset($this->coverImage) && !empty($this->coverImage)) {
            @$songInfo->__set('coverImage', $this->coverImage);
        }
        if (isset($this->waveform) && !empty($this->waveform)) {
            @$songInfo->__set('waveform', $this->waveform);
        }

        foreach ($this->myBPMs as $key => $value) {
            @$songInfo->__set($key, $value);
        }
        foreach ($this->myKeys as $key => $value) {
            @$songInfo->__set($key, $value);
        }
        // MP3SongList::addMP3($songInfo);

        return $songInfo;
    }

    function outputHTMLOutput($processing_time)
    {
        $starttime1 = microtime(true);
        $endtime1 = microtime(true);
        $processing_time1 = $endtime1 - $starttime1;        
        
        $songInfo = $this->loadSongInfo();
        $song = (object) $songInfo->__toArray();
        
        $bitrate_quality_colour = ($this->ThisFileInfo['bitrate'] / 1000 > 256) ? 'springgreen' : 'yellow';


        echo "            <tr style=\"background-color:" . $bitrate_quality_colour . "\">\n";
        echo "                <td>" . $song->title . "</td>\n";
        echo "                <td>" . $song->artist . "</td>\n";
        echo "                <td>" . $this->ThisFileInfo['playtime_string'] . "</td>\n";
        echo "                <td>" . $this->myBPMs['bpm'] . "</td>\n";
        echo "                <td>" . $song->track_number . "</td>\n";
        echo "                <td>" . $this->myKeys['initial_key'] . "</td>\n";
        echo "                <td>" . $song->genre . "</td>\n";
        echo "                <td>" . $song->year . "</td>\n";
        echo "                <td>" . $song->album . "</td>\n";
        echo "                <td>" . $this->myBPMs['bpm_start'] . "</td>\n";
        if (isset($song->coverImage))
        {
            $htmlImage = '<img id="FileImage" width="40" height="40" src="' . $song->coverImage . '">';
            echo "                <td>" . $htmlImage . "</td>\n";
        } else {
            echo "                <td>" . $song->comment . "</td>\n";
        }

        echo "            </tr>\n";

        echo "            <tr>\n";
        echo "                <td colspan=\"11\">";
        echo "It took $processing_time to do regular BPM/Key task<br />\n";
        echo "It took $processing_time1 to do class BPM/Key task<br />\n";
        $diff = $processing_time1 - $processing_time;
        echo "Which is a difference of $diff.<br />\n";
        echo '<pre>' . htmlentities(print_r($this->myKeys, true)) . '</pre>';
        echo '<pre>' . htmlentities(print_r($this->myBPMs, true)) . '</pre>';
        echo "</td>\n";
        echo "            </tr>\n";
        /*
          echo "            <tr>\n";
          echo "                <td colspan=\"11\">";
          echo $songInfo;
          echo "</td>\n";
          echo "            </tr>\n";
         */
        MP3SongList::addMP3($songInfo);
    }
}

?>
