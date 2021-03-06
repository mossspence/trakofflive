<?php

namespace moss\musicapp\query\connect;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-04-20 at 20:09:45.
 */
class SQLConnectionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var SQLConnection
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new SQLConnection;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }

    /**
     * Generated from @assert () != NULL.
     *
     * @covers moss\musicapp\query\connect\SQLConnection::getDBConn
     */
    public function testGetDBConn() {
        $this->assertNotEquals(
                NULL, $this->object->getDBConn()
        );
    }

    /**
     * Generated from @assert ('DJ_Godfather_-_Bring_It_Back_Clean_.wl.mp3', '/media/Home/MUSICA/whitelabel/Breakbeat/DJ Godfather') != NULL.
     *
     * @covers moss\musicapp\query\connect\SQLConnection::getSong
     */
    public function testGetSong() {
        $this->assertNotEquals(
                NULL, $this->object->getSong('DJ_Godfather_-_Bring_It_Back_Clean_.wl.mp3', '/media/Home/MUSICA/whitelabel/Breakbeat/DJ Godfather')
        );
    }

    /**
     * @covers moss\musicapp\query\connect\SQLConnection::deleteSong
     * @todo   Implement testDeleteSong().
     */
    public function testDeleteSong() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers moss\musicapp\query\connect\SQLConnection::getSongID
     * @todo   Implement testGetSongID().
     */
    public function testGetSongID() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}
