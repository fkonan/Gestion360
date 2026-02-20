# Base de datos SARLAFT

## Conexion
- Nombre: `mysql-sarlaft`
- Defaults actuales:
  - host: `172.16.48.99`
  - database: `gestion_admin`

## Tablas (prefijo obligatorio)
- `sarlaft_listas_vinculantes`
- `sarlaft_registros_lista`
- `sarlaft_consultas`
- `sarlaft_alertas`
- `sarlaft_bloqueos`
- `sarlaft_lista_negra_interna`
- `sarlaft_sistemas_consumidores`
- `sarlaft_sincronizacion_logs`

## Notas
- FULLTEXT: `sarlaft_registros_lista(nombres, alias)`.
- FK a usuario: no fisica contra `users`; se guarda `atendida_por/creado_por` como bigint.
- Relacion aplicacion con usuario: `App\\Models\\User` usando owner key `IdUsuario`.
