<script>
  window.notiConfig = {
    urlIndex: "{{ route('notificaciones.sig.index') }}",
    urlLeerBase: "{{ url('/notificaciones') }}",
  };
</script>
@vite(['resources/js/notificaciones.js'])
