<?php
namespace Generator;

use Shell\AbstractShell;

abstract class AbstractGenerator
{

    protected $_handle;

    public function __construct($fileName)
    {
        $fileName = "source/$fileName";
        if (!is_dir('source')) {
            mkdir('source', 0755, true);
        }
        $this->_handle = fopen($fileName, 'w');
    }

    public function __destruct()
    {
        if (is_resource($this->_handle)) {
            fclose($this->_handle);
        }
    }

    /**
     * @param string $field_delimiter
     * @param string $field_encapsulation
     * @param string $escape
     * @return mixed
     */
    abstract public function generateRow( $field_delimiter = "\t", $field_encapsulation = '', $escape = '\\');

    public function run($rowNumber, $field_delimiter = "\t", $field_encapsulation = '', $escape = '\\', $line_termination = "\n")
    {
        for ($i = 0; $i < $rowNumber; ++$i) {
            $row = $this->generateRow($field_delimiter, $field_encapsulation, $escape) . $line_termination;
            fwrite($this->_handle, $row);
        }
    }
}


class ActorGenerator extends AbstractGenerator
{


    protected $_odd = ['b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','w','x','z'];
    protected $_even = ['a','e','i','o','u','y'];

    protected $_names = ['John', 'Jerry', 'Matt', 'Brian', 'Brad', 'Bob', 'Bill', 'Susan', 'Cara', 'Linsday', 'Lis', 'Anthony', 'Ann', 'Edgar', 'Sarah', 'Nick'];

    /**
     * @param string $field_delimiter
     * @param string $field_encapsulation
     * @param string $escape
     * @return mixed
     */
    public function generateRow($field_delimiter = "\t", $field_encapsulation = '', $escape = '\\')
    {
        $rowArray = [
            $this->_generateUuid(), //uin
            $this->_generateName(), //name
            ucfirst($this->_generateWord()), //lastname
            $this->_generateAge(), //age
            $this->_generateMovieName(), //movie
        ];
        array_map(function($val) use ($field_encapsulation) {
            return $field_encapsulation . $val . $field_encapsulation;
        }, $rowArray);
        return implode($field_delimiter, $rowArray);
    }

    /**
     * @param int $min
     * @param int $max
     * @return string
     */
    protected function _generateWord($min = 4, $max = 12)
    {
        $length = mt_rand($min, $max);
        $word = '';
        for ($i=0; $i<$length; ++$i) {
            if ($i%2 === 0) {
                $word .= $this->_even[mt_rand(0, count($this->_even) - 1)];
            } else {
                $word .= $this->_odd[mt_rand(0, count($this->_odd) - 1)];
            }
        }
        return $word;
    }

    /**
     * @return string
     */
    protected function _generateUuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    protected function _generateName()
    {
        return $this->_names[mt_rand(0, count($this->_names) - 1)];
    }

    protected function _generateAge()
    {
        return mt_rand(14, 54);
    }

    protected function _generateMovieName()
    {
        $movie = ucfirst($this->_generateWord());
        if (mt_rand(0,2) === 0) {
            $movie .= ' ' . $this->_generateWord();
        }
        if (mt_rand(0,10) === 0) {
            $movie .= ' ' . $this->_generateWord();
        }
        return $movie;
    }
}
require_once 'shell.php';
class ShellGenerator extends AbstractShell
{

    public function _run()
    {
        $lineNum = $this->getArg('n');
        $file = $this->getArg('f');
        if (!$lineNum) {
            $lineNum = 1;
        }
        if (!$file) {
            echo "Please specify the file to write in.\n";
        }
        $generator = new ActorGenerator($file);
        $generator->run($lineNum);
        echo "GENERATED $lineNum RECORDS;\n";
    }
}

$shell = new ShellGenerator();
$shell->run();