<?php
/**
 * Minion Exception class
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) 2016-2019  Koseven Team
 * @copyright  (c) since 2019 Modseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace Modseven\Minion;

use Throwable;

class Exception extends \Modseven\Exception
{
    /**
     * Inline exception handler, displays the error message, source of the
     * exception, and the stack trace of the error.
     * Should this display a stack trace? It's useful.
     *
     * @param Throwable $t
     */
    public static function handler(Throwable $t): void
    {
        try {
            // Log the exception
            self::log($t);

            echo self::text($t);

            $exit_code = $t->getCode();

            // Never exit "0" after an exception.
            if ($exit_code === 0)
            {
                $exit_code = 1;
            }

            exit($exit_code);
        }
        catch (\Exception $e)
        {
            // Clean the output buffer if one exists
            ob_get_level() and ob_clean();

            // Display the exception text
            echo self::text($e), "\n";

            // Exit with an error status
            exit(1);
        }
    }
}
