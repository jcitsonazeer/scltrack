<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasColumn('parents', 'username')) {
            Schema::connection('tenant')->table('parents', function (Blueprint $table) {
                $table->string('username', 255)->nullable()->after('email');
            });
        }

        if (! $schema->hasColumn('parents', 'password')) {
            Schema::connection('tenant')->table('parents', function (Blueprint $table) {
                $table->string('password', 255)->nullable()->after('username');
            });
        }

        $indexNames = array_column($schema->getIndexes('parents'), 'name');

        if (! in_array('parents_username_unique', $indexNames, true)) {
            Schema::connection('tenant')->table('parents', function (Blueprint $table) {
                $table->unique('username', 'parents_username_unique');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');

        $indexNames = array_column($schema->getIndexes('parents'), 'name');

        if (in_array('parents_username_unique', $indexNames, true)) {
            Schema::connection('tenant')->table('parents', function (Blueprint $table) {
                $table->dropUnique('parents_username_unique');
            });
        }

        Schema::connection('tenant')->table('parents', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('parents', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::connection('tenant')->hasColumn('parents', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
