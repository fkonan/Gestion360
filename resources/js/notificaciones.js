// Notificaciones y utilidades globales del layout.
document.addEventListener('hidden.bs.modal', function () {
  if (document.activeElement) {
    document.activeElement.blur();
  }
});

window.addEventListener('DOMContentLoaded', function () {
  const title = sessionStorage.getItem('toastTitle');
  const type = sessionStorage.getItem('toastType');

  if (title && type) {
    if (window.mostrarToast) {
      window.mostrarToast(title, type);
    }
    sessionStorage.removeItem('toastTitle');
    sessionStorage.removeItem('toastType');
  }
});

window.addEventListener('load', function () {
  document.body.classList.add('show');
});

document.addEventListener('DOMContentLoaded', () => {
  const config = window.notiConfig || {};
  const urlIndex = config.urlIndex;
  const urlLeerBase = config.urlLeerBase;

  if (!urlIndex || !urlLeerBase) {
    return;
  }

  const btn = document.getElementById('btnNotificaciones');
  const dropdown = document.getElementById('dropdownNotificaciones');
  const lista = document.getElementById('listaNotificaciones');
  const sinNot = document.getElementById('sinNotificaciones');
  const badge = document.getElementById('badgeNotificaciones');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  if (!btn || !dropdown || !lista || !sinNot || !badge) {
    return;
  }

  let ultimoConteo = 0;
  let pollingId = null;
  const intervaloBase = 30000;
  const intervaloMax = 120000;
  let intervaloActual = intervaloBase;

  const formatearFecha = (valor) => {
    const fecha = valor ? new Date(valor) : null;
    if (!fecha || Number.isNaN(fecha.getTime())) {
      return '';
    }
    const yyyy = fecha.getFullYear();
    const mm = String(fecha.getMonth() + 1).padStart(2, '0');
    const dd = String(fecha.getDate()).padStart(2, '0');
    const hh = String(fecha.getHours()).padStart(2, '0');
    const min = String(fecha.getMinutes()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd} ${hh}:${min}`;
  };

  const obtenerUrlNotificacion = (item) => {
    if (!item) return null;
    return item.data?.url_destino || null;
  };

  const mismaRuta = (destino) => {
    if (!destino) return false;
    try {
      const destinoUrl = new URL(destino, window.location.origin);
      return destinoUrl.pathname === window.location.pathname;
    } catch (e) {
      return false;
    }
  };

  const marcarComoLeida = (id, nodo, redirectUrl = null) => {
    if (!csrf) {
      if (redirectUrl && !mismaRuta(redirectUrl)) {
        window.location.href = redirectUrl;
      }
      return;
    }
    fetch(`${urlLeerBase}/${id}/leer`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    }).finally(() => {
      nodo.remove();
      const restantes = lista.children.length;
      if (restantes === 0) {
        badge.style.display = 'none';
        sinNot.style.display = 'block';
      } else {
        badge.textContent = restantes;
      }
      if (redirectUrl && !mismaRuta(redirectUrl)) {
        window.location.href = redirectUrl;
      }
    });
  };

  const renderNotificaciones = (items) => {
    lista.innerHTML = '';
    const total = items.length;
    if (total === 0) {
      sinNot.style.display = 'block';
      badge.style.display = 'none';
      return total;
    }

    sinNot.style.display = 'none';
    badge.style.display = 'inline-block';
    badge.textContent = total;

    items.forEach((item) => {
      const fechaTexto = formatearFecha(item.creada_en || item.created_at);
      const div = document.createElement('div');
      div.className = 'list-group-item list-group-item-action noti-item';
      div.innerHTML = `
        <div class="d-flex align-items-start">
          <div class="me-2 pt-1">
            <i class="fas fa-bell text-primary"></i>
          </div>
          <div class="flex-grow-1">
            <div class="fw-semibold">${item.titulo || 'Notificacion'}</div>
            <div class="text-muted small">${fechaTexto}</div>
            <div class="text-muted noti-text">${item.mensaje || ''}</div>
          </div>
        </div>
      `;
      div.addEventListener('click', () => {
        const redirectUrl = obtenerUrlNotificacion(item);
        marcarComoLeida(item.id, div, redirectUrl);
      });
      lista.appendChild(div);
    });

    return total;
  };

  const ajustarIntervalo = (hayNuevas) => {
    intervaloActual = hayNuevas
      ? intervaloBase
      : Math.min(intervaloActual + intervaloBase, intervaloMax);
  };

  const cargarNotificaciones = (mostrarAviso = false) => {
    return fetch(urlIndex, { headers: { 'Accept': 'application/json' } })
      .then((respuesta) => (respuesta.ok ? respuesta.json() : []))
      .then((data) => {
        const total = renderNotificaciones(data);
        const hayNuevas = total > ultimoConteo;
        if (mostrarAviso && hayNuevas && window.Swal) {
          Swal.fire({
            title: 'Nueva notificacion',
            text: 'Tienes nuevas notificaciones.',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false,
            customClass: { popup: 'swalAlert' },
          });
        }
        ajustarIntervalo(hayNuevas);
        ultimoConteo = total;
      })
      .catch(() => {
        sinNot.style.display = 'block';
        badge.style.display = 'none';
        ajustarIntervalo(false);
      });
  };

  const iniciarPolling = () => {
    if (pollingId) return;
    const programar = () => {
      if (document.hidden) {
        pollingId = null;
        return;
      }
      pollingId = setTimeout(() => {
        cargarNotificaciones(true).finally(() => {
          programar();
        });
      }, intervaloActual);
    };
    programar();
  };

  const detenerPolling = () => {
    if (!pollingId) return;
    clearTimeout(pollingId);
    pollingId = null;
  };

  btn.addEventListener('click', () => {
    const visible = dropdown.style.display === 'block';
    dropdown.style.display = visible ? 'none' : 'block';
    if (!visible) {
      cargarNotificaciones();
    }
  });

  document.addEventListener('click', (event) => {
    if (!dropdown.contains(event.target) && !btn.contains(event.target)) {
      dropdown.style.display = 'none';
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      detenerPolling();
    } else {
      cargarNotificaciones(true).finally(() => iniciarPolling());
    }
  });

  cargarNotificaciones();
  iniciarPolling();
});
