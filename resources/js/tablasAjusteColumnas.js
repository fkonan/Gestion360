function initColumnaAjuste(selector, opciones = {}) {
    const tabla = $(selector);
    const columnasProtegidas = opciones.protegidas || [];
    let columnasOcultadas = [];
    let ajustando = false;

    function tieneScrollHorizontal() {
        const container = tabla.closest('.bootstrap-table').find('.fixed-table-body')[0];
        if (!container) return false;
        return container.scrollWidth > container.clientWidth;
    }

    function getTodasLasColumnas() {
        const opts = tabla.bootstrapTable('getOptions');
        const cols = opts.columns?.[0] || [];
        return cols.filter(col => col.field && !columnasProtegidas.includes(col.field));
    }

    function ajustarColumnas() {
        if (ajustando) return;
        ajustando = true;

        requestAnimationFrame(() => {
            columnasOcultadas = [];
            tabla.bootstrapTable('showAllColumns');
            const columnasOrdenadas = getTodasLasColumnas().map(c => c.field).reverse();

            let i = 0;
            while (i < columnasOrdenadas.length && tieneScrollHorizontal()) {
                const col = columnasOrdenadas[i];
                tabla.bootstrapTable('hideColumn', col);
                columnasOcultadas.push(col);
                i++;
            }

            ajustando = false;
        });
    }

    tabla.on('post-body.bs.table', ajustarColumnas);

    let resizeTimeout;
    $(window).on('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(ajustarColumnas, 100);
    });

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


