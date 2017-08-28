#myphp-backup
============

Perform simple and fast MySQL backup/restore using PHP. You can use it to dump a full database or only some tables.

It requires PHP 5.0.5 or later.

##Usage
-----

###Backup
------

Simply upload *myphp-backup.php* script to the DocumentRoot directory of your web application via FTP or other method and run it accessing http://www.example.com/myphp-backup.php.

Don't forget to set your database access credentials before performing any backup editing these lines from *myphp-backup.php* script:

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


###Restore
------

Upload *myphp-restore.php* script to your DocumentRoot directory and your backup file to a subdiretory called myphp-backup-files. Then simply run the script accessing http://www.example.com/myphp-restore.php.

You can change the backup filename and subdirectory editing these lines. Don't forget to set your user credentials too!

	/**
	 * Define database parameters here
	 */
	define("DB_USER", 'your_username');
	define("DB_PASSWORD", 'your_password');
	define("DB_NAME", 'your_db_name');
	define("DB_HOST", 'localhost');
	define("BACKUP_DIR", 'myphp-backup-files'); // Comment this line to use same script's directory ('.')
	define("BACKUP_FILE", 'your-backup-file.sql');
	define("CHARSET", 'utf8');

-----
Project at GitHub: https://github.com/daniloaz/myphp-backup

(c) Daniel López Azaña, 2012 (http://www.daniloaz.com)