<?php 
/**
 * This file contains the Backup_Database class wich performs
 * a partial or complete backup of any given MySQL database
 * @author Daniel López Azaña <daniloaz@gmail.com>
 * @version 1.0
 */

/**
 * Define database parameters here
 */
define("DB_USER", 'your_username');
define("DB_PASSWORD", 'your_password');
define("DB_NAME", 'your_db_name');
define("DB_HOST", 'localhost');
define("BACKUP_DIR", 'myphp-backup-files');
define("TABLES", '*'); // Full backup
//define("TABLES", 'table1 table2 table3'); // Partial backup
define("CHARSET", 'utf8');

/**
 * The Backup_Database class
 */
class Backup_Database {
    /**
     * Host where the database is located
     */
    var $host = '';

    /**
     * Username used to connect to database
     */
    var $username = '';

    /**
     * Password used to connect to database
     */
    var $passwd = '';

    /**
     * Database to backup
     */
    var $dbName = '';

    /**
     * Database charset
     */
    var $charset = '';

    /**
     * Database connection
     */
    var $conn = '';

    /**
     * Backup directory where backup files are stored 
     */
    var $backupDir = '';

    /**
     * Output backup file
     */
    var $backupFile = '';

    /**
     * Constructor initializes database
     */
    function Backup_Database($host, $username, $passwd, $dbName, $charset = 'utf8')
    {
        $this->host       = $host;
        $this->username   = $username;
        $this->passwd     = $passwd;
        $this->dbName     = $dbName;
        $this->charset    = $charset;
        $this->conn       = $this->initializeDatabase();
        $this->backupDir  = BACKUP_DIR ? BACKUP_DIR : '.';
        $this->backupFile = 'myphp-backup-'.$this->dbName.'-'.date("Ymd-His", time()).'.sql';
    }

    protected function initializeDatabase()
    {
        try {
            $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
            if (mysqli_connect_errno()) {
                throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
                die();
            }
            if (!mysqli_set_charset($conn, $this->charset)) {
                mysqli_query($conn, 'SET NAMES '.$this->charset);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die();
        }

        return $conn;
    }

    /**
     * Backup the whole database or just some tables
     * Use '*' for whole database or 'table1 table2 table3...'
     * @param string $tables
     */
    public function backupTables($tables = '*')
    {
        try
        {
            /**
            * Tables to export
            */
            if($tables == '*')
            {
                $tables = array();
                $result = mysqli_query($this->conn, 'SHOW TABLES');
                while($row = mysqli_fetch_row($result))
                {
                    $tables[] = $row[0];
                }
            }
            else
            {
                $tables = is_array($tables) ? $tables : explode(',',$tables);
            }

            $sql = 'CREATE DATABASE IF NOT EXISTS `'.$this->dbName."`;\n\n";
            $sql .= 'USE '.$this->dbName.";\n\n";

            /**
            * Iterate tables
            */
            foreach($tables as $table)
            {
                echo "Backing up `".$table."` table...";
                // Send output buffer only if not running from command line
                if (php_sapi_name() != "cli") {
                    ob_flush();flush();
                }

                /**
                 * CREATE TABLE
                 */
                $sql .= 'DROP TABLE IF EXISTS `'.$table.'`;';
                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE `'.$table.'`'));
                $sql.= "\n\n".$row[1].";\n\n";

                /**
                 * INSERT INTO
                 */

                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SELECT COUNT(*) FROM `'.$table.'`'));
                $numRows = $row[0];

                // Split table in batches in order to not exhaust system memory 
                $batchSize = 1000; // Number of rows per batch
                $numBatches = intval($numRows / $batchSize) + 1; // Number of while-loop calls to perform
                for ($b = 1; $b <= $numBatches; $b++) {
                    
                    $query = 'SELECT * FROM `'.$table.'` LIMIT '.($b*$batchSize-$batchSize+1).','.($b*$batchSize);
                    $result = mysqli_query($this->conn, $query);
                    $numFields = mysqli_num_fields($result);

                    for ($i = 0; $i < $numFields; $i++) 
                    {
                        $rowCount = 0;
                        while($row = mysqli_fetch_row($result))
                        {
                            $rowCount++;
                            $sql .= 'INSERT INTO `'.$table.'` VALUES(';
                            for($j=0; $j<$numFields; $j++) 
                            {
                                $row[$j] = addslashes($row[$j]);
                                $row[$j] = str_replace("\n","\\n",$row[$j]);
                                if (isset($row[$j]))
                                {
                                    $sql .= '"'.$row[$j].'"' ;
                                }
                                else
                                {
                                    $sql.= '""';
                                }

                                if ($j < ($numFields-1))
                                {
                                    $sql .= ',';
                                }
                            }

                            $sql.= ");\n";
                        }
                    }

                    $this->saveFile($sql);
                    $sql = '';
                }

                $sql.="\n\n\n";

                // Send output buffer only if not running from command line
                if (php_sapi_name() != "cli") {
                    echo " OK <br />";
                    ob_flush();flush();
                } else {
                    echo " OK\n";
                }
            }
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return false;
        }

        return $this->saveFile($sql);
    }

    /**
     * Save SQL to file
     * @param string $sql
     */
    protected function saveFile(&$sql)
    {
        if (!$sql) return false;

        try
        {
            if (!file_exists($this->backupDir)) {
                mkdir($this->backupDir, 0777, true);
            }
            file_put_contents($this->backupDir.'/'.$this->backupFile, $sql, FILE_APPEND | LOCK_EX);
        }
        catch (Exception $e)
        {
            print_r($e->getMessage());
            return false;
        }

        return true;
    }
}

/**
 * Instantiate Backup_Database and perform backup
 */

// Report all errors
error_reporting(E_ALL);
// Set script max execution time
set_time_limit(900); // 15 minutes

$backupDatabase = new Backup_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$status = $backupDatabase->backupTables(TABLES, BACKUP_DIR) ? 'OK' : 'KO';
if (php_sapi_name() != "cli") {
    echo "<br />Backup result: ".$status."<br />";
} else {
    echo "\nBackup result: ".$status."\n\n";
}
