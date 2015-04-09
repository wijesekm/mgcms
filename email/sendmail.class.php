<?php
/*!
 * @file		smtp.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-6-2015
 * @section DESCRIPTION
 * This file contains the mailer class which handles sendmail transport
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
class sendmail extends mail{
	
	/*!
	 * This function sends an e-mail using the sendmail application
     *
	 * @author Kevin Wijesekera
	 * @date 4-6-2015
     *
     * @return true on success, false on failure
	 */
	final public function mail_send(){
		$command = sprintf('%s -oi -f%s -t',escapeshellcmd($this->config['sendmail'],escapeshellarg($sender)));
		
		if(!$mail = popen($command,'w')){
			trigger_error('(sendmail): Could not execute sendmail command',E_USER_WARNING);
			return false;
		}
		fputs($mail,$this->headers);
		fputs($mail,$this->body);
		$res = pclose($mail);
		return $res == 0;
	}
	
}
