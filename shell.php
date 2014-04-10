<?php

namespace Shell;

abstract class AbstractShell
{


    /**
     * Input arguments
     *
     * @var array
     */
    protected $_args  = array();

    public function __construct()
    {
        $this->_parseArgs();
    }
    /**
     * Parse input arguments
     */
    protected function _parseArgs()
    {
        $current = null;
        foreach ($_SERVER['argv'] as $arg) {
            $match = array();
            if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
                $current = $match[1];
                $this->_args[$current] = true;
            } else {
                if ($current) {
                    $this->_args[$current] = $arg;
                } else if (preg_match('#^([\w\d_]{1,})$#', $arg, $match)) {
                    $this->_args[$match[1]] = true;
                }
            }
        }
    }

    /**
     * Retrieve argument value by name or false
     *
     * @param string $name the argument name
     * @return mixed
     */
    public function getArg($name)
    {
        if (isset($this->_args[$name])) {
            return $this->_args[$name];
        }
        return false;
    }

    public function run(){
        $time = microtime(true);
        $memory = memory_get_usage();
        $this->_run();
        printf("PROCESS TIME: %.4f seconds\n",  microtime(true) - $time);
        printf("MEMORY USED: %.2f kB\n",  round((memory_get_usage() - $memory) / 1024, 2));
        printf("MEMORY PEAK: %.2f kB\n",  round(memory_get_peak_usage() / 1024, 2));
    }

    abstract protected  function _run();
}