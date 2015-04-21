<?php
/*!
 * @file		c_resources.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-25-2015
 * @section DESCRIPTION
 * This file contains the class which generates css and js
 * resources for each page
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
class c_resources extends content{

	//private function
	private $resources;
	
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
	public function __construct($id){
		$this->id = $id;
		$this->error = 0;
		$conds = array(
			array(DB_STD,'load','=',1),
			DB_AND,
			array(DB_STD,'type','=',(int)$GLOBALS['MG']['V']['GET']['ID_T'])
		);
		if($this->id == '*'){
			$this->id = '';
			$table = TABLE_PREFIX.'resources_global';


		}
		else{
			if(!$this->c_init($id)){
				return;
			}
			$table = TABLE_PREFIX.'resources_packages';
			$list = array();
			$this->packages->pkgs_getAll(array($this->data['package']),$list);
			if(strpos($this->data['providers'],';') > 0){
				$this->packages->pkgs_getAll(explode(';',$this->data['providers']),$list);
			}
			$first = true;
			$conds[3] = DB_AND;
			$conds[4]=array();
			foreach($list as $val){
				if(!$first){
					$conds[4][] = DB_OR;
				}
				$first = false;
				$conds[4][] = array(DB_STD,'pkg_id','=',$val);
			}
		}

		$this->resources = $GLOBALS['MG']['DB']->db_query(array(
			array(
				'type'=>DB_SELECT,
				'table'=>$table,
				'conds'=>$conds
			)
		));
		
		if(!$this->resources[0]['done']){
			trigger_error('(resources.php): Could not query database for resource data',E_USER_ERROR);
		}
		$this->resources = $this->resources[0]['result'];
		if($this->id){
			if($this->data['has_js']){
				$this->resources[] = array('page'=>$this->id,'pkg_id'=>$this->id.'.js','rgroup'=>'pages','type'=>1);
			}
			if($this->data['has_css']){
				$this->resources[] = array('page'=>$this->id,'pkg_id'=>$this->id.'.css','rgroup'=>'pages','type'=>0);
			}
		}
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
		$js = false;
		$lang = $GLOBALS['MG']['V']['GET']['LANGUAGE'];
		if($GLOBALS['MG']['V']['GET']['ID_T']){
			$js = true;
			$GLOBALS['MG']['SITE']['HEADERS']['Content-Type']='application/javascript';
		}
		else{
			$GLOBALS['MG']['SITE']['HEADERS']['Content-Type']='text/css';
		}

		$last_modifiy_time = 0;
		$toload=array();
		foreach($this->resources as $val){
			if(!empty($val['resource_id'])){
				/*$path = $GLOBALS['MG']['CFG']['PATH']['RES'];
				$path1 = '/'.$lang.'/';
				if($val['support_lang']){
					$path .= '/'.$lang.'/';
				}
				else{
					$path .= '/global/';
				}*/
				$pathext = '';
				if(isset($val['page'])){
					
				}
				else if(isset($val['pkg_id'])){
					$pathext .='packages/';
				}
				else if($js){
					$pathext .= 'js/';
				}
				else{
					$pathext .='css/';
				}
				$pathext .= $val['rgroup'].'/'.$val['resource_id'];
				
				if(is_file($GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$lang.'/'.$pathext)){
					$path = $GLOBALS['MG']['CFG']['PATH']['RES'].'/'.$lang.'/'.$pathext;
				}
				else if(is_file($GLOBALS['MG']['CFG']['PATH']['RES'].'/global/'.$pathext)){
					$path = $GLOBALS['MG']['CFG']['PATH']['RES'].'/global/'.$pathext;
				}
				else{
					continue;
				}
				
				//get last modification time
				$lastmtime = filemtime($path);
				$cachemtime = filemtime($path.'.min');
				if($lastmtime > $last_modifiy_time){
					$last_modifiy_time = $lastmtime;
				}
				if($lastmtime > $cachemtime){
					//recache
					if($js){
						$contents = jsMinPlus::minify(file_get_contents($path));
					}
					else{
						$contents = CssMin::minify(file_get_contents($path));
					}
					file_put_contents($path.'.min',$contents);
				}
				$toload[] = $path;
			}
		}

		$etag = md5($lang.$last_modifiy_time);

		$GLOBALS['MG']['SITE']['HEADERS']['Last-Modified']=gmdate('D, d M Y H:i:s ', $last_modifiy_time) . 'GMT';

		//cache-control: private, max-age=0, no-cache
		if(!isset($_SERVER['HTTP_IF_NONE_MATCH'])){
			$_SERVER['HTTP_IF_NONE_MATCH']='';
		}
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
			if(strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modifiy_time && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag){
				$GLOBALS['MG']['SITE']['HEADERS']['HTTP']='304 Not Modified';
				return '';
			}
		}

		//error check
		$GLOBALS['MG']['LOG']->l_fatalCheck();
		$page_contents = '';
		$cache_file_name = $GLOBALS['MG']['CFG']['PATH']['HTDOCS'].'/res/p'.$this->id.'_idt'.$GLOBALS['MG']['V']['GET']['ID_T'].'_l'.$lang.'_d'.($GLOBALS['MG']['V']['GET']['DEBUG']?'1':'0').(($js)?'.js':'.css');
		foreach($toload as $path){
			if($GLOBALS['MG']['V']['GET']['DEBUG']){
				$page_contents .= file_get_contents($path);
			}
			else{
				$page_contents .= file_get_contents($path.'.min');
			}
		}
		$GLOBALS['MG']['SITE']['HEADERS']['Cache-Control']='public, max-age=604800';
		$GLOBALS['MG']['SITE']['HEADERS']['Content-Length']=strlen($page_contents);
		$GLOBALS['MG']['SITE']['HEADERS']['ETag']=$etag;
		file_put_contents($cache_file_name,$page_contents);
		return $page_contents;
	}
}
