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
}
