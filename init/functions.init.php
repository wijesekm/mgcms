<?php
/*!
 * @file		error_logger.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-12-2015
 * @section DESCRIPTION
 * This file contains initialzation and other misc functions
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
 * This function loads a system package
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 * @param $packages Array of packages to load
 */
function mginit_load($packages){
	
	if(is_array($packages)){
		if(is_array($packages[0])){
			foreach($packages as $key=>$val){
				mginit_loadFile($val[0],$val[1],$val[2]);
			}
		}
		else{
			mginit_loadFile($packages[0],$packages[1],$packages[2]);
		}
	}
}

/*!
 * This function loads a single file into the system
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 * @param $group Package Group
 * @param $package Package name
 * @param $type Package Type (pkg, class, abstract)
 * @param $pkg Is custom package?
 *
 * @return true on success, false on failure
 */
function mginit_loadFile($group,$package,$type,$pkg=false){
	$path = ($pkg?$GLOBALS['MG']['CFG']['PATH']['PKG']:$GLOBALS['MG']['CFG']['PATH']['INC']).$group.'/'.$package.'.'.$type.PHPEXT;
	if(!include($path)){
		trigger_error('(INI): Could not load package: '.$path,E_USER_ERROR);
		return false;
	}
	return true;
}

/*!
 * This function checks to see if an array has a string key
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 * @param $array Array to check
 *
 * @return true if string key, false if int key
 */
function mg_isArrayAssoc($array){
	if(!is_array($array)){
		return false;
	}
	if(count($array)==0){
		return false;
	}
	reset($array);
	$v = key($array);
	return is_string($v);
}

/*!
 * This function converts a string bool to a bool
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 * @param $value Value to convert
 *
 * @return boolean value
 */
function mg_toBool($value){
	return (empty($value) || $value=='false')?false:true;
}

/*!
 * This function encodes an array into JSON form
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 * @param $array Array to encode
 * @param $inline Double escape all items for inline loading
 *
 * @return JSON string
 */
function mg_jsonEncode($array,$inline=false){
    if($inline){
        $search = array("\\","\\\\","'");
        $replace = array("\\\\","\\\\\\\\","\\'");
        return str_replace($search, $replace, json_encode($array));  
    }
    else{
        return json_encode($array);
    }
}

function mg_changeDb($db,$revert,&$q){
	if(isset($db)){
		if($revert){
			$q[] = array(
				'type'=>DB_SELECT_DATABASE,
				'db'=>$GLOBALS['MG']['CFG']['DB']['DB']
			);
		}
		else{
			$q[] = array(
				'type'=>DB_SELECT_DATABASE,
				'db'=>$db
			);
		}
	}
}

function mg_generateURL($filename,$args,$absolute=false,$short=false){
    $ret = '';
    if($absolute){
        $ret .= ($GLOBALS['MG']['SITE']['SSL'])?'https://':'http://';
        $ret .= $GLOBALS['MG']['CFG']['SITE']['HOSTNAME'];
    }
    $ret .= $GLOBALS['MG']['CFG']['SITE']['URI'].$filename;
    if(!$args || count($args) == 0){
        return $ret;
    }
    if($short){
        foreach($args as $key=>$val){
            $ret.='/'.$key.'/'.$val;
        }
    }
    else{
        $first = true;
		$ret.='?';
        foreach($args as $key=>$val){
            if(!$first){
                 $ret.='&amp;';
            }
            $first = false;
            $ret.=$key.'='.$val;
        }
    }
    return $ret;
}
