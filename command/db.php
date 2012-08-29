<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Command line interface for Migrations.
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Command_DB extends Command
{
	protected $migrations = NULL;

	public function __construct()
	{
		$this->migrations = new Migrations(array('log' => 'Command::log'));
	}

	const VERSION_BRIEF = "Display last executed timestamp";
	public function version()
	{
		$migrations = $this->migrations->get_executed_migrations();
		Command::log("Current Version: ".Command::colored(end($migrations), Command::OK));
	}

	const MIGRATE_BRIEF = "Execute migrations";
	public function migrate(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate');
	}

	protected function _migrate($executed, $unexecuted, $all, $arguments, $steps, &$up, &$down)
	{
		if (isset($arguments["version"]))
		{
			foreach ($all as $migration)
			{
			  if ( ! in_array($migration, $executed) AND $migration <= $arguments["version"])
			  {
			    $up[] = $migration;
			  }
			  if ( in_array( $migration, $executed) AND $migration > $arguments["version"]) 
			  {
			    $down[] = $migration;
			  }
			}
		}
		else
		{
			$up = $steps ? array_slice($unexecuted, 0, $steps) : $unexecuted;
		}
	}

	const MIGRATE_UP_BRIEF = "Execute next up migration";
	public function migrate_up(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate_up');
	}

	protected function _migrate_up($executed, $unexecuted, $all, $arguments, $steps, &$up, &$down)
	{
		if (isset($arguments["version"]))
		{
			if (in_array($arguments["version"], $unexecuted))
			{
				$up[] = $arguments["version"];	
			}
		}
		else
		{
			$up = array_slice($unexecuted, 0, $steps ? $steps : 1);
		}
	}	

	const MIGRATE_DOWN_BRIEF = "Execute next down migration";
	const MIGRATE_DOWN_DESC = "This will run the down method from the latest migration. If you need to undo several migrations you can provide a --step option
You can also give a --version and it will roll back all the migrations down to the specified";

	public function migrate_down(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate_down');
	}

	protected function _migrate_down($executed, $unexecuted, $all, $options, $steps, &$up, &$down)
	{
		if (isset($options["version"]))
		{
			if (in_array($options["version"], $executed))
			{
				$down[] = $options["version"];	
			}
		}
		else
		{
			$down = array_slice($executed, 0, $steps ? $steps : 1);
		}
	}	

	const MIGRATE_REDO_BRIEF = "Execute next down and up migration";
	const MIGRATE_REDO_DESC = "The db:migrate:redo command is a shortcut for doing a rollback and then migrating back up again. 
As with the db:rollback task you can use the --step option if you need to go more than one version back
You can also give a --version and it will roll back and up all the migrations to the specified";
	public function migrate_redo(Command_Options $options)
	{
		$this->_execute_migration($options, '_migrate_redo');
	}

	protected function _migrate_redo($executed, $unexecuted, $all, $options, $steps, &$up, &$down)
	{
		if (isset($options["version"]))
		{
			if (in_array($options["version"], $executed))
			{
				$down[] = $options["version"];	
			}
		}
		else
		{
			$down = array_slice($executed, 0, $steps ? $steps : 1);
		}

		if (isset($arguments["version"]))
		{
			$up[] = $arguments["version"];
		}
		else
		{
			$up = array_reverse($down);
		}
	}	

	const ROLLBACK_BRIEF = "Execute next down migration";
	const ROLLBACK_DESC = "This will run the down method from the latest migration. If you need to undo several migrations you can provide a --step option
You can also give a --version and it will roll back all the migrations down to the specified";	
	public function rollback(Command_Options $options)
	{
		$this->migrate_down($options);
	}

	protected function _execute_migration(Command_Options $options, $func)
	{
		$dry_run = $options->has('dry-run');

		$executed = array_reverse($this->migrations->get_executed_migrations());
		$unexecuted = $this->migrations->get_unexecuted_migrations();
		$all = $this->migrations->get_migrations();

		$steps = isset($options['step']) ? (int) $options['step'] : NULL;

		$up = array();
		$down = array();

		$this->$func($executed, $unexecuted, $all, $options, $steps, $up, $down);

		if ( ! count($down) AND ! count($up))
		{
			$this->log("Nothing to do", Command::OK);
		}
		else
		{
			foreach ($down as $version) 
			{
	      $migration = $this->migrations->load_migration($version);
		
				$this->log(Command::colored($version.' '.get_class($migration).' : migrating down', Command::WARNING). ($dry_run ? Command::colored(" -- Dry Run", 'purple') : ''));
				$start = microtime(TRUE);

				$migration->dry_run($dry_run)->down();

				if ( ! $dry_run)
				{
					$this->migrations->set_unexecuted($version);
				}

				$end = microtime(TRUE);
				$this->log($version.' '.get_class($migration).' : migrated ('.number_format($end-$start, 4).'s)', Command::WARNING);
			}
			
			foreach ($up as $version) 
			{
				$migration = $this->migrations->load_migration($version);

				$this->log(Command::colored($version.' '.get_class($migration).' : migrating up', Command::OK). ($dry_run ? Command::colored(" -- Dry Run", 'purple') : ''));
				$start = microtime(TRUE);

				$migration->dry_run($dry_run)->up();
				
				if ( ! $dry_run)
				{
					$this->migrations->set_executed($version);
				}			

				$end = microtime(TRUE);
				$this->log($version.' '.get_class($migration).' : migrated ('.number_format($end-$start, 4).'s)', Command::OK);
			}

			$this->structure_dump($options);			
		}

	}

	const RECREATE_BRIEF = "Drop all tables and re-run all migrations";
	const RECREATE_DESC = "This will drop all existing tables, removing all the data, and then recreate the database by runing up all the migrations. Will prompt before preceding, or if you pass --force will preceed without prompting.";	

	public function recreate(Command_Options $options)
	{
		if ( ! $options->has('force') )
		{
			$this->log("This will destroy all data in the current database. Are you sure? [yes/NO]", Command::WARNING);
			$input = strtolower(trim(fgets(STDIN)));
		}
		else
		{
			$input = 'yes';
		}

		if ($input == 'yes')
		{
			$dry_run = $options->has('dry-run');

			$this->log(Command::colored('dropping tables', Command::OK). ($dry_run ? Command::colored(" -- Dry Run", 'purple') : ''));

			if ( ! $dry_run)
			{
				$this->migrations->clear_all();
			}

			$this->migrate($options);
		}
		else
		{
			$this->log("Nothing done", Command::WARNING);
		}		
	}

	static private function _db_params($database)
	{
		$db = Kohana::$config->load("database.$database.connection");	

		if ( ! isset($db['database']) )
		{
			$matches = array();
			if ( ! preg_match('/dbname=([^;]+);', $db['dsn'], $matches));
				throw new Kohana_Exception("Error connecting to database, database missing");
			$db['database'] = $matches[1];
		}

		$db['type'] = Kohana::$config->load("database.$database.type");

		return $db;
	}

	const STRUCTURE_DUMP_BRIEF = "Dump sql schema.sql file";
	const STRUCTURE_DUMP_DESC = "Dump sql schema.sql file";

	public function structure_dump(Command_Options $options, $database = NULL)
	{
		$db = self::_db_params($database ? $database : 'default');

		$file = Kohana::$config->load("migrations.path").DIRECTORY_SEPARATOR.'schema.sql';	

		$this->log_func("system", array(strtr("mysqldump -u:username -p:password --skip-comments --add-drop-database --add-drop-table --no-data :database | sed 's/AUTO_INCREMENT=[0-9]*\b//' > :file ", array(
			':username' => $db['username'],
			':password' => $db['password'],
			':database' => $db['database'],
			':file'      => $file
		))), Command::OK, "Saving structure ".$db['database']." to ".Debug::path($file));
	}

	const STRUCTURE_LOAD_BRIEF = "Load information to database from the schema.sql file";
	const STRUCTURE_LOAD_DESC = "Load sql file, prompts before execution, can pass --force to skip";

	public function structure_load(Command_Options $options, $database = NULL)
	{
		$db = self::_db_params($database ? $database : 'default');

		if ( ! $options->has('force') )
		{
			$this->log("This will destroy database ".$db['database']."Are you sure? [yes/NO]", Command::WARNING);
			$input = strtolower(trim(fgets(STDIN)));
		}
		else
		{
			$input = 'yes';
		}

		if ( $input == 'yes')
		{
			$schema = Kohana::$config->load("migrations.path").DIRECTORY_SEPARATOR.'schema.sql';	
			$this->_load_sql_file($schema, $database);
		}
	}

	protected function _load_sql_file($file, $database = NULL)
	{
		$db = self::_db_params($database ? $database : 'default');

		$this->log_func("system", array(strtr("mysql -u:username -p:password :database < :file ", array(
			':username' => $db['username'],
			':password' => $db['password'],
			':database' => $db['database'],
			':file'      => $file
		))), Command::OK, "Loading data from ".Debug::path($file)." to ".$db['database']);

	}


	const STRUCTURE_COPY_BRIEF = "Copy structure from default DB to another";
	const STRUCTURE_COPY_DESC = "This basically executes db:structure:dump and db:structure:load sequentialy";

	public function structure_copy(Command_Options $options, $database)
	{
		$this->structure_dump($options);
		$this->structure_dump($database);
	}

	const TEST_LOAD_BRIEF = "Load information to the test database from the schema.sql file";
	const TEST_LOAD_DESC = "Load sql file schema file, in the configured location, along with files in <MODULE FOLDER>/tests/test_data/structure/test-schema-<database type>.sql files from the different modules (example: MODPATH/jam/tests/test_data/test-schema-mysql.sql)";
	public function test_load(Command_Options $options)
	{
		$db = self::_db_params(Kohana::TESTING);
		
		$module_test_schemas = Kohana::find_file('tests/test_data/structure', 'test-schema-'.$db['type'], 'sql', TRUE);

		$schema = Kohana::$config->load("migrations.path").DIRECTORY_SEPARATOR.'schema.sql';	
		$this->_load_sql_file($schema, Kohana::TESTING);

		foreach ($module_test_schemas as $schema) 
		{
			$this->_load_sql_file($schema, Kohana::TESTING);
		}

	}

	const GENERATE_BRIEF = "Generate a migration file";
	public function generate(Command_Options $options, $name = NULL)
	{
		if ( ! $name)
			throw new Kohana_Exception("Please set a name for the migration ( db:generate {name} )");

		$template = $options->has('template') ? $options['template'] : NULL;

		$migration = $this->migrations->generate_new_migration_file($name, $template);

		self::log(self::colored($migration, Command::OK).self::colored(' Migration File Generated', Command::WARNING));
	}

}