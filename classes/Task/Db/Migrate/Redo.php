<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migrate down then up one migration. Behavior changes when supplied any of the parameters
 *
 * @param string version migrate all the way down to the specified migration, and then all the way back up.
 * @param integer steps how many times to migrate down before going back up.
 * @param boolean dry-run if this flag is set, will run the migration without accually touching the database, only showing the result.
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_Db_Migrate_Redo extends Minion_Migration {

	protected $_options = array(
		'version' => NULL,
		'steps' => 1,
		'dry-run' => FALSE,
		'module' => NULL,
	);

	public function _execute(array $options)
	{
		$executed = $this->executed_migrations();
		$all_migrations = $this->all_migrations();

		$up = array();
		$down = array();

		if (Arr::get($options, 'module'))
		{
			$module = Arr::get($options, 'module');
			$executed = $this->filter_migrations_by_module($executed, $module);
			$all_migrations = $this->filter_migrations_by_module($all_migrations, $module);
		}

		if (Arr::get($options, 'version') !== NULL)
		{
			foreach ($executed as $migration)
			{
				if($migration['version'] == $options['version'])
				{
					$down[] = $migration;
				}
			}
		}
		else
		{
			$down = array_slice($executed, 0, $options['steps']);
		}

		if (Arr::get($options, 'version') !== NULL)
		{
			foreach ($all_migrations as $migration)
			{
				if($migration['version'] == $options['version'])
				{
					$up[] = $migration;
				}
			}
		}
		else
		{
			$up = array_reverse($down);
		}

		$this->migrate($up, $down, Arr::get($options,"dry-run", FALSE));
	}
}
