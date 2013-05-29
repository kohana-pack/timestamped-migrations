<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Get the current migration version
 *
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Minion_Migration extends Minion_Task {

	protected $_options = array(
		'version' => NULL,
		'steps' => NULL,
		'dry-run' => FALSE,
		'module' => NULL,
	);

	protected $_migrations;

	public function migrations()
	{
		if ( ! $this->_migrations)
		{
			$this->_migrations = new Migrations(array('log' => 'Minion_CLI::write'));
		}

		return $this->_migrations;
	}

	public function executed_migrations()
	{
		return array_reverse($this->migrations()->get_executed_migrations());
	}

	public function unexecuted_migrations()
	{
		return $this->migrations()->get_unexecuted_migrations();
	}

	public function all_migrations()
	{
		return $this->migrations()->get_migrations();
	}

	/**
	 * @param array $migrations
	 * @param string $module
	 * @return array
	 */
	public function filter_migrations_by_module($migrations, $module)
	{
		return array_values(
			array_filter(
				$migrations,
				function ($migrations) use($module)
				{
					return $migrations['module'] == $module;
				}
			)
		);
	}

	public function migrate(array $up, array $down, $dry_run = FALSE)
	{
		$this->migrations()->execute_all($up, $down, $dry_run);

		if ($up OR $down)
		{
			Minion_Task::factory(array('task'=>'db:structure:dump'))->execute(array('database' => 'default', 'file' => NULL));
		}
	}

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('version', 'min_length', array(':value', 10))
			->rule('steps', 'digit');
	}
}
