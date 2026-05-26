# Modulo GestionRRHH

Estructura actual del modulo para mantener una organizacion MVC simple y escalable.

## Carpetas base

- `Http/Controllers`: controladores por contexto funcional (`Novedades`, `Novedades/Api`, etc.).
- `Http/Requests`: validaciones reutilizables en `FormRequest`.
- `Services`: logica de negocio y consultas de dominio.
- `Models`: modelos del modulo cuando aplica.
- `Resources/Views`: vistas web del modulo.
- `Resources/Templates`: plantillas de correo y documentos.
- `Routes`: rutas web y API del modulo.

## Flujo de Novedades

Los tipos de solicitud (permisos, incapacidades, vacaciones) se gestionan bajo el mismo dominio:

- `Http/Controllers/Novedades`: flujo web principal.
- `Http/Controllers/Novedades/Api`: endpoints API.
- `Services/Novedades`: capa de negocio central.
- `Services/Novedades/Permisos|Incapacidades|Vacaciones`: reglas especificas por tipo.
- `Resources/Views/novedades`: UI unificada para radicacion y bandejas.

## Convenciones vigentes

- Las reglas de validacion deben vivir en `Http/Requests`, no en controladores.
- Los controladores coordinan flujo HTTP y delegan reglas/consultas a servicios.
- Las consultas Oracle/MySQL deben concentrarse en `Services`.
- `EMP_NOVEDADES` actua como tabla central y cada tipo conserva su tabla de detalle.
- Los documentos de todos los tipos se centralizan en `EMP_NOVEDADES_DOCUMENTOS`.

## Servicios compartidos

Servicios transversales del modulo:

- `EmpleadoService`
- `JefeEquipoService`
- `BloqueoService`
