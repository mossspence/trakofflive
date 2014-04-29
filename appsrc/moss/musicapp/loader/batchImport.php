<?php

namespace moss\musicapp\loader;
use moss\standard\sanitizeValues as sany;
use moss\musicapp\MP3SongList;
use moss\musicapp\logger\SQLLiteMemoryHelper;
/**
 * import songs into the database
 * JSON file must follow the correct pattern as outlined in :
 * https://gist.github.com/mossspence/11055073
 *
 * @author mosspence
 */
class batchImport {
    
    private $loadFacade;
    private $logger;
    private $linuxFolderPrefix = "/media/Home";
    
    public function __construct() {
        $this->loadFacade = new LoadMusica();
        $this->logger = new SQLLiteMemoryHelper();
    }
    
    public function import($json)
    {
        if(!is_readable($json) || empty($json)){return NULL;}
        
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', file_get_contents($json));
        
        //$contents = json_encode(utf8_encode(file_get_contents($json)));
        $results = json_decode($text, TRUE);
        if(!$results) { return NULL; }

        $count = 104; // Using it as tempID, will be not be used later
        
        foreach($results['songs'] as $songData)
        {
            // Don't forget to sanitize EACH and EVERY value
            
            //construct id does not matter and will not be used on import
            $song = new \moss\musicapp\mp3SongFileInfo($count++);
            
            $song->__set('filename', sany::sanitizeString($songData['filename']));
            
            $song->__set('location', 
                    self::convertLocation(
                            sany::sanitizeString($songData['location']),
                    $this->linuxFolderPrefix));

            $bitRate = (1000 > $songData['bitrate'])?($songData['bitrate']*1000):$songData['bitrate'];
            $song->__set('bitrate', sany::sanitizeINT($bitRate, 0, 600000));
            
            $seconds = sany::sanitizeINT($songData['length_seconds']);
            $properTime = intval($seconds/60) . ':' . str_pad(intval($seconds%60), 2, "0", STR_PAD_LEFT);
            $song->__set('length', $properTime);

            $song->__set('title', sany::sanitizeString($songData['title']));
            $song->__set('artist', sany::sanitizeString($songData['artist']));
            $song->__set('album', sany::sanitizeString($songData['album']));
            $song->__set('genre', sany::sanitizeString($songData['genre']));
            $song->__set('comment', sany::sanitizeString($songData['comment']));
            $song->__set('track_number', sany::sanitizeINT($songData['track']));
            $song->__set('year', sany::sanitizeINT($songData['year']));

            $song->__set('bpm', sany::sanitizeString($songData['bpm']));
            $song->__set('fBPM', sany::sanitizeString($songData['fBPM']));
            $song->__set('bpm_start', sany::sanitizeString($songData['bpm_start']));

            $song->__set('initial_key', sany::sanitizeString($songData['initial_key']));
            $song->__set('key_start', sany::sanitizeString($songData['key_start']));
            $song->__set('key_end', sany::sanitizeString($songData['key_end']));
            $song->__set('content_group_description', sany::sanitizeString($songData['content_group_description']));

            // do some logging stuff
            $trannyType = ($this->loadFacade->getSongID($song->__get('filename'), 
                                $song->__get('location')))
                                ? 'updated'
                                : 'inserted';
                    
            $id = $this->loadFacade->insertSongBasics($song);
            MP3SongList::addMP3($song); //echo $song;
            //$id = $count; 
            $this->logger->insertCompleted($id, $song->__get('filename'), 
                    $trannyType . ' id -> ' . $id);
        }
        return MP3SongList::getSongList();
    }
    function convertLocation($WINlocation, $linuxFolderPrefix = "")
    {
        $newLinuxLocation = NULL;

        if(isset($WINlocation) && !empty($WINlocation))
        {
            $WINlocation = ('\\' == (substr(trim($WINlocation), -1, 1)))?(substr(trim($WINlocation), 0, -1)):$WINlocation;

            $winLocPieces = array_reverse(explode('\\', $WINlocation));

            $linuxFolderPrefix = ('/' == (substr(trim($linuxFolderPrefix), 0, 1)))?(substr(trim($linuxFolderPrefix), 1)):$linuxFolderPrefix;
            $linuxLocPieces = explode('/', $linuxFolderPrefix);

            if (':' == (substr(trim($WINlocation), 1, 1)))
            {
                array_pop($winLocPieces); // remove the Windows drive letter';

            }elseif('\\' == (substr(trim($WINlocation), 0, 1)))
             {
                // remove the server name even though I probably shouldn't
                array_pop($winLocPieces);array_pop($winLocPieces);
             }

            while($linuxLocPieces)
            {
                $winLocPieces[] = array_pop($linuxLocPieces);
            }
            $newLinuxLocation = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_reverse($winLocPieces));
        }
        return $newLinuxLocation;
    }
}
