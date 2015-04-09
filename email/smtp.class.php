<?php
/*!
 * @file		smtp.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-6-2015
 * @section DESCRIPTION
 * This file contains the mailer class which handles SMTP transport
 *
 *
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
 */
 
if(!defined('INIT')){
	die();
}

/*!< SMTP class */
class smtp extends mail{

	private $conn = null;				/*!< Connection object */
	private $server_config = array();	/*!< SMTP Server Config */
	
	/*!
	 * This function sends an e-mail by connecting to a SMTP server
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
     *
     * @return true on success, false on failure
	 */
	final public function mail_send(){
		if($this->smtp_connect()){
			$this->smtp_quit();
			return false;
		}
		$errstr = '';
		
		//set from
		if(!$this->smtp_sendCommand('MAIL FROM:'.$this->sender.$this->config['verp'],array(250),$errstr)){
			$this->smtp_quit();
			return false;
		}
		//set all to,cc,bcc
		foreach($this->recipients as $val){
			if(!$this->smtp_sendCommand('RCPT TO:<'.$val.'>',array(250,251),$errstr)){
				$this->smtp_quit();
				return false;
			}
		}
		
		//set data mode
		if(!$this->sendCommand('DATA',array(354),$errstr)){
			$this->smtp_quit();
			return false;
		}

		//lets send all headers ... per RFC821 send max is 1000 characters
		if(!empty($headers)){
			$to_send = explode("\n",$this->headers);
			foreach($to_send as $data){
				$output=array();
				while(isset($data[998])){
					//locate a break position not in hte middle of a word....
					$pos = strrpos(substr($data, 0, 998), ' ');
					if(!$pos){
						$pos = 997;
						$data = "\t".substr($data,$pos);
					}
					else{
						$data = "\t".substr($data,$pos+1);
					}
					$output[] = substr($data,0,$pos);
				}
				$output[] = $data;
				foreach($output as $item){
					if(!empty($item) && $item[0] == '.'){
						$item = '.'.$item;
					}
					fwrite($this->conn, $item."\r\n");
				}
			}
		}
		$to_send = explode("\n",$this->body);
		foreach($to_send as $data){
			$output=array();
			while(isset($data[998])){
				//locate a break position not in the middle of a word....
				$pos = strrpos(substr($data, 0, 998), ' ');
				if(!$pos){
					$pos = 997;
					$data = substr($data,$pos);
				}
				else{
					$data = substr($data,$pos+1);
				}
				$output[] = substr($data,0,$pos);
			}
			$output[] = $data;
			foreach($output as $item){
				if(!empty($item) && $item[0] == '.'){
					$item = '.'.$item;
				}
				fwrite($this->conn, $item."\r\n");
			}
		}
		
		if(!$this->sendCommand('.',array(250),$errstr)){
			$this->smtp_quit();
			return false;
		}
		$this->smtp_quit();
		return true;
	}
	
	/*!
	 * This function connects to a SMTP server
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
     *
     * @return true on success, false on failure
	 */
	private function smtp_connect(){
		//already connected
		if($conn != null){
			return true;
		}

		$context = stream_context_create($this->config['options']);
		$errno = 0;
		$errstr = '';
		$this->conn = stream_socket_client($this->config['host'].':'.$this->config['port'],$errno,$errstr,$this->config['timeout'],STREAM_CLIENT_CONNECT,$context);
		if(!is_resource($this->conn)){
			trigger_error('(smtp): Could not connected to SMTP server: '.$this->config['host'].':'.$this->config['port'].' '.$errstr,E_USER_WARNING);
			return false;
		}
		$this->smtp_getData();
		
		//send HELLO
		$this->smtp_sendHello($this->config['domain'],true);
		
		//start TLS + send HELLO
		if($this->config['tls']){
			if(!$this->smtp_sendCommand('STARTTLS',array(220),$errstr)){
				trigger_error('(smtp): Could not enable TLS',E_USER_WARNING);
				return false;
			}
			if(!stream_socket_enable_crypto($this->conn,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)){
				trigger_error('(smtp): Could not enable TLS',E_USER_WARNING);
				return false;
			}
			$this->smtp_sendHello($this->config['domain']);
		}
		
		//get auth type
		$authtype = 'LOGIN';
		if(isset($this->server_config['EHLO'])){
			//extended hello
			if(!isset($this->server_config['AUTH'])){
				trigger_error('(smtp): Cannot authenticate to server!',E_USER_WARNING);
				return false;
			}
			foreach (array('LOGIN', 'CRAM-MD5', 'NTLM', 'PLAIN') as $method) {
				if (in_array($method, $this->server_config['AUTH'])) {
					$authtype = $method;
					break;
				}
			}
		}
		//authenticate
		switch($authtype){
			case 'PLAIN':
				if(!$this->smtp_sendCommand('AUTH PLAIN',array(334),$errstr)){
					return false;
				}
				if(!$this->smtp_sendCommand(base64_encode("\0".$this->config['username']."\0".$this->config['password']),array(235),$errstr)){
					return false;
				}
			break;
			case 'LOGIN':
				if(!$this->smtp_sendCommand('AUTH LOGIN',array(334),$errstr)){
					return false;
				}
				if(!$this->smtp_sendCommand(base64_encode($this->config['username']),array(334),$errstr)){
					return false;
				}
				if(!$this->smtp_sendCommand(base64_encode($this->config['password']),array(235),$errstr)){
					return false;
				}
			break;
			case 'NTLM':
				trigger_error('Not supported yet',E_USER_ERROR);
			break;
			case 'CRAM-MD5':
				if(!$this->smtp_sendCommand('AUTH CRAM-MD5',array(334),$errstr)){
					return false;
				}
				$challenge = base64_decode(substr($errstr, 4));
				// Build the response
				$response = $this->config['username'] . ' ' . hash_hmac('md5', $challenge, $this->config['password']);
				if(!$this->smtp_sendCommand(base64_encode($response),array(235),$errstr)){
					return false;
				}
			break;
		};
		return true;
	}
	
	/*!
	 * This function sends a QUIT request to the SMTP server and closes
     * the connection.
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 */
	private function smtp_quit(){
		$errstr = '';
		$this->smtp_sendCommand('QUIT',array(221),$errstr);
		$this->smtp_close();
	}
	
	
	/*!
	 * This function closes the socket connection to the server.
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
	 */
	private function smtp_close(){
		if(is_resource($this->conn)){
			fclose($this->conn);
			$this->conn = null;
		}
	}
	
	/*!
	 * This function sends a HELLO string to the server and gets its configuration
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
     *
     * @param $host Hostname or IP of self
     * @param $conf Check server configuration
     *
     * @return true on success, false on failure
	 */
	private function smtp_sendHello($host,$conf=false){
		$reply='';
		$type = 'EHLO';
		if(!$this->smtp_sendCommand('EHLO '.$host,array(250),$reply)){
			$type='HELO';
			if(!$this->smtp_sendCommand('HELO '.$host,array(250),$reply)){
				trigger_error('(smtp): Could not send HELLO',E_USER_WARNING);
				return false;
			}
		}
        if($conf){
            $this->server_config = array();
            $lines = explode("\n",$reply);
            foreach($lines as $n=>$s){
                $s = trim(substr($s,4));
                if(!$s){
                    continue;
                }
                $fields = explode(' ',$s);
                if($fields){
                    if(!$n){
                        $name = $type;
                        $fields = $fields[0];
                    }
                    else{
                        $name = array_shift($fields);
                        if($name == 'SIZE'){
                            $fields = ($fields) ? $fields[0]:0;
                        }
                    }
                    $this->server_config[$name] = ($fields?$fields:true)
                }
            }
        }
		return true;
	}
	
	/*!
	 * This function sends a command to the server and gets its response.
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
     *
     * @param $command Command string to send (omit \r\n);
     * @param $expect array of expected result codes
     * @param $reply Container for response.
     *
     * @return true on success, false on failure
	 */
	private function smtp_sendCommand($command,$expect,&$reply){
		fwrite($this->conn, $command."\r\n");
		$reply = $this->smtp_getdata();
		
		//check for error code
		$matches = array();
		if(preg_match("/^([0-9]{3})[ -](?:([0-9]\\.[0-9]\\.[0-9]) )?/", $reply $matches)){
			$code = $matches[1];
			$code_ex = (count($matches) > 2 ? $matches[2] : null);
			 $detail = preg_replace(
				"/{$code}[ -]".($code_ex ? str_replace('.', '\\.', $code_ex).' ' : '')."/m",
				'',
				$reply
			);
		}
		else{
			$code = substr($reply, 0, 3);
			$code_ex = null;
			$detail = substr($reply, 4);
		}
		if(!in_array($code,$expect)){
			trigger_error('(smtp): Command failed '.$command.' '.$detail,E_USER_WARNING);
			return false;
		}
		return true;
	}
	
	/*!
	 * This function gets data from the SMTP server
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
     *
     * @return data string
	 */
	private function smtp_getData(){
		//set timeout
		stream_set_timeout($this->conn,$this->config['timeout']);
		$response = '';
		$end = time() + $this->config['timout'];
		while(!feof($this->conn) && is_resource($this->conn)){
			$str = fgets($this->conn,515);
			$response .= $str;
			if(isset($str[3]) && $str[3] == ' '){
				break;
			}
			$info = stream_get_meta_data($this->conn);
            //server timeout
			if($info['timed_out']){
				trigger_error('(smtp): Read timeout',E_USER_NOTICE);
				break;
			}
            //manual timeout
			if(time() > $end){
				trigger_error('(smtp): Read timeout',E_USER_NOTICE);
				break;
			}
		}
		return $response;
	}
}
