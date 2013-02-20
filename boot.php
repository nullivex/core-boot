<?php

//----------------------------
//Init Functions
//----------------------------
function __boot_pre(){
	global $config;

	ob_start();
	session_start();
	define('START',microtime(true));

	//load config
	$config = array();
	__init_load_files(__DIR__.'/conf',false,'__export_config',array(&$config));
	@include('config.php');

	//set timezone
	date_default_timezone_set($config['info']['default_timezone']);

	//set root path
	define('ROOT',__DIR__);
	define('ROOT_URI',$config['url']['uri']);

	//load req libs
	lib('router');
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
	return str_replace(' ','',ucwords(str_replace('_',' ',$name)));
}

function lib(){
	foreach(func_get_args() as $name){
		//check if class is alreayd loaded and stop if fo
		if(class_exists(__make_class_name($name))) continue;
		//check group location (if defined)
		if(defined('ROOT_GROUP') && __load_lib(ROOT_GROUP,$name)) continue;
		//check global location and load
		if(__load_lib(ROOT,$name)) continue;
		//try to load from a subfolder
		$trace = debug_backtrace();
		trigger_error(
				 'Class not found: '.$name
				.' called from '.mda_get($trace[0],'file')
				.' line '.mda_get($trace[0],'line')
			,E_USER_ERROR
		);
	}
	return;
}

function __load_lib($root,$name){
	//try to load from the root
	$file = $root.'/lib/'.$name.'.php';
	if(file_exists($file)){
		require_once($file);
		return true;
	}
	//load parts
	$parts = explode('_',$name);
	if(!count($parts)){
		trigger_error(
				 'Class name invalid for loading: '.$name
				.' called from '.mda_get($trace[0],'file')
				.' line '.mda_get($trace[0],'line')
			,E_USER_ERROR
		);
	}
	//build part based name
	$file = $root.'/lib/'.array_shift($parts).'/'.implode('_',$parts).'.php';
	if(file_exists($file)){
		require_once($file);
		return true;
	}
	return false;
}
