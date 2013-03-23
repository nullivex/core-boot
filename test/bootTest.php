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
namespace LSS;
define('ROOT_GROUP',dirname(__DIR__).'/admin');
define('ROOT',dirname(__DIR__));
require_once(dirname(__DIR__).'/src/boot.php');

class ldTest extends \PHPUNIT_Framework_TestCase {

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
		if(!file_exists(ROOT.'/func')){
			self::$teardown_dirs[] = ROOT.'/func';
			mkdir(ROOT.'/func');
		}
		//create files
		file_put_contents(ROOT.'/lib/test_root.php','<?php class TestRoot{}');
		file_put_contents(ROOT.'/admin/lib/test_admin.php','<?php class TestAdmin{}');
		file_put_contents(ROOT.'/admin/lib/test_relative.php','<?php class TestRelative{}');
		file_put_contents(ROOT.'/admin/lib/test_root.php','<?php class TestRoot2{}');
		file_put_contents(ROOT.'/admin/lib/item/test.php','<?php class ItemTest{}');
		file_put_contents(ROOT.'/func/test.php','<?php function testing(){}');
		//falsely define our group
		if(!defined('ROOT_GROUP')) define('ROOT_GROUP',ROOT.'/admin');
	}

	public static function tearDownAfterClass(){
		//remove files
		unlink(ROOT.'/func/test.php');
		unlink(ROOT.'/lib/test_root.php');
		unlink(ROOT.'/admin/lib/test_admin.php');
		unlink(ROOT.'/admin/lib/test_relative.php');
		unlink(ROOT.'/admin/lib/test_root.php');
		unlink(ROOT.'/admin/lib/item/test.php');
		//teardown dirs if we need to
		rsort(self::$teardown_dirs);
		foreach(self::$teardown_dirs as $dir) unlink($dir);
	}

	public function testExistsClassExists(){
		$this->assertNotSame(false,ld_exists('test_root'));
	}

	public function testExistsRelative(){
		$this->assertEquals(ROOT.'/admin/lib/test_relative.php',ld_exists('test_relative'));
	}

	public function testExistsRoot(){
		$this->assertEquals(ROOT.'/lib/test_root.php',ld_exists('/test_root'));
	}

	public function testExistsGroup(){
		$this->assertEquals(ROOT.'/admin/lib/test_admin.php',ld_exists('admin/test_admin'));
	}
	
	public function testExistsItem(){
		$this->assertEquals(ROOT.'/admin/lib/item/test.php',ld_exists('item_test'));
	}
	
	public function testExistsItemGroup(){
		$this->assertEquals(ROOT.'/admin/lib/item/test.php',ld_exists('admin/item_test'));
	}

	public function testRelative(){
		ld('test_root');
		$this->assertTrue(class_exists('TestRoot2'));
	}

	public function testRoot(){
		ld('/test_root');
		$this->assertTrue(class_exists('TestRoot'));
	}

	public function testGroup(){
		ld('admin/test_admin');
		$this->assertTrue(class_exists('TestAdmin'));
	}
	
	public function testItem(){
		ld('admin/item_test');
		$this->assertTrue(class_exists('ItemTest'));
	}
	
	public function testOverloading(){
		ld('item_test');
		ld('admin/item_test');
		ld('/test_root');
		$this->assertTrue(class_exists('ItemTest'));
	}
	
	public function testFunc(){
		ld('/func/test');
		$this->assertTrue(is_callable('testing'));
	}

}
