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
        $sql = <<<MYSQL
INSERT INTO actor_entity (uin) VALUES (?);
MYSQL;
        $stmpEntity = $this->_pdo->prepare($sql);
        if (!$stmpEntity) {
            echo "Error while preparing statement\n";
            die;
        }
        $sql = <<<MYSQL
INSERT INTO actor_data (actor_id, name, lastname, age, movie)
 VALUES (?, ?, ?, ?, ?);
MYSQL;
        $stmpData = $this->_pdo->prepare($sql);
        if (!$stmpData) {
            echo "Error while preparing statement\n";
            die;
        }
        while(!feof($_handle)) {
            $data = fgetcsv($_handle, null, "\t", '"', "\\");
            $this->doImport($data, $stmpEntity, $stmpData);
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
     * @param \PDOStatement $stmpEntity
     * @param \PDOStatement $stmpData
     * @return mixed
     */
    abstract public function doImport($data, $stmpEntity, $stmpData);
}

class ResourceImporter extends AbstractImporter
{

    /**
     * @param array $data
     * @param \PDOStatement $smtpEntity
     * @param \PDOStatement $smtpData
     * @return int
     * @throws \Exception
     */
    public function doImport($data, $smtpEntity, $smtpData)
    {
        if (empty($data)) {
            return;
        }
        $result = $smtpEntity->execute(array($data[0]));
        if (!$result) {
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
        $result = $smtpData->execute(array($_id, $data[1], $data[2], $data[3], $data[4]));
        if (!$result) {
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