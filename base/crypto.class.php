<?php
/*!
 * @file		crypto.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-27-2015
 * @section DESCRIPTION
 * This file contains the crypto class which provides hash and encryption
 * related tools.
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

define('CRYPTO_HASH_MD5','md5');
define('CRYPTO_HASH_SHA1','sha1');
define('CRYPTO_HASH_SHA256','sha256');
define('CRYPTO_HASH_SHA512','sha512');
define('CRYPTO_HASH_RIPEMD256','ripemd256');
define('CRYPTO_HASH_WHIRLPOOL','whirlpool');

define('CRYPTO_MODE_ECB','ecb');
define('CRYPTO_MODE_CBC','cbc');
define('CRYPTO_MODE_CFB','cfb');
define('CRYPTO_MODE_OFB','ofb');
define('CRYPTO_MODE_NOFB','nofb');
define('CRYPTO_MODE_STREAM','stream');

class crypto{
	
}

/*! HASH Class */
class hash{
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	/*!
	 * This function generates a standard hash.  It should not be used
	 * for hashing passwords as some of the algorithms supported are
	 * not secure.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $value Value to hash
	 * @param $hashType Type of hash to perform
	 * @param $seed Length of seed value
	 *
	 * @return hash on success, false on failure
	 */
	public function cr_generateHash($value,$hashType,$seed=false){
		if(in_array($hashType,hash_algos())){
			return false;
		}
		return base64_encode(hash($hashType,$value).$this->cr_generateSeet($seed));
	}
	
	/*!
	 * This function generates a password hash.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $value Value to hash
	 * @param $seed Length of seed value
	 *
	 * @return hash on success, false on failure
	 */
	public function cr_generatePasswordHash($value,$seed=false){
		return password_hash($value,array('cost'=>12));
	}
	
	/*!
	 * This function compares a value to a password hash
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $value Value to hash
	 * @param $hash Hash to compare
	 *
	 * @return true if match, false if not
	 */
	public function cr_verifyPasswordHash($value,$hash){
		return password_verify($value,$hash);
	}
	
	/*!
	 * This function compares a value to a hash.  It should not be used
	 * for hashing passwords as some of the algorithms supported are
	 * not secure.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $value Value to hash
	 * @param $hash Hash to compare
	 * @param $hashType Type of hash to perform
	 * @param $seed Length of seed value
	 *
	 * @return true if match, false if not
	 */
	public function cr_compareToHash($value,$hash,$hashType,$seed=false){
		$r = $this->cr_generateHash($value,$hashtype,$seed);
		if($r == false){
			return false;
		}
		return hash_equals($hash,$r);
	}

	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */
	
	/*!
	 * This function generates a random alphanumeric string
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $size Size of string to generate
	 *
	 * @return generated string
	 */
	private function cr_generateSeed($size){
		if(!$size){
			return '';
		}
        $str = '';
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$len = count($characters);
        for ($i=0; $i<$size; $i++){
            $str .= $characters[mt_rand(0,$len)];
        }
        return $str;
	}
}
