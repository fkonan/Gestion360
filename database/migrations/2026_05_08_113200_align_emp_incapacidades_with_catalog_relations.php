<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'oracle-360';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('EMP_INCAPACIDADES')) {
            return;
        }

        $schema->table('EMP_INCAPACIDADES', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'id_causa_incapacidad')) {
                $table->string('id_causa_incapacidad', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'id_diagnostico')) {
                $table->string('id_diagnostico', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'id_eps')) {
                $table->string('id_eps', 100)->nullable();
            }

            if (! $schema->hasColumn('EMP_INCAPACIDADES', 'id_arl')) {
                $table->string('id_arl', 100)->nullable();
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'causa_codigo')) {
                $table->dropColumn('causa_codigo');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'causa_descripcion')) {
                $table->dropColumn('causa_descripcion');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'diagnostico_codigo')) {
                $table->dropColumn('diagnostico_codigo');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'diagnostico_descripcion')) {
                $table->dropColumn('diagnostico_descripcion');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'eps_codigo')) {
                $table->dropColumn('eps_codigo');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'eps_nombre')) {
                $table->dropColumn('eps_nombre');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'arl_codigo')) {
                $table->dropColumn('arl_codigo');
            }

            if ($schema->hasColumn('EMP_INCAPACIDADES', 'arl_nombre')) {
                $table->dropColumn('arl_nombre');
            }

            $table->index('id_causa_incapacidad', 'emp_incapacidades_causa_idx');
            $table->index('id_diagnostico', 'emp_incapacidades_diagnostico_idx');
            $table->index('id_eps', 'emp_incapacidades_eps_idx');
            $table->index('id_arl', 'emp_incapacidades_arl_idx');
        });
    }

    public function down(): void
    {
        // Migracion intencionalmente irreversible.
    }
};
