<?php
/*!
 * @file		template.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-23-2015
 * @section DESCRIPTION
 * This file contains the template class which is used to parse templates
 * using a default parser + any custom parsers configured
 
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

/*!< Template Class */
class template{

	//private functions
	private $sections;		/*!< This contains all the template sections */
	private $parser;		/*!< Parser object */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */

	/*!
	 * This is the class constructor
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $vars initial vars list
	 */
	public function __construct($vars=array()){
		$this->sections = array();
		$this->parser = new parser();
		$this->parser->p_setVars($vars);
	}
	
	/*!
	 * This function loads a section of a template into the system
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $content File name or section content
	 * @param $section Section name
	 * @param $isFile True if it is a file
	 *
	 * @return true on success, false on failure
	 */
	public function tpl_load($content,$section,$isFile=true){
		if($isFile){
			if(!$f=fopen($content,'r')){
				trigger_error('(template): Cannot open template: '.$content, E_USER_WARNING);
				return false;
			}
			$start = false;
			$this->sections[$section] = '';
			while(!feof($f)){
				$tmp=fgets($f);
				if(!$start && preg_match('/<\!--TPL_START_'.$section.'-->/',$tmp)){
					$start = true;
				}
				else if($start){
					if(preg_match('/<\!--TPL_END_'.$section.'-->/',$tmp)){
						break;
					}
					$this->sections[$section] .= $tmp;
				}
			}
			fclose($f);
		}
		else{
			$this->sections[$section] = $content;
		}
		if(empty($this->sections[$section])){
			trigger_error('(template): No section found in template',E_USER_WARNING);
			return false;
		}
		return true;
	}
	
	/*!
	 * This function clears out any templates
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 */
	public function tpl_clear(){
		$this->sections = array();
	}
	
	/*!
	 * This function sets the parser vars
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $vars Array of parser vars
	 */
	public function tpl_setVars($vars){
		$this->parser->p_setVars($vars);
	}
	
	/*!
	 * This function sets a variables value
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $var Variable name
	 * @param $value Variable value
	 */
	public function tpl_setVar($var,$value){
		$this->parser->p_setVar($var,$value);
	}
	
	/*!
	 * This function compiles a template section
	 *
	 * Levels:
	 * 0 - Nothing
	 * 1 - Vars & Lang Only
	 * 2 - Vars, Lang & custom parsers
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $section Template section
	 * @param $level Compile level
	 * @param $rempy Remove empty content
	 *
	 * @return true on success, false on failure
	 */
	public function tpl_compile($section,$level=1,$rempty=false){
		if($section == '*'){
			foreach($this->sections as $key=>$val){
				$this->sections[$key] = $this->parser->p_parse($val,$level,$rempty);
			}
		}
		else{
			if(!isset($this->sections[$section])){
				trigger_error('(template): Section does not exists in template',E_USER_WARNING);
				return false;
			}
			$this->sections[$section] = $this->parser->p_parse($this->sections[$section],$level,$rempty);
		}
	}
	
	/*!
	 * This function returns a template section
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $section Template section
	 *
	 * @return false on failure or content
	 */
	public function tpl_return($section){
		if($section == '*'){
			$ret = '';
			foreach($this->sections as $key=>$val){
				$ret .= $val;
			}
			return $ret;
		}
		else{
			if(!isset($this->sections[$section])){
				trigger_error('(template): Section does not exists in template',E_USER_WARNING);
				return false;
			}
			return $this->sections[$section];
		}
	}
	
}