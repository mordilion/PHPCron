<?php
namespace PHPCron\Cron;

use \Symfony\Component\Process\Process;

/**
 * CronManager provides easy access to the crontable
 */
class Tab
{
    /**
     * The lines of the cron table, can be cron command or comment
     *
     * @var array
     */
    protected $lines = array();

    /**
     * The error when using the comment 'crontab'
     *
     * @var string
     */
    protected $error;

    /**
     * The output when using the command 'crontab'
     *
     * @var string
     */
    protected $output;

    function __construct()
    {
        // parsing cron file
        $process = new Process('crontab -l');
        $process->run();
        $lines = \array_filter(\explode(PHP_EOL, $process->getOutput()), function($line) {
            return '' != \trim($line);
        });

        foreach ($lines as $lineNumber => $line) {
            // if line is not a comment, convert it to a cron
            if (\strpos($line, '#suspended: ', 0) === 0 || 0 !== \strpos($line, '#', 0)) {
                $line = Job::parse($line);
            }
            $this->lines['l'.$lineNumber] = $line;
        }

        $this->error = $process->getErrorOutput();
    }

    /**
     * Gets the array of crons indexed by line number
     *
     * @return array<Job>
     */
    public function get()
    {
        return \array_filter($this->lines, function($line){
            return $line instanceof Job;
        });
    }

    /**
     * Add a cron to the cron table
     *
     * @param Job $cron
     * @return PHPCron\Cron\Tab
     */
    public function add(Job $cron)
    {
        $this->lines[] = $cron;

        return $this;
    }

    /**
     * Remove a cron from the cron table
     *
     * @param $index The line number
     * @return PHPCron\Cron\Tab
     */
    public function remove($index)
    {
        $this->lines = \array_diff_key($this->lines, array($index => ''));

        return $this;
    }

    /**
     * Remove all crons which match the given command
     * 
     * @param string $command
     * @return PHPCron\Cron\Tab
     */
    public function removeByCommand($command)
    {
        foreach ($this->lines as $index => $line) {

            if ($line instanceof Job) {

                if ($command == $line->getCommand()) {

                    $this->remove($index);
                }
            }
        }

        return $this;
    }

    /**
     * Write the current crons in the cron table
     *
     * @return PHPCron\Cron\Tab
     */
    public function write()
    {
        $file = \tempnam(\sys_get_temp_dir(), 'cron');

        \file_put_contents($file, $this->getRaw().PHP_EOL);

        $process = new Process('crontab '.$file);
        $process->run();

        $this->error = $process->getErrorOutput();
        $this->output = $process->getOutput();

        return $this;
    }

    /**
     * Gets the error output when using the 'crontab' command
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Gets the output when using the 'crontab' command
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Gets a representation of the cron table file
     *
     * @return mixed
     */
    public function getRaw()
    {
        return \implode(PHP_EOL, $this->lines);
    }
}
