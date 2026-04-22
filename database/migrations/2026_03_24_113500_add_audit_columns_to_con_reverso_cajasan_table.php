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
            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'sequence_id')) {
                $table->unsignedBigInteger('sequence_id')->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'status')) {
                $table->string('status', 80)->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'error_message')) {
                $table->text('error_message')->nullable();
            }

            if (! $schema->hasColumn('CON_REVERSO_CAJASAN', 'additional_data')) {
                $table->text('additional_data')->nullable();
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
            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'additional_data')) {
                $table->dropColumn('additional_data');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'error_message')) {
                $table->dropColumn('error_message');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'status')) {
                $table->dropColumn('status');
            }

            if ($schema->hasColumn('CON_REVERSO_CAJASAN', 'sequence_id')) {
                $table->dropColumn('sequence_id');
            }
        });
    }
};
