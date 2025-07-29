function initColumnaAjuste(selector, opciones = {}) {
    const tabla = $(selector);
    const columnasProtegidas = opciones.protegidas || [];
    let columnasOcultadas = [];
    let ajustando = false;

     // Obtiene el contenedor con scroll horizontal
    const getContainer = () => tabla.closest('.bootstrap-table').find('.fixed-table-body')[0];

     // Verifica si existe scroll horizontal (indicando que el ancho es insuficiente)
    const tieneScrollHorizontal = () => {
        const container = getContainer();
        return container && container.scrollWidth > container.clientWidth;
    };

     // Obtiene todas las columnas no protegidas para poder ocultarlas si es necesario
    const getTodasLasColumnasNoProtegidas = () => {
        const columnas = tabla.bootstrapTable('getOptions')?.columns?.[0] || [];
        return columnas.filter(col => col.field && !columnasProtegidas.includes(col.field));
    };

    // Determina si al menos una fila tiene detalle visible
    const hayDetalleVisible = () => {
        const filas = tabla.bootstrapTable('getData') || [];
        return filas.some(row => {
            const contenido = generarDetalle(selector, row);
            return contenido !== null && String(contenido).trim() !== '';
        });
    };

    /**
     * Ajusta dinámicamente las columnas según el espacio visible
     * También activa o desactiva el detalle si hay contenido para mostrar.
     */
    const ajustarColumnas = () => {
        if (ajustando) return;
        ajustando = true;

         // Esperar al próximo repintado para no forzar recálculos de layout
        requestAnimationFrame(() => {
            columnasOcultadas = [];
            tabla.bootstrapTable('showAllColumns');

            // Ordenar de derecha a izquierda para ocultar las menos prioritarias
            const columnasOrdenadasParaOcultar = getTodasLasColumnasNoProtegidas().map(c => c.field).reverse();
            let i = 0;

            // Oculta columnas hasta que desaparezca el scroll horizontal
            while (i < columnasOrdenadasParaOcultar.length && tieneScrollHorizontal()) {
                const colField = columnasOrdenadasParaOcultar[i];
                tabla.bootstrapTable('hideColumn', colField);
                columnasOcultadas.push(colField);
                i++;
            }

             // Obtener si debe o no mostrarse el detalle por fila
            const opcionesActuales = tabla.bootstrapTable('getOptions');
            const debeMostrarDetalle = hayDetalleVisible();

            // Solo recrear la tabla si ha cambiado la configuración de detalle
            if (opcionesActuales.detailView !== debeMostrarDetalle) {
                const data = tabla.bootstrapTable('getData');
                const prevOnPostBody = opcionesActuales['onPostBody'];

                tabla.bootstrapTable('destroy');
                tabla.bootstrapTable({
                    ...opcionesActuales,
                    data,
                    detailView: debeMostrarDetalle,
                });

                if (prevOnPostBody && typeof prevOnPostBody === 'function') {
                    tabla.on('post-body.bs.table', prevOnPostBody);
                }

                tabla.on('post-body.bs.table', ajustarColumnas);
            }

            ajustando = false;
        });
    };

    // Ejecutar ajuste después de que se renderice el cuerpo de la tabla
    tabla.on('post-body.bs.table', ajustarColumnas);

    // Aplicar ajuste al redimensionar la ventana (con throttling)
    let resizeTimeout;
    $(window).on('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(ajustarColumnas, 100);
    });

    tabla.data('columnasOcultas', () => columnasOcultadas);
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
