<?php
/*!
 * @file		packages.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-20-2015
 * @section DESCRIPTION
 * This file contains the class which provides an interface to the system
 * packages.
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

/*! Packages Class */
class packages{

	//Private Vars
	private $packages;			/*!< System Packages */
	private $objs;				/*!< Object Store */
	private $conds;				/*!< Configuration SQL conditionals */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	
	/*!
	 * This funciton loads all packages and then loads
	 * the page packages and configuration into hte system
	 * 
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $queryRes Result of query of all packages
	 * @param $toLoad Array of packages to load
	 * @param $page_id ID of page to load
	 *
	 */
	public function __construct($queryRes,$toLoad,$page_id){
		if(!is_array($toLoad)){
			return false;
		}
		$this->packages=array();
		//parse all packages
		if(!$queryRes['done']){
			trigger_error('(packages): Could not query database: '.$r[0]['error'],E_USER_ERROR);
			return;
		}

		$this->packages=array();
		foreach($queryRes['result'] as $val){
			if(!empty($val['pkg_id'])){
				$this->packages[$val['pkg_id']] = $val;
				$this->packages[$val['pkg_id']]['config']=array();
			}
		}
		
		//now load page packages into the system
        if(empty($toLoad[0])){
            trigger_error('(packages): No packages specified for page!',E_USER_WARNING);
            return;
        }
		$this->conds=array(array());
		$this->pkgs_loadHelper($toLoad);
		unset($this->conds[0][count($this->conds[0])-1]);
		$this->conds[] = DB_AND;
		$this->conds[]=array(
			array(DB_STD,'c_id','=','*'),
			DB_OR,
			array(DB_STD,'c_id','=',$page_id)
		);

		//now load configuration
		$r = $GLOBALS['MG']['DB']->db_query(array(
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.'packages_config',
				'conds'=>$this->conds
			)
		));
		
		if(!$r[0]['done']){
			trigger_error('(packages): Could not query database: '.$r[0]['error'],E_USER_ERROR);
			return;
		}
		foreach($r[0]['result'] as $val){
			if(!empty($val['pkg_id'])){
				$this->packages[$val['pkg_id']]['config'][$val['var_name']]=$val['var_value'];
			}
		}
	}
	
	/*!
	 * This function returns all packages loaded
	 *
	 * @author Kevin Wijesekera
	 * @date 4-17-2015
	 *
	 * @param $roots Root package or packages
	 * @param $list Container of all packages
	 */
	 public function pkgs_getAll($roots,&$list){
		 foreach($roots as $val){
			if(isset($this->packages[$val])){
				$dta = $this->packages[$val];
				if(!in_array($val,$list)){
					$list[] = $val;
				}
				if(strpos($dta['dependencies'],';') > 0){
					$this->pkgs_getAll(explode(';',$dta['dependencies']),$list);
				}
			}

		 }
	 }

	/*!
	 * This function runs a package hook
	 * 
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $package Package to run
	 * @param $hook Hook to run
	 * @param $retcode Hook return code store
	 *
	 * @return Hook content
	 */	
	public function pkgs_run($package,$hook,&$retcode){
		if(!isset($this->packages[$package])){
			trigger_error('(packages): Cannot run package....not loaded',E_USER_WARNING);
			$retcode = 500;
			return;
		}
		if(!is_object($this->objs[$package])){
			$this->objs[$package] = new $package($this->packages[$package]['config']);
			if(!is_object($this->objs[$package])){
				trigger_error('(packages): Could not create package class '.$package.E_USER_WARNING);
				$retcode = 500;
				return;
			}
		}
		if(!method_exists($this->objs[$package],$hook)){
			trigger_error('(packages): Hook not found in package '.$package.E_USER_WARNING);
			$retcode = 500;
			return;
		}
		$retcode = 200;
		return $this->objs[$package]->{$hook}($retcode);
	}
	
	/*!
	 * This function runs a package hook
	 * 
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $package Package to run
	 * @param $hook Hook to run
	 * @param $retcode Hook return code store
	 *
	 * @return Hook content
	 */	
	public function pkgs_runExtended($package,$hook,&$retcode,&$app){
		if(!isset($this->packages[$package])){
			trigger_error('(packages): Cannot run package....not loaded',E_USER_WARNING);
			$retcode = 500;
			return;
		}
		if(!is_object($this->objs[$package])){
			$this->objs[$package] = new $package($this->packages[$package]['config']);
			if(!is_object($this->objs[$package])){
				trigger_error('(packages): Could not create package class '.$package.E_USER_WARNING);
				$retcode = 500;
				return;
			}
		}
		if(!method_exists($this->objs[$package],$hook)){
			trigger_error('(packages): Hook not found in package '.$package.E_USER_WARNING);
			$retcode = 500;
			return;
		}
		$retcode = 200;
		return $this->objs[$package]->{$hook}($retcode,$app);
	}
	
	/*!
	 * This function gets a packages configuration
	 * 
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $package Package to get data from
	 *
	 * @return array of configuration items
	 */	
	public function pkgs_getConfig($pkg){
		if(isset($this->packages[$pkg])){
			return $this->packages[$pkg]['config'];
		}
		return array();
	}
	
	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */
	
	/*!
	 * This function is the recursive helper
	 * for pkg_load and loads all given packages
	 * an any dependencies.
	 * 
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $toLoad Array of packages to load
	 */	
	private function pkgs_loadHelper($toLoad){
		foreach($toLoad as $val){
			if(isset($this->packages[$val])){
				$dta = $this->packages[$val];

				$this->conds[0][]=array(DB_STD,'pkg_id','=',$val);
				$this->conds[0][]=DB_OR;
				$pkg = 'pkg';
				if($this->packages[$val]['type']== 1){
					$pkg = 'class';
				}
				else if($this->packages[$val]['type']==2){
					$pkg = 'abstract';
				}
				if(strpos($dta['dependencies'],';') > 0){
					$this->pkgs_loadHelper(explode(';',$dta['dependencies']));
				}
				if(!mginit_loadFile($dta['pgroup'],$dta['pkg_id'],$pkg,$this->packages[$val]['type']==0)){
					trigger_error('(packages): Could not load package '.$val,E_USER_ERROR);
				}
			}
		}
	}
	
}