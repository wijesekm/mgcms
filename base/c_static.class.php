<?php
/*!
 * @file		c_static.class.php
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
class c_static extends content{
	
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
		$this->c_init($id);

        //set resource paths
		// * DO we support print media types or not?????
        if($GLOBALS['MG']['CFG']['SITE']['DEBUG']){
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_GLOBAL',mg_generateURL('resources.php',array('p'=>'*','idt'=>0,'l'=>$GLOBALS['MG']['LANG']['NAME'],'d'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']))); // page, isPrint, language, style, debug
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_GLOBAL_PRINT',mg_generateURL('resources.php',array('p'=>'*','idt'=>2,'l'=>$GLOBALS['MG']['LANG']['NAME'],'d'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']))); // page, isPrint, language, style, debug
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_LOCAL',mg_generateURL('resources.php',array('p'=>$this->id,'idt'=>0,'l'=>$GLOBALS['MG']['LANG']['NAME'],'d'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']))); // page, isPrint, language, style, debug
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_LOCAL_PRINT',mg_generateURL('resources.php',array('p'=>$this->id,'idt'=>2,'l'=>$GLOBALS['MG']['LANG']['NAME'],'d'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']))); // page, isPrint, language, style, debug
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('JS_GLOBAL',mg_generateURL('resources.php',array('p'=>'*','idt'=>1,'l'=>$GLOBALS['MG']['LANG']['NAME'],'d'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']))); // page, language, style, debug
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('JS_LOCAL',mg_generateURL('resources.php',array('p'=>$this->id,'idt'=>1,'l'=>$GLOBALS['MG']['LANG']['NAME'],'d'=>$GLOBALS['MG']['CFG']['SITE']['DEBUG']))); // page, language, style, debug
        }
        else{
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_GLOBAL',$GLOBALS['MG']['CFG']['SITE']['URI'].'/res/p_idt0_l'.$GLOBALS['MG']['LANG']['NAME'].'_d0.css');
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_GLOBAL_PRINT',$GLOBALS['MG']['CFG']['SITE']['URI'].'/res/p-_idt-2_l-'.$GLOBALS['MG']['LANG']['NAME'].'_d0.css');
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_LOCAL',$GLOBALS['MG']['CFG']['SITE']['URI'].'/res/p-'.$this->id.'_idt-0_l-'.$GLOBALS['MG']['LANG']['NAME'].'_d0.css');
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CSS_LOCAL_PRINT',$GLOBALS['MG']['CFG']['SITE']['URI'].'/res/p-'.$this->id.'_idt-2_l-'.$GLOBALS['MG']['LANG']['NAME'].'_d0.css');
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('JS_GLOBAL',$GLOBALS['MG']['CFG']['SITE']['URI'].'/res/p-_idt-1_l-'.$GLOBALS['MG']['LANG']['NAME'].'_d0.js');
            $GLOBALS['MG']['SITE']['TPL']->tpl_setVar('JS_LOCAL',$GLOBALS['MG']['CFG']['SITE']['URI'].'/res/p-'.$this->id.'_idt-1_l-'.$GLOBALS['MG']['LANG']['NAME'].'_d0.js');
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
		$app = '';
		$notpl = false;

		if($this->error == 0){
			$content = $this->packages->pkgs_runExtended($this->data['package'],'hook_static',$this->error,$app);
		}
		if(!$GLOBALS['MG']['SITE']['TPL']->tpl_load($GLOBALS['MG']['SITE']['TPL_BASE'].'site.tpl','main')){
			trigger_error('(page): Could not load site template',E_USER_ERROR);
			$notpl = true;
		}
		
		if($GLOBALS['MG']['LOG']->l_hasFatalErrors()){
			$this->error = 500;
		}
		
		if($this->error != 200){
			if($GLOBALS['MG']['SITE']['TPL']->tpl_load($GLOBALS['MG']['SITE']['TPL_BASE'].'errors.tpl',(string)$this->error)){
				$GLOBALS['MG']['SITE']['TPL']->tpl_compile((string)$this->error);
				$content = $GLOBALS['MG']['SITE']['TPL']->tpl_return((string)$this->error);
			}
			else{
				$content = $this->error;
			}
		}

		if($notpl){
			return $content;
		}
		$GLOBALS['MG']['SITE']['TPL']->tpl_setVar('APP',$app);
		$GLOBALS['MG']['SITE']['TPL']->tpl_setVar('CONTENT',$content);
		$GLOBALS['MG']['SITE']['TPL']->tpl_compile('main');
		return $GLOBALS['MG']['SITE']['TPL']->tpl_return('main');
	}
}
