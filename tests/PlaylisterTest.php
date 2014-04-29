<?php

namespace moss\musicapp\exporter;
require '../appsrc/moss/musicapp/exporter/playLister.php';

/**
 * Description of newPHPClass
 *
 * @author oneilstuart
 */
class playListerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var playLister
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        
        $playlist = array(45,984, 34, 3753);
        
        $this->object = new playLister($playlist);

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
         $this->object = new NULL;
    }
    
    protected function getPlayListTest()
    {
        
        $this->assertNotEmpty($this->object->getPlayList());

    }
    protected function getM3U8ListTest()
    {
        $this->assertNotEmpty($this->object->getM3U8List());
    }
}
