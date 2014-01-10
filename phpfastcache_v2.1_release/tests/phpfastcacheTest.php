<?php

require_once dirname(dirname(__FILE__)) . '/phpfastcache/phpfastcache.php';

class phpfastcacheTest extends PHPUnit_Framework_TestCase {
	
	static private function cacheDir() {
		$output = dirname(__FILE__) . '/cache';
		if (!is_dir($output)) @mkdir($output, 0755);
		return $output;
	}
	
	static private function defaultOptions() {
		return array(
			'path' => self::cacheDir(),
			'securityKey' => get_current_user(),
		);
	}
	
	static private function deleteFolder($folder) {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			$todo($fileinfo->getRealPath());
		}
		
		rmdir($folder);
	}
	
	protected function setUp() {
		
	}
	
	protected function tearDown() {
		self::deleteFolder(self::cacheDir());
	}

	public function testSearch() {
		$cache = new phpfastcache('files', self::defaultOptions());
		
		$cache->set('user_1', array('id' => 12, 'name' => 'Paul'));
		$cache->set('user_34', array('id' => 34, 'name' => 'John'));
		$cache->set('user_567', array('id' => 56, 'name' => 'Ringo'));
		$cache->set('user_890', array('id' => 78, 'name' => 'George'));
		
		$userCaches = $cache->search('user_*');
		$this->assertEquals(4, count($userCaches));
		
		$userCache = $userCaches[0];
		$this->assertArrayHasKey('key', $userCache);
		$this->assertArrayHasKey('value', $userCache);
		
		$user = $userCache['value'];
		$this->assertArrayHasKey('id', $user);
		$this->assertArrayHasKey('name', $user);
		
		$userCaches = $cache->search('user_??');
		$this->assertEquals(1, count($userCaches));
		
		$user = $userCaches[0]['value'];		
		$this->assertEquals(34, $user['id']);
		$this->assertEquals('John', $user['name']);
	}

}