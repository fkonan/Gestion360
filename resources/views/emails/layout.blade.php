<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>{{ $title ?? 'Notificación' }}</title>
</head>

@php
  $logoPath = public_path('img/LogoCopeBlancoFull.png');
  $logoSrc = asset('img/LogoCopeBlancoFull.png');

  if (is_file($logoPath)) {
    if (isset($message) && method_exists($message, 'embed')) {
      $logoSrc = $message->embed($logoPath);
    } else {
      $logoSrc = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
    }
  }
@endphp

<body style="margin: 0; padding: 10px; background-color: #f6f6f6; font-family: Arial, sans-serif;">
  <center>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f6f6f6">
      <tr>
        <td align="center">
          <table width="600" cellpadding="0" cellspacing="0" border="0"
            style="background-color: #ffffff; border: 1px solid grey; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">

            <!-- HEADER -->
            <tr>
              <td bgcolor="#005CA3" align="center" style="padding: 20px;">
                <img src="{{ $logoSrc }}"
                  alt="Logo Copetran" style="height: 45px; display: block;" />
              </td>
            </tr>

            <!-- CONTENIDO -->
            <tr>
              <td style="padding: 30px; color: #333333;">
                @yield('content')
              </td>
            </tr>

            <!-- FOOTER -->
            <tr>
              <td align="center" style="padding: 15px; font-size: 12px; color: #888888;">
                Este mensaje ha sido generado automáticamente. Por favor, no responda a este correo.
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </center>
</body>

</html>
