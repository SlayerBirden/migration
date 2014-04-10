<?php
namespace Importer;

use Shell\AbstractShell;

abstract class AbstractImporter
{
    protected $_pdo;

    protected $_tableSchema;

    protected $_autoInc;

    /**
     * @param string $host
     * @param string $user
     * @param string $passw
     * @param string $db_name
     */
    public function __construct($host, $user, $passw, $db_name)
    {
        $this->_tableSchema = $db_name;
        try {
            if (!empty($passw)) {
                $this->_pdo = new \PDO("mysql:dbname=$db_name;host=$host", $user, $passw, array(\PDO::MYSQL_ATTR_LOCAL_INFILE => true));
            } else {
                $this->_pdo = new \PDO("mysql:dbname=$db_name;host=$host", $user, null, array(\PDO::MYSQL_ATTR_LOCAL_INFILE => true));
            }
        } catch (\PDOException $e) {
            echo "Access denied;\n";
        }
    }

    public function __destruct()
    {
        $this->_pdo = null;
    }

    /**
     * @param string $file_path
     * @param bool $skipTmp
     */
    public function run($file_path, $skipTmp = false)
    {
        $file_path = 'source/' . $file_path;
        $this->_startImport($file_path, $skipTmp);
        $time = microtime(true);
        $rows = $this->importEntity($file_path);
        echo "$rows Entity rows imported.\n";
        $time2 = microtime(true);
        printf("IMPORT ENTITY TIME: %.4f seconds\n",  $time2 - $time);
        $rows = $this->importData($file_path);
        echo "$rows Data rows imported.\n";
        $time3 = microtime(true);
        printf("IMPORT DATA TIME: %.4f seconds\n",  $time3 - $time2);
        $this->_endImport($file_path);
    }

    protected function _startImport(&$file_path, $skipTmp = false)
    {
//        $this->_pdo->exec('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0');
//        $this->_pdo->exec('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0');
    }

    protected function _endImport($file_path)
    {
//        $this->_pdo->exec("SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS=0, 0, 1)");
//        $this->_pdo->exec("SET UNIQUE_CHECKS=IF(@OLD_UNIQUE_CHECKS=0, 0, 1)");
    }

    /**
     * @param string $file_path
     * @return mixed
     */
    abstract public function importEntity($file_path);

    /**
     * @param string $file_path
     * @return mixed
     */
    abstract public function importData($file_path);
}

class Importer extends AbstractImporter
{

    /**
     * @param string $file_path
     * @return int
     */
    public function importEntity($file_path)
    {
        $sql = <<<MYSQL
LOAD DATA LOCAL INFILE '$file_path'
INTO TABLE actor_entity
(uin, @name, @lastname, @age, @movie, id);
MYSQL;
        $rows = $this->_pdo->exec($sql);
        if ($rows === false) {
            $error = $this->_pdo->errorInfo();
            echo "ERROR:\n";
            print_r($error);
        }
        return $rows;
    }

    /**
     * @param string $file_path
     * @return mixed
     */
    public function importData($file_path)
    {
        $sql = <<<MYSQL
LOAD DATA LOCAL INFILE '$file_path'
INTO TABLE actor_data
(@uin, name, lastname, age, movie, actor_id);
MYSQL;
        $rows = $this->_pdo->exec($sql);
        if ($rows === false) {
            $error = $this->_pdo->errorInfo();
            echo "ERROR:\n";
            print_r($error);
        }
        return $rows;
    }

    protected function _startImport(&$file_path, $skipTmp = false)
    {
        parent::_startImport($file_path);
        $this->_pdo->exec('LOCK TABLES actor_entity WRITE, actor_data WRITE;');
        if ($skipTmp && file_exists($file_path . '_tmp')) {
            $file_path = $file_path . '_tmp';
            return;
        }
        // get last id
        $select = <<<MYSQL
SELECT `AUTO_INCREMENT`
FROM  INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'test'
AND   TABLE_NAME   = 'actor_entity';
MYSQL;
        $statement = $this->_pdo->query($select);
        $resultSet = $statement->fetch(\PDO::FETCH_NUM);
        $increment = $resultSet[0];
        // file writer - write tmp file with increment column
        $_new_file_path = $file_path . '_tmp';
        $_handleTmp = fopen($_new_file_path, 'w');
        $_handle = fopen($file_path, 'r');
        while (!feof($_handle)) {
            $row = fgets($_handle);
            $row = str_replace("\n", "\t" . $increment++ . "\n", $row);
            fwrite($_handleTmp, $row);
        }
        // replace incoming file with new one
        $file_path = $_new_file_path;
    }

    protected function _endImport($file_path)
    {
        parent::_endImport($file_path);
        $this->_pdo->exec('UNLOCK TABLES;');
        // remove tmp file
        if (strpos($file_path, '_tmp') !== false) {
            // can be unlocked
//            unlink($file_path);
        }
    }
}

require_once 'shell.php';
class ShellImporter extends AbstractShell
{

    public function _run()
    {
        $host = $this->getArg('h');
        $user = $this->getArg('u');
        $password = $this->getArg('p');
        $db = $this->getArg('db');
        $file = $this->getArg('f');
        $skipTmp = $this->getArg('t');
        if ($user === false || $host === false
            || $db === false || $file === false) {
            echo "Please specify all required args\n";
            die;
        }

        $importer = new Importer($host, $user, $password, $db);
        $importer->run($file, $skipTmp);
    }
}

$shell = new ShellImporter();
$shell->run();