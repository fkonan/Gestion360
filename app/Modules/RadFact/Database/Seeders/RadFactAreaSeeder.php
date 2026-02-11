<?php

namespace Database\Seeders;

use App\Modules\RadFact\Models\RadFactArea;
use Illuminate\Database\Seeder;

class RadFactAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            // ÁREAS OBLIGATORIAS DEL FLUJO
            [
                'area' => 'Subgerencia Administrativa',
                'responsable' => 'Subgerente Administrativo',
                'correo' => 'subgerencia.administrativa@copetran.com.co',
                'subgerencia' => true,
                'compras' => false,
                'user_id' => null, // Asignar usuario responsable manualmente después
            ],
            [
                'area' => 'Compras',
                'responsable' => 'Jefe de Compras',
                'correo' => 'compras@copetran.com.co',
                'subgerencia' => false,
                'compras' => true,
                'user_id' => null,
            ],

            // ÁREAS DE EJEMPLO (opcionales)
            [
                'area' => 'Mantenimiento',
                'responsable' => 'Jefe de Mantenimiento',
                'correo' => 'mantenimiento@copetran.com.co',
                'subgerencia' => false,
                'compras' => false,
                'user_id' => null,
            ],
            [
                'area' => 'Operaciones',
                'responsable' => 'Jefe de Operaciones',
                'correo' => 'operaciones@copetran.com.co',
                'subgerencia' => false,
                'compras' => false,
                'user_id' => null,
            ],
            [
                'area' => 'Recursos Humanos',
                'responsable' => 'Jefe de RRHH',
                'correo' => 'rrhh@copetran.com.co',
                'subgerencia' => false,
                'compras' => false,
                'user_id' => null,
            ],
            [
                'area' => 'Tecnología',
                'responsable' => 'Jefe de TI',
                'correo' => 'tecnologia@copetran.com.co',
                'subgerencia' => false,
                'compras' => false,
                'user_id' => null,
            ],
            [
                'area' => 'Contabilidad',
                'responsable' => 'Contador General',
                'correo' => 'contabilidad@copetran.com.co',
                'subgerencia' => false,
                'compras' => false,
                'user_id' => null,
            ],
        ];

        foreach ($areas as $area) {
            RadFactArea::create($area);
        }

        $this->command->info('✓ Áreas de RadFact creadas exitosamente');
        $this->command->warn('⚠ Recuerda asignar usuarios responsables a cada área');
    }
}
