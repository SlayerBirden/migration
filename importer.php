<?php
namespace Importer;

use Shell\AbstractShell;

abstract class AbstractImporter
{
    protected $_pdo;

    /**
     * @param string $host
     * @param string $user
     * @param string $passw
     * @param string $db_name
     */
    public function __construct($host, $user, $passw, $db_name)
    {
        if (!empty($passw)) {
            $this->_pdo = new \PDO("mysql:dbname=$db_name;host=$host", $user, $passw, array(\PDO::MYSQL_ATTR_LOCAL_INFILE => true));
        } else {
            $this->_pdo = new \PDO("mysql:dbname=$db_name;host=$host", $user, null, array(\PDO::MYSQL_ATTR_LOCAL_INFILE => true));
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
        $rows = $this->importEntity($file_path);
        echo "$rows Entity rows imported.\n";
        $rows = $this->importData($file_path);
        echo "$rows Data rows imported.\n";
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
        return $rows;
    }

    /**
     * @param string $file_path
     * @return mixed
     */
    public function importData($file_path)
    {
        return 0;
    }
}

require_once 'shell.php';
class ShellImporter extends AbstractShell
{

    public function run()
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