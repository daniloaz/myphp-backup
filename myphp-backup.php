<?php 
/**
 * This file contains the Backup_Database class wich performs
 * a partial or complete backup of any given MySQL database
 * @author Daniel López Azaña <daniloaz@gmail.com>
 * @version 1.0
 */

// Report all errors
error_reporting(E_ALL);

/**
 * Define database parameters here
 */
define("DB_USER", 'your_username');
define("DB_PASSWORD", 'your_password');
define("DB_NAME", 'your_db_name');
define("DB_HOST", 'localhost');
define("BACKUP_DIR", 'myphp-backup');
define("TABLES", '*'); // Full backup
//define("TABLES", 'table1 table2 table3'); // Partial backup
define("CHARSET", 'utf8');

/**
 * Instantiate Backup_Database and perform backup
 */
$backupDatabase = new Backup_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$status = $backupDatabase->backupTables(TABLES, BACKUP_DIR) ? 'OK' : 'KO';
echo "<br />Backup result: ".$status."<br />";

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
     * Constructor initializes database
     */
    function Backup_Database($host, $username, $passwd, $dbName, $charset = 'utf8')
    {
        $this->host     = $host;
        $this->username = $username;
        $this->passwd   = $passwd;
        $this->dbName   = $dbName;
        $this->charset  = $charset;
        $this->conn     = $this->initializeDatabase();
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
    public function backupTables($tables = '*', $backupDir = '.')
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

            $sql = 'CREATE DATABASE IF NOT EXISTS '.$this->dbName.";\n\n";
            $sql .= 'USE '.$this->dbName.";\n\n";

            /**
            * Iterate tables
            */
            foreach($tables as $table)
            {
                echo "Backing up ".$table." table...";

                $result = mysqli_query($this->conn, 'SELECT * FROM '.$table);
                $numFields = mysqli_num_fields($result);

                $sql .= 'DROP TABLE IF EXISTS '.$table.';';
                $row2 = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE '.$table));
                $sql.= "\n\n".$row2[1].";\n\n";

                for ($i = 0; $i < $numFields; $i++) 
                {
                    while($row = mysqli_fetch_row($result))
                    {
                        $sql .= 'INSERT INTO '.$table.' VALUES(';
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

                $sql.="\n\n\n";

                echo " OK <br />";
            }
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return false;
        }

        return $this->saveFile($sql, $backupDir);
    }

    /**
     * Save SQL to file
     * @param string $sql
     */
    protected function saveFile(&$sql, $backupDir = '.')
    {
        if (!$sql) return false;

        try
        {
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            $handle = fopen($backupDir.'/myphp-backup-'.$this->dbName.'-'.date("Ymd-His", time()).'.sql','w+');
            fwrite($handle, $sql);
            fclose($handle);
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return false;
        }

        return true;
    }
}
