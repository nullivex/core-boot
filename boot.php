<?php

//----------------------------
//Init Functions
//----------------------------
function __boot_pre($cfgpath='.'){
	global $config;

	define('START',microtime(true));

	//load config
	$config = array();
	__init_load_files(__DIR__.'/conf',false,'__export_config',array(&$config));
	include($cfgpath.'/config.php');

	//set timezone
	date_default_timezone_set($config['info']['default_timezone']);

	//set root path
	define('ROOT',__DIR__);
	define('ROOT_URI',$config['url']['uri']);

}

function __boot_post(){

	try {
		//load global funcs
		__init_load_files(ROOT.'/func',true);

		//init modules
		__init_load_files(ROOT.'/init',true);

		//load error codes
		$err = array();
		__init_load_files(ROOT.'/err',false,'registerErrCodes',array(&$err));

	} catch(Exception $e){
		sysError($e->getMessage());
	}

}

function __init_load_files($dir_path,$ordered=false,$callback=false,$callback_params=array()){
	$dir = false;
	$files = array();
	//try to open dir
	if(!is_dir($dir_path)) return false;
	$dir = opendir($dir_path);
	if(!$dir) return false;
	//read dir into array
	while($dir && ($file = readdir($dir)) !== false){
		if(substr($file,-4)!='.php') continue;		//only *.php
		if(substr($file,0,1)=='.') continue;		//skip hidden files (including . and ..)
		if(is_dir($dir_path.'/'.$file)) continue;	//skip subdirectories
		$files[] = $file;
	}
	closedir($dir);
	//sort files if needed
	if($ordered) sort($files);
	//load files
	foreach($files as $file){
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

function __make_class_name($name){
	return str_replace(' ','',ucwords(str_replace('_',' ',basename($name))));
}

//Global Library Loader similar to LD for linux
//	Takes unlimited arguments with the following syntax
//		'lib_name' - load the lib automatically based on its name
//			will load group level and if not found will load root level
//			will also try collection loading for libs in a collection
//			EG: if item_taxes is passed it will check lib/item/taxes.php
//			NOTE: even forced locations still perform this lookup
//		'/lib_name' - force lib to load from root, other locations
//			will not be tried
//		'group/lib_name' - cross load lib from other group
//			other locations will not be tried
function lib(){
	foreach(func_get_args() as $name){
		$lib = lib_exists($name);
		if($lib === true) continue;
		if($lib !== false){
			require_once($lib);
			continue;
		}
		//print error
		$trace = debug_backtrace();
		trigger_error(
				 'Class not found: '.$name
				.' called from '.mda_get($trace[0],'file')
				.' line '.mda_get($trace[0],'line')
			,E_USER_ERROR
		);
	}
	return false;
}

//Global lib existence checker
//	Will check to see if a lib exists and supports all the
//	syntax of the global lib loader
//	Returns the following
//		true: class has already been loaded by name
//		false: class does not exist and hasnt been loaded
//		string: absolute file path to the class to be loaded
function lib_exists($name){
	//check if class is alreayd loaded and stop if fo
	if(class_exists(__make_class_name($name))) return true;
	//check if this class is explicitly loaded from root
	if(strpos($name,'/') === 0)
		if(($rv = __load_lib(ROOT,basename($name))) !== false) return $rv;
	//check if this a cross load to a group
	if(strpos($name,'/') !== false && strpos($name,'/') !== 0){
		list($group,$lib) = explode('/',$name);
		if(($rv = __load_lib(ROOT.'/'.$group,$lib)) !== false) return $rv;
	}
	//check group location (if defined)
	if(defined('ROOT_GROUP') && ($rv = __load_lib(ROOT_GROUP,$name)) !== false) return $rv;
	//check global location and load
	if(($rv = __load_lib(ROOT,$name)) !== false) return $rv;
	return false;
}


function __load_lib($root,$name,$return_on_error=false){
	//try to load from the root
	$file = $root.'/lib/'.$name.'.php';
	if(file_exists($file)) return $file;
	//load parts
	$parts = explode('_',$name);
	if(!count($parts)){
		if($return_on_error) return false;
		trigger_error(
				 'Class name invalid for loading: '.$name
				.' called from '.mda_get($trace[0],'file')
				.' line '.mda_get($trace[0],'line')
			,E_USER_ERROR
		);
	}
	//build part based name
	$file = $root.'/lib/'.array_shift($parts).'/'.implode('_',$parts).'.php';
	if(file_exists($file)) return $file;
	return false;
}
