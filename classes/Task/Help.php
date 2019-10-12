<?php
/**
 * Help task to display general instructons and list all tasks
 *
 * @package    KO7
 * @category   Helpers
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */
class Task_Help extends Minion_Task {

	/**
	 * Generates a help list for all tasks
	 *
	 * @return null
	 */
	protected function _execute(array $params)
	{
		$tasks = $this->_compile_task_list(KO7::list_files('classes/Task'));

		$view = new View('minion/help/list');

		$view->tasks = $tasks;

		echo $view;
	}

}
