<?php
/*!
 * @file		time.init.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-16-2015
 * @section DESCRIPTION
 * This file contains a function to load the system + user time
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

/*!
 * This function gets the site + client date/time stamps
 *
 * @author Kevin Wijesekera
 * @date 3-12-2015
 *
 * @param $tz_server Server timezone
 * @param $tz_client Client timezone
 */
function mginit_time($tz_server,$tz_client){
	$GLOBALS['MG']['SITE']['TIME']=time();
	if(empty($tz_client)){
		$GLOBALS['MG']['USER']->act_set('time',time());
		return;
	}
	
	$d_server = new DateTimeZone($tz_server);
	$d_client = new DateTimeZone($tz_client);
	$t_server = new DateTime('now',$d_server);
	$t_client = new DateTime('now',$d_client);
	$off_server = $d1->getOffset($t_server);
	$off_client = $d1->getOffset($t_client);
	$GLOBALS['MG']['USER']->act_set('time',$GLOBALS['MG']['SITE']['TIME'] - ($off_server-$off_client));
}
