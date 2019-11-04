<?php
/**
 * Minion CLI class
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) 2016-2019  Koseven Team
 * @copyright  (c) since 2019 Modseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace Modseven\Minion;

use Modseven\Core;

class CLI
{
    /**
     * Message for user input
     * @var string
     */
    public static string $wait_msg = 'Press any key to continue...';

    /**
     * Message for invalid option
     * @var string
     */
    public static string $invalid_option_msg = 'This is not a valid option. Please try again.';

    /**
     * Terminal Colors
     * @var array
     */
    protected static array $foreground_colors = [
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'brown'        => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37',
    ];

    /**
     * Terminal Background colors
     * @var array
     */
    protected static array $background_colors = [
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47',
    ];

    /**
     * Returns one or more command-line options. Options are specified using
     * standard CLI syntax.
     *
     * @param mixed $options,... option name
     *
     * @return  array
     */
    public static function options($options = null) : array
    {
        // Get all of the requested options
        $options = func_get_args();

        // Found option values
        $values = [];

        // Skip the first option, it is always the file executed
        for ($i = 1; $i < $_SERVER['argc']; ++$i)
        {
            if ( ! isset($_SERVER['argv'][$i]))
            {
                // No more args left
                break;
            }

            // Get the option
            $opt = $_SERVER['argv'][$i];

            if (strpos($opt, '--') !== 0)
            {
                // This is a positional argument
                $values[] = $opt;
                continue;
            }

            // Remove the "--" prefix
            $opt = substr($opt, 2);

            if (strpos($opt, '=') !== false)
            {
                // Separate the name and value
                [$opt, $value] = explode('=', $opt, 2);
            }
            else
            {
                $value = null;
            }

            $values[$opt] = $value;
        }

        if ($options)
        {
            foreach ($values as $opt => $value)
            {
                if ( ! in_array($opt, $options, true))
                {
                    // Set the given value
                    unset($values[$opt]);
                }
            }
        }

        return count($options) === 1 ? array_pop($values) : $values;
    }

    /**
     * Reads input from the user. This can have either 1 or 2 arguments.
     *
     * @param string $text    text to show user before waiting for input
     * @param array  $options array of options the user is shown
     *
     * @return string  the user input
     */
    public static function read(string $text = '', ?array $options = null) : string
    {
        // If a question has been asked with the read
        if ( ! empty($options))
        {
            $text .= ' [ ' . implode(', ', $options) . ' ]';
        }
        if ($text !== '')
        {
            $text .= ': ';
        }

        fwrite(STDOUT, $text);

        // Read the input from keyboard.
        $input = trim(fgets(STDIN));

        // If options are provided and the choice is not in the array, tell them to try again
        if ( ! empty($options) && ! in_array($input, $options))
        {
            self::write(static::$invalid_option_msg);

            $input = self::read($text, $options);
        }

        // Read the input
        return $input;
    }

    /**
     * Experimental feature.
     * Reads hidden input from the user.
     *
     * @author Mathew Davies
     *
     * @param string $text
     *
     * @return string
     */
    public static function password(string $text = '') : string
    {
        $text .= ': ';

        if (Core::$is_windows)
        {
            $vbscript = sys_get_temp_dir() . 'Minion_CLI_Password.vbs';

            // Create temporary file
            file_put_contents($vbscript, 'wscript.echo(InputBox("' . addslashes($text) . '"))');

            $password = shell_exec('cscript //nologo ' . escapeshellarg($vbscript));

            // Remove temporary file.
            unlink($vbscript);
        }
        else
        {
            $password =
                shell_exec('/usr/bin/env bash -c \'read -s -p "' . escapeshellcmd($text) . '" var && echo $var\'');
        }

        self::write();

        return trim($password);
    }

    /**
     * Outputs a string to the cli. If you send an array it will implode them
     * with a line break.
     *
     * @param string|array $text the text to output, or array of lines
     */
    public static function write($text = '') : void
    {
        if (is_array($text))
        {
            foreach ($text as $line) {
                self::write($line);
            }
        }
        else
        {
            fwrite(STDOUT, $text . PHP_EOL);
        }
    }

    /**
     * Outputs a replacable line to the cli. You can continue replacing the
     * line until `TRUE` is passed as the second parameter in order to indicate
     * you are done modifying the line.
     *
     * @param string  $text     the text to output
     * @param boolean $end_line whether the line is done being replaced
     */
    public static function write_replace(string $text = '', bool $end_line = false) : void
    {
        // Append a newline if $end_line is TRUE
        $text = $end_line ? $text . PHP_EOL : $text;

        if (Core::$is_windows)
        {
            fwrite(STDOUT, "\r" . $text);
        }
        else
        {
            fwrite(STDOUT, "\r\033[K" . $text);
        }
    }

    /**
     * Waits a certain number of seconds, optionally showing a wait message and
     * waiting for a key press.
     *
     * @author     Fuel Development Team
     * @copyright  2010 - 2011 Fuel Development Team
     * @link       http://fuelphp.com
     * @license    MIT License
     *
     * @param int  $seconds   number of seconds
     * @param bool $countdown show a countdown or not
     *
     */
    public static function wait(int $seconds = 0, bool $countdown = false) : void
    {
        if ($countdown === true)
        {
            $time = $seconds;

            while ($time > 0) {
                fwrite(STDOUT, $time . '... ');
                sleep(1);
                --$time;
            }

            self::write();
        }
        elseif ($seconds > 0)
        {
            sleep($seconds);
        }
        else
        {
            self::write(static::$wait_msg);
            self::read();
        }
    }

    /**
     * Returns the given text with the correct color codes for a foreground and
     * optionally a background color.
     *
     * @param string $text       the text to color
     * @param string $foreground the foreground color
     * @param string $background the background color
     *
     * @author     Fuel Development Team
     * @copyright  2010 - 2011 Fuel Development Team
     * @link       http://fuelphp.com
     * @license    MIT License
     *
     * @return string the color coded string
     *
     * @throws Exception
     */
    public static function color(string $text, string $foreground, ?string $background = null) : string
    {
        if (Core::$is_windows)
        {
            return $text;
        }

        if ( ! array_key_exists($foreground, static::$foreground_colors))
        {
            throw new Exception('Invalid CLI foreground color: ' . $foreground);
        }

        if ($background !== null && ! array_key_exists($background, static::$background_colors))
        {
            throw new Exception('Invalid CLI background color: ' . $background);
        }

        $string = "\033[" . static::$foreground_colors[$foreground] . 'm';

        if ($background !== null)
        {
            $string .= "\033[" . static::$background_colors[$background] . 'm';
        }

        $string .= $text . "\033[0m";

        return $string;
    }
}
