<?php
namespace moss\musicapp\loader;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ID3ImageProcess
 *
 * @author oneilstuart
 */
class ID3ImageProcess {

    //put your code here, asshole
    //var $maxSize = 512 * 1024;
    //var $pictureTypes = array(3 => 'cover', 17 => 'waveform');
    //var $imagesFolderName = 'imglib';
    //var $imagesFolder = $_SERVER['DOCUMENT_ROOT'] . PATH_SEPARATOR . $imagesFolderName;

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
        $maxSize = 512 * 1024;  // less than 512 kB
        
        foreach ($this->ThisFileInfo['id3v2']['APIC'] as $pictureInfo)
        {
            if (($maxSize > $pictureInfo['datalength']))    
            {  
                // 3 == $pictureInfo['picturetypeid']
                $imageFilename = writeID3Image($pictureInfo);
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
        /* standard size for image thumbs */
        $memoryHelper = new SQLLiteMemoryHelper();
        $settings = $memoryHelper->fetchSettings();
        //$thumbSize = \thumbSize;
        $thumbSize = $settings['thumbSize'];
        $tmpfilename = NULL;
        
        if (17 == $picture['picturetypeid']) //special case for waveforms because I know about them
        {
            $width = $picture['image_width'] / 2;
            $height = $picture['image_height'] / 2;
        }
        if (function_exists('imagecreatefromstring'))
        {
            $image = imagecreatefromstring($picture['data']);
            if ($image !== false)
            {
                $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
                if(FALSE !== imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbSize, $thumbSize, $picture['image_width'], $picture['image_height']))
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
    function moveID3ImageFile($id, $imageArray)
    {
        // someone is going to have to make sure these folders exist and are writeable
        $memoryHelper = new SQLLiteMemoryHelper();
        $settings = $memoryHelper->fetchSettings();
        $coversFolderName = $settings['coverDir'];
        
        foreach ($imageArray as $key=>$tmpfilename)
        {
            $folder = $coversFolderName;
            $dst_filename = $_SERVER['DOCUMENT_ROOT'] . PATH_SEPARATOR . $folder . PATH_SEPARATOR . $id . '.png';
            try
            {
                if(FALSE !== rename($tmpfilename, $dst_filename))
                {
                    copy($tmpfilename, $dst_filename);
                    unlink($tmpfilename);
                }
            }catch (ErrorException $moveImage)
             {
                echo 'Looky here, bwoy! Mi catch an exception: ',  $moveImage->getMessage(), "\n";
             }
        }
    }
}

?>
