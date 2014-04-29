<?php
namespace moss\musicapp;
/**
 * Description of songSearch
 *
 * @author mosspence
 * - accepts search paramaters and searches
 */
//require_once 'retrieveMusica.class.php';
//require_once 'sanitizeValues.class.php';
use \moss\musicapp\finder;
use moss\standard;

class songSearch
{
    private $searchString;
    private $bpmString;
    private $keySearch;
    private $page;
    private $offset;
    private $keyMovement;
    private $bpmMovement;
    public $numResults = 0;
    public $songsArray = array();
    public $limit = 0;
    private $disallowed = array("the", "and");    
    
    public function __construct($searchString = '', $bpmString = '', 
            $keySearch = '', $page = 1, $keyMovement = '', $bpmMovement = '')
    {
        $this->searchString = \moss\standard\sanitizeValues::sanitizeString($searchString);
        $this->bpmString = \moss\standard\sanitizeValues::sanitizeINT($bpmString);
        $this->keySearch = \moss\standard\sanitizeValues::sanitizeString($keySearch);
        $this->page = \moss\standard\sanitizeValues::sanitizeINT($page);
        
        $this->keyMovement = \moss\standard\sanitizeValues::sanitizeString($keyMovement);
        $this->bpmMovement = \moss\standard\sanitizeValues::sanitizeString($bpmMovement);

        $this->searchString = trim (preg_replace('/\b('.implode('|',$this->disallowed).')\b/','',$this->searchString));
    }
    
    public function search()
    {
        $myMusic = new \moss\musicapp\query\retrieveMusica();

        $this->limit = $myMusic->getLimit();
        $this->offset = ($this->page > 1)?($this->page*$this->limit)-$this->limit:0;
        if($this->searchString)
        {
            $retval = $myMusic->searchSongsBasic($this->searchString, $this->offset);
        }
        elseif($this->keySearch && $this->bpmString)
        {
            $kTools = new finder\keyTools();
            $beatTools = new finder\bpmTools();
            $bpmRange = finder\bpmTools::$standardRange;
            $givenKey = $this->keySearch;

            switch ($this->keyMovement)
            {
                case 'FOUR':
                    $givenKey = $kTools->perfectFourth($this->keySearch);
                    break;
                case 'FIVE':
                    $givenKey = $kTools->perfectFifth($this->keySearch);
                    break;
                case 'THIRD':
                    $givenKey = $kTools->minorThird($this->keySearch);
                    break;
                case 'WHOLE':
                    $givenKey = $kTools->wholeStep($this->keySearch);
                    break;
                case 'HALF':
                    $givenKey = $kTools->halfStep($this->keySearch);
                    break;
                case 'DOMINANT':
                    $givenKey = $kTools->dominantRelative($this->keySearch);
                    break;
                case 'MINOR':
                    $givenKey = $kTools->minorToMajor($this->keySearch);
                    break;
                case 'GOLDEN':
                    $givenKey = $kTools->goldenRatioHarmony($this->keySearch);
                    break;
                case 'RELATIVE':
                    $givenKey = $kTools->relativeMinorToMajor($this->keySearch);
                    break;
                default :
                    $givenKey = $this->keySearch;
            }

            switch ($this->bpmMovement)
            {
                case 'DOUBLE':
                    $bpmRange = $beatTools->getLocalBPMs(
                                    $beatTools->getDoubleBPM($this->bpmString));
                    break;
                case 'HALF':
                    $bpmRange = $beatTools->getLocalBPMs(
                                    $beatTools->getDoubleBPM($this->bpmString));
                    break;
                default :
                    $bpmRange = $beatTools->getLocalBPMs($this->bpmString);
            }
            $retval = $myMusic->searchSongsBPMKeyMatch($givenKey, $bpmRange, $this->offset);
            $this->bpmString = implode(' to ', $bpmRange);
        }        
        if ($retval)
        {
            $myMusicList = new \moss\musicapp\MP3SongList();
            $this->numResults = intval($retval);
            $this->songsArray = $myMusicList->getSongList();
            return $this->songsArray;	// not necessary if I want to __get info
        }
    }
    public function __get($field)
    {
        return $this->$field;
    }
}