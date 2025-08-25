<?php

namespace App\Constants;

class Permisos
{
    // GESTIÓN RRHH
    const GESTION_RRHH_ACCEDER = 'gestion_rrhh.acceder';
    const GESTION_RRHH_GESTION_EMPLEADO_CREAR = 'gestion_rrhh.gestion_empleado.crear';
    const GESTION_RRHH_GESTION_EMPLEADO_ACCEDER = 'gestion_rrhh.gestion_empleado.acceder';
    const GESTION_RRHH_GESTION_EMPLEADO_ACTUALIZAR = 'gestion_rrhh.gestion_empleado.actualizar';

    // CONFIGURACIÓN
    const CONFIGURACION_ACCEDER = 'configuracion.acceder';
    const CONFIGURACION_GESTION_SISTEMA_ACCEDER = 'configuracion.gestion_sistema.acceder';
    const CONFIGURACION_GESTION_SISTEMA_CREAR = 'configuracion.gestion_sistema.crear';
    const CONFIGURACION_GESTION_SISTEMA_ACTUALIZAR = 'configuracion.gestion_sistema.actualizar';

    // ADMINISTRACIÓN
    const ADMINISTRACION_ACCEDER = 'administracion.acceder';
    const ADMINISTRACION_USUARIOS_ACCEDER = 'administracion.usuarios.acceder';
    const ADMINISTRACION_USUARIOS_CREAR = 'administracion.usuarios.crear';
    const ADMINISTRACION_USUARIOS_ACTUALIZAR = 'administracion.usuarios.actualizar';
    const ADMINISTRACION_USUARIOS_ASIGNAR_ROLES = 'administracion.usuarios.asignar_roles';
    const ADMINISTRACION_USUARIOS_ASIGNAR_PERMISOS = 'administracion.usuarios.asignar_permisos';

    const ADMINISTRACION_PERSONAS_ACCEDER = 'administracion.personas.acceder';
    const ADMINISTRACION_PERSONAS_CREAR = 'administracion.personas.crear';
    const ADMINISTRACION_PERSONAS_ACTUALIZAR = 'administracion.personas.actualizar';

    const ADMINISTRACION_REPORTES_ACCEDER = 'administracion.reportes.acceder';
    const ADMINISTRACION_REPORTES_CONDUCTORES = 'administracion.reportes.reportes_conductores';
    const ADMINISTRACION_REPORTES_CARGA = 'administracion.reportes.reportes_carga';
    const ADMINISTRACION_REPORTES_PASAJES = 'administracion.reportes.reportes_pasajes';
    const ADMINISTRACION_REPORTES_EMPLEADOS = 'administracion.reportes.reportes_empleados';

    const ADMINISTRACION_EMPLEADOS_ACCEDER = 'administracion.empleados.acceder';

    // VENTA DE PASAJES
    const VENTA_DE_PASAJES_ACCEDER = 'venta_de_pasajes.acceder';

    // OLIMPIADAS COPETRAN
    const OLIMPIADAS_COPETRAN_ACCEDER = 'olimpiadas_copetran.acceder';

    // GESTIÓN PASAJES
    const GESTION_PASAJES_ACCEDER = 'gestion_pasajes.acceder';

    // GESTIÓN SISTEMAS
    const GESTION_SISTEMAS_ACCEDER = 'gestion_sistemas.acceder';

    // GESTIÓN DATOS
    const GESTION_DATOS_ACCEDER = 'gestion_datos.acceder';

    // GESTIÓN WEB
    const GESTION_WEB_ACCEDER = 'gestion_web.acceder';
    const GESTION_WEB_GESTION_APP_MOVIL_ACCEDER = 'gestion_web.gestion_app_movil.acceder';

    // PAGOS Y RECAUDOS
    const PAGOS_Y_RECAUDOS_ACCEDER = 'pagos_y_recaudos.acceder';
    const PAGOS_Y_RECAUDOS_PAGOS_CONVENIOS_ACCEDER = 'pagos_y_recaudos.pagos_convenios.acceder';
}
