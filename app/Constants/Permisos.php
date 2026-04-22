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

    // ROLES REPORTES
    const ADMINISTRACION_REPORTES_ACCEDER = 'administracion.reportes.acceder';
    const ADMINISTRACION_REPORTES_CONDUCTORES = 'administracion.reportes.reportes_conductores';
    const ADMINISTRACION_REPORTES_CARGA = 'administracion.reportes.reportes_carga';
    const ADMINISTRACION_REPORTES_PASAJES = 'administracion.reportes.reportes_pasajes';
    const ADMINISTRACION_REPORTES_EMPLEADOS = 'administracion.reportes.reportes_empleados';
    const ADMINISTRACION_REPORTES_PERSONAS = 'administracion.reportes.reportes_personas';
    const ADMINISTRACION_REPORTES_CARTERA = 'administracion.reportes.reportes_cartera';
    const ADMINISTRACION_REPORTES_AUDITORIA = 'administracion.reportes.reportes_auditoria';
    const ADMINISTRACION_REPORTES_CRUDO = 'administracion.reportes.reportes_crudo';
    const ADMINISTRACION_REPORTES_FINANCIERA = 'administracion.reportes.reportes_financiera';
    const ADMINISTRACION_REPORTES_FUNDACION_DE_LA_MUJER = 'administracion.reportes.reportes_fundacion_de_la_mujer';
    const ADMINISTRACION_REPORTES_CONTABILIDAD = 'administracion.reportes.reportes_contabilidad';
    const ADMINISTRACION_REPORTES_FICHA_TECNICA = 'administracion.reportes.reportes_ficha_tecnica';
    const ADMINISTRACION_REPORTES_GIROS_Y_CONVENIOS_EMPRESARIALES = 'administracion.reportes.reportes_giros_y_convenios_empresariales';

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

    // SIG
    const SIG_ACCEDER = 'sig.acceder';
    const SIG_MAPA_PROCESOS_ACCEDER = 'sig.mapa_procesos.acceder';
    const SIG_MAPA_PROCESOS_ELIMINAR = 'sig.mapa_procesos.eliminar';
    const SIG_MAPA_PROCESOS_CREAR = 'sig.mapa_procesos.crear';
    const SIG_MAPA_PROCESOS_CREAR_EMISION = 'sig.mapa_procesos.crear_emision';
    const SIG_MAPA_PROCESOS_VER_EMISION = 'sig.mapa_procesos.ver_emision';
    const SIG_MAPA_PROCESOS_EDITAR = 'sig.mapa_procesos.editar';
    const SIG_MAPA_PROCESOS_VER_TODOS = 'sig.mapa_procesos.ver_todos';
    const SIG_MAPA_PROCESOS_LISTADO_MAESTRO = 'sig.mapa_procesos.listado_maestro';

    // BIOMETRIA
    const BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL = 'biometria.gestion_huellero.acceso_personal';
    const BIOMETRIA_GESTION_HUELLERO_INGRESO_MANUAL = 'biometria.gestion_huellero.ingreso_manual';
    const BIOMETRIA_GESTION_HUELLERO_ENROLL = 'biometria.gestion_huellero.enroll';
    const BIOMETRIA_GESTION_HUELLERO_VERIFICAR = 'biometria.gestion_huellero.verificar';
    const BIOMETRIA_GESTION_HUELLERO_DESCANSO_CONDUCTORES = 'biometria.gestion_huellero.descanso_conductores';

    // BIOMETRIA - CAMARA
    const BIOMETRIA_GESTION_CAMARA_ENROLL = 'biometria.reconocimiento_facial.enroll';
    const BIOMETRIA_GESTION_CAMARA_RECONOCER = 'biometria.reconocimiento_facial.reconocer';
}
