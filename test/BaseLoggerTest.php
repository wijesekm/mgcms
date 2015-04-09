<?php

define('INIT',true);
include('base/logger.class.php');

$_SERVER['REQUEST_URI']='asdf';
$GLOBALS['MG']['CFG']['PATH']['LOG']='W:/www//backend/test/tmp';
$GLOBALS['MG']['CFG']['LOG']['SIZE']='10000';
$GLOBALS['MG']['CFG']['LOG']['ERRORS'] = array (
    E_ERROR              => array('file'=>'Error','fatal'=>true,'id'=>'E_ERROR'),
    E_WARNING            => array('file'=>'Warning','fatal'=>false,'id'=>'E_WARNING'),
    E_PARSE              => array('file'=>'Error','fatal'=>false,'id'=>'E_PARSE'),
    E_NOTICE             => array('file'=>'Notice','fatal'=>false,'id'=>'E_NOTICE'),
    E_CORE_ERROR         => array('file'=>'Error','fatal'=>true,'id'=>'E_CORE_ERROR'),
    E_CORE_WARNING       => array('file'=>'Warning','fatal'=>false,'id'=>'E_CORE_WARNING'),
    E_COMPILE_ERROR      => array('file'=>'Error','fatal'=>true,'id'=>'E_COMPILE_ERROR'),
    E_COMPILE_WARNING    => array('file'=>'Warning','fatal'=>false,'id'=>'E_COMPILE_WARNING'),
    E_USER_ERROR         => array('file'=>'Error','fatal'=>true,'id'=>'E_USER_ERROR'),
    E_USER_WARNING       => array('file'=>'Warning','fatal'=>false,'id'=>'E_USER_WARNING'),
    E_USER_NOTICE        => array('file'=>'Notice','fatal'=>false,'id'=>'E_USER_NOTICE'),
    E_STRICT             => array('file'=>'Notice','fatal'=>false,'id'=>'E_STRICT'),
    E_RECOVERABLE_ERROR  => array('file'=>'Error','fatal'=>false,'id'=>'E_RECOVERABLE_ERROR'),
    E_DEPRECATED         => array('file'=>'Deprecated','fatal'=>false,'id'=>'E_DEPRECATED'),
	E_USER_DEPRECATED 	 => array('file'=>'Deprecated','fatal'=>false,'id'=>'E_USER_DEPRECATED'),
);
$GLOBALS['MG']['CFG']['LOG']['LOGS']=array(
	'L_DATABASE'=>array('file'=>'Log DB','id'=>'L_DATABASE'),
	'L_AUDIT'=>array('file'=>'Log Audit','id'=>'L_DATABASE'),
	'L_EMAIL'=>array('file'=>'Log Email','id'=>'L_EMAIL')
);

class BaseLoggerTest extends PHPUnit_Framework_TestCase{
    
    protected static $res;
    
    public static function setUpBeforeClass(){
        self::$res = new logger('en-us');
    }
    
    public function testFatalError(){
        $this->assertFalse(self::$res->l_hasFatalErrors());
        trigger_error('testing1',E_USER_ERROR);
        $this->assertTrue(self::$res->l_hasFatalErrors());
    }
   
    /**
    * @depends testFatalError
    */
    public function testLogging(){
        trigger_error('testing2',E_USER_WARNING);
        trigger_error('testing3',E_USER_NOTICE);
        self::$res->l_message('L_DATABASE','testing4','myfile');
        self::$res->l_message('L_AUDIT','testing5','myfile');
        self::$res->l_message('L_EMAIL','testing6','myfile');
        $logs = self::$res->l_getAll();
        $this->assertEquals($logs['Error'][0][2],'testing1');
        $this->assertEquals($logs['Warning'][0][2],'testing2');
        $this->assertEquals($logs['Notice'][0][2],'testing3');
        $this->assertEquals($logs['Log DB'][0][2],'testing4');
        $this->assertEquals($logs['Log Audit'][0][2],'testing5');
        $this->assertEquals($logs['Log Email'][0][2],'testing6');
        $this->assertCount(1,$logs['Error']);
        $this->assertCount(1,$logs['Warning']);
        $this->assertCount(1,$logs['Notice']);
        $this->assertCount(1,$logs['Log DB']);
        $this->assertCount(1,$logs['Log Audit']);
        $this->assertCount(1,$logs['Log Email']);
    }
  
    /**
    * @depends testLogging
    */
    public function testBadLogging(){
        
        self::$res->l_message('LL','testing4','myfile');
        trigger_error('testing3',0);
        $logs = self::$res->l_getAll();
        $this->assertEquals($logs['Warning'][1][2],'(error_logger): No logfile found for logtype: LL');
        $this->assertEquals($logs['Warning'][2][2],'Invalid error type specified');
        $this->assertCount(3,$logs['Warning']);
    }
  
    /**
    * @depends testBadLogging
    */
    public function testWriteFile(){
        $dirs = scandir($GLOBALS['MG']['CFG']['PATH']['LOG']);
        foreach($dirs as $dir){
            if($dir != '..' && $dir != '.'){
                unlink($GLOBALS['MG']['CFG']['PATH']['LOG'].'/'.$dir);
            }
        }
        self::$res->l_writeToFile();
        $dirs = scandir($GLOBALS['MG']['CFG']['PATH']['LOG']);
        $this->assertContains('Error.log',$dirs);
        $this->assertContains('Warning.log',$dirs);
        $this->assertContains('Notice.log',$dirs);
        $this->assertContains('Log Audit.log',$dirs);
        $this->assertContains('Log DB.log',$dirs);
        $this->assertContains('Log Email.log',$dirs);
        
        $c = file_get_contents($GLOBALS['MG']['CFG']['PATH']['LOG'].'/Warning.log');
        preg_match_all('/<msg>(.*?)<\/msg>/',$c,$matches);
        $this->assertEquals($matches[1][0],'testing2');
        $this->assertEquals($matches[1][1],'(error_logger): No logfile found for logtype: LL');
        $this->assertEquals($matches[1][2],'Invalid error type specified');
        $this->assertCount(3,$matches[1]);
    }
  
    /**
    * @depends testWriteFile
    */
    public function testRotate(){
        for($i=0;$i<50;$i++){
            trigger_error('testing',E_USER_WARNING);
        }
        self::$res->l_writeToFile();
        self::$res->l_writeToFile();
        
        $count = 0;
        $dirs = scandir($GLOBALS['MG']['CFG']['PATH']['LOG']);
        foreach($dirs as $dir){
            if(preg_match('/^Warning-/',$dir)){
                $count++;
            }
        }
        $this->assertEquals($count,2);
    }
    
}