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
class c_provider{

	//Private Vars
	private $id;		/*!< Provider ID */
	private $error;		/*!< Provider Init Error Code */
	private $data;		/*!< Provider Data */
	private $packages;	/*!< Package List Object */
	private $tpl;		/*!< Template Object */
	
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
		$this->id = $id;
		$this->error = 0;
		
		//get page data
		$r = $GLOBALS['MG']['DB']->db_query(array(
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.((LOAD_TYPE==1)?'c_providers':'c_apis'),
				'conds'=>array(
					array(DB_STD,((LOAD_TYPE==1)?'provider_id':'api_id'),'=',$id)
				)
			),
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.'packages',
				'conds'=>false
			)
		));

		if(!$r[0]['done']){
			trigger_error('(c_provider): Could not query database: '.$r[0]['error'],E_USER_ERROR);
			return;
		}
		else if(!isset($r[0]['result'][0])){
			trigger_error('(c_provider): Page '.$id.' not found',E_USER_WARNING);
			$this->error = 404;
			return;
		}
		
		//setup globals
		$GLOBALS['MG']['SITE']['PAGE_PATH'] = $GLOBALS['MG']['V']['GET']['PAGE'];
		$GLOBALS['MG']['SITE']['PAGE_PATH_EXP'] = explode('-',$GLOBALS['MG']['V']['GET']['PAGE']);
		$GLOBALS['MG']['SITE']['TPL_BASE'] = $GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$GLOBALS['MG']['LANG']['NAME'].'/tpl/';
		$GLOBALS['MG']['SITE']['PAGE_TPL'] = $GLOBALS['MG']['SITE']['TPL_BASE'].'/pages/'.implode('/',$GLOBALS['MG']['SITE']['PAGE_PATH_EXP']).'.tpl';
		
		//setup template
		$this->tpl = new template(array(
			'SERVER_NAME'=>$_SERVER['SERVER_NAME'],
			'SSL'=>(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'1':'0',
			'REQUEST_URI'=>$_SERVER['REQUEST_URI'],
			'SERVER_SOFTWARE'=>$_SERVER['SERVER_SOFTWARE'],
			'SERVER_NAME'=>$_SERVER['SERVER_NAME'],
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
		
		//parse page data
		$this->data = $r[0]['result'][0];
		$this->data['allow_cache'] = (bool)$this->data['allow_cache'];
		
		$this->packages = new packages($r[1],array($this->data['package']),$id);
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