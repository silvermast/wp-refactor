#!/usr/bin/php
<?php
/**
 * This script is used to refactor a Wordpress database.
 * Every field in every table is checked, unserialized if necessary, and recursively updated.
 * @author Jason Wright <jason@silvermast.io>
 */

/**
 * Performs a str_replace on a serialized string
 */
function str_replace_serialize($search, $replace, $contents) {

    $object = @unserialize($contents);
    if ($object !== false && is_iterable($object)) {
        str_replace_recursive($search, $replace, $object);
        return @serialize($object);
    } elseif (is_scalar($object)) {
        return str_replace($search, $replace, $contents);
    } else {
        return $contents;
    }

}

/**
 * Recursively updates an array/object's strings
 */
function str_replace_recursive($search, $replace, &$object) {
    foreach ($object as $key => &$value) {
        if (is_array($value) || $value instanceof Traversable)
            str_replace_recursive($search, $replace, $value);
        elseif (is_string($value))
            $value = str_replace($search, $replace, $value);
    }
}

/**
 * Returns a constant from a wp-config.php content string
 */
function get_wp_constant($file_contents, $key) {
    preg_match_all("/define\(.$key., ?.([^'\"]+).\)/", $file_contents, $matches);
    return isset($matches[1][0]) ? $matches[1][0] : null;
}
/**
 * Returns a variable from a wp-config.php content string
 */
function get_wp_var($file_contents, $key) {
    preg_match_all("/\\\$$key\s*=\s*([^;]+);/ms", $file_contents, $matches);
    return isset($matches[1][0]) ? trim($matches[1][0]) : null;
}
if (!class_exists('mysqli'))
    die("mysqli library not found.");

if (!isset($argv[1], $argv[2], $argv[3]))
    die("Usage: php wp-refactor.php <path/to/wp-config.php> <search> <replace>\n");

list($file, $wpConfig, $search, $replace) = $argv;

if (!file_exists($wpConfig) || !is_readable($wpConfig))
    die("$wpConfig not found");

$configContents = file_get_contents($wpConfig);

if (!$DB_HOST = get_wp_constant($configContents, 'DB_HOST'))
    die("DB_HOST is empty");
if (!$DB_NAME = get_wp_constant($configContents, 'DB_NAME'))
    die("DB_NAME is empty");
if (!$DB_USER = get_wp_constant($configContents, 'DB_USER'))
    die("DB_USER is empty");
if (!$DB_PASSWORD = get_wp_constant($configContents, 'DB_PASSWORD'))
    die("DB_PASSWORD is empty");

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);

$search_x = $db->real_escape_string($search);
$replace_x = $db->real_escape_string($replace);

$tables = $db->query('show tables');
while ($table = $tables->fetch_row()) {
    $tablename = $table[0];

    $fields = $db->query("DESCRIBE $table[0]");

    while ($field = $fields->fetch_object()) {
        $fieldname = $field->Field;

        $results = $db->query("SELECT `$fieldname` FROM `$tablename` WHERE `$fieldname` LIKE '%$search_x%'");
        if ($results->num_rows) {
            echo "$results->num_rows items in $tablename.$fieldname: ";
            while ($row = $results->fetch_object()) {
                $value = $row->{$fieldname};
                $value_x = $db->real_escape_string($value);
                $new_value_x = $db->real_escape_string(str_replace_serialize($search, $replace, $value));

                $db->query("UPDATE `$tablename` SET `$fieldname` = '$new_value_x' WHERE `$fieldname` = '$value_x'");
                echo '.';
            }
            echo "\n";
        }
        $results->free();
    }

}
