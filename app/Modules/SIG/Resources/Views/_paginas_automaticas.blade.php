<script>
  (() => {
    const archivoInput = document.getElementById('archivo');
    const paginasInput = document.getElementById('paginas');
    const ayudaPaginas = document.getElementById('paginas-ayuda');
    const estadoPaginas = document.getElementById('paginas-estado');
    const errorPaginas = document.getElementById('error-paginas');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const valorInicial = paginasInput?.value ?? '';

    if (!archivoInput || !paginasInput || !ayudaPaginas || !estadoPaginas || !token) {
      return;
    }

    const rutaCalculo = "{{ route('mapa-procesos.documento.paginas.calcular') }}";
    const ayudaInicial = ayudaPaginas.textContent.trim();

    const actualizarAyuda = (mensaje, clase = 'text-muted') => {
      ayudaPaginas.textContent = mensaje;
      ayudaPaginas.className = `form-text ${clase}`;
    };

    const actualizarEstado = (texto, clase = 'sig-upload-panel__status--pending') => {
      estadoPaginas.textContent = texto;
      estadoPaginas.className = `sig-upload-panel__status ${clase}`;
    };

    const fijarSoloLectura = (soloLectura) => {
      paginasInput.readOnly = soloLectura;
      paginasInput.classList.toggle('sig-upload-panel__input--editable', !soloLectura);
    };

    const activarModoAutomatico = (paginas) => {
      paginasInput.value = paginas ?? '';
      paginasInput.placeholder = '';
      fijarSoloLectura(true);
      paginasInput.classList.remove('is-invalid');
      if (errorPaginas) {
        errorPaginas.textContent = '';
      }
      actualizarEstado('Auto', 'sig-upload-panel__status--auto');
      actualizarAyuda('Detectadas al cargar.', 'text-muted');
    };

    const activarModoManual = () => {
      paginasInput.value = '';
      paginasInput.placeholder = 'Ingresa el total';
      fijarSoloLectura(false);
      actualizarEstado('Manual', 'sig-upload-panel__status--manual');
      actualizarAyuda('Ingresa el total.', 'text-muted');
    };

    const activarModoCarga = () => {
      paginasInput.value = '';
      paginasInput.placeholder = '';
      fijarSoloLectura(true);
      actualizarEstado('Auto', 'sig-upload-panel__status--pending');
      actualizarAyuda('Detectando...', 'text-muted');
    };

    const restablecerEstado = (restaurarValor = true) => {
      if (restaurarValor) {
        paginasInput.value = valorInicial;
      }
      paginasInput.placeholder = 'Se completa al cargar';
      fijarSoloLectura(true);
      actualizarEstado('Auto', 'sig-upload-panel__status--pending');
      actualizarAyuda(ayudaInicial, 'text-muted');
    };

    restablecerEstado();

    archivoInput.addEventListener('change', async () => {
      const archivo = archivoInput.files?.[0];
      paginasInput.classList.remove('is-invalid');
      if (errorPaginas) {
        errorPaginas.textContent = '';
      }

      if (!archivo) {
        restablecerEstado();
        return;
      }

      activarModoCarga();

      const formData = new FormData();
      formData.append('archivo', archivo);

      try {
        const response = await fetch(rutaCalculo, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: formData
        });

        const data = await response.json();

        if (!response.ok) {
          activarModoManual();
          return;
        }

        if (data.automatico && data.paginas) {
          activarModoAutomatico(data.paginas);
          return;
        }

        activarModoManual();
      } catch (error) {
        activarModoManual();
      }
    });
  })();
</script>
