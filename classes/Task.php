<?php
/**
 * Interface that all minion tasks must implement
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace Modseven\Minion;

use KO7\Arr;
use KO7\Validation;
use KO7\View;

abstract class Task
{
    /**
     * The list of options this task accepts and their default values.
     * @var array
     */
    protected array $_options = [];

    /**
     * Populated with the accepted options for this task.
     * This array is automatically populated based on $_options.
     * @var array
     */
    protected array $_accepted_options = [];

    /**
     * Default method to execute
     * @var string
     */
    protected string $_method = '_execute';

    /**
     * Translation file that get's passed to Validation::errors() when validation fails
     * @var string
     */
    protected string $_errors_file = 'validation';

    /**
     * Factory for loading minion tasks
     *
     * @param array An array of command line options. It should contain the 'task' key
     *
     * @return Task
     *
     * @throws Exception
     */
    public static function factory(array $options) : Task
    {
        if (($task = Arr::get($options, 'task')) !== null)
        {
            unset($options['task']);
        }
        elseif (($task = Arr::get($options, 0)) !== null)
        {
            // The first positional argument (aka 0) may be the task name
            unset($options[0]);
        }
        else
        {
            // If we didn't get a valid task, generate the help
            $task = '\\Modseven\\Task\\Help';
        }

        if ( ! class_exists($task))
        {
            throw new Exception(
                "Task ':task' is not a valid minion task",
                [':task' => $task]
            );
        }

        $class = new $task;

        if ( ! $class instanceof self)
        {
            throw new Exception(
                "Task ':task' is not a valid minion task",
                [':task' => $class]
            );
        }

        $class->set_options($options);

        // Show the help page for this task if requested
        if (array_key_exists('help', $options))
        {
            $class->_method = '_help';
        }

        return $class;
    }

    /**
     * Task constructor.
     */
    protected function __construct()
    {
        // Populate $_accepted_options based on keys from $_options
        $this->_accepted_options = array_keys($this->_options);
    }


    /**
     * Gets the task name for the task
     *
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }

    /**
     * Sets options for this task
     * $param  array  the array of options to set
     *
     * @param array $options Options to set
     *
     * @return self
     */
    public function set_options(array $options) : self
    {
        foreach ($options as $key => $value)
        {
            $this->_options[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the options that were passed into this task with their defaults
     *
     * @return array
     */
    public function get_options() : array
    {
        return (array)$this->_options;
    }

    /**
     * Get a set of options that this task can accept
     *
     * @return array
     */
    public function get_accepted_options() : array
    {
        return (array)$this->_accepted_options;
    }

    /**
     * Adds any validation rules/labels for validating _options
     *
     * @param Validation $validation  The validation object to add rules to
     *
     * @return Validation
     */
    public function build_validation(Validation $validation) : Validation
    {
        // Add a rule to each key making sure it's in the task
        foreach ($validation->data() as $key => $value)
        {
            $validation->rule($key, [$this, 'valid_option'], [':validation', ':field']);
        }

        return $validation;
    }

    /**
     * Returns $_errors_file
     *
     * @return string
     */
    public function get_errors_file() : string
    {
        return $this->_errors_file;
    }

    /**
     * Execute the task with the specified set of options
     */
    public function execute() : void
    {
        $options = $this->get_options();

        // Validate $options
        $validation = Validation::factory($options);
        $validation = $this->build_validation($validation);

        if ($this->_method !== '_help' && ! $validation->check())
        {
            echo View::factory('minion/error/validation')
                     ->set('task', get_class($this))
                     ->set('errors', $validation->errors($this->get_errors_file()));
        }
        else
        {
            // Finally, run the task
            $method = $this->_method;
            echo $this->{$method}($options);
        }
    }

    abstract protected function _execute(array $params);

    /**
     * Outputs help for this task
     *
     * @return null
     */
    protected function _help(array $params)
    {
        $tasks = $this->_compile_task_list(KO7::list_files('classes/task'));

        $inspector = new ReflectionClass($this);

        list($description, $tags) = $this->_parse_doccomment($inspector->getDocComment());

        $view = View::factory('minion/help/task')
                    ->set('description', $description)
                    ->set('tags', (array)$tags)
                    ->set('task', Minion_Task::convert_class_to_task($this));

        echo $view;
    }


    public function valid_option(Validation $validation, $option)
    {
        if ( ! in_array($option, $this->_accepted_options)) {
            $validation->error($option, 'minion_option');
        }
    }

    /**
     * Parses a doccomment, extracting both the comment and any tags associated
     * Based on the code in Kodoc::parse()
     *
     * @param string The comment to parse
     *
     * @return array First element is the comment, second is an array of tags
     */
    protected function _parse_doccomment($comment)
    {
        // Normalize all new lines to \n
        $comment = str_replace(["\r\n", "\n"], "\n", $comment);

        // Remove the phpdoc open/close tags and split
        $comment = array_slice(explode("\n", $comment), 1, -1);

        // Tag content
        $tags = [];

        foreach ($comment as $i => $line) {
            // Remove all leading whitespace
            $line = preg_replace('/^\s*\* ?/m', '', $line);

            // Search this line for a tag
            if (preg_match('/^@(\S+)(?:\s*(.+))?$/', $line, $matches)) {
                // This is a tag line
                unset($comment[$i]);

                $name = $matches[1];
                $text = isset($matches[2]) ? $matches[2] : '';

                $tags[$name] = $text;
            }
            else {
                $comment[$i] = (string)$line;
            }
        }

        $comment = trim(implode("\n", $comment));

        return [$comment, $tags];
    }

    /**
     * Compiles a list of available tasks from a directory structure
     *
     * @param array Directory structure of tasks
     * @param string prefix
     *
     * @return array Compiled tasks
     */
    protected function _compile_task_list(array $files, $prefix = '')
    {
        $output = [];

        foreach ($files as $file => $path) {
            $file = substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);

            if (is_array($path) AND count($path)) {
                $task = $this->_compile_task_list($path, $prefix . $file . Minion_Task::$task_separator);

                if ($task) {
                    $output = array_merge($output, $task);
                }
            }
            else {
                $output[] = strtolower($prefix . substr($file, 0, -strlen(EXT)));
            }
        }

        return $output;
    }

    /**
     * Sets the domain name for minion tasks
     * Minion tasks have no $_SERVER variables; to use the base url functions
     * the domain name can be set in the site config file, or as argument.
     *
     * @param string $domain_name the url of the server: example https://www.example.com
     */
    public static function set_domain_name($domain_name = '')
    {
        if (Request::$initial === null) {
            $domain_name =
                empty($domain_name) ? Arr::get(KO7::$config->load('site'), 'minion_domain_name', '') : $domain_name;

            // Add trailing slash
            KO7::$base_url = preg_replace('~^https?://[^/]+$~', '$0/', $domain_name);

            // Initialize Request Class
            $request = Request::factory();

            // Set HTTPS for https based urls
            $request->secure(preg_match_all('#(https)://#i', KO7::$base_url, $result) === 1);

            Request::$initial = $request;
        }
    }
}
