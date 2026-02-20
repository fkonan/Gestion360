# Checklist de migracion SARLAFT

1. Verificar rutas SARLAFT:
   - `php artisan route:list --name=sarlaft`
   - `php artisan route:list --path=api/v1/consulta`
2. Verificar comando:
   - `php artisan list --raw | Select-String listas:sincronizar`
3. Ejecutar migraciones modulo:
   - `php artisan migrate --database=mysql-sarlaft --path=app/Modules/Sarlaft/Database/Migrations --realpath --no-interaction`
4. Confirmar estado:
   - `php artisan migrate:status --database=mysql-sarlaft`
5. Probar flujo minimo:
   - Login web -> `/sarlaft`
   - API con token valido/invalido
   - Crear bloqueo manual y revisar persistencia
6. Verificar scheduler:
   - existe `Schedule::command('listas:sincronizar')->daily()->at('02:00');`
