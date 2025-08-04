<?php 

namespace App\Services;

use App\Mail\CorreoCredenciales;
use App\Models\GESTIONADMIN\RolApp;
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

            // Asignar el rol al usuario (Gestion360)
            $user->syncRoles($data['rol']);

            // Asignar rol al usuario appmovil
            self::crearRolApp($user);

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


    public static function crearRolApp(User $usuario): void{

        $tipoCargo = self::obtenerTipoCargo($usuario->persona->PerNumDoc);

        $RolSocio = "FALSE";
        $RolEmp = "FALSE";
        $RolCli = "TRUE";

        //Tipo 1 empleado
        if( $tipoCargo->contains(1)){
            $RolEmp = "TRUE";
        }

        //Tipo 13 socio
        if( $tipoCargo->contains(13)){
            $RolSocio = "TRUE";
        } 

        $rol = new RolApp();
        $rol->IdUser = $usuario->IdUsuario;
        $rol->RolSocio = $RolSocio;
        $rol->RolEmp = $RolEmp;
        $rol->RolCli = $RolCli;
        $rol->Movil = "TRUE";
        $rol->Web = "FALSE";
        $rol->RolFecReg = now();
        $rol->RolHorReg = now();
        $rol->save();
    }

    public static function obtenerTipoCargo($documento){
        return DB::connection('oracle')
            ->table('per_empresapersonas as ep')
            ->join('per_personas as p', 'ep.pe_id_pe', '=', 'p.id')
            ->where('ep.activo', 1)
            ->where('ep.estborrado', 0)
            ->where('p.identificacion', $documento)
            ->pluck('ep.tp_id');
    }    
}
