<?php
/**
 * Invalid Task Exception
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) 2016-2019  Koseven Team
 * @copyright  (c) since 2019 Modseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace Modseven\Minion\Exception;

use Modseven\Minion\Exception;

class InvalidTask extends Exception
{
    /**
     * Formats the error Message to work in CLI
     *
     * @return string
     */
    public function format_for_cli() : string
    {
        return 'ERROR: ' . $this->getMessage() . PHP_EOL;
    }
}
