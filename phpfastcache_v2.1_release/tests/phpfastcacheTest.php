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
		$cache->set('user_567', array('id' => 567, 'name' => 'Ringo'));
		$cache->set('user_890', array('id' => 890, 'name' => 'George'));
		$cache->set('product_1', array('name' => 'Laptop'));
		
		// Star query - check that we got the right number of cache items
		$allCaches = $cache->search('*');
		$this->assertEquals(5, count($allCaches));
		
		$userCaches = $cache->search('user_*');
		$this->assertEquals(4, count($userCaches));
		
		// Check that each cache items has the right keys
		$userCache = $userCaches[0];
		$this->assertArrayHasKey('key', $userCache);
		$this->assertArrayHasKey('value', $userCache);
		
		// Check that each cache value has the right keys
		$user = $userCache['value'];
		$this->assertArrayHasKey('id', $user);
		$this->assertArrayHasKey('name', $user);
		
		// ?? query - check that we got the right number of items
		$userCaches = $cache->search('user_??');
		$this->assertEquals(1, count($userCaches));
		
		// Check that we got the right value
		$user = $userCaches[0]['value'];		
		$this->assertEquals(34, $user['id']);
		$this->assertEquals('John', $user['name']);
		
		// Search for one specific object
		$userCaches = $cache->search('user_567');
		$this->assertEquals(1, count($userCaches));
		
		// Check that we got the right value
		$user = $userCaches[0]['value'];		
		$this->assertEquals(567, $user['id']);
		$this->assertEquals('Ringo', $user['name']);
		
		// Search for non-existant objects
		$userCaches = $cache->search('blabla');
		$this->assertEquals(0, count($userCaches));
	
		$userCaches = $cache->search('');
		$this->assertEquals(0, count($userCaches));
		
		// Check nothing is returned if no cache and * query
		$cache->clean();
		$userCaches = $cache->search('*');
		$this->assertEquals(0, count($userCaches));
	}

}