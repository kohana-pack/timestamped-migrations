<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migrate down the latest migration. Behavior changes when supplied any of the parameters
 *
 * It can accept the following options:
 *  - version       (string) migrate all the way down to the specified migration.
 *  - module        (string) indicates that it is necessary to work only with migrations of this module
 *  - steps         (integer) how many times to migrate down
 *  - dry-run       (boolean) if this flag is set, will run the migration without accually touching the database, only showing the result.
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_Db_Migrate_Down extends Minion_Migration {

	protected $_options = array(
		'version' => NULL,
		'steps' => 1,
		'dry-run' => FALSE,
		'module' => NULL,
	);

	public function _execute(array $options)
	{
		$executed = $this->executed_migrations();

		$up = array();
		$down = array();

		if (Arr::get($options, 'module'))
		{
			$module = Arr::get($options, 'module');
			$executed = $this->filter_migrations_by_module($executed, $module);
		}

		if (Arr::get($options,"version"))
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

		$this->migrate($up, $down, Arr::get($options,"dry-run", FALSE));
	}
}
