/**
 * Inicializa el ajuste dinámico de columnas en una tabla Bootstrap Table,
 * ocultando columnas no protegidas si hay desbordamiento horizontal,
 * y activando/desactivando la vista de detalle si no hay contenido que mostrar.
*/
function initColumnaAjuste(selector, opciones = {}) {
    const tabla = $(selector);
    const columnasProtegidas = opciones.protegidas || [];
    let columnasOcultadas = [];
    let ajustando = false;

    // Obtiene el contenedor con scroll horizontal
    const getContainer = () =>
        tabla.closest('.bootstrap-table').find('.fixed-table-body')[0];

    // Verifica si existe scroll horizontal (indicando que el ancho es insuficiente)
    const tieneScrollHorizontal = () => {
        const container = getContainer();
        return container && container.scrollWidth > container.clientWidth;
    };

    // Obtiene todas las columnas no protegidas para poder ocultarlas si es necesario
    const getTodasLasColumnas = () => {
        const opts = tabla.bootstrapTable('getOptions');
        const columnas = opts.columns?.[0] || [];
        return columnas.filter(col => col.field && !columnasProtegidas.includes(col.field));
    };

    // Determina si al menos una fila tiene detalle visible
    const hayDetalleVisible = () => {
        const filas = tabla.bootstrapTable('getData') || [];
        return filas.some(row => {
            const contenido = generarDetalle(selector, row);
            return contenido !== null && contenido.toString().trim() !== '';
        });
    };

    /**
     * Ajusta dinámicamente las columnas según el espacio visible.
     * También activa o desactiva el detalle si hay contenido para mostrar.
     */
    const ajustarColumnas = () => {
        if (ajustando) return;
        ajustando = true;

        // Esperar al próximo repintado para no forzar recálculos de layout
        requestAnimationFrame(() => {
            columnasOcultadas = [];
            tabla.bootstrapTable('showAllColumns'); // Restaurar todas las columnas primero

            // Ordenar de derecha a izquierda para ocultar las menos prioritarias
            const columnasOrdenadas = getTodasLasColumnas().map(c => c.field).reverse();
            let i = 0;

            // Oculta columnas hasta que desaparezca el scroll horizontal
            while (i < columnasOrdenadas.length && tieneScrollHorizontal()) {
                const col = columnasOrdenadas[i];
                tabla.bootstrapTable('hideColumn', col);
                columnasOcultadas.push(col);
                i++;
            }

            // Obtener si debe o no mostrarse el detalle por fila
            const opcionesActuales = tabla.bootstrapTable('getOptions');
            const mostrarDetalle = hayDetalleVisible();

            // Solo recrear la tabla si ha cambiado la configuración de detalle
            if (opcionesActuales.detailView !== mostrarDetalle) {
                const data = tabla.bootstrapTable('getData');

                tabla.bootstrapTable('destroy'); // Destruir tabla actual
                tabla.bootstrapTable({
                    ...opcionesActuales,
                    data,
                    detailView: mostrarDetalle
                });

                // Volver a registrar el listener de ajuste tras la reinicialización
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

    // Exponer las columnas ocultas para diagnóstico o control externo
    tabla.data('columnasOcultas', () => columnasOcultadas);
}



export function generarDetalle(selector, row, opcionesFormatter = {}) {
    const tabla = $(selector);
    const obtenerOcultas = tabla.data('columnasOcultas') || (() => []);
    const ocultas = obtenerOcultas();
    const columnas = tabla.bootstrapTable('getOptions').columns?.[0] || [];

    //orden del detalle segun el data-order definido
    const columnasOrdenadas = columnas
        .filter(col => col.field && ocultas.includes(col.field))
        .sort((a, b) => (a.orden ?? 999) - (b.orden ?? 999));

    let html = '';
    let hayContenido = false;

    for (const col of columnasOrdenadas) {
        const key = col.field;
        const label = col.title || key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());

        let contenido = '';
        //si la columna es una columna con formato
        if (opcionesFormatter[key]) {
            contenido = opcionesFormatter[key](row[key], row);
        } else if (row[key] !== null && row[key] !== undefined) {
            contenido = row[key];
        }

        if (contenido) {
            hayContenido = true;
            html += `
                <li class="list-group-item">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <strong style="white-space: nowrap;">${label}:</strong>
                        <div>${contenido}</div>
                    </div>
                </li>
            `;
        }
        
    }

    if (!hayContenido) return null; 

    return `<ul class="list-group list-group-flush">${html}</ul>`;
}

export function initTablaBootstrapTable(selector, opciones = {},nombreFormatter, formattersDetalle = {}) {
    initColumnaAjuste(selector, opciones);
    window[nombreFormatter] = (index, row) => generarDetalle(selector, row,formattersDetalle);
}


