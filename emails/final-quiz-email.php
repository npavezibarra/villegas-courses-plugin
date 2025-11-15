<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Evaluación Final completada</title>
  <style type="text/css">
    @media only screen and (max-width: 500px) {
      #villegas-email-graficas td {
        display: block !important;
        width: 90% !important;
        margin: 0 auto !important;
        text-align: center !important;
      }
      #villegas-email-graficas h2 {
        margin-top: 24px !important;
      }
    }

    table[id$="villegas-email-card"] {
      border-radius: 8px;
      overflow: hidden;
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f6f6f6;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">
  <table id="villegas-email-wrapper" role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" background="{{background_image_url}}" bgcolor="{{background_color}}" style="{{wrapper_background_style}}">
    <tr>
      <td align="center" valign="top" background="{{background_image_url}}" bgcolor="{{background_color}}" style="{{wrapper_background_style}}">
        {{mso_background_block}}
        <div style="{{wrapper_div_style}}">
          <table id="villegas-email-card" role="presentation" width="720" border="0" cellspacing="0" cellpadding="0" style="width:100%;max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:8px;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">
            <tr>
              <td id="villegas-email-encabezado" style="text-align:center;padding:0;">
                {{logo_image}}
              </td>
            </tr>
            <tr>
              <td id="villegas-email-presentacion" style="padding:20px 48px 32px;text-align:center;">
                <p style="margin:0;font-size:12px;color:#6d6d6d;">Completado el {{completion_date}}</p>
                <h1 style="margin:12px 0 8px;font-size:26px;color:#111111;">¡Gran trabajo, {{user_name}}!</h1>
                <p style="margin:0;font-size:16px;line-height:1.5;">Completaste la Evaluación Final de {{quiz_name}}.</p>
              </td>
            </tr>
            <tr>
              <td style="padding:0 32px;">
                <table id="villegas-email-graficas" role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;padding:32px 0;text-align:center;">
                  <tr>
                    <td align="center">
                      <table border="0" cellspacing="0" cellpadding="0" role="presentation">
                        <tr>
                          <td style="padding:0 14px;text-align:center;">
                            <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">Evaluación Inicial</h2>
                            <img src="{{initial_chart_url}}" alt="Evaluación Inicial {{initial_percentage}}" style="max-width:240px;height:auto;">
                            <p style="margin-top:12px;font-size:14px;color:#444444;">{{initial_percentage}}</p>
                          </td>
                          <td style="padding:0 14px;text-align:center;">
                            <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">Evaluación Final</h2>
                            <img src="{{final_chart_url}}" alt="Evaluación Final {{final_percentage}}" style="max-width:240px;height:auto;">
                            <p style="margin-top:12px;font-size:14px;color:#444444;">{{final_percentage}}</p>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td id="villegas-email-cta" style="padding:32px 48px;text-align:center;">
                {{variacion_evaluacion}}
                <p style="margin:0 0 18px;font-size:15px;color:#333333;">{{cta_message}}</p>
                <a href="{{button_url}}" style="display:inline-block;background:#000000;color:#ffffff;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;">{{button_label}}</a>
                <p style="margin-top:16px;font-size:13px;color:#666666;">{{button_note}}</p>
                <p style="margin-top:28px;font-size:13px;color:#666666;">¡Gracias por participar en el curso!</p>
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
