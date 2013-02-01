<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Generate a migration file
 * 
 * Based on the name of the migration it will be populated with appropraite commands:
 * 
 *  - add_<column>_and_<column>_to_<table>
 *  - remove_<column>_and_<column>_from_<table>
 *  - drop_table_<table>
 *  - rename_table_<table>_to_<new table>
 *  - rename_<column>_to_<new column>_in_<table>
 *  - change_<column>_in_<table>
 * 	
 * You can also chain those together with also:
 * 
 *  add_<column>_to_<table>_also_drop_table_<table>
 * 	
 * Additionally based on column names it will try to guess the type, using 'string' by default:
 * 
 *  - ..._id, ...__count, id or position - integer
 *  - ..._at - datetime
 *  - ..._on - date
 *  - is_... - boolean
 *  - description or text - text
 *
 * @param string name required paramter - the name of the migration
 * @param string module optional parametr - the module name for migration
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Minion_Task_DB_Generate extends Minion_Task {

	protected $_config = array(
		'name' => NULL,
		'template' => NULL,
		'module' => NULL,
	);

	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('name', 'not_empty');
	}

	public function execute(array $options)
	{
		$migrations = new Migrations(array('log' => 'Minion_CLI::write'));

		$migration = $migrations->generate_new_migration_file($options['name'], $options['template'], arr::get($options, 'module'));

		Minion_CLI::write(Minion_CLI::color($migration, 'green').Minion_CLI::color(' Migration File Generated', 'brown'));
	}
}
