<?php

declare(strict_types=1);

namespace Axn\ModelsScanner\Services;

use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Database\Connection as LaravelConnection;
use Illuminate\Support\Facades\DB;

final class DatabaseScanner
{
    public function scan(?string $connectionName = null): array
    {
        $connectionName ??= config('database.default');

        /** @var LaravelConnection $laravelConn */
        $laravelConn = DB::connection($connectionName);

        $dbalConn = $this->resolveDbalConnection($laravelConn);
        $schemaManager = $dbalConn->createSchemaManager();

        $result = [];

        foreach ($schemaManager->introspectTables() as $table) {
            $tableName = $this->unquoteIdentifier($table->getObjectName()->toString());

            $result[$tableName] ??= ['relations' => []];
            $result[$tableName]['manager'] = $table;

            // attend un nom non-quoté
            $fks = $schemaManager->introspectTableForeignKeyConstraintsByUnquotedName($tableName);

            foreach ($fks as $fk) {
                $fkInfo = $this->getForeignKeyInfo($fk);
                $fkInverseInfo = $this->getForeignKeyInverseInfo($fkInfo, $tableName);

                $result[$tableName]['relations'][] = $fkInfo;

                $result[$fkInfo['foreign_table']] ??= ['relations' => []];
                $result[$fkInfo['foreign_table']]['relations'][] = $fkInverseInfo;
            }
        }

        ksort($result);

        return $result;
    }

    private function getForeignKeyInfo(ForeignKeyConstraint $fk): array
    {
        return [
            'type' => 'BelongsTo',
            'constraint' => $this->unquoteIdentifier($fk->getObjectName()->toString()),
            'local_column' =>  $this->unquoteIdentifier($fk->getLocalColumns()[0]),
            'foreign_table' => $this->unquoteIdentifier($fk->getForeignTableName()),
            'foreign_column' => $this->unquoteIdentifier($fk->getForeignColumns()[0]),
        ];
    }

    private function getForeignKeyInverseInfo(array $fkInfo, $foreignTable): array
    {
        return [
            'type' => 'HasMany',
            'constraint' => $fkInfo['constraint'],
            'local_column' => $fkInfo['foreign_column'],
            'foreign_table' => $foreignTable,
            'foreign_column' => $fkInfo['local_column'],
        ];
    }

    /**
     * Récupère une connexion DBAL depuis Laravel (si dispo) ou reconstruit via config.
     */
    private function resolveDbalConnection(LaravelConnection $laravelConn): DbalConnection
    {
        if (method_exists($laravelConn, 'getDoctrineConnection')) {
            /** @var DbalConnection $conn */
            $conn = $laravelConn->getDoctrineConnection();
            return $conn;
        }

        $cfg = $laravelConn->getConfig();

        $driver = match ($cfg['driver'] ?? null) {
            'mysql'  => 'pdo_mysql',
            'pgsql'  => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
            default  => throw new \RuntimeException('Driver non supporté pour DBAL.'),
        };

        $params = array_filter([
            'driver'   => $driver,
            'host'     => $cfg['host'] ?? null,
            'port'     => $cfg['port'] ?? null,
            'dbname'   => $cfg['database'] ?? null,
            'user'     => $cfg['username'] ?? null,
            'password' => $cfg['password'] ?? null,
            'charset'  => $cfg['charset'] ?? null,
            'path'     => ($cfg['driver'] ?? null) === 'sqlite' ? ($cfg['database'] ?? null) : null,
        ], static fn ($v) => $v !== null && $v !== '');

        return DriverManager::getConnection($params);
    }

    /**
     * Dé-quote un identifiant SQL de façon robuste, selon ce qu’on reçoit réellement.
     *
     * Supporte :
     *  - "name"  (double quotes)
     *  - `name`  (backticks)
     *  - [name]  (brackets)
     * Et les identifiants qualifiés : schema."table", `db`.`table`, [dbo].[table]
     */
    private function unquoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return $identifier;
        }

        $parts = $this->splitQualifiedIdentifier($identifier);

        $parts = array_map(function (string $part): string {
            $p = trim($part);

            // 1) Brackets SQL Server: [name]
            if (str_starts_with($p, '[') && str_ends_with($p, ']') && strlen($p) >= 2) {
                $inner = substr($p, 1, -1);
                return str_replace(']]', ']', $inner);
            }

            // 2) Double quotes: "name"
            if (str_starts_with($p, '"') && str_ends_with($p, '"') && strlen($p) >= 2) {
                $inner = substr($p, 1, -1);
                return str_replace('""', '"', $inner);
            }

            // 3) Backticks: `name`
            if (str_starts_with($p, '`') && str_ends_with($p, '`') && strlen($p) >= 2) {
                $inner = substr($p, 1, -1);
                return str_replace('``', '`', $inner);
            }

            return $p;
        }, $parts);

        return implode('.', $parts);
    }

    /**
     * Split un identifiant qualifié en segments, sans splitter les '.' à l'intérieur des quotes.
     * Ex: schema."my.table" -> ['schema', '"my.table"']
     */
    private function splitQualifiedIdentifier(string $identifier): array
    {
        $out = [];
        $buf = '';
        $len = strlen($identifier);

        $inDouble = false;
        $inBack   = false;
        $inBrack  = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $identifier[$i];

            if (!$inBack && !$inBrack && $ch === '"') {
                $inDouble = !$inDouble;
            } elseif (!$inDouble && !$inBrack && $ch === '`') {
                $inBack = !$inBack;
            } elseif (!$inDouble && !$inBack && $ch === '[') {
                $inBrack = true;
            } elseif ($inBrack && $ch === ']') {
                $inBrack = false;
            }

            if ($ch === '.' && !$inDouble && !$inBack && !$inBrack) {
                $out[] = $buf;
                $buf = '';
                continue;
            }

            $buf .= $ch;
        }

        $out[] = $buf;

        return $out;
    }
}
