const escapeHtml = value => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

const stripHtml = (value) => {
    if (value === null || value === undefined) return '';
    if (typeof value !== 'string') return String(value);
    const contenedor = document.createElement('div');
    contenedor.innerHTML = value;
    return (contenedor.textContent || '').trim();
};

function obtenerCampoUnico(opciones = {}) {
    return opciones.uniqueId || opciones.idField || null;
}

function obtenerClaveFila(row, opciones = {}, index = null) {
    if (!row) return `index:${index ?? 'x'}`;
    const campoUnico = obtenerCampoUnico(opciones);
    if (campoUnico && row[campoUnico] !== undefined && row[campoUnico] !== null) {
        return `${campoUnico}:${row[campoUnico]}`;
    }
    if (row.id !== undefined && row.id !== null) return `id:${row.id}`;
    if (row.codigo !== undefined && row.codigo !== null) return `codigo:${row.codigo}`;
    if (row.uuid !== undefined && row.uuid !== null) return `uuid:${row.uuid}`;
    return `index:${index ?? 'x'}`;
}

function obtenerSetExpandido(tabla) {
    const actual = tabla.data('expandedKeys');
    if (actual instanceof Set) return actual;
    const nuevo = new Set(Array.isArray(actual) ? actual : []);
    tabla.data('expandedKeys', nuevo);
    return nuevo;
}

function restaurarDetalleExpandido(tabla) {
    const opciones = tabla.bootstrapTable('getOptions') || {};
    if (!opciones.detailView) return;

    const setExpandido = obtenerSetExpandido(tabla);
    if (!setExpandido.size) return;

    const data = tabla.bootstrapTable('getData') || [];
    if (!data.length) return;

    const mapa = new Map();
    data.forEach((row, index) => {
        mapa.set(obtenerClaveFila(row, opciones, index), index);
    });

    setExpandido.forEach((key) => {
        const index = mapa.get(key);
        if (index !== undefined) {
            tabla.bootstrapTable('expandRow', index);
        }
    });
}

function registrarDetallePersistente(tabla) {
    const previousNamespace = tabla.data('detalleNamespace');
    if (previousNamespace) {
        tabla.off(`expand-row.bs.table.${previousNamespace}`);
        tabla.off(`collapse-row.bs.table.${previousNamespace}`);
    }

    const namespace = `detallePersist-${Math.random().toString(36).slice(2)}`;
    tabla.data('detalleNamespace', namespace);

    tabla.on(`expand-row.bs.table.${namespace}`, (event, index, row) => {
        if (tabla.data('bloquearSyncDetalle')) return;
        const opciones = tabla.bootstrapTable('getOptions') || {};
        const setExpandido = obtenerSetExpandido(tabla);
        setExpandido.add(obtenerClaveFila(row, opciones, index));
    });

    tabla.on(`collapse-row.bs.table.${namespace}`, (event, index, row) => {
        if (tabla.data('bloquearSyncDetalle')) return;
        const opciones = tabla.bootstrapTable('getOptions') || {};
        const setExpandido = obtenerSetExpandido(tabla);
        setExpandido.delete(obtenerClaveFila(row, opciones, index));
    });
}

function normalizarFormatters(selector, nombreFormatterGlobal) {
    const tabla = $(selector);
    const instancia = tabla.data('bootstrap.table');
    if (!instancia) return false;

    const opciones = tabla.bootstrapTable('getOptions') || {};
    const columnas = opciones.columns || [];
    const setExpandido = new Set(obtenerSetExpandido(tabla));
    tabla.data('bloquearSyncDetalle', true);
    let cambio = false;

    if (columnas.length) {
        columnas.forEach(grupo => {
            (grupo || []).forEach(col => {
                if (!col) return;
                if (typeof col.formatter === 'string' && typeof window[col.formatter] === 'function') {
                    col.formatter = window[col.formatter];
                    cambio = true;
                }
                if (typeof col.detailFormatter === 'string' && typeof window[col.detailFormatter] === 'function') {
                    col.detailFormatter = window[col.detailFormatter];
                    cambio = true;
                }
                if (typeof col.footerFormatter === 'string' && typeof window[col.footerFormatter] === 'function') {
                    col.footerFormatter = window[col.footerFormatter];
                    cambio = true;
                }
            });
        });
    }

    let detailFormatter = opciones.detailFormatter;
    if (typeof detailFormatter === 'string' && typeof window[detailFormatter] === 'function') {
        detailFormatter = window[detailFormatter];
        cambio = true;
    }

    if (!detailFormatter && typeof window[nombreFormatterGlobal] === 'function') {
        detailFormatter = window[nombreFormatterGlobal];
        cambio = true;
    }

    if (cambio) {
        tabla.bootstrapTable('refreshOptions', {
            columns: columnas,
            detailFormatter
        });

        const data = tabla.bootstrapTable('getData') || [];
        if (data.length) {
            tabla.bootstrapTable('load', data);
        } else {
            tabla.bootstrapTable('resetView');
        }
    }

    tabla.data('expandedKeys', new Set(setExpandido));
    setTimeout(() => {
        tabla.data('bloquearSyncDetalle', false);
        restaurarDetalleExpandido(tabla);
    }, 0);

    return true;
}

function initColumnaAjuste(selector, opciones = {}) {
    const tabla = $(selector);
    const columnasProtegidas = opciones.protegidas || [];
    const columnasOcultasFijas = opciones.ocultas || [];
    const expandirDetalle = opciones.expandirDetalle || false;
    const forzarDetalle = opciones.forzarDetalle || false;
    let columnasOcultadas = [];
    let ajustando = false;
    let resizeTimeout;

    // Guarda columnas ocultas para que generarDetalle pueda usarlas
    tabla.data('columnasOcultas', () => columnasOcultadas);

    // Contenedor de scroll horizontal
    const getContainer = () => tabla.closest('.bootstrap-table').find('.fixed-table-body')[0];
    const tieneScrollHorizontal = () => {
        const container = getContainer();
        return container && container.scrollWidth > container.clientWidth;
    };

    // Columnas que se pueden ocultar
    const getTodasLasColumnasNoProtegidas = () => {
        const columnas = tabla.bootstrapTable('getOptions')?.columns?.[0] || [];
        return columnas.filter(col => col.field
            && !columnasProtegidas.includes(col.field)
            && !columnasOcultasFijas.includes(col.field));
    };

    const hayDetalleVisible = () => {
        if (!columnasOcultadas.length) return false;

        const definicionesColumnas = tabla.bootstrapTable('getOptions')?.columns?.[0] || [];
        const columnasParaDetalle = definicionesColumnas
            .filter(col => col.field && columnasOcultadas.includes(col.field))
            .sort((a, b) => (a.orden ?? 999) - (b.orden ?? 999));

        if (!columnasParaDetalle.length) return false;

        const filas = tabla.bootstrapTable('getData') || [];

        return filas.some(row => {
            return columnasParaDetalle.some(col => {
                const valor = row[col.field];
                return valor !== null && valor !== undefined && String(valor).trim() !== '';
            });
        });
    };

    const ajustarColumnas = () => {
        if (ajustando) return;
        ajustando = true;

        requestAnimationFrame(() => {
            columnasOcultadas = [];
            tabla.bootstrapTable('showAllColumns');

            // Ocultar columnas fijas (siempre en detalle)
            columnasOcultasFijas.forEach(colField => {
                tabla.bootstrapTable('hideColumn', colField);
            });
            columnasOcultadas = columnasOcultasFijas.slice();

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
            let debeMostrarDetalle = hayDetalleVisible();
            if (forzarDetalle && columnasOcultadas.length > 0) {
                debeMostrarDetalle = true;
            }

            if (opcionesActuales.detailView !== debeMostrarDetalle) {
                const instancia = tabla.data('bootstrap.table');
                if (instancia) {
                    instancia.options.detailView = debeMostrarDetalle;
                    instancia.initHeader();
                    instancia.initBody();
                } else {
                    tabla.bootstrapTable('refreshOptions', {
                        detailView: debeMostrarDetalle
                    });
                }
            }

            if (expandirDetalle && debeMostrarDetalle) {
                tabla.bootstrapTable('expandAllRows');
            }

            restaurarDetalleExpandido(tabla);
            ajustando = false;
        });
    };

    // Ajustar despues de cargar/actualizar datos (evita duplicar handlers)
    const previousPostNamespace = tabla.data('postBodyNamespace');
    if (previousPostNamespace) {
        tabla.off(`post-body.bs.table.${previousPostNamespace}`);
    }
    const postBodyNamespace = `ajusteColPost-${Math.random().toString(36).slice(2)}`;
    tabla.data('postBodyNamespace', postBodyNamespace);
    tabla.on(`post-body.bs.table.${postBodyNamespace}`, ajustarColumnas);

    // Ajustar en resize (con throttling y namespace para evitar duplicados)
    const previousNamespace = tabla.data('resizeNamespace');
    if (previousNamespace) {
        $(window).off(`resize.${previousNamespace}`);
    }
    const resizeNamespace = `ajusteCol-${Math.random().toString(36).slice(2)}`;
    tabla.data('resizeNamespace', resizeNamespace);

    $(window).on(`resize.${resizeNamespace}`, () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(ajustarColumnas, 100);
    });

    registrarDetallePersistente(tabla);
}

export function generarDetalle(selector, row, opcionesFormatter = {}) {
    const tabla = $(selector);
    const obtenerColumnasOcultas = tabla.data('columnasOcultas') || (() => []);
    const columnasOcultas = obtenerColumnasOcultas();
    const definicionesColumnas = tabla.bootstrapTable('getOptions')?.columns?.[0] || [];

    // orden del detalle segun el data-order definido
    const columnasOrdenadasParaDetalle = definicionesColumnas
        .filter(col => col.field && columnasOcultas.includes(col.field))
        .sort((a, b) => (a.orden ?? 999) - (b.orden ?? 999));

    let htmlContenidoDetalle = '';
    let hayContenidoEnDetalle = false;

    for (const col of columnasOrdenadasParaDetalle) {
        const key = col.field;
        const label = col.title || key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());

        let contenidoCampo = '';
        // si la columna es una columna con formato
        if (opcionesFormatter[key]) {
            contenidoCampo = opcionesFormatter[key](row[key], row);
        } else if (row[key] !== null && row[key] !== undefined) {
            if (key === 'acciones') {
                contenidoCampo = row[key];
            } else if (key === 'formato' && String(row[key]).includes('<')) {
                contenidoCampo = escapeHtml(stripHtml(row[key]));
            } else {
                contenidoCampo = escapeHtml(row[key]);
            }
        }

        if (String(contenidoCampo).trim() !== '') {
            hayContenidoEnDetalle = true;
            if (key === 'acciones') {
                htmlContenidoDetalle += `
                    <li class="list-group-item sidebar-dark-primary">
                        <div class="d-flex justify-content-center">${contenidoCampo}</div>
                    </li>
                `;
            } else {
                htmlContenidoDetalle += `
                    <li class="list-group-item sidebar-dark-primary">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong style="white-space: nowrap;">${escapeHtml(label)}:</strong>
                            <div>${contenidoCampo}</div>
                        </div>
                    </li>
                `;
            }
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

    let intentos = 0;
    const reintentar = () => {
        if (normalizarFormatters(selector, nombreFormatterGlobal)) return;
        if (intentos >= 6) return;
        intentos += 1;
        setTimeout(reintentar, 80);
    };
    reintentar();
}
