<?php
/**
 * Universal MySQL DB Update Generator
 *
 * WORKFLOW:
 * 1. Put this file in your LOCAL project
 * 2. Set DB details below
 * 3. First time:
 *      php DBupdate.php --init
 * 4. Make database changes locally
 * 5. Run:
 *      php DBupdate.php
 *
 * Generates:
 *      db_schema.json
 *      db_updates.sql
 *
 * Detects:
 *      - New tables
 *      - Dropped tables
 *      - New columns
 *      - Dropped columns
 *      - Changed columns
 *      - New indexes
 *      - Dropped indexes
 *
 * IMPORTANT:
 * This file NEVER changes LIVE database automatically.
 * It only generates db_updates.sql.
 */


/* ============================================================
   DATABASE CONFIGURATION
   CHANGE THESE VALUES FOR EACH PROJECT
   ============================================================ */

$host = 'localhost';
$db   = 'crm_portal';
$user = 'root';
$pass = '';


/* ============================================================
   FILES
   ============================================================ */

$baseDir    = __DIR__;
$schemaFile = $baseDir . DIRECTORY_SEPARATOR . 'db_schema.json';
$sqlFile    = $baseDir . DIRECTORY_SEPARATOR . 'db_updates.sql';


if (!file_exists($sqlFile)) {
    file_put_contents(
        $sqlFile,
        "-- Generated database update file" . PHP_EOL .
        "-- Review this file before running on LIVE." . PHP_EOL .
        PHP_EOL
    );
}


/* ============================================================
   HELPERS
   ============================================================ */

function qi(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}


function qstr(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}


function isSqlExpression(string $value): bool
{
    $v = trim($value);

    return (bool) preg_match(
        '/^(CURRENT_TIMESTAMP(?:\(\d+\))?|CURRENT_DATE|CURRENT_TIME(?:\(\d+\))?|NULL|NOW\(\)|UUID\(\)|[A-Z_]+\(\))$/i',
        $v
    );
}


function columnDefinition(array $column): string
{
    $definition = $column['Type'];

    if (($column['Null'] ?? '') === 'NO') {
        $definition .= ' NOT NULL';
    } else {
        $definition .= ' NULL';
    }

    if (($column['Default'] ?? null) !== null) {

        $default = (string)$column['Default'];

        if (isSqlExpression($default)) {
            $definition .= ' DEFAULT ' . $default;
        } else {
            $definition .= ' DEFAULT ' . qstr($default);
        }
    }

    $extra = trim((string)($column['Extra'] ?? ''));

    if (stripos($extra, 'auto_increment') !== false) {
        $definition .= ' AUTO_INCREMENT';
    }

    if (stripos($extra, 'on update CURRENT_TIMESTAMP') !== false) {
        $definition .= ' ON UPDATE CURRENT_TIMESTAMP';
    }

    if (!empty($column['Comment'])) {
        $definition .= ' COMMENT ' . qstr((string)$column['Comment']);
    }

    return $definition;
}


function normalizeColumn(array $column): array
{
    return [
        'Field'   => (string)($column['Field'] ?? ''),
        'Type'    => (string)($column['Type'] ?? ''),
        'Null'    => (string)($column['Null'] ?? ''),
        'Default' => $column['Default'] ?? null,
        'Extra'   => (string)($column['Extra'] ?? ''),
        'Comment' => (string)($column['Comment'] ?? ''),
    ];
}


function getSchema(PDO $pdo): array
{
    $schema = [];

    $tables = $pdo->query(
        'SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'
    )->fetchAll(PDO::FETCH_NUM);


    foreach ($tables as $row) {

        $table = $row[0];


        /* ---------------- COLUMNS ---------------- */

        $stmt = $pdo->query(
            'SHOW FULL COLUMNS FROM ' . qi($table)
        );

        $columns = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = normalizeColumn($column);
        }


        /* ---------------- INDEXES ---------------- */

        $indexes = [];

        $indexRows = $pdo->query(
            'SHOW INDEX FROM ' . qi($table)
        )->fetchAll(PDO::FETCH_ASSOC);


        foreach ($indexRows as $idx) {

            $keyName = (string)$idx['Key_name'];

            if ($keyName === '') {
                continue;
            }


            if (!isset($indexes[$keyName])) {

                $indexes[$keyName] = [
                    'name'    => $keyName,
                    'unique'  => ((int)$idx['Non_unique'] === 0),
                    'type'    => (string)($idx['Index_type'] ?? 'BTREE'),
                    'columns' => [],
                ];
            }


            $seq = (int)$idx['Seq_in_index'];

            $indexes[$keyName]['columns'][$seq] =
                (string)$idx['Column_name'];
        }


        foreach ($indexes as &$index) {

            ksort($index['columns']);

            $index['columns'] =
                array_values($index['columns']);
        }

        unset($index);


        /* ---------------- CREATE TABLE ---------------- */

        $createStmt = $pdo->query(
            'SHOW CREATE TABLE ' . qi($table)
        )->fetch(PDO::FETCH_NUM);

        $createSql = $createStmt[1] ?? '';


        $schema[$table] = [
            'columns' => $columns,
            'indexes' => array_values($indexes),
            'create'  => $createSql,
        ];
    }


    ksort($schema);

    return $schema;
}


function indexSignature(array $index): string
{
    return json_encode([
        'unique'  => (bool)$index['unique'],
        'type'    => strtoupper((string)$index['type']),
        'columns' => array_values($index['columns']),
    ]);
}


function indexIsPrimary(array $index): bool
{
    return strtoupper((string)$index['name']) === 'PRIMARY';
}


/* ============================================================
   DATABASE CONNECTION
   ============================================================ */

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,
        ]
    );

} catch (PDOException $e) {

    die(
        "Database connection failed: "
        . $e->getMessage()
        . PHP_EOL
    );
}


/* ============================================================
   GET CURRENT LOCAL SCHEMA
   ============================================================ */

$currentSchema = getSchema($pdo);


/* ============================================================
   INITIAL BASELINE
   ============================================================ */

if (($argv[1] ?? '') === '--init') {

    file_put_contents(
        $schemaFile,
        json_encode(
            $currentSchema,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE
        )
    );


    echo PHP_EOL;
    echo "========================================" . PHP_EOL;
    echo "Initial database baseline created" . PHP_EOL;
    echo "========================================" . PHP_EOL;
    echo "Database : {$db}" . PHP_EOL;
    echo "Tables   : " . count($currentSchema) . PHP_EOL;
    echo "File     : {$schemaFile}" . PHP_EOL;
    echo PHP_EOL;

    exit(0);
}


/* ============================================================
   CHECK BASELINE
   ============================================================ */

if (!file_exists($schemaFile)) {

    echo PHP_EOL;
    echo "db_schema.json does not exist." . PHP_EOL;
    echo PHP_EOL;
    echo "Run this first:" . PHP_EOL;
    echo "php DBupdate.php --init" . PHP_EOL;
    echo PHP_EOL;

    exit(1);
}


$oldSchema = json_decode(
    file_get_contents($schemaFile),
    true
);


if (!is_array($oldSchema)) {
    die("Invalid db_schema.json" . PHP_EOL);
}


/* ============================================================
   COMPARE
   ============================================================ */

$sqlParts = [];
$changes  = [];


/* ============================================================
   NEW TABLES
   ============================================================ */

foreach ($currentSchema as $table => $tableInfo) {

    if (!isset($oldSchema[$table])) {

        $createSql = $tableInfo['create'] ?? '';

        if ($createSql !== '') {

            $sqlParts[] =
                "-- NEW TABLE: {$table}" . PHP_EOL .
                $createSql .
                ';' .
                PHP_EOL;

            $changes[] =
                "NEW TABLE: {$table}";
        }
    }
}


/* ============================================================
   DROPPED TABLES
   ============================================================ */

foreach ($oldSchema as $table => $oldTableInfo) {

    if (!isset($currentSchema[$table])) {

        $sqlParts[] =
            "-- WARNING: DROPPED TABLE: {$table}" . PHP_EOL .
            'DROP TABLE ' . qi($table) . ';' .
            PHP_EOL;

        $changes[] =
            "DROPPED TABLE: {$table}";
    }
}


/* ============================================================
   EXISTING TABLES
   ============================================================ */

foreach ($currentSchema as $table => $tableInfo) {

    if (!isset($oldSchema[$table])) {
        continue;
    }


    $oldTable = $oldSchema[$table];


    /* ========================================================
       COLUMNS
       ======================================================== */

    $oldColumns = [];

    foreach (($oldTable['columns'] ?? []) as $column) {

        $oldColumns[$column['Field']] =
            $column;
    }


    $currentColumns = [];

    foreach (($tableInfo['columns'] ?? []) as $column) {

        $currentColumns[$column['Field']] =
            $column;
    }


    /* ---------------- NEW COLUMNS ---------------- */

    foreach ($currentColumns as $columnName => $column) {

        if (!isset($oldColumns[$columnName])) {

            $definition =
                columnDefinition($column);


            $sqlParts[] =
                "-- NEW COLUMN: {$table}.{$columnName}" .
                PHP_EOL .

                'ALTER TABLE ' .
                qi($table) .
                PHP_EOL .

                'ADD COLUMN ' .
                qi($columnName) .
                ' ' .
                $definition .
                ';' .
                PHP_EOL;


            $changes[] =
                "NEW COLUMN: {$table}.{$columnName}";
        }
    }


    /* ---------------- CHANGED COLUMNS ---------------- */

    foreach ($currentColumns as $columnName => $column) {

        if (!isset($oldColumns[$columnName])) {
            continue;
        }


        $oldDefinition =
            columnDefinition(
                $oldColumns[$columnName]
            );

        $newDefinition =
            columnDefinition($column);


        if ($oldDefinition !== $newDefinition) {

            $sqlParts[] =
                "-- CHANGED COLUMN: {$table}.{$columnName}" .
                PHP_EOL .

                'ALTER TABLE ' .
                qi($table) .
                PHP_EOL .

                'MODIFY COLUMN ' .
                qi($columnName) .
                ' ' .
                $newDefinition .
                ';' .
                PHP_EOL;


            $changes[] =
                "CHANGED COLUMN: {$table}.{$columnName}";
        }
    }


    /* ---------------- DROPPED COLUMNS ---------------- */

    foreach ($oldColumns as $columnName => $oldColumn) {

        if (!isset($currentColumns[$columnName])) {

            $sqlParts[] =
                "-- WARNING: DROPPED COLUMN: {$table}.{$columnName}" .
                PHP_EOL .

                'ALTER TABLE ' .
                qi($table) .
                PHP_EOL .

                'DROP COLUMN ' .
                qi($columnName) .
                ';' .
                PHP_EOL;


            $changes[] =
                "DROPPED COLUMN: {$table}.{$columnName}";
        }
    }


    /* ========================================================
       INDEXES
       ======================================================== */

    $oldIndexes = [];

    foreach (($oldTable['indexes'] ?? []) as $index) {

        $oldIndexes[$index['name']] =
            $index;
    }


    $currentIndexes = [];

    foreach (($tableInfo['indexes'] ?? []) as $index) {

        $currentIndexes[$index['name']] =
            $index;
    }


    /* ---------------- NEW INDEXES ---------------- */

    foreach ($currentIndexes as $indexName => $index) {

        if (indexIsPrimary($index)) {
            continue;
        }


        if (!isset($oldIndexes[$indexName])) {

            $unique =
                !empty($index['unique'])
                    ? 'UNIQUE '
                    : '';


            $columns =
                implode(
                    ', ',
                    array_map(
                        'qi',
                        $index['columns']
                    )
                );


            $indexType =
                strtoupper(
                    (string)$index['type']
                );


            $using =
                (
                    $indexType !== '' &&
                    $indexType !== 'BTREE'
                )
                    ? ' USING ' . $indexType
                    : '';


            $sqlParts[] =
                "-- NEW INDEX: {$table}.{$indexName}" .
                PHP_EOL .

                'ALTER TABLE ' .
                qi($table) .
                PHP_EOL .

                'ADD ' .
                $unique .
                'INDEX ' .
                qi($indexName) .
                $using .
                ' (' .
                $columns .
                ');' .
                PHP_EOL;


            $changes[] =
                "NEW INDEX: {$table}.{$indexName}";
        }
    }


    /* ---------------- DROPPED INDEXES ---------------- */

    foreach ($oldIndexes as $indexName => $oldIndex) {

        if (indexIsPrimary($oldIndex)) {
            continue;
        }


        if (!isset($currentIndexes[$indexName])) {

            $sqlParts[] =
                "-- WARNING: DROPPED INDEX: {$table}.{$indexName}" .
                PHP_EOL .

                'ALTER TABLE ' .
                qi($table) .
                PHP_EOL .

                'DROP INDEX ' .
                qi($indexName) .
                ';' .
                PHP_EOL;


            $changes[] =
                "DROPPED INDEX: {$table}.{$indexName}";
        }
    }


    /* ---------------- CHANGED INDEXES ---------------- */

    foreach ($currentIndexes as $indexName => $index) {

        if (
            indexIsPrimary($index) ||
            !isset($oldIndexes[$indexName])
        ) {
            continue;
        }


        if (
            indexSignature($oldIndexes[$indexName])
            !==
            indexSignature($index)
        ) {

            $changes[] =
                "INDEX CHANGED - MANUAL REVIEW: {$table}.{$indexName}";
        }
    }
}


/* ============================================================
   NO CHANGES
   ============================================================ */

if (empty($sqlParts)) {

    echo PHP_EOL;
    echo "No new database changes found." .
        PHP_EOL;

    if (!empty($changes)) {

        echo PHP_EOL;
        echo "Manual review:" .
            PHP_EOL;

        foreach ($changes as $change) {

            echo "  ! {$change}" .
                PHP_EOL;
        }
    }

    echo PHP_EOL;

    exit(0);
}


/* ============================================================
   CREATE SQL BLOCK
   ============================================================ */

$header =
    PHP_EOL .
    "-- =====================================================" .
    PHP_EOL .
    "-- DATABASE UPDATE" .
    PHP_EOL .
    "-- Generated: " .
    date('Y-m-d H:i:s') .
    PHP_EOL .
    "-- Database: {$db}" .
    PHP_EOL .
    "-- =====================================================" .
    PHP_EOL .
    PHP_EOL;


$block =
    $header .
    implode(PHP_EOL, $sqlParts) .
    PHP_EOL .
    "-- End of generated changes" .
    PHP_EOL;


/* ============================================================
   CREATE SQL FILE
   ============================================================ */

if (!file_exists($sqlFile)) {

    file_put_contents(
        $sqlFile,
        "-- Generated database update file" .
        PHP_EOL .
        "-- Review before running on LIVE." .
        PHP_EOL
    );
}


file_put_contents(
    $sqlFile,
    $block,
    FILE_APPEND
);


/* ============================================================
   UPDATE BASELINE
   ============================================================ */

file_put_contents(
    $schemaFile,
    json_encode(
        $currentSchema,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE
    )
);


/* ============================================================
   OUTPUT
   ============================================================ */

echo PHP_EOL;
echo "========================================" .
    PHP_EOL;
echo "DB changes detected and saved" .
    PHP_EOL;
echo "========================================" .
    PHP_EOL;
echo PHP_EOL;


foreach ($changes as $change) {

    echo "  + {$change}" .
        PHP_EOL;
}


echo PHP_EOL;
echo "SQL file:" .
    PHP_EOL;

echo "  {$sqlFile}" .
    PHP_EOL;

echo PHP_EOL;
echo "Review db_updates.sql before running on LIVE." .
    PHP_EOL;
echo PHP_EOL;