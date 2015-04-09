<?php
/*!
 * @file		database.abstract.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-17-2015
 * @section DESCRIPTION
 * This file contains the abstract database class which is designed to
 * act as a container for all database related classes.
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
 * You should have received a copy of t, see http://www.gnu.org/licenses/.
 * ###################################
 */
 
if(!defined('INIT')){
	die();
}


//defines for db_info
define('DB_INFO_CLIENT_VER',0);
define('DB_INFO_SERVER_VER',1);
define('DB_INFO_PROTOCOL_VER',2);
define('DB_INFO_THREAD',3);

//defines for query_type	
define('DB_RAW_QUERY',0);
define('DB_SELECT',1);
define('DB_INSERT',2);
define('DB_UPDATE',3);
define('DB_DELETE',4);
define('DB_SELECT_DATABASE',5);
define('DB_CREATE_DATABASE',6);
define('DB_CREATE_TABLE',7);
define('DB_ALTER_TABLE',8);
define('DB_DROP_TABLE',9);
define('DB_DROP_DATABASE',10);

//defines for fields
define('DB_FUNCTION',0);

//defines for conds
define('DB_STD',0);
define('DB_BETWEEN',1);
define('DB_IN',2);
define('DB_REGEX',3);
define('DB_LIKE',4);
define('DB_AND',5);
define('DB_OR',6);

//select
define('DB_ASCENDING',1);
define('DB_DESCENDING',2);
define('DB_INNER_JOIN',3);
define('DB_LEFT_JOIN',4);
define('DB_RIGHT_JOIN',5);
define('DB_FULL_JOIN',6);

/*! Database abstract class */
abstract class database{
	
	//protected vars
	protected $db;			/*!< Database access object */
	protected $cfg;			/*!< Database configuration */
	protected $debug;		/*!< Enable Debug */
	protected $from_self;	/*!< Last query was internal */
	
	/* ######################
	 * ABSTRACT FUNCTIONS
	 * ######################
	 */
	abstract public function db_connect($host,$user,$password,$initalDB);
	abstract public function db_close();
	abstract public function db_connectionOK();
	abstract public function db_info($type);
	abstract public function db_switch($database);
	abstract public function db_list();
	abstract public function db_getLastError();
	abstract public function db_query($query_data);
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	 
	/*!
	 * This function sets up the class
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $debug Turn on debugging
	 */
	final public function __construct($debug=false){
		$this->debug = $debug;
		$this->db = null;
		$this->cfg = array(
			'persistent'=>false,
			'logging' => false,
			'sslkey'=>'',
			'sslcert'=>'',
			'sslca'=>'',
			'lastdb'=>''
		);
		$this->from_self = false;
	}
	
	/*!
	 * This function configures persistent access to the DB
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $value true for persistent mode, false otherwise
	 */
	final public function db_cfg_persistent($value){
		if($this->db != null){
			trigger_error('(database): db_cfg_persistent should be called while db is idle',E_USER_WARNING);
			return;
		}
		$this->cfg['persistent']=$value;
	}
	
	/*!
	 * This function configures debugging on the DB
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $value true for debug mode, false otherwise
	 */
	final public function db_cfg_debug($value=true){
		$this->debug = $value;
	}
	
	/*!
	 * This function configures database logging
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $value table name for logging, false otherwise
	 */
	final public function db_cfg_logging($log_to=false){
		$this->cfg['logging'] = $log_to;
	}
	
	/*!
	 * This function configures ssl access on the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $key Path to SSL Key
	 * @param $cert Path to SSL Cert
	 * @param $ca Path to SSL CA
	 */
	final public function db_cfg_ssl($key,$cert,$ca){
		if($this->db != null){
			trigger_error('(database): db_cfg_persistent should be called while db is idle',E_USER_WARNING);
			return;
		}
		$this->cfg['sslkey']=$key;
		$this->cfg['sslcert']=$cert;
		$this->cfg['sslca']=$ca;
	}
}
