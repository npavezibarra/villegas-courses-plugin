<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resultado Final Quiz</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: white; margin: 0; padding: 0;">
  
  <!-- Encabezado con Logo -->
  <div style="text-align:center; max-width:500px; padding:20px 40px; margin:0px auto 0px auto; border:1px solid #dbdbdb; border-radius:8px 8px 0px 0px; background:white;">
    <img src="http://elvillegas.cl/wp-content/uploads/2025/04/academiavillegaslogohorizontal.png" alt="Academia Villegas" style="max-width:300px; height:auto;">
  </div>

  <!-- Contenido principal -->
  <div style="max-width:500px; margin:0 auto; background-color:#f4f4f4; padding:40px; border-radius:0px 0px 8px 8px; border:1px solid #dbdbdb;">

    <!-- Saludo -->
    <h2 style="color:#333; text-align:center; font-size:22px;">¡Hola <strong>{{user_name}}</strong>!</h2>
    <p style="color:#555; text-align:center; font-size:16px; max-width:400px; margin:auto;">
      Has completado el <strong>Final Quiz</strong> del curso <strong>{{quiz_title}}</strong> el día <strong>{{completion_date}}</strong>.
    </p>

    <!-- Final Quiz -->
    <div style="margin-top:30px; padding:20px; background-color:white; border-radius:8px; border:1px solid #d5d5d5;">
      <div style="font-weight:bold; font-size:16px; margin-bottom:5px;">{{quiz_title}}</div>
      <div style="color:#888; font-size:14px; margin-bottom:10px;">{{completion_date}}</div>
      <div style="background:#e9ecef; border-radius:15px; height:20px; overflow:hidden;">
        <div style="{{final_bar_style}} height:100%; background:#ff9800;"></div>
      </div>
      <div style="text-align:right; font-size:20px; font-weight:bold; margin-top:10px;">{{quiz_percentage}}%</div>
    </div>

    <!-- First Quiz -->
    <div style="margin-top:20px; padding:20px; background-color:white; border-radius:8px; border:1px solid #d5d5d5;">
      <div style="font-weight:bold; font-size:16px; margin-bottom:5px;">{{first_quiz_title}}</div>
      <div style="color:#888; font-size:14px; margin-bottom:10px;">{{first_quiz_date}}</div>
      <div style="background:#e9ecef; border-radius:15px; height:20px; overflow:hidden;">
        <div style="{{first_bar_style}} height:100%; background:#ff9800;"></div>
      </div>
      <div style="text-align:right; font-size:20px; font-weight:bold; margin-top:10px;">{{first_quiz_percentage}}%</div>
    </div>

    <!-- Comparativa -->
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:30px; background-color:white; border:1px solid #d5d5d5; border-radius:8px;">
      <tr>
        <td align="center" valign="top" style="padding:20px;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <!-- Variación conocimientos -->
              <td width="50%" align="center" valign="top" style="padding:10px;">
                <div style="font-size:16px; color:#666;">Variación conocimientos</div>
                <div style="font-size:28px; font-weight:bold; color:#9fd99f;">
                  {{knowledge_variation}}% <span style="font-size:20px;">{{variation_arrow}}</span>
                </div>
              </td>

              <!-- Días para completar -->
              <td width="50%" align="center" valign="top" style="padding:10px;">
                <div style="font-size:16px; color:#666;">Completaste el curso en</div>
                <div style="font-size:28px; color:#333; font-weight:bold;">
                  {{days_to_complete}} {{days_label}}
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Mensaje de cierre -->
    <p style="margin-top:30px; color:#555; text-align:center;">¡Felicitaciones por tu progreso!</p>

  </div>

</body>
</html>