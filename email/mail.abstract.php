<?php
/*!
 * @file		mail.abstract.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015 Kevin Wijesekera
 * @edited		4-2-2015
 * @section DESCRIPTION
 * This file contains the abstract class for a mailer supported by the system
 * @license 
 * ###################################
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/.
 * ###################################
 *
 */
 
if(!defined('INIT')){
	die();
}

/*!< MAIL class */
abstract class mail{

	protected $config;	/*!< Configuration Array */
	protected $subject;
	protected $sender;
	protected $recipients;
	protected $headers;
	protected $body;
	protected $encoding;
    
	/*!
	 * This is the class constructor which sets up the mailer system
	 *
	 * @author Kevin Wijesekera
	 * @date 4-2-2015
	 *
	 * @param $cfg Configuration Array
	 */
	public function __construct($cfg=array()){
		$this->config=array(
			'timeout'=>30,
			'tls'=>true,
			'options'=>array(),
			'verp'=>''
		);
        $this->encoding = new mail_encoding();
		$this->config = array_merge($this->config,$cfg);
	}
    
    public function get_body(){
        return $this->body;
    }
    
    public function get_headers(){
        return $this->headers;
    }

	abstract public function mail_send();
    
	/*!
	 * This function parses a mail array into a mail packet
     * ready to be sent.
	 *
     *  from=>array(name,email)
	 *  to=>array(email=>name)
	 *  cc..
	 *  bcc...
	 *  replyto...
	 *  subject=>
	 *  content=>
	 *  plaincontent=>
	 *  attachments=>array(file,file,....),
	 *  charset=>UTF-8
	 *  encoding=>8bit
	 *  priority=>1..2..3
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 *
	 * @param $message Message array to parse
     *
     * @return true on success, false on failure
	 */
	final public function mail_parse($message){
		/*

		*/
		if(!isset($message['from'])||!isset($message['to'])||!isset($message['content'])||!isset($message['subject'])){
			return false;
		}
		
		if(empty($message['charset'])){
			$this->encoding->CharSet = 'iso-8859-1';
		}
		else{
			$this->encoding->CharSet = $this->encoding->enc_stripEOL($message['charset']);
		}
		
		if(empty($message['encoding'])){
			$message['encoding'] = '8bit';
		}
		else{
			$message['encoding'] = $this->encoding->mail_stripEOL($message['encoding']);
		}

		$boundry_id = md5(uniqid(time()));

		$this->mail_generateHeaders($message,$boundry_id);
		$this->mail_generateBody($message,$boundry_id);
        return true;
	}
	
	/*!
	 * This function generates the mail body
	 *
     *  from=>array(name,email)
	 *  to=>array(email=>name)
	 *  cc..
	 *  bcc...
	 *  replyto...
	 *  subject=>
	 *  content=>
	 *  plaincontent=>
	 *  attachments=>array(file,file,....),
	 *  charset=>UTF-8
	 *  encoding=>8bit
	 *  priority=>1..2..3
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 *
	 * @param $message Message array to parse
     * @param $bid Boundry ID Base
	 */
	final private function mail_generateBody($message,$bid){
		$this->body = '';
		$bodyEncoding = $message['encoding'];
        $bodyCharSet = $this->encoding->CharSet;
		if ($bodyEncoding == '8bit' && !$this->encoding->enc_has8bitChars($message['content'])) {
            $bodyEncoding = '7bit';
            $bodyCharSet = 'us-ascii';
        }
        $altBodyEncoding = $message['encoding'];
        $altBodyCharSet = $this->encoding->CharSet;
        if ($altBodyEncoding == '8bit' && !$this->encoding->enc_has8bitChars($message['plaincontent'])) {
            $altBodyEncoding = '7bit';
            $altBodyCharSet = 'us-ascii';
        }
		$hasInlineAttach = false;
		$hasAttach = false;
		if(isset($message['attachments'][0])){
			$hasAttach = true;
		}
        if(isset($message['inline'][0])){
			$hasInlineAttach = true;
		}
		//b1_....b2_...b3_
		$bounds = array(
			'b1_'.$bid,
			'b2_'.$bid,
			'b3_'.$bid
		);
		//plain message
		if(empty($message['content'])){
			$this->body = $this->encoding->enc_string($message['plaincontent'],$altBodyEncoding);
		}
		else{
			if(isset($message['plaincontent'])){
				/*if($hasAttach){
					$this->body .= $this->mail_formatBoundry($bounds[0],'multipart/alternative',false,false,$bounds[1]);
				}
				$this->body .= $this->mail_formatBoundry($bounds[1],'text/plain',$altBodyCharSet,$altBodyEncoding);
				$this->body .= $this->encoding->enc_string($message['plaincontent'],$altBodyEncoding);
				$this->body .= "\n\n";
				if($hasInlineAttach){
					$this->body .= $this->mail_formatBoundry($bounds[1],'multipart/related',false,false,$bounds[2]);
				}
				$this->body .= $this->mail_formatBoundry($bounds[0],'text/html',$bodyCharSet,$bodyEncoding);
				$this->body .= $this->encoding->enc_string($message['content'],$bodyEncoding);
				$this->body .= "\n\n";
				if($hasInlineAttach){
					//attach all
                   // 
				}
				$this->body .= '--'.$bounds[0]."--\n";
				if($hasAttach){
					//attach all
                    $this->mail_addAttachments($message['attachments'],'attachment',($hasInlineAttach)?$bounds[2]:$bounds[1]);
				}*/
			}
			else{
				if($hasInlineAttach){
					$this->body .= $this->mail_formatBoundry($bounds[0],'multipart/related',false,false,$bounds[1]);
				}
				$this->body .= $this->mail_formatBoundry($bounds[0],'text/html',$bodyCharSet,$bodyEncoding);
				$this->body .= $this->encoding->enc_string($message['content'],$bodyEncoding);
				$this->body .= "\n\n";
				if($hasInlineAttach){
					//attach all
                    $this->body .= $this->mail_addAttachments($message['inline'],$hasAttach?$bounds[1]:$bounds[0],true);
				}
				if($hasAttach){
					//attach all
                    $this->body .= $this->mail_addAttachments($message['attachments'],$bounds[0],false);
				}
			}
		}
		
		//TODO: Sign body????
	}
	
	/*!
	 * This function generates the mail headers
	 *
     *  from=>array(name,email)
	 *  to=>array(email=>name)
	 *  cc..
	 *  bcc...
	 *  replyto...
	 *  subject=>
	 *  content=>
	 *  plaincontent=>
	 *  attachments=>array(file,file,....),
	 *  charset=>UTF-8
	 *  encoding=>8bit
	 *  priority=>1..2..3
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 *
	 * @param $message Message array to parse
     * @param $boundry_id Boundry ID Base
	 */
	final private function mail_generateHeaders($message,$boundry_id){
		$this->headers = '';
		//Date: header
		$this->headers .= 'Date: '. date('D, j M Y H:i:s O')."\n";
	
		//To: Header
		if(!count($message['to'])){
			$this->headers .= "To: undisclosed-recipients:;\n";
			//name<email>, name<email>
		}
		else{
			$this->recipients=array();
            $this->headers .= 'To: ';
			$next = false;
			foreach($message['to'] as $email=>$name){
                if(!is_string($name) && !empty($name)){
                    trigger_error('(mail): Bad formatting on TO',E_USER_WARNING);
                    continue;
                }
				if($next){
					$this->headers.=',';
				}
				$next = true;
				$this->headers.=$this->mail_formatAddress($name,$email);
                $this->recipients[] = $email;
			}
            $this->headers .= "\n";
		}
        
		//From: header\
        $this->sender = $this->mail_formatAddress($message['from'][0],$message['from'][1]);
		$this->headers .= 'From: '.$this->sender."\n";
		
		//Cc: Header
		if(count($message['cc'])){
			$this->headers.='Cc: ';
			$next = false;
			foreach($message['cc'] as $email=>$name){
                if(!is_string($name) && !empty($name)){
                    trigger_error('(mail): Bad formatting on CC',E_USER_WARNING);
                    continue;
                }
				if($next){
					$this->headers.=',';
				}
				$next = true;
				$this->headers.=$this->mail_formatAddress($name,$email);
			}
			$this->headers.="\n";
		}
		
		//Bcc: Header
		if(count($message['bcc'])){
			$this->headers.='Bcc: ';
			$next = false;
			foreach($message['bcc'] as $email=>$name){
                if(!is_string($name) && !empty($name)){
                    trigger_error('(mail): Bad formatting on BCC',E_USER_WARNING);
                    continue;
                }
				if($next){
					$this->headers.=',';
				}
				$next = true;
				$this->headers.=$this->mail_formatAddress($name,$email);
			}
			$this->headers.="\n";
		}
		
		//Reply-To: Header
		if(count($message['replyto'])){
			$this->headers.='Reply-To: ';
			$next = false;
			foreach($message['replyto'] as $email=>$name){
                if(!is_string($name) && !empty($name)){
                    trigger_error('(mail): Bad formatting on REPLYTO',E_USER_WARNING);
                    continue;
                }
				if($next){
					$this->headers.=',';
				}
				$next = true;
				$this->headers.=$this->mail_formatAddress($name,$email);
			}
			$this->headers.="\n";
		}
		
		//subject
		$this->subject = $this->encoding->enc_header($this->encoding->enc_stripEOL($message['subject']));
		$this->headers.='Subject: '.$this->subject."\n";
		
		//Message-ID:
		$this->headers.="Message-ID: $boundry_id@".$GLOBALS['MG']['CFG']['SITE']['HOSTNAME']."\n";
		
		//Priority
		if(isset($message['priority'])){
			$this->headers.='X-Priority: '.$message['priority']."\n";
		}
		
		//Mailer
		$this->headers.='X-Mailer: mgcore V'.$GLOBALS['MG']['CFG']['SITE']['VERSION']."\n";
		//TODO Encryption????....Confirm Reading????
	}
    
    final private function mail_addAttachments($attachments,$boundry_id,$inline=false){
        
        /*
        * 0 -> file
        * 1 -> extension
        * 2 -> encoding if applicible
        */
        /*

        /*
        Content-Type: image/png; name="test.png"
        Content-Transfer-Encoding: base64
        Content-ID: <part1.02080004.04000407@sample.com>
        Content-Disposition: inline; filename="test.png"
        */
        $content = '';
        foreach($attachments as $attach){
            if(!isset($GLOBALS['MG']['MIME'][$attach[1]])){
                trigger_error('(mail): Attachment MIME not found.'.$attach[0],E_USER_NOTICE);
                continue;
            }
            $data = $GLOBALS['MG']['MIME'][$attach[1]];
            $data['ext'] = $attach[1];
            $data['fname'] = basename($attach[0]);
            if($data['bin']){
                $attach[2] = 'binary';
            }
            else if(!isset($attach[2])){
                $attach[2] = 'base64';
            }
            $content .= $this->mail_formatBoundry($boundry_id,$data['t'],false,$attach[2],false,$data['fname']);
            if($inline){
                $id = '';
                $content .= sprintf("Content-ID: %s@%s\n",md5($attach[0]),$GLOBALS['MG']['CFG']['SITE']['HOSTNAME']);
            }
            
            $encoded_name = $this->encoding->enc_header($this->encoding->enc_stripEOL($data['fname']));
            if(preg_match('/[ \(\)<>@,;:\\"\/\[\]\?=]/', $encoded_name)){
                $content .= sprintf("Content-Disposition: %s; filename=\"%s\"\n",$inline?'inline':'attachment',$encoded_name);
            }
            else{
                $content .= sprintf("Content-Disposition: %s; filename=%s\n",$inline?'inline':'attachment',$encoded_name);
            }

            $content .= $this->encoding->enc_file($attach[0],$attach[2]);
            $content .= "--$boundry_id--\n";
        }
        return $content;
    }
	
	/*!
	 * This function formats a boundry statement
     *
     * If a charset and content type are provided it will insert a content boundry
     * If a second Boundry ID is provided it will insert a sub-boundry boundry
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 *
	 * @param $bid Boundry ID
     * @param $contentType Boundry content type
     * @param $charset Boundry charset
     * @param $encoding Boundry encoding
     * @param $bid2 Sub Boundry
     * @param $file Type with file
     *
     * @return boundry statement
	 */
	final private function mail_formatBoundry($bid,$contentType,$charset=false,$encoding=false,$bid2=false,$fileName=false){
		$result = '--'.$bid."\n";
		$result .= "Content-Type: $contentType;";
		if($charset){
			$result.=" charset=$charset";
		}
		else if($bid2){
			$result.="\tboundry=\"$bid2\"";
		}
        else if($fileName){
            $result.=" name=\"$fileName\"";
        }
		$result.="\n";
		if($encoding && $encoding != '7bit'){
			$result .= "Content-Transfer_Encoding: $encoding\n";
		}
		return $result;
	}
	
	/*!
	 * This function formats a email address
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 *
	 * @param $name User Name
     * @param $email E-Mail Address
     *
     * @return Formatted address
	 */
	final private function mail_formatAddress($name,$email){
		$name = $this->encoding->enc_stripEOL($name);
		$email = $this->encoding->enc_stripEOL($email);
		if(empty($name)){
			return $email;
		}
		else{
			return $this->encoding->enc_header($name,'phrase').' <'.$email.'>';
		}
	}
}
