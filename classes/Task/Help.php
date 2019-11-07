<?php
/**
 * Help task to display general instructons and list all tasks
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) 2016-2019  Koseven Team
 * @copyright  (c) since 2019 Modseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace Modseven\Minion\Task;

use Modseven\Core;
use Modseven\View;
use Modseven\Minion\Task;
use Modseven\Minion\Exception;

class Help extends Task
{
    /**
     * Generates a help list for all tasks
     *
     * @param array $params Parameter
     *
     * @throws Exception
     */
    protected function _execute(array $params) : void
    {
        $tasks = $this->_compileTaskList(Core::listFiles('classes' . DIRECTORY_SEPARATOR . 'Task'));

        try
        {
            $view = new View('minion' . DIRECTORY_SEPARATOR . 'help' . DIRECTORY_SEPARATOR . 'list');
        }
        catch (\Modseven\Exception $e)
        {
            throw new Exception($e->getMessage(), null, $e->getCode(), $e);
        }

        $view->tasks = $tasks;

        echo $view;
    }

}
