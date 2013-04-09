<?php
/**
 *  OpenLSS - Lighter Smarter Simpler
 *
 *	This file is part of OpenLSS.
 *
 *	OpenLSS is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Lesser General Public License as
 *	published by the Free Software Foundation, either version 3 of
 *	the License, or (at your option) any later version.
 *
 *	OpenLSS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Lesser General Public License for more details.
 *
 *	You should have received a copy of the 
 *	GNU Lesser General Public License along with OpenLSS.
 *	If not, see <http://www.gnu.org/licenses/>.
 */

//NOTICE: requires vendor/autoload.php to be loaded first
//	or any other autoloader registered through spl_autoload_registers

//Set the timezone to UTC before we start
date_default_timezone_set('UTC');
//set root path
if(!defined('ROOT')) define('ROOT',dirname(dirname(dirname(dirname(__DIR__)))));

//---------------------------------------------------------
//Error Handling
//---------------------------------------------------------
//Error handling gets setup before we start booting
function __error_handler($errno, $errstr, $errfile, $errline ) {
	//ignore strict errors
	if($errno == E_STRICT) return null;
	//throw all other errors to the exception handler
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler('__error_handler');

function __exception_handler($e){
	if(php_sapi_name() != 'cli'){
		echo '<h1>Error</h1><p>'.$e->getMessage().'</p><pre>'.$e.'</pre>';
		exit($e->getCode());
	}
	try {
		if(is_callable('dolog')){
			dolog($e->getMessage()."\n".$e,LOG_ERROR);
			exit($e->getCode());
		}
	} catch(Exception $le){
		echo $le;
	}
	exit($e);
}
set_exception_handler('__exception_handler');

//---------------------------------------------------------
//Init Functions
//---------------------------------------------------------
function __boot(){
	__boot_pre();
	__boot_post();
}

function __boot_pre(){
	global $config;

	define('START',microtime(true));

	//load config
	__init_load_files(ROOT.'/conf','__export_config',array(&$config));
	if(defined('ROOT_GROUP') && is_dir(ROOT_GROUP.'/conf'))
		__init_load_files(ROOT_GROUP.'/conf','__export_config',array(&$config));
	if(file_exists(ROOT.'/config.php'))
		include(ROOT.'/config.php');
	if(defined('ROOT_GROUP') && file_exists(ROOT_GROUP.'/config.php'))
		include(ROOT_GROUP.'/config.php');
	//set the config if we can
	if(class_exists('\LSS\Config') && is_callable(array(\LSS\Config::_get(),'setConfig')))
		\LSS\Config::_get()->setConfig($config);

	//set timezone
	if(isset($config['timezone']))
		date_default_timezone_set($config['timezone']);
	unset($config);
}

function __boot_post(){
	//init core modules
	if(is_dir(ROOT.'/init'))
		__init_load_files(ROOT.'/init');
	if(defined('ROOT_GROUP') && is_dir(ROOT_GROUP.'/init')){
		//init group modules
		__init_load_files(ROOT_GROUP.'/init');
	}
}

function __init_load_files($dir_path,$callback=false,$callback_params=array(),$recurse=true){
	$dir = false;
	$files = array();
	//try to open dir
	if(!is_dir($dir_path)) return false;
	$dir = opendir($dir_path);
	if(!$dir) return false;
	//read dir into array
	while($dir && ($file = readdir($dir)) !== false){
		if(substr($file,0,1)=='.') continue;		//skip hidden files (including . and ..)
		if(!is_dir($dir_path.'/'.$file) && substr($file,-4)!='.php') continue;		//only *.php
		$files[] = $file;
	}
	closedir($dir);
	//sort files if needed
	sort($files);
	//load files
	foreach($files as $file){
		if(is_dir($dir_path.'/'.$file)){
			if(!$recurse) continue;	//skip subdirectories
			__init_load_files($dir_path.'/'.$file,$callback,$callback_params,$recurse);
			continue;
		}
		if(defined('INIT_LOAD_DEBUG')) echo 'loaded file: '.$dir_path.'/'.$file."\n";
		if($callback && function_exists($callback)){
			$params = $callback_params;
			array_unshift($params,$dir_path.'/'.$file);
			call_user_func_array($callback,$params);
		} else {
			include_once($dir_path.'/'.$file);
		}
	}
	return true;
}

function __export_config($file,&$config){
	include_once($file);
}

//---------------------------------------------------------
//Error code loading
//---------------------------------------------------------
function __e($err=array()){
	global $__err;
	if(!isset($__err) || !is_array($__err)) $__err = array();
	foreach($err as $code => $constant){
		if(strpos($constant,'E_') !== 0){
			trigger_error('Invalid error code constant: '.$constant.' must start with E_ definition ignored');
			continue;
		}
		if(in_array($code,array_keys($__err))){
			trigger_error('Error code already defined: '.$code.' by constant: '.$__err[$code].' definition ignored');
			continue;
		}
		if(defined($constant)){
			trigger_error('Error constant has already been defined: '.$constant.' and is in use by code '.constant($constant).' definition ignored');
			continue;
		}
		define($constant,$code);
		$__err[$code] = $constant;
	}
}

