<?php

/**
 * Invalid Task Exception
 *
 * @package    KO7/Minion
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */
class KO7_Minion_Exception_InvalidTask extends Minion_Exception {

	public function format_for_cli()
	{
		return 'ERROR: '. $this->getMessage().PHP_EOL;
	}

}
