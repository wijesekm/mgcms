<?php
/*!
 * @file		parser.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-23-2015
 * @section DESCRIPTION
 * This file contains the parser class which is used to parse templates
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

/*!< Parser Class */
class parser{

	private $vars;	/*!< Container for all variables */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */

	/*!
	 * This function sets the variable list
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $vars vars list
	 */
	public function p_setVars($vars){
		$this->vars = $vars;
		foreach($this->vars as $key=>$val){
			$this->vars[$key] = $this->p_parseLanguage($val);
		}
	}

	/*!
	 * This function sets a variables value
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $var var key
	 * @param $value var value
	 */
	public function p_setVar($var,$value){
		$this->vars[$var] = $value;
	}

	/*!
	 * This function parses a block of text
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $text Text to parse
	 * @param $level Level to parse at (0 = none, 1 = PHP, Lang, vars, 2 - Custom)
	 * @param $rempty Remove empty tags
	 *
	 * @return parsed text
	 */
	public function p_parse($text,$level,$rempty=true){
		if($level > 0){
			$text = $this->p_compilePHP($text);
			$text = $this->p_parseLanguage($text);
			$text = $this->p_parseVars($text);
		}
		else if($level > 1){
			//custom - TODO
		}

		if($rempty){
			$text=preg_replace("/{[a-z0-9_-]+}/i","",$text);
		}
		return $text;
	}
	
	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */

	/*!
	 * This function parses any language tags in a block of text
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $text Text to parse
	 *
	 * @return parsed text
	 */
	private function p_parseLanguage($text){
		$matches = array();
		if(preg_match_all('/{LANG:([a-z0-9_-]+)}/i',$text,$matches) > 0){
			$soq = count($matches[1]);
			for($i=0;$i<$soq;$i++){
				$text = str_replace($matches[0][$i],$GLOBALS['MG']['LANG']['I'][$matches[1][$i]],$text);
			}
		}
		return $text;
	}

	/*!
	 * This function parses any var tags in a block of text
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $text Text to parse
	 *
	 * @return parsed text
	 */
	private function p_parseVars($text){
		$matches = array();
		if(preg_match_all('/{VAR\:([a-z0-9_-]+)}/i',$text,$matches) > 0){
			$soq = count($matches[1]);
			for($i=0;$i<$soq;$i++){
				$text = str_replace($matches[0][$i],$this->vars[$matches[1][$i]],$text);
			}
		}
		return $text;
	}
	
	/*!
	 * This function compiles any embedded PHP in the template
	 *
	 * @author Kevin Wijesekera
	 * @date 3-23-2015
	 *
	 * @param $text Text to parse
	 *
	 * @return parsed text
	 */
	private function p_compilePHP($text){
		if(preg_match_all('/<\!--TPL_CODE_START-->(.*?)<\!--TPL_CODE_END-->/s',$text,$matches)){
			$soq = count($matches[1]);
			for($i=0;$i<$soq;$i++){
				$retvar = '';
				$matches[1][$i] = $this->p_parseVars($matches[1][$i]);
				if(!eval($matches[1][$i])){
					trigger_error('(TEMPLATE): Compile Error or return value not specified!',E_USER_WARNING);
				}
				$text = str_replace($matches[0][$i],$retvar,$text);
			}
		}
		return $text;
	}
}
