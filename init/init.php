<?php
/*!
 * @file		init.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-11-2015
 * @section DESCRIPTION
 * This file initializes the system core switching based on the LOAD_TYPE
 *
 * LOAD_TYPE - 
 * 0 - Static (GET,COOKIE)
 * 1 - Dynamic (GET,COOKIE,POST,PUT,DELETE)
 * 2 - API (GET,POST,PUT,DELETE)
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

//set the default timezone before anything else to prevent errors
date_default_timezone_set($GLOBALS['MG']['CFG']['SITE']['TIME_ZONE']);

//initalize global variable bases
$GLOBALS['MG']['V'] = array();
$GLOBALS['MG']['SITE']=array();

//path init
if(isset($GLOBALS['MG']['CFG']['PATH']['TMP'])){
	if(!is_dir($GLOBALS['MG']['CFG']['PATH']['TMP'])){
		mkdir($GLOBALS['MG']['CFG']['PATH']['TMP'],0777,true);
	}
}

//load functions
include('functions.init'.PHPEXT);

//load error logging
mginit_load(array(array('base','logger','class')));
$GLOBALS['MG']['LOG']=new logger($GLOBALS['MG']['CFG']['SITE']['DEFAULT_LANG']);

//load base packages
mginit_load(array(
	array('init','time','init'),
	array('init','varparse','init'),
	array('auth','acl','class'),
	array('base','packages','class'),
	array('base',(LOAD_TYPE==0)?'c_static':'c_provider','class'),
	array('auth','session','class'),
	array('db','database','abstract'),
	array('db',$GLOBALS['MG']['CFG']['DB']['METHOD'],'class'),
	array('act','account','abstract'),
	array('act',$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TYPE'],'class'),
	array('tpl','template','class'),
	array('tpl','parser','class'),
	array('email','mail_router','class')
));

//setup DB$this->objs[$package] = new $package();
$GLOBALS['MG']['DB'] = new $GLOBALS['MG']['CFG']['DB']['METHOD']();
if(empty($GLOBALS['MG']['DB'])){
	trigger_error('(init): Invalid DB method or no method set!', E_USER_ERROR);
}
else{
	$GLOBALS['MG']['DB']->db_cfg_logging($GLOBALS['MG']['CFG']['DB']['LOG']);
	$t=$GLOBALS['MG']['DB']->db_connect($GLOBALS['MG']['CFG']['DB']['HOST'],$GLOBALS['MG']['CFG']['DB']['USERNAME']
		,$GLOBALS['MG']['CFG']['DB']['PASSWORD'],$GLOBALS['MG']['CFG']['DB']['DB']);
	if(!$t){
		trigger_error('(INI): Could not connect to database!', E_USER_ERROR);
	}
}

//load user + vars + etc
mginit_loadVars();

//load session + account
$ses=new session(0);
if(LOAD_TYPE == 2){
	$GLOBALS['MG']['V']['COOKIE']['AUTH_USER'] = $GLOBALS['MG']['V']['POST']['AUTH_USER'];
	$GLOBALS['MG']['V']['COOKIE']['AUTH_SESSION'] = $GLOBALS['MG']['V']['POST']['AUTH_SESSION'];
}
if(!$ses->ses_check($GLOBALS['MG']['V']['COOKIE']['AUTH_USER'],$GLOBALS['MG']['V']['COOKIE']['AUTH_SESSION'])){
	$GLOBALS['MG']['V']['COOKIE']['AUTH_USER'] = false;
}
$GLOBALS['MG']['USER'] = new $GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TYPE']($GLOBALS['MG']['V']['COOKIE']['AUTH_USER']);

//load impersonate user
if(!empty($GLOBALS['MG']['SITE']['ALLOW_IMPERSONATE']) && !empty($GLOBALS['MG']['V']['COOKIE']['AUTH_ALT_USER'])){
	$GLOBALS['MG']['USER']->act_setImpersonate($GLOBALS['MG']['V']['COOKIE']['AUTH_ALT_USER']);
}

//set user language
$lang = $GLOBALS['MG']['USER']->act_get('language');
if($GLOBALS['MG']['CFG']['SITE']['LANG_ALLOW_OVERRIDE']){
	if(!empty($GLOBALS['MG']['V']['GET']['LANGUAGE'])){
		$lang = $GLOBALS['MG']['V']['GET']['LANGUAGE'];
		@setcookie(
			$GLOBALS['MG']['CFG']['SITE']['SESSION_COOKIE'].'lang',
			$lang,
			0,
			$GLOBALS['MG']['CFG']['SITE']['COOKIE_PATH'],
			$GLOBALS['MG']['CFG']['SITE']['COOKIE_DOM'],
			$GLOBALS['MG']['CFG']['SITE']['COOKIE_SECURE']
		);
	}
	else if(!empty($GLOBALS['MG']['V']['COOKIE']['LANGUAGE'])){
		$lang = $GLOBALS['MG']['V']['COOKIE']['LANGUAGE'];
	}
}

if(empty($lang) || !is_file($GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$lang.'/init.php')){
	trigger_error('(init): System could not find language '.$lang.' reverting to default',E_USER_NOTICE);
	$lang = $GLOBALS['MG']['CFG']['SITE']['DEFAULT_LANG'];
}

if(!include_once($GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$lang.'/init.php')){
	trigger_error('(init): Could not load language file!',E_USER_ERROR);
}

//set some constants
$GLOBALS['MG']['LOG']->lang = $lang;
$GLOBALS['MG']['LOG']->uid = $GLOBALS['MG']['USER']->act_get('id');
$GLOBALS['MG']['SITE']['OS'] = substr(PHP_OS,0,3);

//load time data
mginit_time($GLOBALS['MG']['CFG']['SITE']['TIME_ZONE'],$GLOBALS['MG']['USER']->act_get('timezone'));

//update cookies
if(LOAD_TYPE == 0 && $GLOBALS['MG']['USER']->act_isAuth()){
	$ses->stime = $GLOBALS['MG']['USER']->act_get('time');
	$ses->ses_update($GLOBALS['MG']['V']['COOKIE']['AUTH_USER'],$GLOBALS['MG']['V']['COOKIE']['AUTH_SESSION']);
}

//setup mail router
$GLOBALS['MG']['MAIL'] = new mail_router();

//load page data
if(LOAD_TYPE==0){
	$page = new c_static($GLOBALS['MG']['V']['GET']['PAGE']);
}
else{
	$page = new c_provider($GLOBALS['MG']['V']['GET']['PAGE']);
}

//error check
$GLOBALS['MG']['LOG']->l_fatalCheck();

//get content
$content = $page->c_run();

//cleanup resources
$GLOBALS['MG']['MAIL']->m_processQueue();
$GLOBALS['MG']['DB']->db_close();
$GLOBALS['MG']['LOG']->l_writeToFile();
