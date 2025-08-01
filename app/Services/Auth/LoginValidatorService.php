<?php

namespace App\Services\Auth;

use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\PersonaDatos;
use App\Models\User;
use App\Services\EmpleadoService;
use Auth;
use DB;
use Hash;
use Exception;
use Log;
use Spatie\Permission\Models\Role;
class LoginValidatorService
{
   public function validar(User $user, string $identificacion): ?array
   {
      if (!EmpleadoService::esEmpleadoActivo($identificacion)) {
         return ['message' => 'Solo los empleados activos pueden iniciar sesión.', 'type' => 'danger'];
      }

      if ($user->persona->PerEstado === 'INACTIVO') {
         return ['message' => 'Persona inactiva, contacte con un administrador', 'type' => 'warning'];
      }

      return match ($user->UsuarioEstado) {
         'INACTIVO' => ['message' => 'Usuario inactivo, contacte con un administrador', 'type' => 'warning'],
         'SUSPENDIDO' => ['message' => 'Usuario suspendido, contacte con un administrador', 'type' => 'warning'],
         default => null
      };
   }

   public function validarLogtrans(string $identificacion, string $password)
   {
      $persona = EmpleadoService::esEmpleadoActivo($identificacion, true);
      dd($persona);
      if (!$persona) {
         return ['message' => 'Solo los empleados activos pueden iniciar sesión.', 'type' => 'danger'];
      }
      if ($this->checkSHA1Password($password, $persona->clave)) {
         //crear el registro en autogestion trayendo los campos de logtrans
         $rh = $persona->rh == '0' ? '+' : '-';
         switch ($persona->tipo_sangre) {
            case '0':
               $tipo_sangre = "A" . $rh;
               break;
            case '1':
               $tipo_sangre = "B" . $rh;
               break;
            case '2':
               $tipo_sangre = "O" . $rh;
               break;
            case '3':
               $tipo_sangre = "AB" . $rh;
               break;
            default:
               $tipo_sangre = "O" . $rh;
               break;
         }

         DB::beginTransaction();
         try {
            $personas = new Persona();
            $personas->PerTipoDoc = $persona->tipdocumento;
            $personas->PerNumDoc = $persona->identificacion;
            $personas->PerApellidos = $persona->papellido . ' ' . $persona->sapellido;
            $personas->PerNombres = $persona->snombre;
            $personas->PerGenero = $persona->sexo == 'M' ? 'MASCULINO' : 'FEMENINO';
            $personas->PerFecNac = $persona->fecnacimiento;
            $personas->PerLugNac = $persona->mu_nacimiento;
            $personas->PerFecExp = now()->format('Y-m-d');
            $personas->PerLugExp = '0';
            $personas->PerGruRh = $tipo_sangre;
            $personas->PerFechReg = now()->format('Y-m-d');
            $personas->PerHorReg = now()->format('H:i:s');
            $personas->PerEstado = "ACTIVO";
            $personas->save();

            $personas_datos = new PersonaDatos();
            $personas_datos->IdPersona = $personas->IdPersona;
            $personas_datos->PerEmail = $persona->dirweb;
            $personas_datos->PerDir = $persona->direccion;
            $personas_datos->PerMunRes = $persona->mu_id;
            $personas_datos->PerTelefono = $persona->celular;
            $personas_datos->PerFecReg = now()->format('Y-m-d');
            $personas_datos->PerHorReg = now()->format('H:i:s');
            $personas_datos->PerAutTra = "SI";
            $personas_datos->PerComDat = "SI";
            $personas_datos->PerConPol = "SI";
            $personas_datos->PerAutNot = "SI";
            $personas_datos->save();

            $user = new User();
            $user->idPersona = $personas->IdPersona;
            $user->Password =  Hash::make($password);
            $user->UsuFecReg = now()->format('Y-m-d');
            $user->UsuHorReg = now()->format('H:i:s');
            $user->UsuReg = "Gestion";
            $user->UsuarioEstado = "ACTIVO";
            $user->Verificado = "TRUE";
            $user->save();
            DB::commit();
            return $user;

         } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear la persona: ' . $e->getMessage());
            return ['message' => 'Error al crear la persona.' . $e->getMessage(), 'type' => 'danger'];
         }
         // return redirect()->intended('/dashboard');
      }
      return ['message' => 'Contraseña incorrecta.', 'type' => 'danger'];
   }

   private function checkSHA1Password($plainPassword, $hashedPassword)
   {
      // Replicar exactamente el proceso de Java
      $isoString = mb_convert_encoding($plainPassword, 'ISO-8859-1', 'UTF-8');
      $sha1Hash = sha1($isoString);
      return hash_equals($hashedPassword, $sha1Hash);
   }

   private function shouldRehashPassword($user)
   {
      // Opcional: migrar gradualmente a bcrypt
      return Hash::needsRehash($user->Password);
      //return false; // Por ahora mantener SHA-1
   }
}
