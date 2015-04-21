<?php
/*!
 * @file		database.abstract.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-1-2015
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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/.
 * ###################################
 */
 
if(!defined('INIT')){
	die();
}

class mysql extends database{
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	 
	/*!
	 * This function initializes a connection to the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $host Connection string for database
	 * @param $user Connection user account
	 * @param $password Connection password
	 * @param $initialDB Initial database to use
	 *
	 * @return true on success, false on failure
	 */
	final public function db_connect($host,$user,$password,$initalDB){
		if($this->db){
			trigger_error('(mysql): Database connection already established',E_USER_NOTICE);
			return true;
		}
		
		$this->db = mysqli_init();
		if(!$this->db){
			trigger_error('(mysql): Init failed',E_USER_WARNING);
			return false;
		}
		
		//set timeout
		if(!@$this->db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)){
			trigger_error('(mysql): Could not set options',E_USER_WARNING);
		}
		
		//set ssl
		if(!empty($this->cfg['sslkey'])){
			if(!@$this->db->ssl_set($this->cfg['sslkey'],$this->cfg['sslcert'],$this->cfg['sslca'],NULL,NULL)){
				trigger_error('(mysql): Could not set SSL...defaulting to OFF',E_USER_WARNING);
			}
		}
		
		//parse connection string
		//host:3306 or host:/tmp/stuff
		$tmp = explode(':',$host);
		$host = $tmp[0];
		$port = false;
		$socket = false;
		if(count($tmp) > 1){
			if($tmp[1][0] == '/'){
				$socket = $tmp[1];
			}
			else{
				$port = $tmp[1];
			}
		}
		
		if(!@$this->db->real_connect($host,$user,$password,$initalDB,$port,$socket)){
            $this->db = false;
			trigger_error('(mysql): Could not connect to database!'.$this->db->connect_error,E_USER_WARNING);
			return false;
		}
		$this->cfg['current_db']=$initalDB;
		return true;
	}
	
	/*!
	 * This function closes a connection to the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @return true on success, false on failure
	 */
	final public function db_close(){
		if(!$this->db){
			return true;
		}
		if(!$this->db->close()){
			trigger_error('(mysql): Could not close connection.'.$this->db_getLastError(),E_USER_WARNING);
            $this->db = false;
			return false;
		}
        $this->db = false;
		return true;
		
	}
	
	/*!
	 * This function checks a connection to the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @return true if OK, false if not
	 */
	final public function db_connectionOK(){
		if(!$this->db){
			return false;
		}
		return $this->db->ping();
	}
	
	/*!
	 * This function returns information on the database
	 *
	 * Types:
	 * DB_INFO_CLIENT_VER - Client version
	 * DB_INFO_SERVER_VER - Server version
	 * DB_INFO_PROTOCOL_VER - Protocol version
	 * DB_INFO_THREAD - Thread ID
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $type Information type to get
	 *
	 * @return database information
	 */
	final public function db_info($type){
		if(!$this->db){
			return false;
		}
		switch($type){
			case DB_INFO_CLIENT_VER:
				return $this->db_client_version;
			break;
			case DB_INFO_SERVER_VER:
				return $this->db->server_version;
			break;
			case DB_INFO_PROTOCOL_VER:
				return $this->db->protocol_version;
			break;
			case DB_INFO_THREAD:
				return $this->db->thread_id;
			break;
			default:
			
			break;
		}
		return false;
	}
	
	/*!
	 * This function changes the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $database Database to change to
	 *
	 * @return true on sucess, false on failure
	 */
	final public function db_switch($database){
		if(!$this->db){
			return false;
		}
		if(!$this->db->select_db($database)){
			trigger_error('(mysql): Could not change to database '.$database.' '.$this->db_getLastError(),E_USER_WARNING);
			return false;
		}
		$this->cfg['current_db']=$database;
		return true;
	}
	
	/*!
	 * This lists the tables in the current database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @return false on failure or table list array
	 */
	final public function db_list(){
		$this->from_self = true;
		$res = $this->db_query(array(
			array('type'=>DB_RAW_QUERY,'q'=>'SHOW TABLES;')
		),true,MYSQLI_NUM);
		if(!$res[0]['done']){
			trigger_error('(mysql): Could not list tables: '.$res[0]['error'],E_USER_WARNING);
			return array();
		}
		return $res[0]['result'][0];
	}
	
	/*!
	 * This function gets the errors from the last executed query
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @return string of all errors or false if none
	 */
	final public function db_getLastError(){
		if(!$this->db){
			return false;
		}
		
		$ret = '';
		
		foreach($this->db->error_list as $key=>$val){
			$ret.='#'.$val['errno'].': '.$val['error'].' -- ';
		}
		return $ret;
	}

	/*!
	 * This function runs one or more queries against the 
	 * database:
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $query_data Array containing one or more queries
	 * @param $res_type Type of result to return
	 *
	 * @return array with return data or return codes for each query
	 */
	final public function db_query($query_data,$res_type=MYSQLI_ASSOC){
		if(!$this->db){
			return false;
		}
		$returnData = array();
		$index = 0;
		$multi_query = array(
			's'=>'',
			'result'=>array(),
			'c'=>0
		);
		
		$transaction = array();
		$log = '';
		$start_db = $this->cfg['current_db'];
		
		//make query string
		/*
		* mysqli_multi_query does not work well with commands that don't return a result set.
		* There is really no way to get the return status of an individual command that does not have a result
		* which makes it impossible to get the status of each command.....
		*
		* Therefore transaction commands will be piped and run individually until a better way of doing this
		* is put into place....
		*/
		foreach($query_data as $key=>$val){
			if(isset($val['table'])){
				$val['table'] = '`'.$this->db->escape_string($val['table']).'`';
			}
			switch($val['type']){
				case DB_RAW_QUERY:
					if(!$this->from_self){
						trigger_error('(mysql): Raw queries should not be run by user',E_USER_NOTICE);
					}
					//NEEDS WORK......
					$multi_query['s'] .= $val['q'].';';
					$multi_query['result'][] = true;
					$multi_query['c']++;
				break;
				case DB_SELECT:
					if(!empty($val['db']) && $val['db'] != $this->cfg['current_db']){
						$this->cfg['current_db'] = $val['db'];
						$multi_query['s'] .= 'USE `'.$this->db->escape_string($val['db']).'`;';
						$multi_query['result'][] = false;
						$multi_query['c']++;
					}
					$multi_query['s'] .= $this->db_formatSelect($val).';';
					$multi_query['result'][] = true;
					$multi_query['c']++;
				break;
				case DB_INSERT:
					if(!empty($val['db']) && $val['db'] != $this->cfg['current_db']){
						$this->cfg['current_db'] = $val['db'];
						$transaction[] = array('s'=>'USE `'.$this->db->escape_string($val['db']).'`;','result'=>false);
					}
					$q = $this->db_formatInsert($val).';';
					$log .= $q;
					$transaction[] = array('s'=>$q,'result'=>true);
				break;
				case DB_UPDATE:
					if(!empty($val['db']) && $val['db'] != $this->cfg['current_db']){
						$this->cfg['current_db'] = $val['db'];
						$transaction[] = array('s'=>'USE `'.$this->db->escape_string($val['db']).'`;','result'=>false);
					}
					$q = $this->db_formatUpdate($val).';';
					$log .= $q;
					$transaction[] = array('s'=>$q,'result'=>true);
				break;
				case DB_DELETE:
					if(!empty($val['db']) && $val['db'] != $this->cfg['current_db']){
						$this->cfg['current_db'] = $val['db'];
						$transaction[] = array('s'=>'USE `'.$this->db->escape_string($val['db']).'`;','result'=>false);
					}
					$q = $this->db_formatDelete($val).';';
					$log .= $q;
					$transaction[] = array('s'=>$q,'result'=>true);
				break;
				case DB_CREATE_DATABASE:
					$q = 'CREATE DATABASE `'.$this->db->escape_string($val['db']).'`;';
					$log .= $q;
					$transaction[] = array('s'=>$q,'result'=>true);
				break;
				case DB_DROP_DATABASE:
					$q = 'DROP DATABASE `'.$this->db->escape_string($val['db']).'`;';
					$log .= $q;
					$transaction[] = array('s'=>$q,'result'=>true);
				break;
				case DB_CREATE_TABLE:
				
				break;
				case DB_ALTER_TABLE:
				
				break;
				case DB_DROP_TABLE:
					$q = 'DROP TABLE `'.$val['table'].'`;';
					$log .= $q;
					$transaction[] = array('s'=>$q,'result'=>true);
				break;
			};
		}
		
		if($this->cfg['current_db'] != $start_db){
			$this->cfg['current_db'] = $start_db;
			if($multi_query['c'] != 0){
				$multi_query['s'] .= 'USE `'.$this->db->escape_string($start_db).'`;';
				$multi_query['result'][] = false;
				$multi_query['c']++;
			}
			else{
				$transaction[] = array('s'=>'USE `'.$this->db->escape_string($start_db).'`;','result'=>false);
			}
		}
		
		//if debug is enabled lets print the query.....
		if($this->debug){
			echo "----\r\n";
			echo preg_replace('/;/',"\r\n",$multi_query['s'])."\r\n";
			echo $log."\r\n";
			echo "----\r\n";
		}
		
		//if logging is enabled lets log the query to the database.....
		if($this->cfg['logging']){
			$GLOBALS['MG']['LOG']->l_message(L_DATABASE,'Query: '.$multi_query['s'].';'.$log,'mysql.class.php');
		}
		
		//run transactions
		$nextBad = false;
		foreach($transaction as $val){
			if(!$this->db->query($val['s']) || $nextBad){
				if(!$val['result']){
					$nextBad = true;
				}
				else{
					$returnData[$index]['error']=$this->db_getLastError();
					$returnData[$index]['done']=false;
					$index++;
				}
				continue;
			}
			if($val['result']){
				$returnData[$index]['done']=true;
				$returnData[$index]['rows']=$this->db->affected_rows;
				$index++;
			}
		}
		
		//run multi_query
		if(!empty($multi_query['s'])){
			$this->db->multi_query($multi_query['s']);
			for($i=0;$i<$multi_query['c'];$i++){
				if($multi_query['result'][$i]){
					if($result = $this->db->store_result()){
						$returnData[$index] = array();
						$returnData[$index]['done'] = true;
						$returnData[$index]['row'] = $result->num_rows;
						$returnData[$index]['col'] = $result->field_count;
						$returnData[$index]['result'] = $result->fetch_all($res_type);
						$result->free();
					}
					else{
						$returnData[$index] = array();
						$returnData[$index]['done'] = true;
						$returnData[$index]['error'] = $this->db_getLastError();
					}
					$index++;
				}
				if($this->db->more_results()){
					$this->db->next_result();
				}
			}
		}
		$this->from_self = false;
		return $returnData;
	}

	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */
	 
	/*!
	 * This function formats an UPDATE SQL command
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $data Array containing query data
	 *
	 * @return query string
	 */
	private function db_formatUpdate($data){
		$q = 'UPDATE '.$data['table'].' SET ';
		$start = false;
		foreach($data['changes'] as $key=>$val){
			if($start){
				$q.=',';
			}
			$start = true;
			$this->db_formatColumn(false,$key,$q);
			$q.='=';
			$this->db_formatdata($val,$q);
		}
		$q.=' WHERE ';
		$this->db_formatConditions($data['conds'],$data['table'],$q);
		return $q;
	}
	 
	/*!
	 * This function formats an INSERT SQL command
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $data Array containing query data
	 *
	 * @return query string
	 */
	private function db_formatInsert($data){
		$start = false;
		$q = 'INSERT INTO '.$data['table'];

		if(!empty($data['cols'])){
			$q.= '(';
			foreach($data['cols'] as $id){
				if($start){
					$q.=',';
				}
				$start = true;
				$this->db_formatColumn(false,$id,$q);
			}
			$q.=')';
		}
		
		$q .= ' VALUES ';
		$start = false;
		$start1 = false;
		foreach($data['rows'] as $ins){
			if($start){
				$q.=',';
			}
			$start = true;
			$q.='(';
			$start1 = false;
			foreach($ins as $item){
				if($start1){
					$q.=',';
				}
				$start1 = true;
				$this->db_formatdata($item,$q);
			}
			$q.=')';
		}
		return $q;
	}
	 
	/*!
	 * This function formats a DELETE or TRUNCATE SQL command
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $data Array containing query data
	 *
	 * @return query string
	 */
	private function db_formatDelete($data){
		//truncate
		if(empty($data['conds']) || (!is_array($data['conds']) || $data['conds'] == '*')){
			$q = 'TRUNCATE TABLE '.$data['table'];
		}
		//delete
		else{
			$q = 'DELETE FROM '.$data['table'].' WHERE ';
			$this->db_formatConditions($data['conds'],$data['table'],$q);
		}
		return $q;
	}
	 
	/*!
	 * This function formas a SELECT SQL command
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $data Array containing query data
	 *
	 * @return query string
	 */
	private function db_formatSelect($data){
		$start = false;
		$q = 'SELECT ';
		if(!empty($data['distinct'])){
			$q.='DISTINCT ';
		}

		//format fields
		if(!empty($data['fields'])){
			foreach($data['fields'] as $id=>$val){
				if($start){
					$q.=', ';
				}
				$start = true;
				if($val == false){
					$this->db_formatColumn($data['table'],$id,$q);
				}
				else if($val[0] == DB_FUNCT){
					//only allow alphanumeric characters
					if(ctype_alpha($val[1])){
						$q .= $val[1].'(';
						$this->db_formatColumn($data['table'],$id,$q);
						$q .= ')';
						if(!empty($val[2])){
							$q .=' AS \''.$this->db->escape_string($val[2]).'\'';
						}
					}
					//if function is incorrect just display the column
					else{
						$this->db_formatColumn($data['table'],$id,$q);
					}
				}
				else{
					$this->db_formatColumn($data['table'],$id,$q);
					$q .= ' AS \''.$this->db->escape_string($val[0]).'\'';
				}
			}	
		}
		else{
			$q.=' *';
		}

		
		//table
		$q.=' FROM '.$data['table'];
		
		//join
		if(!empty($data['join'])){
			switch($data['join'][0]){
				case DB_LEFT_JOIN:
					$q.= '  LEFT JOIN `';
				break;
				case DB_RIGHT_JOIN:
					$q.=' RIGHT JOIN `';
				break;
				case DB_FULL_JOIN:
					$q.=' FULL JOIN `';
				break;
				default:
				case DB_INNER_JOIN:
					$q.=' INNER JOIN `';
				break;
			};
			$q .= $this->db->escape_string($data['join'][1]).'` ON ';
			$this->db_formatColumn($data['table'],$data['join'][2],$q);
			$q.='=';
			$this->db_formatColumn($data['table'],$data['join'][3],$q);
		}
		
		$q .=' WHERE ';
		//format conditions
		if(!empty($data['conds'])){
			$this->db_formatConditions($data['conds'],$data['table'],$q);
		}
		else{
			$q.='1';
		}
		
		//order by
		if(!empty($data['orderby'])){
			$q.=' ORDER BY ';
			$start = false;
			foreach($data['orderby'] as $key=>$val){
				if($start){
					$q.=', ';
				}
				$start = true;
				$this->db_formatColumn($data['table'],$key,$q);
				if($val == DB_ASCENDING){
					$q.= ' ASC';
				}
				else if($val == DB_DESCENDING){
					$q.= ' DESC';
				}
			}
		}
		
		//TODO Group By
		if(!empty($data['groupby'])){
			$q.=' GROUP BY ';
			$start = false;
			foreach($data['groupby'] as $val){
				if($start){
					$q.=', ';
				}
				$start = true;
				$this->db_formatColumn($data['table'],$val,$q);
			}
		}
		
		//TODO Having
		if(!empty($data['having']) && isset($data['having'][0]) && ctype_alpha($data['having'][0])){
			$q .= ' HAVING '.$data['having'][0].'(';
			$this->db_formatColumn($data['table'],$data['having'][1],$q);
			$q .= $this->db->escape_string($data['having'][2]);
			$this->db_formatdata($data['having'][3],$q);
		}
		
		//limit
		if(!empty($data['limit'])){
			$q .=' LIMIT '.$this->db->escape_string($data['limit'][0]);
			if(isset($data['limit'][1])){
				$q .=' LIMIT '.$this->db->escape_string($data['limit'][1]).','.$this->db->escape_string($data['limit'][0]);
			}
		}
		return $q;
	}

	/*!
	 * This function formats a sql data item
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $item Value to add to query
	 * @param $q Query string
	 */
	private function db_formatData($item,&$q){
		if(is_array($item) || is_object($item)){
			return;
		}
		if(is_int($item) || is_float($item) || is_bool($item)){
			if(empty($item)){
				$item = '0';
			}
			$q.= (string)$item;
		}
		else {
			$q.= '\''.$this->db->escape_string($item).'\'';
		}
	}

	/*!
	 * This function formats a column
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $tbl Table Name
	 * @param $col Column Name
	 * @param $q Query string
	 */
	private function db_formatColumn($tbl,$col,&$q){
		if(strpos($col,'.') > 0){
			$q .= '`'.str_replace('.','`.`',$col).'`';
		}
		else if(!empty($tbl)){
			$q.= $tbl.'.`'.$this->db->escape_string($col).'`';
		}
		else{
			$q.= '`'.$this->db->escape_string($col).'`';
		}
	}

	/*!
	 * This function formats WHERE clause conditionals
	 *
	 * @author Kevin Wijesekera
	 * @date 3-17-2015
	 *
	 * @param $data WHERE data
	 * @param $tbl Table Name
	 * @param $query Query string
	 */
	private function db_formatConditions($data,$tbl,&$query){
		
		//handle AND or OR
		if(!is_array($data)){
			echo $data;
			if($data == DB_AND){
				$query.=' AND ';
			}
			else{
				$query.=' OR ';
			}
		}
		//process group
		else{
			$query .= '(';
			foreach($data as $val){
				//AND or OR
				if(!is_array($val)){
					if($val == DB_AND){
						$query.=' AND ';
					}
					else{
						$query.=' OR ';
					}
				}
				//sub group
				else if(is_array($val[0])){
					$this->db_formatConditions($val,$tbl,$query);
				}
				//item
				else{
					switch($val[0]){
						case DB_BETWEEN:
							$this->db_formatColumn($tbl,$val[1],$q);
							$query .= ' BETWEEN \''.$this->db->escape_string($val[2]).'\' AND \''.$this->db->escape_string($val[3]).'\'';
						break;
						case DB_IN:
							//$query .= '`'.$this->db->escape_string($val[1]).'` ';
							//$query.=' IN \''.$this->db->escape_string($val[3]).'\'';
						break;
						case DB_REGEX:
							$this->db_formatColumn($tbl,$val[1],$q);
							if($val[2] == '<>'){
								$query.=' NOT';
							}
							$query.=' REGEXP \''.$this->db->escape_string($val[3]).'\'';
						break;
						case DB_LIKE:
							$this->db_formatColumn($tbl,$val[1],$q);
							if($val[2] == '<>'){
								$query.=' NOT';
							}
							$query.=' LIKE \''.$this->db->escape_string($val[3]).'\'';
						break;
						case DB_STD:
						default:
							$this->db_formatColumn($tbl,$val[1],$query);
							//NULL case
							if($val[3] === null){
								if($val[2] == '='){
									$query .= ' IS NULL';
								}
								else{
									$query.=' NOT NULL';
								}
							}
							else{
								$query.=$this->db->escape_string($val[2]).'\''.$this->db->escape_string($val[3]).'\'';
							}
						break;
					}
				}
			}
			$query .= ')';
		}
	}
}
