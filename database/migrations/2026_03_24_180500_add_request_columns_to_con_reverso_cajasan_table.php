<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('oracle');

        if (! $schema->hasTable('CON_REVERSO_CAJASAN')) {
            return;
        }

        $schema->table('CON_REVERSO_CAJASAN', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'identification_type')) {
                $table->string('identification_type', 10)->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'identification')) {
                $table->string('identification', 50)->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'amount_tran')) {
                $table->string('amount_tran', 30)->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'state_code')) {
                $table->unsignedInteger('state_code')->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'city_code')) {
                $table->unsignedInteger('city_code')->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('oracle');

        if (! $schema->hasTable('CON_REVERSO_CAJASAN')) {
            return;
        }

        $schema->table('CON_REVERSO_CAJASAN', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'city_code')) {
                $table->dropColumn('city_code');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'state_code')) {
                $table->dropColumn('state_code');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'amount_tran')) {
                $table->dropColumn('amount_tran');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'identification')) {
                $table->dropColumn('identification');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'identification_type')) {
                $table->dropColumn('identification_type');
            }
        });
    }
};
