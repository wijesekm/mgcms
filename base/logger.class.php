<?php
/*!
 * @file		logger.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-1-2015
 * @section DESCRIPTION
 * This class contains methods for logging errors and messages
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

define('L_DATABASE','L_DATABASE');
define('L_AUDIT','L_AUDIT');
define('L_EMAIL','L_EMAIL');

/*! Error Logger Class */
class logger{
	
	//Public vars
	public $uid;
	public $lang;
	
	//Private Vars
	private $logs;			/*!< Logged non-fatal errors */
	private $logged_fatal_errors;	/*!< Logged fatal errors */
	private $bad_file;				/*!< Could not write to file */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	
	/*!
	 * This is the class constructor which sets up the log file names
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 */
	public function __construct($lang){
		if(!is_array($GLOBALS['MG']['CFG']['LOG']['ERRORS']) || !isset($GLOBALS['MG']['CFG']['LOG']['SIZE'])){
			die('Error Logger Configuration Incorrect');
		}
		set_error_handler(array($this,'l_error'));
		$this->logs = array();
		$this->logged_fatal_errors = false;
		$this->bad_file = false;
		$this->lang = $lang;
		$this->uid = '';
		if(!is_dir($GLOBALS['MG']['CFG']['PATH']['LOG'])){
			mkdir($GLOBALS['MG']['CFG']['PATH']['LOG']);
		}
	}

	/*!
	 * This is the class destructor which clears any cached errors
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 */
	public function __destruct(){
		$this->logs=false;
		$this->logged_fatal_errors=false;
		restore_error_handler();
	}
	
	/*!
	 * This function logs an error message to the stack
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 * @param $errNo Error Number
	 * @param $errMsg Error Message
	 * @param $filename File Name
	 * @param $linenum Line in file with error
	 * @param $vars Any passed vars
	 *
	 * @return true
	 */
	public function l_error($errNo,$errMsg,$filename,$linenum,$vars){
		$dt = @date("Y-m-d H:i:s (T)");
		if(isset($GLOBALS['MG']['CFG']['LOG']['ERRORS'][$errNo])){
			if(!isset($this->logs[$GLOBALS['MG']['CFG']['LOG']['ERRORS'][$errNo]['file']])){
				$this->logs[$GLOBALS['MG']['CFG']['LOG']['ERRORS'][$errNo]['file']] = array();
			}
			 
			if($GLOBALS['MG']['CFG']['LOG']['ERRORS'][$errNo]['fatal']){
				$this->logged_fatal_errors = true;
			}
			$this->logs[$GLOBALS['MG']['CFG']['LOG']['ERRORS'][$errNo]['file']][] = array($dt,$errNo,$errMsg,$filename,$linenum);
		}
		else{
			if(!isset($this->logs[$GLOBALS['MG']['CFG']['LOG']['ERRORS'][E_NOTICE]['file']])){
				$this->logs[$GLOBALS['MG']['CFG']['LOG']['ERRORS'][E_NOTICE]['file']] = array();
			}
			$this->logs[$GLOBALS['MG']['CFG']['LOG']['ERRORS'][E_NOTICE]['file']][] = array($dt,$errNo,$errMsg,$filename,$linenum);
		}
		return true;
	}
	
	/*!
	 * This function logs a message to the message queue
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 * @param $msgNo Message Number
	 * @param $message Message
	 * @param $filename File Name
	 */
	public function l_message($msgNo,$message,$filename){
		$dt = @date("Y-m-d H:i:s (T)");
		if(isset($GLOBALS['MG']['CFG']['LOG']['LOGS'][$msgNo])){
			if(!isset($this->logs[$GLOBALS['MG']['CFG']['LOG']['LOGS'][$msgNo]['file']])){
				$this->logs[$GLOBALS['MG']['CFG']['LOG']['LOGS'][$msgNo]['file']] = array();
			}
			$this->logs[$GLOBALS['MG']['CFG']['LOG']['LOGS'][$msgNo]['file']][] = array($dt,$msgNo,$message,$filename,false);
		}
		else{
			trigger_error('(error_logger): No logfile found for logtype: '.$msgNo,E_USER_WARNING);
		}
		
	}
	
	/*!
	 * This function checks for any fatal errors and modifies
	 * the display to output the errors instead of any content
	 *
	 * @author Kevin Wijesekera
	 * @date 3-12-2015
	 * @note May terminate application upon fatal errors
	 *
	 * @output JSON error array if LOAD_TYPE != 0 or HTML output if LOAD_TYPE == 0
	 */
	public function l_fatalCheck(){
        if(!$this->logged_fatal_errors){
            return false;
        }
		$this->l_writeToFile();

		if(LOAD_TYPE == 0){
			if(!is_file($GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$this->lang.'/tpl/fatal_errors.tpl')){
				die('Could not display fatal errors....no errors.tpl file');
			}
			$data = file_get_contents($GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$this->lang.'/tpl/fatal_errors.tpl');
			$lines = '';
			foreach($this->logs as $file=>$errors){
				foreach($errors as $key=>$val){
					$id = isset($GLOBALS['MG']['CFG']['LOG']['ERRORS'][$val[1]])?$GLOBALS['MG']['CFG']['LOG']['ERRORS'][$val[1]]['id']:$GLOBALS['MG']['CFG']['LOG']['LOGS'][$val[1]]['id'];
					$lines .= '<tr>';
					$lines .= '<td>'.$id.'</td>';
					$lines .= "<td>${val[0]}</td>";
					$lines .= "<td>${val[2]} ${val[3]} @ ${val[4]}</td>";
					$lines .= '</tr>';
				}
			}
			
			echo preg_replace('/\{MSG\}/',$lines,$data);
		}
		else{
			if(LOAD_TYPE == 3){
				echo '<!--ERROR: ';
			}
			echo mg_jsonEncode(array('code'=>500,'errs'=>$this->logs));
			if(LOAD_TYPE == 3){
				echo '-->';
			}
		}
		if(isset($GLOBALS['MG']['DB'])){
			$GLOBALS['MG']['DB']->db_close();
		}
        die();
	}
	
	/*!
	 * This function checks to see if fatal errors
	 * have been generated
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 * @return true on fatal, false otherwise
	 */
	public function l_hasFatalErrors(){
		return $this->logged_fatal_errors;
	}
	
	/*!
	 * This function gets all errors 
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 * @return array of all errors
	 */
	public function l_getAll(){
		return $this->logs;
	}

	/*!
	 * This function writes the error stack to a file and then
	 * clears the stack
	 *
	 * @author Kevin Wijesekera
	 * @date 3-11-2015
	 *
	 */
	public function l_writeToFile(){
		if($this->bad_file){
			return;
		}
		foreach($this->logs as $file=>$errors){
			$line = '';
			foreach($errors as $key=>$val){
				$id = isset($GLOBALS['MG']['CFG']['LOG']['ERRORS'][$val[1]])?$GLOBALS['MG']['CFG']['LOG']['ERRORS'][$val[1]]['id']:$GLOBALS['MG']['CFG']['LOG']['LOGS'][$val[1]]['id'];
				$line .= "<err>\r\n";
				$line .= "\t<ts>${val[0]}</ts>\r\n";
				$line .= "\t<etype>".$id."</etype>\r\n";
				$line .= "\t<uri>${_SERVER['REQUEST_URI']}</uri>\r\n";
				$line .= "\t<usr>".$this->uid."</usr>\r\n";
				$line .= "\t<file>${val[3]} @ ${val[4]}</file>\r\n";
				$line .= "\t<msg>${val[2]}</msg>\r\n";
				$line .="</err>\r\n";
			}
			if(!@error_log($line, 3,$GLOBALS['MG']['CFG']['PATH']['LOG'].'/'.$file.'.log')){
				trigger_error('(error_logger): Could not write error logs to file',E_USER_ERROR);
				$this->bad_file = true;
				return;
			}
			$this->l_logRotate($GLOBALS['MG']['CFG']['PATH']['LOG'].'/'.$file.'.log');
		}
	}
	
	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */
	
	/*!
	 * This function rotates the log files if they
	 * grow too large in size
	 *
	 * @author Kevin Wijesekera
	 * @date 3-12-2015
	 *
	 * @param $fname File Name
	 *
	 * @return true on success, false on failure
	 */
	private function l_logRotate($fname){
		$dt = date("Y-m-d-h-i-s");
		if(!is_file($fname)){
			touch($fname);
		}
		if(@filesize($fname)>$GLOBALS['MG']['CFG']['LOG']['SIZE']){
			$new_name=preg_replace('/\.log/i','-'.$dt.'.log',$fname);
			if(!@copy($fname,$new_name)){
				return false;
			}
			return @unlink($fname);
		}
		return true;
	}
}
