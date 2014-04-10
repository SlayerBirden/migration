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
     */
    public function run($file_path)
    {
        $this->_startImport();
        $time = microtime(true);
        $rows = $this->importEntity($file_path);
        echo "$rows Entity rows imported.\n";
        $time2 = microtime(true);
        printf("IMPORT ENTITY TIME: %.4f seconds\n",  $time2 - $time);
        $rows = $this->importData($file_path);
        echo "$rows Data rows imported.\n";
        $time3 = microtime(true);
        printf("IMPORT DATA TIME: %.4f seconds\n",  $time3 - $time2);
        $this->_endImport();
    }

    protected function _startImport()
    {
        $this->_pdo->exec('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0');
//        $this->_pdo->exec('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0');
    }

    protected function _endImport()
    {
        $this->_pdo->exec("SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS=0, 0, 1)");
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
        $file_path = 'source/' . $file_path;
        $sql = <<<MYSQL
LOAD DATA LOCAL INFILE '$file_path'
INTO TABLE actor_entity
(uin, @name, @lastname, @age, @movie);
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
        $file_path = 'source/' . $file_path;
        $sql = <<<MYSQL
LOAD DATA LOCAL INFILE '$file_path'
INTO TABLE actor_data
(uin, name, lastname, age, movie);
MYSQL;
        $rows = $this->_pdo->exec($sql);
        if ($rows === false) {
            $error = $this->_pdo->errorInfo();
            echo "ERROR:\n";
            print_r($error);
        }
        // insert id
        $sql = <<<MYSQL
UPDATE actor_data d
 JOIN actor_entity e ON d.uin = e.uin
 SET d.actor_id = e.id;
MYSQL;
        $rowsUpdated = $this->_pdo->exec($sql);
        if ($rowsUpdated === false) {
            $error = $this->_pdo->errorInfo();
            echo "ERROR:\n";
            print_r($error);
        }
        return $rows;
    }

    protected function _startImport()
    {
        parent::_startImport();
        // add column
        $sql = <<<MYSQL
ALTER TABLE actor_data ADD COLUMN uin VARCHAR(255) NOT NULL;
MYSQL;
        $this->_pdo->exec($sql);
    }

    protected function _endImport()
    {
        parent::_endImport();
        // add column
        $sql = <<<MYSQL
ALTER TABLE actor_data DROP COLUMN uin;
MYSQL;
        $this->_pdo->exec($sql);
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
        if ($user === false || $host === false
            || $db === false || $file === false) {
            echo "Please specify all required args\n";
            die;
        }

        $generator = new Importer($host, $user, $password, $db);
        $generator->run($file);
    }
}

$shell = new ShellImporter();
$shell->run();