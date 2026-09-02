<?php

/*
|--------------------------------------------------------------------------
| Simple CRM Migration Generator
|--------------------------------------------------------------------------
| Local DB: crm_portal
|
| First time:
|   php migrations/generate.php --init
|
| After making DB changes:
|   php migrations/generate.php
|
|--------------------------------------------------------------------------
*/

$host = 'localhost';
$db   = 'crm_portal';
$user = 'root';
$pass = '';

$migrationDir = __DIR__;
$schemaFile   = $migrationDir . '/schema.json';

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage() . PHP_EOL);
}


/*
|--------------------------------------------------------------------------
| Get current database structure
|--------------------------------------------------------------------------
*/

function getSchema(PDO $pdo): array
{
    $schema = [];

    $tables = $pdo->query("
        SHOW TABLES
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {

        $columns = $pdo->query(
            "SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`"
        )->fetchAll();

        $schema[$table] = $columns;
    }

    return $schema;
}


$currentSchema = getSchema($pdo);


/*
|--------------------------------------------------------------------------
| First time - create baseline
|--------------------------------------------------------------------------
*/

if (isset($argv[1]) && $argv[1] === '--init') {

    file_put_contents(
        $schemaFile,
        json_encode(
            $currentSchema,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
    );

    echo PHP_EOL;
    echo "Initial database structure saved." . PHP_EOL;
    echo "File: migrations/schema.json" . PHP_EOL;
    echo "Tables: " . count($currentSchema) . PHP_EOL;
    echo PHP_EOL;

    exit;
}


/*
|--------------------------------------------------------------------------
| Check baseline
|--------------------------------------------------------------------------
*/

if (!file_exists($schemaFile)) {

    echo PHP_EOL;
    echo "schema.json does not exist." . PHP_EOL;
    echo PHP_EOL;
    echo "Run first:" . PHP_EOL;
    echo "php migrations/generate.php --init" . PHP_EOL;
    echo PHP_EOL;

    exit(1);
}


$oldSchema = json_decode(
    file_get_contents($schemaFile),
    true
);

if (!is_array($oldSchema)) {
    die("Invalid schema.json" . PHP_EOL);
}


/*
|--------------------------------------------------------------------------
| Find differences
|--------------------------------------------------------------------------
*/

$sql = '';

$newTables = [];
$newColumns = [];


/*
|--------------------------------------------------------------------------
| New tables
|--------------------------------------------------------------------------
*/

foreach ($currentSchema as $table => $columns) {

    if (!isset($oldSchema[$table])) {

        $newTables[] = $table;

        $quotedTable = str_replace('`', '``', $table);

        $create = $pdo->query(
            "SHOW CREATE TABLE `{$quotedTable}`"
        )->fetch();

        $createSql = array_values($create)[1] ?? '';

        if ($createSql) {

            $sql .= "-- New table: {$table}" . PHP_EOL;
            $sql .= $createSql . ";" . PHP_EOL . PHP_EOL;
        }
    }
}


/*
|--------------------------------------------------------------------------
| New columns
|--------------------------------------------------------------------------
*/

foreach ($currentSchema as $table => $columns) {

    if (!isset($oldSchema[$table])) {
        continue;
    }

    $oldColumns = [];

    foreach ($oldSchema[$table] as $column) {
        $oldColumns[$column['Field']] = $column;
    }

    foreach ($columns as $column) {

        $columnName = $column['Field'];

        if (!isset($oldColumns[$columnName])) {

            $newColumns[] = "{$table}.{$columnName}";

            $definition = $column['Type'];

            if ($column['Null'] === 'NO') {
                $definition .= ' NOT NULL';
            } else {
                $definition .= ' NULL';
            }

            if ($column['Default'] !== null) {

                $default = $column['Default'];

                if (
                    strtoupper($default) === 'CURRENT_TIMESTAMP'
                    || preg_match('/^[A-Z_]+\(\)$/i', $default)
                ) {
                    $definition .= " DEFAULT {$default}";
                } else {
                    $default = str_replace("'", "''", $default);
                    $definition .= " DEFAULT '{$default}'";
                }
            }

            if (strpos($column['Extra'], 'auto_increment') !== false) {
                $definition .= ' AUTO_INCREMENT';
            }

            $sql .= "-- New column: {$table}.{$columnName}" . PHP_EOL;

            $sql .= "ALTER TABLE `{$table}`" . PHP_EOL;
            $sql .= "ADD COLUMN `{$columnName}` {$definition};" . PHP_EOL;
            $sql .= PHP_EOL;
        }
    }
}


/*
|--------------------------------------------------------------------------
| No changes
|--------------------------------------------------------------------------
*/

if ($sql === '') {

    echo PHP_EOL;
    echo "No database changes found." . PHP_EOL;
    echo PHP_EOL;

    exit;
}


/*
|--------------------------------------------------------------------------
| Find next migration number
|--------------------------------------------------------------------------
*/

$files = glob($migrationDir . '/*.sql');

$number = 1;

foreach ($files as $file) {

    $name = basename($file);

    if (preg_match('/^(\d+)_/', $name, $match)) {

        $n = (int)$match[1];

        if ($n >= $number) {
            $number = $n + 1;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Migration name
|--------------------------------------------------------------------------
*/

$nameParts = [];

if (!empty($newTables)) {
    $nameParts[] = 'add_tables';
}

if (!empty($newColumns)) {
    $nameParts[] = 'add_columns';
}

if (empty($nameParts)) {
    $nameParts[] = 'db_update';
}

$filename =
    str_pad($number, 3, '0', STR_PAD_LEFT)
    . '_'
    . implode('_', $nameParts)
    . '.sql';

$migrationFile = $migrationDir . '/' . $filename;


/*
|--------------------------------------------------------------------------
| Create migration
|--------------------------------------------------------------------------
*/

$content  = "-- CRM Portal Database Migration" . PHP_EOL;
$content .= "-- Generated: " . date('Y-m-d H:i:s') . PHP_EOL;
$content .= "-- Database: {$db}" . PHP_EOL;
$content .= PHP_EOL;
$content .= "START TRANSACTION;" . PHP_EOL;
$content .= PHP_EOL;
$content .= $sql;
$content .= "COMMIT;" . PHP_EOL;


file_put_contents(
    $migrationFile,
    $content
);


/*
|--------------------------------------------------------------------------
| Update schema baseline
|--------------------------------------------------------------------------
*/

file_put_contents(
    $schemaFile,
    json_encode(
        $currentSchema,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);


echo PHP_EOL;
echo "========================================" . PHP_EOL;
echo "Migration created successfully" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo PHP_EOL;
echo "File: migrations/{$filename}" . PHP_EOL;
echo PHP_EOL;

if (!empty($newTables)) {

    echo "New tables:" . PHP_EOL;

    foreach ($newTables as $table) {
        echo "  + {$table}" . PHP_EOL;
    }

    echo PHP_EOL;
}

if (!empty($newColumns)) {

    echo "New columns:" . PHP_EOL;

    foreach ($newColumns as $column) {
        echo "  + {$column}" . PHP_EOL;
    }

    echo PHP_EOL;
}