<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class PerContratoPersona extends Model
{
  protected $connection = "oracle";
  protected $table = "PER_CONTRATO_PERSONA";
  protected $primaryKey = "id";
  public $incrementing = false;
  protected $keyType = 'string';

  public $timestamps = false;

  public function nombreCompleto()
  {
    $nombre = $this->pnombre;
    if ($this->snombre) {
      $nombre .= ' ' . $this->snombre;
    }
    $apellido = $this->papellido;
    if ($this->sapellido) {
      $apellido .= ' ' . $this->sapellido;
    }
    return mb_strtoupper($nombre . ' ' . $apellido, 'UTF-8');
  }

  public function perEmpresaPersonas()
  {
    return $this->hasOne(PerEmpresaPersonas::class, 'pe_id_pe', 'pe_id_pe')
      ->where('activo', 1)
      ->where('estborrado', 0)
      ->whereIn('tp_id', [1, 11])
      ->whereNull('fecfin');
  }

  public function cargoDetallado(): ?PerCargos
  {
    $ca = (new PerCargos)->getTable();

    return PerCargos::from("$ca as ca")
      ->select('ca.*')
      ->join('per_cargoccostos as cc', 'cc.ca_codigo', '=', 'ca.codigo')
      ->join('per_empresapersonas as ep', 'ep.cc_id', '=', 'cc.id')
      ->join('per_centrocostos as ct', 'ct.codigo', '=', 'cc.ct_codigo')
      ->where('ep.pe_id_pe', $this->pe_id_pe)
      ->whereIn('ep.tp_id', [1, 11])
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->whereNull('ep.fecfin')
      ->where('cc.activo', 1)
      ->where('cc.estborrado', 0)
      ->where('ct.estado', 1)
      ->where('ct.estborrado', 0)
      ->where('ca.estborrado', 0)
      ->first();
  }
}
