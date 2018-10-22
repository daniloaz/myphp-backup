myphp-backup
============

Perform simple and fast MySQL backup/restore using PHP. You can use it to dump a full database or only some tables.

It requires PHP 5.0.5 or later.

More information: [Using PHP to backup MySQL databases](http://www.daniloaz.com/en/using-php-to-backup-mysql-databases/)

Usage
-----

**Backup:**

Simply upload *myphp-backup.php* script to the DocumentRoot directory of your web application via FTP or other method and run it accessing http://www.example.com/myphp-backup.php. You can also run it from command line.

Don't forget to set your database access credentials before performing any backup editing these lines from *myphp-backup.php* script:

	/**
	 * Define database parameters here
	 */
	define("DB_USER", 'your_username');
	define("DB_PASSWORD", 'your_password');
	define("DB_NAME", 'your_db_name');
	define("DB_HOST", 'localhost');

	define("BACKUP_DIR", 'myphp-backup-files'); // Comment this line to use same script's directory ('.')
	define("TABLES", '*'); // Full backup
	//define("TABLES", 'table1, table2, table3'); // Partial backup
	define("CHARSET", 'utf8');
	define("GZIP_BACKUP_FILE", true); // Set to false if you want plain SQL backup files (not gzipped)
	define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails

By default backup files will be called *myphp-backup-{DB_NAME}-YYYYmmdd-HHMMSS.sql.gz* and stored in *myphp-backup-files* subdirectory. Example output backup file:

	myphp-backup-files/myphp-backup-daniloaz-20170828-131745.sql.gz

**Restore:**

Upload *myphp-restore.php* script to your DocumentRoot directory and your backup file to a subdirectory called *myphp-backup-files*. Then simply run the script accessing http://www.example.com/myphp-restore.php or from command line.

You can change the backup filename and subdirectory editing these lines. Don't forget to set your user credentials too!

	/**
	 * Define database parameters here
	 */
	define("DB_USER", 'your_username');
	define("DB_PASSWORD", 'your_password');
	define("DB_NAME", 'your_db_name');
	define("DB_HOST", 'localhost');
	define("BACKUP_DIR", 'myphp-backup-files'); // Comment this line to use same script's directory ('.')
	define("BACKUP_FILE", 'your-backup-file.sql.gz'); // Script will autodetect if backup file is gzipped or not based on .gz extension
	define("CHARSET", 'utf8');
	define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails

-----
Project at GitHub: https://github.com/daniloaz/myphp-backup

(c) Daniel López Azaña, 2012-2017 (http://www.daniloaz.com)
