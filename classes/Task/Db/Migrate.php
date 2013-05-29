<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Execute all unexecuted migrations. Behavior changes when supplied any of the parameters
 *
 * It can accept the following options:
 *  - version:      (string) set which version you want to go to. Will execute nessesary migrations to reach this version (up or down)
 *  - module        (string) indicates that it is necessary to work only with migrations of this module
 *  - steps         (integer) how many migrations to execute before stopping. works for both up and down.
 *  - dry-run       (boolean) if this flag is set, will run the migration without accually touching the database, only showing the result.
 * 
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_Db_Migrate extends Minion_Migration {

	public function _execute(array $options)
	{
		$executed = $this->executed_migrations();
		$unexecuted = $this->unexecuted_migrations();
		$all = $this->all_migrations();

		$up = array();
		$down = array();

		if (Arr::get($options, "module"))
		{
			$module = Arr::get($options, "module");
			$executed = $this->filter_migrations_by_module($executed, $module);
			$unexecuted = $this->filter_migrations_by_module($unexecuted, $module);
			$all = $this->filter_migrations_by_module($all, $module);
		}

		if (Arr::get($options, "version"))
		{
			foreach ($all as $migration)
			{
				if ( FALSE === array_search($migration, $executed) AND $migration['version'] <= $options['version'])
				{
					$up[] = $migration;
				}
				if ( FALSE !== array_search($migration, $executed) AND $migration['version'] > $options['version'])
				{
					$down[] = $migration;
				}
			}
		}
		elseif (Arr::get($options,"steps"))
		{
			$up = array_slice($unexecuted, 0, $options['steps']);
		}
		else
		{
			$up = $unexecuted;
		}

		$this->migrate($up, $down, Arr::get($options,"dry-run", FALSE));
	}
}
