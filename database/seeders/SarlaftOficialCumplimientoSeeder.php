<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Idempotente. Crea el rol `oficial_cumplimiento` si no existe y lo asigna al usuario 1741 si no lo tiene.
 *
 * Ejecutar SOLO con: php artisan db:seed --class=SarlaftOficialCumplimientoSeeder
 */
class SarlaftOficialCumplimientoSeeder extends Seeder
{
    private const ROLE_NAME = 'oficial_cumplimiento';

    private const USER_ID = 1741;

    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['name' => self::ROLE_NAME, 'guard_name' => 'web'],
        );

        if ($role->wasRecentlyCreated) {
            $this->command->info("Rol '".self::ROLE_NAME."' creado (id={$role->id}).");
        } else {
            $this->command->line("Rol '".self::ROLE_NAME."' ya existia (id={$role->id}).");
        }

        $user = User::query()->where('IdUsuario', self::USER_ID)->first();

        if (! $user) {
            $this->command->warn('Usuario IdUsuario='.self::USER_ID.' no encontrado. Rol creado pero no asignado.');

            return;
        }

        if ($user->hasRole(self::ROLE_NAME)) {
            $this->command->line("Usuario {$user->IdUsuario} ya tiene el rol '".self::ROLE_NAME."'.");

            return;
        }

        DB::transaction(function () use ($user): void {
            $user->assignRole(self::ROLE_NAME);
        });

        $this->command->info("Rol '".self::ROLE_NAME."' asignado al usuario IdUsuario={$user->IdUsuario}.");
    }
}
