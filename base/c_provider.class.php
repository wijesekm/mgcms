<?php
/*!
 * @file		c_provider.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-25-2015
 * @section DESCRIPTION
 * This file contains the class which generates the static
 * data for page generation.
 *
 * @todo Caching
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

/*! Provider Class */
class c_provider extends content{
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	
	/*!
	 * This is the class constructor which loads the provider data
	 *
	 * @author Kevin Wijesekera
	 * @date 3-25-2015
	 *
	 * @param $id Content ID to load
	 */
	public function __construct($id){
		$this->c_init($id);
	}
	
	/*!
	 * This function generates the content
	 *
	 * @author Kevin Wijesekera
	 * @date 3-25-2015
	 *
	 * @param $id Content ID to load
	 */
	public function c_run(){
		$content = array('res'=>'');
		if($this->error == 0){
			$content['res'] = $this->packages->pkgs_run($this->data['package'],'hook_static',$this->error);
		}
		$content['code']= $this->error;
		if($this->error != 200){
			$content['errors'] = $GLOBALS['MG']['LOG']->l_getAll();
		}
		return mg_jsonEncode($content);
	}
}