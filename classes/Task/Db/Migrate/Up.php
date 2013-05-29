<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Migrate up the first unexecuted migration. Behavior changes when supplied any of the parameters
 *
 * @param string version migrate all the way up to the specified migration.
 * @param integer steps how many times to migrate up
 * @param boolean dry-run if this flag is set, will run the migration without accually touching the database, only showing the result.
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_Db_Migrate_Up extends Minion_Migration {

	protected $_options = array(
		'version' => NULL,
		'steps' => 1,
		'dry-run' => FALSE
	);

	public function _execute(array $options)
	{
		$unexecuted = $this->unexecuted_migrations();

		$up = array();
		$down = array();

		if (Arr::get($options, 'module'))
		{
			$module = Arr::get($options, 'module');
			$unexecuted = $this->filter_migrations_by_module($unexecuted, $module);
		}

		if (Arr::get($options, 'version') !== NULL)
		{
			foreach ($unexecuted as $migration)
			{
				if($migration['version'] == $options['version'])
				{
					$up[] = $migration;
				}
			}
		}
		else
		{
			$up = array_slice($unexecuted, 0, $options['steps']);
		}

		$this->migrate($up, $down, Arr::get($options,"dry-run", FALSE));
	}
}
