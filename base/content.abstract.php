<?php
/*!
 * @file		content.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-17-2015
 * @section DESCRIPTION
 * This file contains the class which acts as a base for delivering
 * different content methods
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

/*! Content Abstract Class */
abstract class content{

	//Protected Vars
	protected $id;				/*!< Page ID */
	protected $error = 0;		/*!< Page Init Error Code */
	protected $data;			/*!< Page Data */
	protected $packages;		/*!< Package List Object */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	
	/*!
	 * This is the class constructor which loads the Page data
	 *
	 * @author Kevin Wijesekera
	 * @date 3-25-2015
	 *
	 * @param $id Content ID to load
	 */
	final public function c_init($id){
		$this->id = $id;
		
		if(LOAD_TYPE != 3){
			//setup template
			$GLOBALS['MG']['SITE']['TPL_BASE'] = $GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$GLOBALS['MG']['LANG']['NAME'].'/tpl/';
			$GLOBALS['MG']['SITE']['PAGE_PATH'] = $id;
			$GLOBALS['MG']['SITE']['PAGE_PATH_EXP'] = explode('-',$id);
			$GLOBALS['MG']['SITE']['PAGE_TPL'] = $GLOBALS['MG']['SITE']['TPL_BASE'].'/pages/'.implode('/',$GLOBALS['MG']['SITE']['PAGE_PATH_EXP']).'.tpl';
			$GLOBALS['MG']['SITE']['ISAJAX'] = true;
			
			$GLOBALS['MG']['SITE']['TPL'] = new template(array(
				'SERVER_NAME'=>$_SERVER['SERVER_NAME'],
				'SSL'=>(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'1':'0',
				'REQUEST_URI'=>$_SERVER['REQUEST_URI'],
				'SERVER_SOFTWARE'=>$_SERVER['SERVER_SOFTWARE'],
				'SERVER_NAME'=>$_SERVER['SERVER_NAME'],
				'DEBUG'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']?'0':'1',
				'SERVER_SIGNATURE'=>$_SERVER['SERVER_SIGNATURE'],
				'SERVER_TZ'=>$GLOBALS['MG']['CFG']['SITE']['TIME_ZONE'],
				'LANGUAGE'=>$GLOBALS['MG']['LANG']['NAME'],
				'SERVER_TS'=>$GLOBALS['MG']['SITE']['TIME'],
				'YEAR'=>date('o',$GLOBALS['MG']['SITE']['TIME']),
				'USER_UID'=>$GLOBALS['MG']['USER']->act_get('id'),
				'USER_NAME'=>preg_replace('/  /',' ',$GLOBALS['MG']['USER']->act_get('name')),
				'USER_TZ'=>$GLOBALS['MG']['USER']->act_get('timezone'),
				'USER_ISAUTH'=>$GLOBALS['MG']['USER']->act_isAuth()?'1':'0',
				'USER_TS'=>$GLOBALS['MG']['USER']->act_get('time'),
				'ACL_ADMIN'=>$GLOBALS['MG']['USER']->acl->acl_check('*','full'),
				'ACL_FULL'=>$GLOBALS['MG']['USER']->acl->acl_check($GLOBALS['MG']['SITE']['PAGE_PATH'],'full')
			));
			
			//check acl
			if(!$GLOBALS['MG']['USER']->acl->acl_check($GLOBALS['MG']['SITE']['PAGE_PATH'],'access')){
				$this->error=403;
				return;
			}
		}
		
		//get page data
		$table = TABLE_PREFIX;
		if(LOAD_TYPE==1){
			$table.='c_providers';
		}
		else if(LOAD_TYPE==2){
			$table.='c_apis';
		}
		else{
			$table.='c_pages';
		}
		$r = $GLOBALS['MG']['DB']->db_query(array(
			array(
				'type'=>DB_SELECT,
				'table'=>$table,
				'conds'=>array(
					array(DB_STD,'c_id','=',$id)
				)
			),
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.'packages',
				'conds'=>false
			)
		));

		if(!$r[0]['done']){
			trigger_error('(content): Could not query database: '.$r[0]['error'],E_USER_ERROR);
			return false;
		}
		else if(!isset($r[0]['result'][0])){
			trigger_error('(content): Content '.$id.' not found',E_USER_WARNING);
			$this->error = 404;
			return false;
		}

		//parse page data
		$this->data = $r[0]['result'][0];
		$this->data['allow_cache'] = (bool)$this->data['allow_cache'];
		
		$this->packages = new packages($r[1],array($this->data['package']),$id);
		return true;
	}
	
	abstract public function __construct($id);
	abstract public function c_run();
}
