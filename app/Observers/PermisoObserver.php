<?php

namespace App\Observers;

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;

class PermisoObserver
{
  public function created(Permission $permission): void
  {
    Cache::forget("permisos_guard_{$permission->guard_name}");
  }

  public function updated(Permission $permission): void
  {
    Cache::forget("permisos_guard_{$permission->guard_name}");
  }

  public function deleted(Permission $permission): void
  {
    Cache::forget("permisos_guard_{$permission->guard_name}");
  }
}
