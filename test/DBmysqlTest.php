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
   
    /**
    * @depends testReconnect
    */
	public function testDBCreate(){
		//cleanup
		$r=self::$db->db_query(array(
			array(
				'type'=>DB_DROP_DATABASE,
				'db'=>'unit_test'
			),
			array(
				'type'=>DB_DROP_DATABASE,
				'db'=>'unit_test1'
			)
		));
		
		//test DB create
		$r = self::$db->db_query(array(
			array(
				'type'=>DB_CREATE_DATABASE,
				'db'=>'unit_test'
			),
			array(
				'type'=>DB_CREATE_DATABASE,
				'db'=>'unit_test1'
			),
			array(
				'type'=>DB_CREATE_DATABASE,
				'db'=>'unit_test'
			)
		));
		$this->assertTrue($r[0]['done']);
		$this->assertTrue($r[1]['done']);
		$this->assertFalse($r[2]['done']);
		$this->assertStringStartsWith('#1007:',$r[2]['error']);
		$this->assertTrue(self::$db->db_switch('unit_test'));
		$this->assertTrue(self::$db->db_switch('unit_test1'));
	}
   
    /**
    * @depends testDBCreate
	* @expectedException PHPUnit_Framework_Error_Warning
    */
	public function testDropDB(){
		
		//delete db'
		$r = self::$db->db_query(array(
			array(
				'type'=>DB_DROP_DATABASE,
				'db'=>'unit_test'
			),
			array(
				'type'=>DB_DROP_DATABASE,
				'db'=>'unit_test1'
			),
			array(
				'type'=>DB_DROP_DATABASE,
				'db'=>'unit_test'
			)
		));
		$this->assertTrue($r[0]['done']);
		$this->assertTrue($r[1]['done']);
		$this->assertFalse($r[2]['done']);
		$this->assertStringStartsWith('#1008:',$r[2]['error']);
		$this->assertFalse(self::$db->db_switch('unit_test'));
		$this->assertFalse(self::$db->db_switch('unit_test1'));
    }
   
    /**
    * @depends testDBCreate
    */
	public function testSTDQuery(){
		
	}
}