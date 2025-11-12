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
            cellStyle: {
              css: {
                'white-space': 'nowrap',
                'text-overflow': 'ellipsis',
                'overflow': 'hidden',
                'max-width': '250px'
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

          // Activar filtros por columna
          enableColumnFilters('#table', response.rows);

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
      exportBtn.setAttribute('data-url', JSON.stringify(reportData));
      exportBtn.setAttribute('onclick', `exportarExcel(this.id, this.dataset.url, this.dataset.name, false)`);
    }
  }

  function enableColumnFilters(tableSelector, tableData) {
    const $table = $(tableSelector);
    if (!$table.length || !Array.isArray(tableData)) {
      return;
    }

    const originalData = Array.isArray(tableData) ? tableData.map((row) => ({ ...row })) : [];

    $table.data('column-filter-data', originalData);
    $table.data('column-filter-values', {});

    const buildFilterRow = () => {
      const $thead = $table.find('thead');
      if (!$thead.length) {
        return;
      }

      const $headerRow = $thead.find('tr').last();

      if (!$headerRow.length || $headerRow.hasClass('table-filter-row')) {
        return;
      }

      $thead.find('tr.table-filter-row').remove();

      const $filterRow = $('<tr class="table-filter-row"></tr>');
      const baseData = $table.data('column-filter-data') || [];
      const storedValues = $table.data('column-filter-values') || {};

      $headerRow.find('th').each(function () {
        const $headerTh = $(this);
        const $filterTh = $('<th></th>');
        const field = $headerTh.data('field');

        if (field) {
          const hasFieldData = baseData.some((row) => Object.prototype.hasOwnProperty.call(row, field));

          if (hasFieldData) {
            const $input = $('<input type="text" class="form-control form-control-sm column-filter" placeholder="Filtrar..." />');
            $input.attr('data-field', field);
            if (Object.prototype.hasOwnProperty.call(storedValues, field)) {
              $input.val(storedValues[field]);
            }
            $input.on('input change', () => applyColumnFilters($table));
            $filterTh.append($input);
          }
        }

        $filterRow.append($filterTh);
      });

      $headerRow.after($filterRow);
    };

    $table.off('post-header.bs.table.columnFilters').on('post-header.bs.table.columnFilters', buildFilterRow);
    buildFilterRow();
  }

  function applyColumnFilters($table, shouldResetSearch = true) {
    const baseData = $table.data('column-filter-data') || [];
    const filters = {};
    const rawValues = {};

    $table.find('input.column-filter').each(function () {
      const value = $(this).val();
      const field = $(this).data('field');
      if (!field) {
        return;
      }

      const stringValue = value ? value.toString() : '';
      rawValues[field] = stringValue;

      if (stringValue) {
        const normalized = stringValue.trim().toLowerCase();
        if (normalized) {
          filters[field] = normalized;
        }
      }
    });

    $table.data('column-filter-values', rawValues);

    const hasFilters = Object.keys(filters).length > 0;

    const filteredData = hasFilters
      ? baseData.filter((row) => {
        return Object.entries(filters).every(([field, value]) => {
          const cell = row[field];
          if (cell === null || cell === undefined) {
            return false;
          }
          return cell.toString().toLowerCase().trim().includes(value);
        });
      })
      : baseData;

    const options = $table.bootstrapTable('getOptions');
    const searchText = options.searchText || '';
    const sortName = options.sortName;
    const sortOrder = options.sortOrder;

    if (!hasFilters && shouldResetSearch === false) {
      return;
    }

    $table.bootstrapTable('load', filteredData);

    if (shouldResetSearch && searchText) {
      $table.bootstrapTable('resetSearch', searchText);
    }

    if (sortName) {
      $table.bootstrapTable('refreshOptions', { sortName, sortOrder });
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
