<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Get the current migration version
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Task_Db_Version extends Minion_Task {

	public function _execute(array $options)
	{
		$migrations = new Migrations(array('log' => 'Minion_CLI::write'));

		$executed_migrations = $migrations->get_executed_migrations();
		$executed_migrations_versions = Arr::path($executed_migrations, '*.version', array());

		$migration_modules = array();

		foreach ($executed_migrations as $migration) {
			if (is_array($migration))
			{
				$migration_modules[arr::get($migration, 'module')] = arr::get($migration, 'version');
			}
		}

		Minion_CLI::write('Current version for modules : ');
		if (count($migration_modules) == 0)
		{
			Minion_CLI::write("No applied migrations");
		}
		else
		{
			foreach ($migration_modules as $module => $version)
			{
				Minion_CLI::write(sprintf("  %20s - %s", $module, $version));
			}
		}
	}
}
