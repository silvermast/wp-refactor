# wp-refactor
This script takes a mysqldump .sql file and performs a recursive find & replace on a hostname or other string. It even supports replacing text within PHP-serialized fields.

Usage:
```
php wp-refactor.php <sqlFile> <search> <replace>

Example:
php wp-refactor.php ./database.sql test.silvermast.io www.silvermast.io
```
