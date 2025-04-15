# wp-refactor
This script performs a recursive find & replace on a hostname or other string within a wordpress database. It even de-serializes, recursively searches, and re-serializes any field which has been stored with PHP's `serialize`. 

Usage:
```
php wp-refactor.php wp-config.php <search> <replace>

Example:
php wp-refactor.php ./wp-config.php test.silvermast.io www.silvermast.io
```
