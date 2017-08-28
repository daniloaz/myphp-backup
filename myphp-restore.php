<?php 
/**
 * This file contains the Restore_Database class wich performs
 * a partial or complete restoration of any given MySQL database
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
define("BACKUP_FILE", 'your-backup-file.sql');
define("CHARSET", 'utf8');

/**
 * The Restore_Database class
 */
class Restore_Database {
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
    function Restore_Database($host, $username, $passwd, $dbName, $charset = 'utf8') {
        $this->host     = $host;
        $this->username = $username;
        $this->passwd   = $passwd;
        $this->dbName   = $dbName;
        $this->charset  = $charset;
        $this->conn     = $this->initializeDatabase();
    }

    protected function initializeDatabase() {
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
    public function restoreDb($backupDir = '.', $backupFile = null) {
        try {
            $sql = '';
            $multiLineComment = false;

            /**
            * Read backup file line by line
            */
            $handle = fopen($backupDir . '/' . $backupFile, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = ltrim(rtrim($line));
                    if (strlen($line) > 1) { // avoid blank lines
                        $lineIsComment = false;
                        if (preg_match('/^\/\*/', $line)) {
                            $multiLineComment = true;
                            $lineIsComment = true;
                        }
                        if ($multiLineComment or preg_match('/^\/\//', $line)) {
                            $lineIsComment = true;
                        }
                        if (!$lineIsComment) {
                            $sql .= $line;
                            if (preg_match('/;$/', $line)) {
                                // execute query
                                if(mysqli_query($this->conn, $sql)) {
                                    if (preg_match('/^CREATE TABLE `([^`]+)`/i', $sql, $tableName)) {
                                        if (php_sapi_name() != "cli") {
                                            echo "Table `" . $tableName[1] . "` succesfully created.<br />";
                                            ob_flush();flush();
                                        } else {
                                            echo "Table `" . $tableName[1] . "` succesfully created.\n";
                                        }
                                    }
                                    $sql = '';
                                } else {
                                    throw new Exception("ERROR: SQL execution error: " . mysqli_error($this->conn));
                                }
                            }
                        } else if (preg_match('/\*\/$/', $line)) {
                            $multiLineComment = false;
                        }
                    }
                }
                fclose($handle);
            } else {
                throw new Exception("ERROR: couldn't open backup file " . $backupDir . '/' . $backupFile);
            } 
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }

        return true;
    }
}

/**
 * Instantiate Restore_Database and perform backup
 */
// Report all errors
error_reporting(E_ALL);
// Set script max execution time
set_time_limit(900); // 15 minutes

$restoreDatabase = new Restore_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$status = $restoreDatabase->restoreDb(BACKUP_DIR, BACKUP_FILE) ? 'OK' : 'KO';
if (php_sapi_name() != "cli") {
    echo "<br />Restoration result: ".$status."<br />";
} else {
    echo "\nRestoration result: ".$status."\n\n";
}