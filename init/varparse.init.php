<?php
/*!
 * @file		varparse.init.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-11-2015
 * @section DESCRIPTION
 * This section contains the variable parsing function  the system is designed to
 * parse the following:
 *
 * GET as either in $_GET or redirected into ?url=
 * POST as either JSON or Form Data
 * FILE only with Form data Post
 * PUT, HEAD, DELETE as JSON or Form Data
 * COOKIE
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

/*!
 * This function loads the vars into the system
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 */
function mginit_loadVars(){
	
	//check for server vars & process
	if(!isset($_SERVER['REQUEST_METHOD'])){
		trigger_error('(varparse): REQUEST_METHOD must be set by server for MG to run!',E_USER_ERROR);
		return;
	}
	if(isset($_SERVER['CONTENT_TYPE'])){
		$GLOBALS['MG']['SITE']['REQUEST_CONTENT'] = substr($_SERVER['CONTENT_TYPE'],0,strpos($_SERVER['CONTENT_TYPE'],';'));
	}
	else{
		$GLOBALS['MG']['SITE']['REQUEST_CONTENT'] = false;
	}
	$GLOBALS['MG']['SITE']['REQUEST'] = $_SERVER['REQUEST_METHOD'];
	
	//parse alternate url scheme
	if(isset($_GET['mgurl'])){
		$raw_url=(isset($_GET['url']))?$_GET['url']:'';
		$raw_url=preg_replace('/\/\//','/',$raw_url);
		$raw_url = explode('/',$raw_url);
		$_GET = array();
		$size = count($raw_url);
		for($i=0;$i<$size;$i++){
			if(!empty($raw_url[$i]) && isset($raw_url[$i+1])){
				$_GET[$raw_url[$i]] = $raw_url[$i+1];
				$i++;
			}
		}
	}
	
	//load GET variables
	foreach($GLOBALS['MG']['CFG']['V']['GET'] as $key=>$val){
		if(substr($key,-1)=='*'){
			$tmpkey = substr($key,0,-1);
			$len = strlen($tmpkey);
			foreach($_GET as $varname=>$varval){
				if($tmpkey == substr($varname,0,$len)){
					$tmpId = strtoupper(substr($varname,strlen($tmpkey)));
					$GLOBALS['MG']['V']['GET'][$val[0].$tmpId]=isset($varval)?mginit_cleanVar($varval,$val[1],$val[2]):$val[3];
					unset($_GET[$varname]);
				}
			}
		}
		else{
			$GLOBALS['MG']['V']['GET'][$val[0]]=isset($_GET[$key])?mginit_cleanVar($_GET[$key],$val[1],$val[2]):$val[3];
			unset($_GET[$key]);
		}
	}
	
	//load cookies if we are not in API mode
	if(LOAD_TYPE != 2){
		$GLOBALS['MG']['V']['COOKIE'] = array();
		foreach($GLOBALS['MG']['CFG']['V']['COOKIE'] as $key=>$val){
			$GLOBALS['MG']['V']['COOKIE'][$val[0]]=isset($_COOKIE[$key])?mginit_cleanVar($_COOKIE[$key],$val[1],$val[2]):$val[3];
		}
	}

	//dont load stuff for static pages
	if(LOAD_TYPE != 0 && $GLOBALS['MG']['SITE']['REQUEST'] != 'GET'){

		//put JSON decoded data into $_POST if we are getting JSON data
		if($GLOBALS['MG']['SITE']['REQUEST_CONTENT'] == 'application/json'){
			$_POST = json_decode(file_get_contents('php://input'),true);
		}
		
		//put POST, DELETE, HEAD, etc data into $_POST
		else if($GLOBALS['MG']['SITE']['REQUEST'] != 'POST'){
			$_POST=array();
			parse_str(file_get_contents('php://input'),$_POST);
		}

		//load POST
		foreach($GLOBALS['MG']['CFG']['V']['POST'] as $key=>$val){
			if(substr($key,-1)=='*'){
				$tmpkey = substr($key,0,-1);
				$len = strlen($tmpkey);
				mginit_postHelper($_POST,$GLOBALS['MG']['V']['POST'],$val,$tmpkey,$len);
			}
			else{
				$GLOBALS['MG']['V']['POST'][$val[0]]=isset($_POST[$key])?mginit_cleanVar($_POST[$key],$val[1],$val[2]):$val[3];
				unset($_POST[$key]);
			}
		}

		//load FILE
		$fileUploadErrors = array(
			UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
			UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
			UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
			UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
		);
		foreach($GLOBALS['MG']['CFG']['V']['FILE'] as $key=>$val){
			$GLOBALS['MG']['V']['FILE'][$val[0]]=array();
			$GLOBALS['MG']['V']['FILE'][$val[0]]['ERROR']='';
			if(is_array($_FILES[$key])){
				if($_FILES[$key]['error'] !=  UPLOAD_ERR_OK){
					if($_FILES[$key]['size'] > $val[1]){
						trigger_error('(varparse): Uploaded file too large',E_USER_NOTICE);
						$GLOBALS['MG']['V']['FILE'][$val[0]]['ERROR']='The uploaded file exceeds the configured max file size.';
					}
					else{
						$GLOBALS['MG']['V']['FILE'][$val[0]]['HOST_FILENAME']=stripslashes($_FILES[$key]['name']);
						$GLOBALS['MG']['V']['FILE'][$val[0]]['EXT']=pathinfo($GLOBALS['MG']['V']['FILE'][$val[0]]['HOST_FILENAME'],PATHINFO_EXTENSION);	
						$GLOBALS['MG']['V']['FILE'][$val[0]]['SIZE']=$_FILES[$key]['size'];
						$GLOBALS['MG']['V']['FILE'][$val[0]]['SERVER_FILENAME']=$_FILES[$key]['tmp_name'];
					}	
				}
				else{
					if(isset($fileUploadErrors[$_FILES[$key]['error']])){
						$GLOBALS['MG']['V']['FILE'][$val[0]]['ERROR'] = $fileUploadErrors[$_FILES[$key]['error']];
					}
					else{
						$GLOBALS['MG']['V']['FILE'][$val[0]]['ERROR'] = 'Unknown Upload Error';
					}
					trigger_error('(varparse): File upload error: '.$GLOBALS['MG']['FILE'][$val[0]]['ERROR'],E_USER_NOTICE);
				}
			}
		}
	}
}

/*!
 * This function is the recursive helper function
 * for descending though arrays in POST data.
 * Only POST data supports arrays.
 *
 * @author Kevin Wijesekera
 * @date 3-13-2015
 *
 * @param $base Base access object for data
 * @param $baseStore Base storage object for data
 * @param $varData Data for base store
 * @param $tmpkey Key prefix for all storage
 * @param $len Length of $tmpkey
 *
 */
function mginit_postHelper(&$base,&$baseStore,$varData,$tmpkey,$len){
	foreach($base as $varname=>$varval){
		if(mg_isArrayAssoc($varval)){
			if(!isset($baseStore[strtoupper($varname)])){
				$baseStore[strtoupper($varname)] = array();
			}
			mginit_postHelper($base[$varname],$baseStore[strtoupper($varname)],$varData,$tmpkey,$len);
			if(count($base[$varname]) == 0){
				unset($base[$varname]);
			}
		}
		else{
			if($tmpkey == substr($varname,0,$len)){
				$tmpId = strtoupper(substr($varname,strlen($tmpkey)));
				$baseStore[$varData[0].$tmpId]=isset($varval)?mginit_cleanVar($varval,$varData[1],$varData[2]):$varData[33];
				unset($base[$varname]);
			}
		}
	}
}

/*!
 * This function cleans an object and returns its value
 *
 * Clean Array Meanings
 * 0 - URL Decode
 * 1 - Base64 Decode
 * 2 - Decrypt (future support**)
 * 3 - Don't strip tags
 * 4 - Don't strip slashes
 * 5 - Don't Trim
 *
 * @author Kevin Wijesekera
 * @date 3-13-2015
 *
 * @param $value var to clean
 * @param $type Type of cleaning
 * @param $clean Base clean array
 *
 * @return Cleaned object
 */
function mginit_cleanVar($value,$type,$clean){
	if(count($clean) < 6){
		$clean=array_pad($clean,6,false);
	}
	
	if(is_array($value)){
		$newval = array();
		foreach($value as $item){
			$newval[] = mginit_cleanOneVar($item,$type,$clean);
		}
		return $newval;
	}
	else{
		return mginit_cleanOneVar($value,$type,$clean);
	}
}

/*!
 * This function cleans an var and returns its value
 *
 * Clean Array Meanings
 * 0 - URL Decode
 * 1 - Base64 Decode
 * 2 - Decrypt (future support**)
 * 3 - Don't strip tags
 * 4 - Don't strip slashes
 * 5 - Don't Trim
 *
 * @author Kevin Wijesekera
 * @date 3-13-2015
 *
 * @param $value var to clean
 * @param $type Type of cleaning
 * @param $clean Base clean array
 *
 * @return Cleaned var
 */
function mginit_cleanOneVar($value,$type,$clean){
	if($clean[0]){
		$value=urldecode($value);
	}
	if($clean[1]){
		$value=base64_decode($value);
	}
	if($clean[2]){
		
	}
	if(!$clean[3]){
		$value=strip_tags($value);
	}
	if(!$clean[4]){
		$value=stripslashes($value);
	}
	if(!$clean[5]){
		$value=trim($value);
	}
	
	switch($type){
		case 'int':
			return (int)(preg_match('/^(-|\+)?[0-9]+$/',$value))?$value:0;
		break;
		case 'float':
			return (float)(preg_match('/^(-|\+)?[0-9]+(\.[0-9]+)?$/',$value))?$value:0;
		break;
		case 'char':
			return (preg_match('/^.{1}$$/',$value))?$value:false;
		break;
		case 'string':
			return $value;
		break;
		case 'bool':
		case 'boolean':
			return (empty($value) || $value=='false')?false:true;
		break;
		default:
			if(isset($GLOBALS['MG']['CFG']['CLEAN'][$type])){
				return (preg_match($GLOBALS['MG']['CFG']['CLEAN'][$type][0],$value) == $GLOBALS['MG']['CFG']['CLEAN'][$type][1])?$value:false;
			}
			return $value;
		break;
	}
}