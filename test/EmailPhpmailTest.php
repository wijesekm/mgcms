<?php

define('INIT',true);

$GLOBALS['MG']['CFG']['SITE']['VERSION'] ='3.1.1';
$GLOBALS['MG']['CFG']['SITE']['HOSTNAME'] = 'localhost';

include('email/mail_encoding.class.php');
include('email/mail.abstract.php');
include('email/phpmail.class.php');


$GLOBALS['MG']['MIME']=array(
    '*'=>array(      't'=>'application/octet-stream'        ,'bin'=>true),
    'xl'=>array(     't'=>'application/excel'               ,'bin'=>true),
    'js'=>array(     't'=>'application/javascript'          ,'bin'=>false),
    'hqx'=>array(    't'=>'application/mac-binhex40'        ,'bin'=>true),
    'cpt'=>array(    't'=>'application/mac-compactpro'      ,'bin'=>true),
    'bin'=>array(    't'=>'application/macbinary'           ,'bin'=>true),
    'doc'=>array(    't'=>'application/msword'              ,'bin'=>true),
    'word'=>array(   't'=>'application/msword'              ,'bin'=>true),
    'class'=>array(  't'=>'application/octet-stream'        ,'bin'=>true),
    'dll'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'dms'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'exe'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'lha'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'lzh'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'psd'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'sea'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'dwg'=>array(    't'=>'application/octet-stream'        ,'bin'=>true),
    'so'=>array(     't'=>'application/octet-stream'        ,'bin'=>true),
    'oda'=>array(    't'=>'application/oda'                 ,'bin'=>true),
    'pdf'=>array(    't'=>'application/pdf'                 ,'bin'=>true),
    'ai'=>array(     't'=>'application/postscript'          ,'bin'=>false),
    'eps'=>array(    't'=>'application/postscript'          ,'bin'=>false),
    'ps'=>array(     't'=>'application/postscript'          ,'bin'=>false),
    'smi'=>array(    't'=>'application/smil'                ,'bin'=>true),
    'smil'=>array(   't'=>'application/smil'                ,'bin'=>true),
    'mif'=>array(    't'=>'application/vnd.mif'             ,'bin'=>true),
    'xls'=>array(    't'=>'application/vnd.ms-excel'        ,'bin'=>true),
    'ppt'=>array(    't'=>'application/vnd.ms-powerpoint'   ,'bin'=>true),
    'wbxml'=>array(  't'=>'application/vnd.wap.wbxml'       ,'bin'=>true),
    'wmlc'=>array(   't'=>'application/vnd.wap.wmlc'        ,'bin'=>true),
    'dcr'=>array(    't'=>'application/x-director'          ,'bin'=>true),
    'dir'=>array(    't'=>'application/x-director'          ,'bin'=>true),
    'dxr'=>array(    't'=>'application/x-director'          ,'bin'=>true),
    'dvi'=>array(    't'=>'application/x-dvi'               ,'bin'=>true),
    'gtar'=>array(   't'=>'application/x-gtar'              ,'bin'=>true),
    'php3'=>array(   't'=>'application/x-httpd-php'         ,'bin'=>false),
    'php4'=>array(   't'=>'application/x-httpd-php'         ,'bin'=>false),
    'php'=>array(    't'=>'application/x-httpd-php'         ,'bin'=>false),
    'phtml'=>array(  't'=>'application/x-httpd-php'         ,'bin'=>false),
    'phps'=>array(   't'=>'application/x-httpd-php-source'  ,'bin'=>false),
    'swf'=>array(    't'=>'application/x-shockwave-flash'   ,'bin'=>true),
    'sit'=>array(    't'=>'application/x-stuffit'           ,'bin'=>true),
    'tar'=>array(    't'=>'application/x-tar'               ,'bin'=>true),
    'tgz'=>array(    't'=>'application/x-tar'               ,'bin'=>true),
    'xht'=>array(    't'=>'application/xhtml+xml'           ,'bin'=>false),
    'xhtml'=>array(  't'=>'application/xhtml+xml'           ,'bin'=>false),
    'zip'=>array(    't'=>'application/zip'                 ,'bin'=>true),
    'mid'=>array(    't'=>'audio/midi'                      ,'bin'=>true),
    'midi'=>array(   't'=>'audio/midi'                      ,'bin'=>true),
    'mp2'=>array(    't'=>'audio/mpeg'                      ,'bin'=>true),
    'mp3'=>array(    't'=>'audio/mpeg'                      ,'bin'=>true),
    'mpga'=>array(   't'=>'audio/mpeg'                      ,'bin'=>true),
    'aif'=>array(    't'=>'audio/x-aiff'                    ,'bin'=>true),
    'aifc'=>array(   't'=>'audio/x-aiff'                    ,'bin'=>true),
    'aiff'=>array(   't'=>'audio/x-aiff'                    ,'bin'=>true),
    'ram'=>array(    't'=>'audio/x-pn-realaudio'            ,'bin'=>true),
    'rm'=>array(     't'=>'audio/x-pn-realaudio'            ,'bin'=>true),
    'rpm'=>array(    't'=>'audio/x-pn-realaudio-plugin'     ,'bin'=>true),
    'ra'=>array(     't'=>'audio/x-realaudio'               ,'bin'=>true),
    'wav'=>array(    't'=>'audio/x-wav'                     ,'bin'=>true),
    'bmp'=>array(    't'=>'image/bmp'                       ,'bin'=>true),
    'gif'=>array(    't'=>'image/gif'                       ,'bin'=>true),
    'jpeg'=>array(   't'=>'image/jpeg'                      ,'bin'=>true),
    'jpe'=>array(    't'=>'image/jpeg'                      ,'bin'=>true),
    'jpg'=>array(    't'=>'image/jpeg'                      ,'bin'=>true),
    'png'=>array(    't'=>'image/png'                       ,'bin'=>true),
    'tiff'=>array(   't'=>'image/tiff'                      ,'bin'=>true),
    'tif'=>array(    't'=>'image/tiff'                      ,'bin'=>true),
    'eml'=>array(    't'=>'message/rfc822'                  ,'bin'=>false),
    'css'=>array(    't'=>'text/css'                        ,'bin'=>false),
    'html'=>array(   't'=>'text/html'                       ,'bin'=>false),
    'htm'=>array(    't'=>'text/html'                       ,'bin'=>false),
    'shtml'=>array(  't'=>'text/html'                       ,'bin'=>false),
    'log'=>array(    't'=>'text/plain'                      ,'bin'=>false),
    'text'=>array(   't'=>'text/plain'                      ,'bin'=>false),
    'txt'=>array(    't'=>'text/plain'                      ,'bin'=>false),
    'rtx'=>array(    't'=>'text/richtext'                   ,'bin'=>false),
    'rtf'=>array(    't'=>'text/rtf'                        ,'bin'=>false),
    'vcf'=>array(    't'=>'text/vcard'                      ,'bin'=>false),
    'vcard'=>array(  't'=>'text/vcard'                      ,'bin'=>false),
    'xml'=>array(    't'=>'text/xml'                        ,'bin'=>false),
    'xsl'=>array(    't'=>'text/xml'                        ,'bin'=>false),
    'mpeg'=>array(   't'=>'video/mpeg'                      ,'bin'=>true),
    'mpe'=>array(    't'=>'video/mpeg'                      ,'bin'=>true),
    'mpg'=>array(    't'=>'video/mpeg'                      ,'bin'=>true),
    'mov'=>array(    't'=>'video/quicktime'                 ,'bin'=>true),
    'qt'=>array(     't'=>'video/quicktime'                 ,'bin'=>true),
    'rv'=>array(     't'=>'video/vnd.rn-realvideo'          ,'bin'=>true),
    'avi'=>array(    't'=>'video/x-msvideo'                 ,'bin'=>true),
    'movie'=>array(  't'=>'video/x-sgi-movie'               ,'bin'=>true)
);

class BaseLoggerTest extends PHPUnit_Framework_TestCase{
        
    protected static $res;
    
    public static function setUpBeforeClass(){
        self::$res = new phpmail();
    }
    
    public function testFullFeatureMessage(){
        $testFullFeatureHeaders = "Date: (.*?)\nTo: Kevin Wijesekera <kevin@wijesekera-home.net>,web@wijesekera-home.net,billing@wijesekera-home.net,Test <test@wijesekera-home.net>\nFrom: Kevin Wijesekera <kevin@wijesekera-home.net>\nCc: Kevin Wijesekera <kevin@wijesekera-home.net>,web@wijesekera-home.net,billing@wijesekera-home.net,Test <test@wijesekera-home.net>\nBcc: Kevin Wijesekera <kevin@wijesekera-home.net>,web@wijesekera-home.net,billing@wijesekera-home.net,Test <test@wijesekera-home.net>\nReply-To: Kevin Wijesekera <kevin@wijesekera-home.net>,web@wijesekera-home.net,billing@wijesekera-home.net,Test <test@wijesekera-home.net>\nSubject: This is a test subject\nMessage-ID: (.*?)@localhost\nX-Priority: 3\nX-Mailer: mgcore V3.1.1\n";
        $testFullFeatureHeaders = '/'.($testFullFeatureHeaders).'/';
        $base = array(
            'from'=>array('Kevin Wijesekera','kevin@wijesekera-home.net'),
            'to'=>array(
                'kevin@wijesekera-home.net'=>'Kevin Wijesekera',
                'web@wijesekera-home.net'=>false,
                'billing@wijesekera-home.net'=>'',
                'test@wijesekera-home.net'=>'Test'
            ),
            'cc'=>array(
                'kevin@wijesekera-home.net'=>'Kevin Wijesekera',
                'web@wijesekera-home.net'=>false,
                'billing@wijesekera-home.net'=>'',
                'test@wijesekera-home.net'=>'Test'
            ),
            'bcc'=>array(
                'kevin@wijesekera-home.net'=>'Kevin Wijesekera',
                'web@wijesekera-home.net'=>false,
                'billing@wijesekera-home.net'=>'',
                'test@wijesekera-home.net'=>'Test'
            ),
            'replyto'=>array(
                'kevin@wijesekera-home.net'=>'Kevin Wijesekera',
                'web@wijesekera-home.net'=>false,
                'billing@wijesekera-home.net'=>'',
                'test@wijesekera-home.net'=>'Test'
            ),
            'subject'=>'This is a test subject',
            'content'=>'<p>Testing</p><br/></p>Testing 1</p><img src="'.md5('W:\www\backend\test\files\linkedin.gif').'@localhost">',
            'attachments'=>array(
                array('W:\www\backend\test\files\test.html','html'),
                array('W:\www\backend\test\files\report.txt','txt'),
                array('W:\www\backend\test\files\Error.log','log')
            ),
            'inline'=>array(
                array('W:\www\backend\test\files\linkedin.gif','gif')
            ),
            'priority'=>3
        );
        
        //test reg-inline-attach
        self::$res->mail_parse($base);
        $this->assertRegExp($testFullFeatureHeaders,self::$res->get_headers()); //multi-part inline attach
        echo self::$res->get_body();
        
        //test reg-inline
        $test = $base;
        unset($test['attachments']);
        self::$res->mail_parse($base);
        $this->assertRegExp($testFullFeatureHeaders,self::$res->get_headers()); //multi-part inline attach
        //echo self::$res->get_body();
        
        //test reg-attach
        $test = $base;
        unset($test['inline']);
        self::$res->mail_parse($base);
        $this->assertRegExp($testFullFeatureHeaders,self::$res->get_headers()); //multi-part inline attach
        
        //test reg
        $test = $base;
        unset($test['attachments']);
        unset($test['inline']);
        self::$res->mail_parse($base);
        $this->assertRegExp($testFullFeatureHeaders,self::$res->get_headers()); //multi-part inline attach
        
        //test plain
        $test = $base;
        $test['plaincontent']="testing\ntesting1234\n";
        unset($test['attachments']);
        unset($test['inline']);
        unset($test['content']);
        self::$res->mail_parse($base);
        $this->assertRegExp($testFullFeatureHeaders,self::$res->get_headers()); //multi-part inline attach
        
        //test multi-part inline
        
        //test multi-part attach
        
        //test multi-part

        
        
    }
    
    //test incorrect message construction
    
    //test incorrect message formatting
    

}