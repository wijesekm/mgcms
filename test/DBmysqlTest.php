<?php

define('INIT',true);
include('db/database.abstract.php');
include('db/mysql.class.php');

class DBmysqlTest extends PHPUnit_Framework_TestCase{
    
    protected static $db;
    
    public static function setUpBeforeClass(){
        self::$db = new mysql();
    }
    
    public static function tearDownAferClass(){
        self::$db->db_close();
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testBadHost(){
        $this->assertFalse(self::$db->db_connect('localhos:','unittest','phGhpffGsrK34YjD','test'));
    }
    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testBadCredentials(){
        $this->assertFalse(self::$db->db_connect('localhost:3306','unittest','phGhpffGsrK34Yj','test'));
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testBadDatabase(){
        $this->assertFalse(self::$db->db_connect('localhost:3306','unittest','phGhpffGsrK34YjD','thisisabaddatabase'));
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testConnect(){
        $this->assertTrue(self::$db->db_connect('localhost:3306','unittest','phGhpffGsrK34YjD','test'));
        $this->assertTrue(self::$db->db_connect('localhost:3306','unittest','phGhpffGsrK34YjD','test'));
    }
    
    /**
    * @depends testConnect
    */
    public function testReconnect(){
        $this->assertTrue(self::$db->db_close());
        $this->assertTrue(self::$db->db_close());
        $this->assertTrue(self::$db->db_connect('localhost:3306','unittest','phGhpffGsrK34YjD','test'));
    }
    
    
}