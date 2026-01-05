<script>
  // Limpia el foco al cerrar cualquier modal (evita desplazamientos inesperados)
  document.addEventListener('hidden.bs.modal', function () {
    if (document.activeElement) {
      document.activeElement.blur();
    }
  });

  // Muestra el toast guardado temporalmente (reutilizado en todo el layout)
  window.addEventListener('DOMContentLoaded', function () {
    const title = sessionStorage.getItem('toastTitle');
    const type = sessionStorage.getItem('toastType');

    if (title && type) {
      mostrarToast(title, type);
      sessionStorage.removeItem('toastTitle');
      sessionStorage.removeItem('toastType');
    }
  });

  // Muestra el body cuando todo ha cargado (evita parpadeos)
  window.addEventListener('load', function () {
    document.body.classList.add('show');
  });

  // Notificaciones de la campana (polling sencillo)
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnNotificaciones');
    const dropdown = document.getElementById('dropdownNotificaciones');
    const lista = document.getElementById('listaNotificaciones');
    const sinNot = document.getElementById('sinNotificaciones');
    const badge = document.getElementById('badgeNotificaciones');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const urlIndex = "{{ route('notificaciones.sig.index') }}";
    const urlLeerBase = "{{ url('/notificaciones') }}";

    if (!btn || !dropdown || !lista || !sinNot || !badge) {
      return;
    }

    let ultimoConteo = 0;

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

    const marcarComoLeida = (id, nodo) => {
      if (!csrf) {
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
        div.addEventListener('click', () => marcarComoLeida(item.id, div));
        lista.appendChild(div);
      });

      return total;
    };

    const cargarNotificaciones = (mostrarAviso = false) => {
      fetch(urlIndex, { headers: { 'Accept': 'application/json' } })
        .then((respuesta) => (respuesta.ok ? respuesta.json() : []))
        .then((data) => {
          const total = renderNotificaciones(data);
          if (mostrarAviso && total > ultimoConteo) {
            Swal.fire({
              title: 'Nueva notificacion',
              text: 'Tienes nuevas notificaciones.',
              icon: 'info',
              timer: 2000,
              showConfirmButton: false,
              customClass: { popup: 'swalAlert' },
            });
          }
          ultimoConteo = total;
        })
        .catch(() => {
          sinNot.style.display = 'block';
          badge.style.display = 'none';
        });
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

    cargarNotificaciones();
    setInterval(() => cargarNotificaciones(true), 30000);
  });
</script>
