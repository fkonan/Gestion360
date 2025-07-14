<?php 

namespace App\Services;

use App\Mail\CorreoCredenciales;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsuarioService
{
    public function crearUsuarioConRol(array $data): void
    {
        DB::transaction(function () use ($data) {
            $contraseñaPlana = Str::random(12);

            $user = User::create([
                'idPersona' => $data['idPersona'],
                'Password' => Hash::make($contraseñaPlana),
                'UsuarioEstado' => 'ACTIVO',
                'Verificado' => 'TRUE',
                'UsuFecReg' => now(),
                'UsuHorReg' => now(),
                'UsuReg' => 'Gestion',
            ]);

            $user->syncRoles($data['rol']);

            $correo = $user->persona->datos->PerEmail ?? null;

            //se envia el correo con los datos de autenticacion
            if ($correo) {
                Mail::to($correo)->send(new CorreoCredenciales([
                    'usuario' => $user->persona->PerNumDoc,
                    'contraseña' => $contraseñaPlana,
                ]));
            }
        });
    }

    public static function cambiarEstado($idUsuario,$idUsuarioLogeado)
    {
        if($idUsuario == $idUsuarioLogeado){
            return ['message' => 'No puede cambiar a estado INACTIVO a su propio registro','type' => 'warning'];
        }

        $usuario = User::findOrFail($idUsuario);
        $usuario->UsuarioEstado = $usuario->UsuarioEstado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        $usuario->save();

        return ['message' => 'Estado cambiado a ' . $usuario->UsuarioEstado,'type' => 'success'];
    }
}
