@extends('layouts.dashboard')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Resultados']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">

  <x-sectionHeader titulo="{{ $nombreReporte }}" rutaVolver="{{ $ruta }}" excelReporte="true" excelRoute=""
    excelName="{{ $nombreDocExcel }}" />

  <!-- Contenedor para mensajes de error -->
  <div id="error-container" class="alert alert-danger" style="display: none;"></div>

  <!-- Contenedor para loading -->
  <div id="loading" style="
        display: none;
        position: fixed;
        z-index: 1055;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(2px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;">

    <div class="spinner-border text-light" role="status" style="width: 4rem; height: 4rem;"></div>
    <span class="mt-3">Cargando datos del reporte...</span>
  </div>

  <div style="padding:1.5em">
    <!-- Tabla Bootstrap Table -->
    <table id="table" class="table table-sm table-striped" style="display: none;"></table>
  </div>
</div>
@endsection

@pushOnce('script')
<script>
  // Variable global para almacenar los datos del reporte
  let reportData = [];

  $(document).ready(function () {
    const params = @json($params);
    const queryString = new URLSearchParams(params).toString();
    const url = "{{ route('reportes.data') }}" + "?" + queryString;

    $('#loading').show();

    $.ajax({
      url: url,
      method: 'GET',
      success: function (response) {
        $('#loading').hide();

        if (response.rows && response.rows.length > 0) {
          reportData = response.rows;

          const columns = [{
            field: 'numero',
            title: 'NUMERO',
            formatter: function (value, row, index) {
              return index + 1;
            },
            align: 'left',
            width: 80
          },
          ...Object.keys(response.rows[0]).map(key => ({
            field: key,
            title: key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' '),
            sortable: true,
            formatter: function (value) {
              if (value == null) return '';
              return `<span title="${value}">${value}</span>`;
            },
            cellStyle: {
              css: {
                'white-space': 'nowrap',
                'text-overflow': 'ellipsis',
                'overflow': 'hidden',
                'max-width': '250px',
                'cursor': 'pointer'
              }
            }
          }))
          ];

          //Inicializar Bootstrap Table
          $('#table').bootstrapTable({
            columns: columns,
            data: response.rows,
            pagination: true,
            search: true,
            showRefresh: false,
            showColumns: false,
            showToggle: false,
            showFullscreen: false,
            locale: 'es-ES',
            pageSize: 25,
            pageList: [10, 25, 50, 100],
            searchAlign: 'right',
            buttonsAlign: 'right',
            detailView: true,
            detailFormatter: detailFormatter,
            theadClasses: 'table-primary',
            rowStyle: function (row, index) {
              return {
                classes: 'bg-primary text-white'
              }
            }
          });

          $('#table').show();

          // Habilitar la exportación con datos locales
          setupLocalExcelExport();

        } else {
          Swal.fire({
            icon: 'info',
            title: 'No se encontraron registros para los criterios seleccionados.',
            text: 'Intenta ajustar los filtros o parámetros de búsqueda para obtener resultados.',
            confirmButtonColor: "#3366CC",
            confirmButtonText: "Aceptar",
            customClass: {
              popup: 'swalAlert'
            }
          }).then((result) => {
            if (result.isConfirmed) {
              const form = document.getElementById('formReporte');
              if (form) {
                habilitarSubmit(form);
              }
              window.history.back();
            }
          });
        }
      },
      error: function (xhr) {
        $('#loading').hide();

        Swal.fire({
          icon: 'error',
          title: 'Error al cargar el reporte.',
          confirmButtonColor: "#3366CC",
          confirmButtonText: "Aceptar",
          customClass: {
            popup: 'swalAlert'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            const form = document.getElementById('formReporte');
            if (form) {
              habilitarSubmit(form);
            }
            window.history.back();
          }
        });
      }
    });
  });

  function setupLocalExcelExport() {
    const exportBtn = document.getElementById('exportar');
    if (exportBtn && reportData.length > 0) {
      // Reutiliza los datos en memoria para evitar duplicarlos en atributos del DOM
      exportBtn.removeAttribute('data-url');
      exportBtn.onclick = () => exportarExcel(exportBtn.id, reportData, exportBtn.dataset.name, false);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTablaBootstrapTable(
      '#table', {
      protegidas: ['']
    },
      'detailFormatter');
  });
</script>
@endpushOnce
