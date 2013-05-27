<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Migrations class.
 *
 * @package    Despark/timestamped-migrations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Migrations
{
	protected $config;
	protected $driver;
	protected $migrations = array();
	public $output = NULL;

	/**
	 * Intialize migration library
	 *
	 * @param   bool   Do we want output of migration steps?
	 * @param   string Database group
	 */
	public function __construct($config = NULL)
	{
		$this->config = arr::merge(Kohana::$config->load('migrations')->as_array(), (array) $config);

		$database = Kohana::$config->load('database.'.Arr::get(Kohana::$config->load('migrations'), 'database', 'default'));

		// Set the driver class name
		$driver = 'Migration_Driver_'.ucfirst(strtolower($database['type']));

		// Create the database connection instance
		$this->driver = new $driver(Arr::get(Kohana::$config->load('migrations'), 'database', 'default'));    

		$this->driver->versions()->init();

		if( ! is_dir(APPPATH.DIRECTORY_SEPARATOR.$this->config['path']))
		{
			mkdir(APPPATH.DIRECTORY_SEPARATOR.$this->config['path'], 0777, TRUE);
		}
	}
	
	public function set_executed($version)
	{
		$this->driver->versions()->set($version['version']);
	}
	
	public function set_unexecuted($version)
	{
		$this->driver->versions()->clear($version['version']);
	}

	public function generate_new_migration_file($name, $actions_template = NULL, $module = NULL)
	{
		$actions = new Migration_Actions($this->driver);

		if ($actions_template)
		{
			$actions->template(getcwd().DIRECTORY_SEPARATOR.$actions_template);
		}
		else
		{
			$actions->parse($name);
		}

		$template = file_get_contents(Kohana::find_file('templates', 'migration', 'tpl'));
		$class_name = str_replace(' ', '_', ucwords(str_replace('_',' ',$name)));
		$filename = sprintf("%d_$name.php", time());
		if($module)
		{
			$module_path = Arr::get(Kohana::modules(), $module);
			if($module_path)
			{
				$path = $module_path.$this->config['path'];
			}
			else
			{
				throw new Migration_Exception('Module, :name, not found', array(':name' => $module));
			}
		}
		else
		{
			$path = APPPATH.$this->config['path'];
		}

		if( ! file_exists($path))
		{
			mkdir($path);
		}

		$full_path = $path . DIRECTORY_SEPARATOR . $filename;
		$migration_file_content = strtr($template, array(
			'{up}' => join("\n", array_map('Migrations::indent', $actions->up)),
			'{down}' => join("\n", array_map('Migrations::indent', $actions->down)),
			'{class_name}' => $class_name
		));

		$this->write_migration_file($full_path, $migration_file_content);

		return $full_path;
	}

	protected function write_migration_file($path, $content)
	{
		return file_put_contents($path, $content);
	}

	static function indent($action)
	{
		return "\t\t$action";
	}

	/**
	 * Loads a migration
	 *
	 * @param   integer   Migration version number
	 * @return  Migration_Core  Class object
	 */
	public function load_migration($version)
	{
		$f = $version['file'];

		if (count($f) > 1)
			throw new Migration_Exception('Only one migration per step is permitted, there are :count of version :version', array(':count' => count($f), ':version' => $version['version']));

		if (count($f) == 0)
			throw new Migration_Exception('Migration step not found with version :version', array(":version" => $version['version']));

		$file = basename($f);
		$name = basename($f, EXT);

		// Filename validations
		if ( ! preg_match('/^\d+_(\w+)$/', $name, $match))
			throw new Migration_Exception('Invalid filename :file', array(':file' => $file));

		$match[1] = strtolower($match[1]);

		include_once $f;
		$class = ucfirst($match[1]);

		if ( ! class_exists($class))
			throw new Migration_Exception('Migration class :class does not exist', array( ':class' => $class));

		return new $class($this->config);
	}
	
	/**
	 * Retrieves all the timestamps of the migration files 
	 *
	 * @return   array
	 */
	public function get_migrations()
	{
		if ( ! $this->migrations)
		{
			$migrations = Kohana::list_files($this->config['path']);
			$migrations = array_filter($migrations, function($var) {
				return preg_match('/'.EXT.'$/', $var);
			});
			foreach ((array) $migrations as $file)
			{
				$name = basename($file, EXT);
				$migration = array();
				$migration['name'] = $name;
				$migration['file'] = $file;
				$migration['version'] = preg_replace("/(\d+)_.*/", "$1", $name);
				$migration['module'] = $this->module_by_file($file);
				$this->migrations[] = $migration;
			}
		}
		return $this->migrations;
	}

	public function module_by_file($file)
	{
		foreach (Kohana::modules() as $module_name => $module_path)
		{
			if (preg_match("/".preg_quote($module_path, "/")."/", $file))
			{
				return $module_name;
			}
		}

		return false;
	}

	public function clear_all()
	{
		$this->driver->clear_all();
		$this->driver->versions()->clear_all();
		return $this;
	}

	public function get_executed_migrations()
	{
		// TODO: handle the situation when the migration does not exist
		$migrations = $this->get_migrations();
		$versions = $this->driver->versions()->get();
		$versions = array_map(
			function($version) use($migrations)
			{
				$array = array_filter(
					$migrations,
					function($value) use($version)
					{
						return $value['version'] == $version;
					}
				);
				return array_shift($array);
			},
			$versions
		);

		return $versions;
	}

	public function get_unexecuted_migrations()
	{
		$result =  array_udiff(
			$this->get_migrations(),
			$this->get_executed_migrations(),
			function ($a, $b)
			{
				if($a['version'] == $b['version']) return 0;
				return ($a['version'] > $b['version'])?1:-1;
			}
		);

		return array_values($result);
	}

	protected function execute($version, $direction, $dry_run)
	{
		$migration = $this->load_migration($version)->dry_run($dry_run);

		$this->log($version['version'].' '.get_class($migration).' : migrating '.$direction.($dry_run ? " -- Dry Run" : ''));
		$start = microtime(TRUE);

		switch ($direction) 
		{
			case 'down':
				$migration->down();
				if ( ! $dry_run)
				{
					$this->set_unexecuted($version);
				}
			break;
			
			case 'up':
				$migration->up();
				if ( ! $dry_run)
				{
					$this->set_executed($version);
				}
			break;
		}

		$end = microtime(TRUE);
		$this->log($version['version'].' '.get_class($migration).' : migrated ('.number_format($end - $start, 4).'s)');
	}

	public function execute_all($up = array(), $down = array(), $dry_run = FALSE)
	{
		if ( ! count($down) AND ! count($up))
		{
			$this->log("Nothing to do");
		}
		else
		{
			foreach ($down as $version) 
			{
				$this->execute($version, 'down', $dry_run);
			}
			
			foreach ($up as $version) 
			{
				$this->execute($version, 'up', $dry_run);
			}
		}
	}

	public function log($message)
	{
		if ($this->config['log'])
		{
			call_user_func($this->config['log'], $message);
		}
		else
		{
			echo $message."\n";
			ob_flush();
		}
	}

	public function get_driver()
	{
		return $this->driver;
	}
}