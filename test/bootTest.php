<?php

require_once(__DIR__.'/test_common.php');

class libTest extends PHPUNIT_Framework_TestCase {

	static $teardown_dirs = array();

	public static function setUpBeforeClass(){
		//create dirs if we have to
		if(!file_exists(ROOT.'/lib')){
			self::$teardown_dirs[] = ROOT.'/lib';
			mkdir(ROOT.'/lib',0777,true);
		}
		if(!file_exists(ROOT.'/admin/lib')){
			self::$teardown_dirs[] = ROOT.'/admin/lib';
			mkdir(ROOT.'/admin/lib',0777,true);
		}
		if(!file_exists(ROOT.'/admin/lib/item')){
			self::$teardown_dirs[] = ROOT.'/admin/lib/item';
			mkdir(ROOT.'/admin/lib/item',0777,true);
		}
		//create files
		file_put_contents(ROOT.'/lib/test_root.php','<?php class TestRoot{}');
		file_put_contents(ROOT.'/admin/lib/test_admin.php','<?php class TestAdmin{}');
		file_put_contents(ROOT.'/admin/lib/test_relative.php','<?php class TestRelative{}');
		file_put_contents(ROOT.'/admin/lib/test_root.php','<?php class TestRoot2{}');
		file_put_contents(ROOT.'/admin/lib/item/test.php','<?php class ItemTest{}');
		//falsely define our group
		define('ROOT_GROUP',ROOT.'/admin');
	}

	public static function tearDownAfterClass(){
		//remove files
		unlink(ROOT.'/lib/test_root.php');
		unlink(ROOT.'/admin/lib/test_admin.php');
		unlink(ROOT.'/admin/lib/test_relative.php');
		unlink(ROOT.'/admin/lib/test_root.php');
		unlink(ROOT.'/admin/lib/item/test.php');
		//teardown dirs if we need to
		foreach(self::$teardown_dirs as $dir) unlink($dir);
	}

	public function testExistsClassExists(){
		$this->assertEquals(true,lib_exists('db'));
	}

	public function testExistsRelative(){
		$this->assertEquals(ROOT.'/admin/lib/test_relative.php',lib_exists('test_relative'));
	}

	public function testExistsRoot(){
		$this->assertEquals(ROOT.'/lib/test_root.php',lib_exists('/test_root'));
	}

	public function testExistsGroup(){
		$this->assertEquals(ROOT.'/admin/lib/test_admin.php',lib_exists('admin/test_admin'));
	}
	
	public function testExistsItem(){
		$this->assertEquals(ROOT.'/admin/lib/item/test.php',lib_exists('item_test'));
	}
	
	public function testExistsItemGroup(){
		$this->assertEquals(ROOT.'/admin/lib/item/test.php',lib_exists('admin/item_test'));
	}

	public function testRelative(){
		lib('test_root');
		$this->assertEquals(true,class_exists('TestRoot2'));
	}

	public function testRoot(){
		lib('/test_root');
		$this->assertEquals(true,class_exists('TestRoot'));
	}

	public function testGroup(){
		lib('admin/test_admin');
		$this->assertEquals(true,class_exists('TestAdmin'));
	}
	
	public function testItem(){
		lib('admin/item_test');
		$this->assertEquals(true,class_exists('ItemTest'));
	}
	
	public function testOverloading(){
		lib('item_test');
		lib('admin/item_test');
		lib('/test_root');
		$this->assertEquals(true,class_exists('ItemTest'));
	}

}
