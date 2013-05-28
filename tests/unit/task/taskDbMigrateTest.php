<?php

/**
 * Tests for Migraiton
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class taskDbMigrateTest extends Unittest_TestCase
{
	public function test_db_down_with_version()
	{
		$task_db_migrate_down_mock = $this->getMock('Task_Db_Migrate_Down', array('executed_migrations', 'migrate'), array(), '', FALSE);
		$task_db_migrate_down_mock
			->expects($this->any())
			->method('executed_migrations')
			->will($this->returnValue($this->get_executed_migrations()));

		$up = array();

		/****************************
		 * With version
		 ****************************/
		$down = array(
			array(
				'name' => 'test_migration_2',
				'file' => '/path/to/test/migration/file_2.php',
				'version' => 123456790,
				'module' => 'test',
			)
		);

		$task_db_migrate_down_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up),
				$this->equalTo($down)
			);

		$options = array(
			'version' => 123456790
		);

		$task_db_migrate_down_mock->_execute($options);

		/****************************
		 * With steps
		 ****************************/
		$down_with_steps = array(
			array(
				'name' => 'test_migration_3',
				'file' => '/path/to/test/migration/file_3.php',
				'version' => 123456793,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_2',
				'file' => '/path/to/test/migration/file_2.php',
				'version' => 123456790,
				'module' => 'test',
			),
		);

		$task_db_migrate_down_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up),
				$this->equalTo($down_with_steps)
			);

		$options = array(
			'steps' => 2
		);

		$task_db_migrate_down_mock->_execute($options);
	}

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

	public function get_executed_migrations()
	{
		return array_reverse($this->get_migrations());
	}
}
