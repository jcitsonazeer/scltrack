<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TenantDatabaseService
{
    private ?string $defaultDatabase = null;

    public function currentDatabase(): string
    {
        return (string) config('database.connections.tenant.database');
    }

    public function defaultDatabase(): string
    {
        if ($this->defaultDatabase === null) {
            $this->defaultDatabase = $this->currentDatabase();
        }

        return $this->defaultDatabase;
    }

    public function databaseExists(?string $databaseName): bool
    {
        if (empty($databaseName)) {
            return false;
        }

        $found = DB::connection('mysql')->selectOne(
            'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$databaseName]
        );

        return $found !== null;
    }

    public function switchTo(?string $databaseName): void
    {
        if (empty($databaseName)) {
            return;
        }

        $this->defaultDatabase();

        config(['database.connections.tenant.database' => $databaseName]);

        DB::purge('tenant');
    }

    public function restoreDefault(): void
    {
        if ($this->defaultDatabase === null) {
            return;
        }

        config(['database.connections.tenant.database' => $this->defaultDatabase]);

        DB::purge('tenant');
    }
}
