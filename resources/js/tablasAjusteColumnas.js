function initColumnaAjuste(selector, opciones = {}) {
    const tabla = $(selector);
    const columnasProtegidas = opciones.protegidas || [];
    let columnasOcultadas = [];
    let ajustando = false;

    // Guarda columnas ocultas para que generarDetalle pueda usarlas
    tabla.data('columnasOcultas', () => columnasOcultadas);

    // Contenedor de scroll horizontal
    const getContainer = () => tabla.closest('.bootstrap-table').find('.fixed-table-body')[0];
    const tieneScrollHorizontal = () => {
        const container = getContainer();
        return container && container.scrollWidth > container.clientWidth;
    };

    // Columnas que sí se pueden ocultar
    const getTodasLasColumnasNoProtegidas = () => {
        const columnas = tabla.bootstrapTable('getOptions')?.columns?.[0] || [];
        return columnas.filter(col => col.field && !columnasProtegidas.includes(col.field));
    };

    const hayDetalleVisible = () => {
        const filas = tabla.bootstrapTable('getData') || [];
        return filas.some(row => {
            const contenido = generarDetalle(selector, row);
            return contenido !== null && String(contenido).trim() !== '';
        });
    };

    const ajustarColumnas = () => {
        if (ajustando) return;
        ajustando = true;

        requestAnimationFrame(() => {
            columnasOcultadas = [];
            tabla.bootstrapTable('showAllColumns');

            // Ocultar de derecha a izquierda
            const columnasOrdenadasParaOcultar = getTodasLasColumnasNoProtegidas().map(c => c.field).reverse();
            let i = 0;
            while (i < columnasOrdenadasParaOcultar.length && tieneScrollHorizontal()) {
                const colField = columnasOrdenadasParaOcultar[i];
                tabla.bootstrapTable('hideColumn', colField);
                columnasOcultadas.push(colField);
                i++;
            }

            // Cambiar detailView sin destruir tabla
            const opcionesActuales = tabla.bootstrapTable('getOptions');
            const debeMostrarDetalle = hayDetalleVisible();

            if (opcionesActuales.detailView !== debeMostrarDetalle) {
                tabla.bootstrapTable('refreshOptions', {
                    detailView: debeMostrarDetalle
                });
            }

            ajustando = false;
        });
    };

    // Ajustar después de cargar/actualizar datos
    tabla.on('post-body.bs.table', ajustarColumnas);

    // Ajustar en resize (con throttling)
    let resizeTimeout;
    $(window).on('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(ajustarColumnas, 100);
    });
}


export function generarDetalle(selector, row, opcionesFormatter = {}) {
    const tabla = $(selector);
    const obtenerColumnasOcultas = tabla.data('columnasOcultas') || (() => []);
    const columnasOcultas = obtenerColumnasOcultas();
    const definicionesColumnas = tabla.bootstrapTable('getOptions')?.columns?.[0] || [];

     //orden del detalle segun el data-order definido
    const columnasOrdenadasParaDetalle = definicionesColumnas
        .filter(col => col.field && columnasOcultas.includes(col.field))
        .sort((a, b) => (a.orden ?? 999) - (b.orden ?? 999));

    let htmlContenidoDetalle = '';
    let hayContenidoEnDetalle = false;

    for (const col of columnasOrdenadasParaDetalle) {
        const key = col.field;
        const label = col.title || key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());

        let contenidoCampo = '';
        //si la columna es una columna con formato
        if (opcionesFormatter[key]) {
            contenidoCampo = opcionesFormatter[key](row[key], row);
        } else if (row[key] !== null && row[key] !== undefined) {
            contenidoCampo = row[key];
        }

        if (String(contenidoCampo).trim() !== '') {
            hayContenidoEnDetalle = true;
            htmlContenidoDetalle += `
                <li class="list-group-item sidebar-dark-primary">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <strong style="white-space: nowrap;">${label}:</strong>
                        <div>${contenidoCampo}</div>
                    </div>
                </li>
            `;
        }
    }

    if (!hayContenidoEnDetalle) return null;

    return `<ul class="list-group list-group-flush">${htmlContenidoDetalle}</ul>`;
}

/* Como parametro recibe:
1. Id de la tabla
2. Columnas que seran visibles siempre
3. Nombre del campo formatter donde se mostrata el detalle
4. Columnas que vienen calculadas o modificadas con algun formatter */
export function initTablaBootstrapTable(selector, opciones = {}, nombreFormatterGlobal, formattersDetalle = {}) {
    initColumnaAjuste(selector, opciones);
    window[nombreFormatterGlobal] = (index, row) => generarDetalle(selector, row, formattersDetalle);
}
