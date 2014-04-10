<?php
namespace Resource;

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
                $this->_pdo = new \PDO("mysql:dbname=$db_name;host=$host", $user, $passw, array(
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
            } else {
                $this->_pdo = new \PDO("mysql:dbname=$db_name;host=$host", $user, null, array(
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ));
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
     * @throws \Exception
     */
    public function run($file_path)
    {
        $file_path = 'source/' . $file_path;
        $this->_startImport();
        $_handle = fopen($file_path, 'r');
        while(!feof($_handle)) {
            $data = fgetcsv($_handle, null, "\t", '"', "\\");
            $this->doImport($data);
        }
        fclose($_handle);
        echo "All rows imported\n";
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
     * @param array $data
     * @return mixed
     */
    abstract public function doImport($data);
}

class ResourceImporter extends AbstractImporter
{

    /**
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function doImport($data)
    {
        $sql = <<<MYSQL
INSERT INTO actor_entity (uin) VALUES ('{$data[0]}');
SELECT LAST_INSERT_ID();
MYSQL;
        $length = $this->_pdo->exec($sql);
        if (false === $length) {
            echo "ERROR:\n";
            print_r($this->_pdo->errorInfo());
        }
        $sql = <<<MYSQL
SELECT LAST_INSERT_ID();
MYSQL;
        $statement = $this->_pdo->query($sql);
        if (false === $statement) {
            echo "ERROR:\n";
            print_r($this->_pdo->errorInfo());
        }
        // get id
        $results = $statement->fetch(\PDO::FETCH_NUM);
        $_id = $results[0];
        $sql = <<<MYSQL
INSERT INTO actor_data (actor_id, name, lastname, age, movie)
 VALUES ('$_id', '{$data[1]}', '{$data[2]}', '{$data[3]}', '{$data[4]}');
MYSQL;
        $rows = $this->_pdo->exec($sql);
        if (false === $rows) {
            echo "ERROR:\n";
            print_r($this->_pdo->errorInfo());
        }
    }

    protected function _startImport()
    {
        parent::_startImport();
        $this->_pdo->exec('LOCK TABLES actor_entity WRITE, actor_data WRITE;');
    }

    protected function _endImport()
    {
        parent::_endImport();
        $this->_pdo->exec('UNLOCK TABLES;');
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

        $importer = new ResourceImporter($host, $user, $password, $db);
        $importer->run($file);
    }
}

$shell = new ShellImporter();
$shell->run();