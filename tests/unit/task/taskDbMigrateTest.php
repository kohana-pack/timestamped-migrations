<?php

/**
 * Tests for Migraiton
 * @group timestamped-migrations
 * @package Timestamped Migraitons
 */
class taskDbMigrateTest extends Unittest_TestCase
{
	public function test_db_down()
	{
		$task_db_migrate_down_mock = $this->getMock('Task_Db_Migrate_Down', array('executed_migrations', 'migrate'), array(), '', FALSE);
		$task_db_migrate_down_mock
			->expects($this->any())
			->method('executed_migrations')
			->will($this->returnValue(array_reverse($this->get_migrations())));

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

		/****************************
		 * With default steps value
		 ****************************/
		$down_with_default_steps_value = array(
			array(
				'name' => 'test_migration_3',
				'file' => '/path/to/test/migration/file_3.php',
				'version' => 123456793,
				'module' => 'test',
			),
		);

		$task_db_migrate_down_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up),
				$this->equalTo($down_with_default_steps_value)
			);

		$options = array(
			'steps' => 1
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

	public function test_db_up()
	{
		$task_db_migrate_up_mock = $this->getMock('Task_Db_Migrate_Up', array('unexecuted_migrations', 'migrate'), array(), '', FALSE);
		$task_db_migrate_up_mock
			->expects($this->any())
			->method('unexecuted_migrations')
			->will($this->returnValue($this->get_migrations()));

		$down = array();

		/****************************
		 * With version
		 ****************************/
		$up_with_version = array(
			array(
				'name' => 'test_migration_2',
				'file' => '/path/to/test/migration/file_2.php',
				'version' => 123456790,
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

		$options = array(
			'version' => 123456790
		);

		$task_db_migrate_up_mock->_execute($options);

		/****************************
		 * With steps
		 ****************************/
		$up_with_steps = array(
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
		);

		$task_db_migrate_up_mock
			->expects($this->at(1))
			->method('migrate')
			->with(
				$this->equalTo($up_with_steps),
				$this->equalTo($down)
			);

		$options = array(
			'steps' => 2
		);

		$task_db_migrate_up_mock->_execute($options);

		/****************************
		 * With default steps value
		 ****************************/
		$up_with_default_steps_value = array(
			array(
				'name' => 'test_migration',
				'file' => '/path/to/test/migration/file.php',
				'version' => 123456789,
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

		$options = array(
			'steps' => 1
		);

		$task_db_migrate_up_mock->_execute($options);
	}

	public function test_db_redo()
	{
		$task_db_migrate_redo_mock = $this->getMock(
			'Task_Db_Migrate_Redo',
			array('executed_migrations', 'migrate', 'all_migrations'),
			array(),
			'',
			FALSE
		);
		$task_db_migrate_redo_mock
			->expects($this->any())
			->method('executed_migrations')
			->will($this->returnValue(array_reverse($this->get_migrations())));
		$task_db_migrate_redo_mock
			->expects($this->any())
			->method('all_migrations')
			->will($this->returnValue($this->get_migrations()));

		/****************************
		 * With version
		 ****************************/
		$down_with_version = $up_with_version = array(
			array(
				'name' => 'test_migration_2',
				'file' => '/path/to/test/migration/file_2.php',
				'version' => 123456790,
				'module' => 'test',
			),
		);

		$options = array(
			'version' => 123456790,
		);

		$task_db_migrate_redo_mock
			->expects($this->at(2))
			->method('migrate')
			->with(
				$this->equalTo($up_with_version),
				$this->equalTo($down_with_version)
			);

		$task_db_migrate_redo_mock->_execute($options);

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
		$up_with_steps = array_reverse($down_with_steps);

		$options = array(
			'steps' => 2,
		);

		$task_db_migrate_redo_mock
			->expects($this->at(2))
			->method('migrate')
			->with(
				$this->equalTo($up_with_steps),
				$this->equalTo($down_with_steps)
			);

		$task_db_migrate_redo_mock->_execute($options);

		/****************************
		 * With default steps value
		 ****************************/
		$down_with_default_steps_value = array(
			array(
				'name' => 'test_migration_3',
				'file' => '/path/to/test/migration/file_3.php',
				'version' => 123456793,
				'module' => 'test',
			),
		);
		$up_with_default_steps_value = array_reverse($down_with_default_steps_value);

		$options = array(
			'steps' => 1,
		);

		$task_db_migrate_redo_mock
			->expects($this->at(2))
			->method('migrate')
			->with(
				$this->equalTo($up_with_default_steps_value),
				$this->equalTo($down_with_default_steps_value)
			);

		$task_db_migrate_redo_mock->_execute($options);
	}

	public function test_db_redo_not_executed_migration()
	{
		$task_db_migrate_redo_mock = $this->getMock(
			'Task_Db_Migrate_Redo',
			array('executed_migrations', 'migrate', 'all_migrations'),
			array(),
			'',
			FALSE
		);
		$task_db_migrate_redo_mock
			->expects($this->any())
			->method('all_migrations')
			->will($this->returnValue($this->get_migrations()));

		/****************************
		 * With version not executed migration
		 ****************************/
		$execute_migrations = $this->get_migrations();
		unset($execute_migrations[2]);

		$task_db_migrate_redo_mock
			->expects($this->once())
			->method('executed_migrations')
			->will($this->returnValue(array_reverse($execute_migrations)));

		$down_with_version_not_executed_migration = array();
		$up_with_version_not_executed_migration = array(
			array(
				'name' => 'test_migration_3',
				'file' => '/path/to/test/migration/file_3.php',
				'version' => 123456793,
				'module' => 'test',
			),
		);

		$options = array(
			'version' => 123456793,
		);

		$task_db_migrate_redo_mock
			->expects($this->at(2))
			->method('migrate')
			->with(
				$this->equalTo($up_with_version_not_executed_migration),
				$this->equalTo($down_with_version_not_executed_migration)
			);

		$task_db_migrate_redo_mock->_execute($options);
	}

	public function test_db_migrate()
	{
		$task_db_migrate_mock = $this->getMock(
			'Task_Db_Migrate',
			array('executed_migrations', 'migrate', 'all_migrations', 'unexecuted_migrations'),
			array(),
			'',
			FALSE
		);

		$all_migrations = $this->get_migrations();
		$executed = array_values(Arr::extract($all_migrations, array(1)));
		$unexecuted = array_values(Arr::extract($all_migrations, array(0,2)));

		$task_db_migrate_mock
			->expects($this->any())
			->method('all_migrations')
			->will($this->returnValue($all_migrations));
		$task_db_migrate_mock
			->expects($this->any())
			->method('executed_migrations')
			->will($this->returnValue($executed));
		$task_db_migrate_mock
			->expects($this->any())
			->method('unexecuted_migrations')
			->will($this->returnValue($unexecuted));


		/****************************
		 * With version
		 ****************************/
		$up_with_version = array($unexecuted[0]);
		$down_with_version = $executed;

		$options = array(
			'version' => 123456789
		);

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_with_version),
				$this->equalTo($down_with_version)
			);

		$task_db_migrate_mock->_execute($options);


		/****************************
		 * With steps
		 ****************************/
		$up_with_steps = $unexecuted;
		$down_with_steps = array();

		$options = array(
			'steps' => 2,
		);

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_with_steps),
				$this->equalTo($down_with_steps)
			);

		$task_db_migrate_mock->_execute($options);

		/****************************
		 * Without all
		 ****************************/
		$up_without_all = $unexecuted;
		$down_without_all = array();

		$options = array();

		$task_db_migrate_mock
			->expects($this->at(3))
			->method('migrate')
			->with(
				$this->equalTo($up_without_all),
				$this->equalTo($down_without_all)
			);

		$task_db_migrate_mock->_execute($options);
	}
}
