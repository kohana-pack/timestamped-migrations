<?php

/**
 * Tests for Migraiton
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class MigrationsTest extends Unittest_TestCase
{
	public function test_get_executed_migrations()
	{
		$migrations = $this->get_migrations();

		$driver_mysql_version_mock = $this->getMockForAbstractClass('Migration_Driver_Versions');
		$driver_mysql_version_mock->expects($this->any())->method('get')->will($this->returnValue(array(123456789, 123456793)));

		$migrations_mock = $this->getMock('Migrations', array('get_migrations'), array('config' => array('type' => 'mysql')));
		$migrations_mock->expects($this->any())->method('get_migrations')->will($this->returnValue($migrations));
		$migrations_mock->get_driver()->versions($driver_mysql_version_mock);

		$executed_migrations = $migrations_mock->get_executed_migrations();
		$unexecuted_migrations = $migrations_mock->get_unexecuted_migrations();

		$this->assertCount(2, $executed_migrations);
		$this->assertEquals(123456789, Arr::path($executed_migrations, '0.version'));
		$this->assertEquals(123456793, Arr::path($executed_migrations, '1.version'));

		$this->assertCount(1, $unexecuted_migrations);
		$this->assertEquals(123456790, Arr::path($unexecuted_migrations, '0.version'));
	}

	public function test_get_executed_migrations_empty()
	{
		$migrations = $this->get_migrations();

		$driver_mysql_version_mock = $this->getMockForAbstractClass('Migration_Driver_Versions');
		$driver_mysql_version_mock->expects($this->any())->method('get')->will($this->returnValue(array()));

		$migrations_mock = $this->getMock('Migrations', array('get_migrations'), array('config' => array('type' => 'mysql')));
		$migrations_mock->expects($this->any())->method('get_migrations')->will($this->returnValue($migrations));
		$migrations_mock->get_driver()->versions($driver_mysql_version_mock);

		$executed_migrations = $migrations_mock->get_executed_migrations();
		$unexecuted_migrations = $migrations_mock->get_unexecuted_migrations();

		$this->assertCount(0, $executed_migrations);

		$this->assertCount(3, $unexecuted_migrations);
		$this->assertEquals(123456789, Arr::path($unexecuted_migrations, '0.version'));
		$this->assertEquals(123456790, Arr::path($unexecuted_migrations, '1.version'));
		$this->assertEquals(123456793, Arr::path($unexecuted_migrations, '2.version'));
	}

	/**
	 * @return array
	 */
	public function get_migrations()
	{
		$migrations = array(
			array(
				'name' => 'test_migration',
				'file' => '/path/to/test/migration/file.php',
				'version' => 123456789,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_2',
				'file' => '/path/to/test/migration/file_2.php',
				'version' => 123456790,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_3',
				'file' => '/path/to/test/migration/file_3.php',
				'version' => 123456793,
				'module' => 'test',
			),
		);

		return $migrations;
	}

	public function get_migration_files()
	{
		return array(
			APPPATH.'migrations/123456789_test_1.php',
			MODPATH.'test_module/migrations/123456790_test_2.php',
			MODPATH.'test_module/migrations/123456791_test_3.php',
		);
	}

	/**
	 * @return array
	 */
	public function get_kohana_modules()
	{
		return array(
			'database' => MODPATH . 'database',
			'minion' => MODPATH . 'minion',
			'unittest' => MODPATH . 'unittest',
			'migration' => MODPATH . 'timestamped-migrations',
			'test_module' => MODPATH . 'test_module',
		);
	}

	public function test_generate_new_migration_file()
	{
		$test_module_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kohana_modules_timestamped_migrations_test';
		if( ! file_exists($test_module_path))
		{
			mkdir($test_module_path);
		}
		Kohana::modules(array(
			'database'   => MODPATH.'database',
			'minion'     => MODPATH.'minion',
			'unittest'   => MODPATH.'unittest',
			'migration'  => MODPATH.'timestamped-migrations',
			'test_module'=> $test_module_path,
		));

		$migrations_mock = $this->getMock('Migrations', array('write_migration_file'));

		$migration_path = $migrations_mock->generate_new_migration_file('test', NULL, 'test_module');
		$this->assertContains($test_module_path.DIRECTORY_SEPARATOR.'migrations', $migration_path);


		$migration_path = $migrations_mock->generate_new_migration_file('test', NULL, NULL);
		$this->assertContains(APPPATH.'migrations', $migration_path);

		rmdir($test_module_path.DIRECTORY_SEPARATOR.'migrations');
		rmdir($test_module_path);
	}

	public function test_get_migrations()
	{
		$migrations_mock = $this->getMock(
			'Migrations',
			array('get_migration_files', 'get_kohana_modules'),
			array(),
			'',
			FALSE
		);

		$migrations_mock
			->expects($this->any())
			->method('get_kohana_modules')
			->will($this->returnValue($this->get_kohana_modules()));

		$migrations_mock
			->expects($this->any())
			->method('get_migration_files')
			->will($this->returnValue($this->get_migration_files()));

		$expected = array(
			array(
				'name' => '123456789_test_1',
				'file' => APPPATH.'migrations/123456789_test_1.php',
				'version' => '123456789',
				'module' => 'application',
			),
			array(
				'name' => '123456790_test_2',
				'file' => MODPATH.'test_module/migrations/123456790_test_2.php',
				'version' => '123456790',
				'module' => 'test_module',
			),
			array(
				'name' => '123456791_test_3',
				'file' => MODPATH.'test_module/migrations/123456791_test_3.php',
				'version' => '123456791',
				'module' => 'test_module',
			),
		);

		$actual = $migrations_mock->get_migrations();
		$this->assertEquals($expected, $actual);
	}
}
