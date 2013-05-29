<?php

/**
 * Tests for Migraiton
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class taskDbMigrateWithModuleOptionTest extends Unittest_TestCase
{
	public function get_migrations()
	{
		$migrations = array(
			array(
				'name' => 'test_migration',
				'file' => '/path/to/test/migration/file.php',
				'version' => 100000001,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_2',
				'file' => '/path/to/test/migration/file_2.php',
				'version' => 100000002,
				'module' => 'test_2',
			),
			array(
				'name' => 'test_migration_3',
				'file' => '/path/to/test/migration/file_3.php',
				'version' => 100000003,
				'module' => 'test_2',
			),
			array(
				'name' => 'test_migration_4',
				'file' => '/path/to/test/migration/file_4.php',
				'version' => 100000004,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_5',
				'file' => '/path/to/test/migration/file_5.php',
				'version' => 100000005,
				'module' => 'test_2',
			),
			array(
				'name' => 'test_migration_6',
				'file' => '/path/to/test/migration/file_6.php',
				'version' => 100000006,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_7',
				'file' => '/path/to/test/migration/file_7.php',
				'version' => 100000007,
				'module' => 'test',
			),
		);

		return $migrations;
	}

	public function test_db_migrate_task()
	{
		$task_db_migrate_mock = $this->getMock(
			'Task_Db_Migrate',
			array('migrate', 'executed_migrations', 'all_migrations', 'unexecuted_migrations'),
			array(),
			'',
			FALSE
		);

		$all_migrations = $this->get_migrations();
		$executed_migrations = array_reverse(array_values(Arr::extract($this->get_migrations(), array(0,1,2,5))));
		$unexecuted_migrations = array_values(Arr::extract($this->get_migrations(), array(3,4,6)));

		$task_db_migrate_mock
			->expects($this->any())
			->method('all_migrations')
			->will($this->returnValue($all_migrations));
		$task_db_migrate_mock
			->expects($this->any())
			->method('executed_migrations')
			->will($this->returnValue($executed_migrations));
		$task_db_migrate_mock
			->expects($this->any())
			->method('unexecuted_migrations')
			->will($this->returnValue($unexecuted_migrations));

		/****************************
		 * With module
		 ****************************/
		$options = array(
			'module' => 'test_2',
		);

		$up_with_module = array(
			array(
				'name' => 'test_migration_5',
				'file' => '/path/to/test/migration/file_5.php',
				'version' => 100000005,
				'module' => 'test_2',
			),
		);
		$down_with_module = array();

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_with_module),
				$this->equalTo($down_with_module)
			);

		$task_db_migrate_mock->_execute($options);

		/****************************
		 * With module and version
		 ****************************/
		$options = array(
			'module' => 'test',
			'version' => 100000005,
		);

		$up_with_module_and_version = array(
			array(
				'name' => 'test_migration_4',
				'file' => '/path/to/test/migration/file_4.php',
				'version' => 100000004,
				'module' => 'test',
			),
		);
		$down_with_module_and_version = array(
			array(
				'name' => 'test_migration_6',
				'file' => '/path/to/test/migration/file_6.php',
				'version' => 100000006,
				'module' => 'test',
			),
		);

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_with_module_and_version),
				$this->equalTo($down_with_module_and_version)
			);

		$task_db_migrate_mock->_execute($options);

		/****************************
		 * With module and steps=2
		 ****************************/
		$options = array(
			'module' => 'test',
			'steps' => 2,
		);

		$up_with_module_and_steps = array(
			array(
				'name' => 'test_migration_4',
				'file' => '/path/to/test/migration/file_4.php',
				'version' => 100000004,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_7',
				'file' => '/path/to/test/migration/file_7.php',
				'version' => 100000007,
				'module' => 'test',
			),
		);
		$down_with_module_and_steps = array();

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_with_module_and_steps),
				$this->equalTo($down_with_module_and_steps)
			);

		$task_db_migrate_mock->_execute($options);

		/****************************
		 * With module and steps=1
		 ****************************/
		$options = array(
			'module' => 'test',
			'steps' => 1,
		);

		$up_with_module_and_steps = array(
			array(
				'name' => 'test_migration_4',
				'file' => '/path/to/test/migration/file_4.php',
				'version' => 100000004,
				'module' => 'test',
			),
		);
		$down_with_module_and_steps = array();

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_with_module_and_steps),
				$this->equalTo($down_with_module_and_steps)
			);

		$task_db_migrate_mock->_execute($options);
	}

	public function test_db_up_task()
	{
		$task_db_migrate_up_mock = $this->getMock('Task_Db_Migrate_Up', array('unexecuted_migrations', 'migrate'), array(), '', FALSE);
		$task_db_migrate_up_mock
			->expects($this->any())
			->method('unexecuted_migrations')
			->will($this->returnValue($this->get_migrations()));

		$down = array();

		/****************************
		 * With version and module
		 ****************************/
		$options = array(
			'version' => 100000004,
			'module' => 'test',
		);

		$up_with_version = array(
			array(
				'name' => 'test_migration_4',
				'file' => '/path/to/test/migration/file_4.php',
				'version' => 100000004,
				'module' => 'test',
			)
		);

		$task_db_migrate_up_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up_with_version),
				$this->equalTo($down)
			);

		$task_db_migrate_up_mock->_execute($options);

		/****************************
		 * With steps
		 ****************************/
		$options = array(
			'steps' => 2,
			'module' => 'test',
		);

		$up_with_steps = array(
			array(
				'name' => 'test_migration',
				'file' => '/path/to/test/migration/file.php',
				'version' => 100000001,
				'module' => 'test',
			),
			array(
				'name' => 'test_migration_4',
				'file' => '/path/to/test/migration/file_4.php',
				'version' => 100000004,
				'module' => 'test',
			),
		);

		$task_db_migrate_up_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up_with_steps),
				$this->equalTo($down)
			);

		$task_db_migrate_up_mock->_execute($options);

		/****************************
		 * With default steps value
		 ****************************/
		$options = array(
			'steps' => 1,
			'module' => 'test',
		);

		$up_with_default_steps_value = array(
			array(
				'name' => 'test_migration',
				'file' => '/path/to/test/migration/file.php',
				'version' => 100000001,
				'module' => 'test',
			),
		);

		$task_db_migrate_up_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up_with_default_steps_value),
				$this->equalTo($down)
			);

		$task_db_migrate_up_mock->_execute($options);
	}
}
