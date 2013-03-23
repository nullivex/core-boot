<?php

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
	if(is_callable('dolog')){
		dolog($e->getMessage()."\n".$e,LOG_ERROR);
		exit($e->getCode());
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
	$config = array();
	__init_load_files(ROOT.'/conf','__export_config',array(&$config));
	if(defined('ROOT_GROUP') && is_dir(ROOT_GROUP.'/conf'))
		__init_load_files(ROOT_GROUP.'/conf','__export_config',array(&$config));
	if(file_exists(ROOT.'/config.php'))
		include(ROOT.'/config.php');
	if(defined('ROOT_GROUP') && file_exists(ROOT_GROUP.'/config.php'))
		include(ROOT_GROUP.'/config.php');

	//set timezone
	if(isset($config['timezone']))
		date_default_timezone_set($config['timezone']);
}

function __boot_post(){
	//init core modules
	__init_load_files(ROOT.'/init');
	if(defined('ROOT_GROUP')){
		//init group modules
		__init_load_files(ROOT_GROUP.'/init');
	}
	//load composer autoloader
	if(file_exists(ROOT.'/vendor/autoload.php'))
		require_once(ROOT.'/vendor/autoload.php');
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

//Global Auto Loader similar to LD for linux
//	Takes unlimited arguments with the following syntax
//		LIBRARIES (default)
//		'lib_name' - load the lib automatically based on its name
//			will load group level and if not found will load root level
//			will also try collection loading for libs in a collection
//			EG: if item_taxes is passed it will check lib/item/taxes.php
//			NOTE: even forced locations still perform this lookup
//		'/lib_name' - force lib to load from root, other locations
//			will not be tried
//		'group/lib_name' - cross load lib from other group
//			other locations will not be tried
//		FUNCTIONS
//		'func/pkg' - will load functions in the same fashion
//			can also be forced with /func/pkg admin/func/pkg etc
function ld(){
	global $__ld_loaded;
	if(!isset($__ld_loaded) || !is_array($__ld_loaded))
		$__ld_loaded = array();
	//load
	foreach(func_get_args() as $name){
		$prefix = 'lib';
		if(strpos($name,'func') !== false && strpos($name,'func') !== strlen($name)-4){
			$prefix = 'func';
			//strip func from $name
			$name = str_replace('func/','',$name);
		}
		$load = ld_exists($name,$prefix);
		if($load === true){
			//already loaded
			continue;
		}
		if(defined('LD_DEBUG'))
			echo "LD about to load: ".print_r($load,true)."\n";
		if($load !== false && in_array($load,$__ld_loaded)){
			if(defined('LD_DEBUG'))
				echo "LD already loaded: $load\n";
			continue;
		}
		if($load !== false){
			$__ld_loaded[] = $load;
			if(defined('LD_DEBUG'))
				echo "LD loaded file: $load\n";
			require_once($load);
			continue;
		}
		//print error
		$trace = debug_backtrace();
		trigger_error(
				 'LD file not found: '.$name
				.' called from '.$trace[0]['file']
				.' line '.$trace[0]['line']
			,E_USER_ERROR
		);
	}
	return true;
}

//Global auto loader existence checker
//	Will check to see if a lib exists and supports all the
//	syntax of the global lib loader
//	Returns the following
//		true: class has already been loaded by name
//		false: class does not exist and hasnt been loaded
//		string: absolute file path to the class to be loaded
function ld_exists($name,$prefix='lib'){
	//check if class is already loaded and stop if so
	if($prefix == 'lib' && class_exists(__make_class_name(basename($name)))) return true;
	//check if this class is explicitly loaded from root
	if(strpos($name,'/') === 0)
		if(($rv = __load_ld(ROOT,basename($name),$prefix)) !== false) return $rv;
	//check if this a cross load to a group
	if(strpos($name,'/') !== false && strpos($name,'/') !== 0){
		list($group,$load) = explode('/',$name);
		if(($rv = __load_ld(ROOT.'/'.$group,$load,$prefix)) !== false) return $rv;
	}
	//check group location (if defined)
	if(defined('ROOT_GROUP') && ($rv = __load_ld(ROOT_GROUP,$name,$prefix)) !== false) return $rv;
	//check global location and load
	if(($rv = __load_ld(ROOT,$name,$prefix)) !== false) return $rv;
	//check vendor area
	if(($rv = __load_ld_vendor(ROOT,$name,$prefix)) !== false) return $rv;
	return false;
}


function __load_ld($root,$name,$prefix='lib',$return_on_error=false){
	//try to load from the root
	$file = $root.'/'.$prefix.'/'.$name.'.php';
	if(defined('LD_DEBUG'))
		echo "LD Trying to load file $file\n";
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
	if(count($parts) == 1) return false;
	$file = implode(array($root,$prefix,array_shift($parts),implode('_',$parts)),'/').'.php';
	if(defined('LD_DEBUG'))
		echo "LD Trying to load file $file\n";
	if(file_exists($file)) return $file;
	return false;
}

function __load_ld_vendor($root,$name,$prefix='lib',$vendor='openlss'){
	//try to load from vendor packages
	$name = basename($name);
	$path = implode('/',array($root,'vendor',$vendor,sprintf('%s-%s',$prefix,$name),$prefix,sprintf('%s.php',$name)));
	if(defined('LD_DEBUG'))
		echo "LD Vendor Path: $path\n";
	if(file_exists($path)) return $path;
	return false;
}

function __make_class_name($name){
	return str_replace(' ','',ucwords(str_replace('_',' ',basename($name))));
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

