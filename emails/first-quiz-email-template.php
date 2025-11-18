<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo esc_html( $subject ); ?></title>

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

    @media only screen and (max-width: 600px) {
      .villegas-circle-container,
      .villegas-circle-wrapper {
        margin-left: auto !important;
        margin-right: auto !important;
        text-align: center !important;
      }

      .villegas-first-circle {
        margin-bottom: 40px !important;
      }

      #villegas-final-title-row td,
      #villegas-final-title-row {
        padding-top: 40px !important;
      }
    }

    table[id$="villegas-email-card"] {
      border-radius: 8px;
      overflow: hidden;
    }

    @media only screen and (min-width: 1024px) {
      #villegas-email-logo {
        width: 76% !important;
        height: 170px !important;
      }
    }

    @media only screen and (max-width: 1023px) {
      #villegas-email-logo {
        width: 100% !important;
        height: 140px !important;
      }
    }

  </style>
</head>

<body style="margin:0;padding:0;background-color:#f6f6f6;font-family:Helvetica,Arial,sans-serif;color:#1c1c1c;">

<table id="villegas-email-wrapper" role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
  background="<?php echo esc_url($background_image_url); ?>"
  bgcolor="#f6f6f6"
  style="background-color:#f6f6f6;
         background-image:url('<?php echo esc_url($background_image_url); ?>');
         background-repeat:no-repeat;
         background-position:center center;
         background-size:cover;
         padding:32px 0;">

  <tr>
    <td align="center" valign="top">

      <table id="villegas-email-card" role="presentation" width="720" border="0" cellspacing="0" cellpadding="0"
        style="width:100%;max-width:720px;margin:0 auto;background:#ffffff;
               border:1px solid #e5e5e5;border-radius:8px;">

        <!-- LOGO -->
        <tr>
          <td id="villegas-email-encabezado" style="text-align:center;background:black;">
            <img id="villegas-email-logo"
                 src="<?php echo esc_url($logo_url); ?>"
                 alt="Academia Villegas"
                 style="width:100%;max-width:720px;height:162px;
                        object-fit:cover;object-position:center;
                        display:block;margin:0 auto;border-top-left-radius:8px;border-top-right-radius:8px;">
          </td>
        </tr>

        <!-- PRESENTATION -->
        <tr>
          <td id="villegas-email-presentacion" style="padding:20px 48px 32px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#6d6d6d;">
              <?php printf( esc_html__( 'Completado el %s', 'villegas-courses' ), esc_html( $completion_date ) ); ?>
            </p>

            <h1 style="margin:12px 0 8px;font-size:26px;color:#111111;">
              <?php
echo wp_kses_post(
    sprintf( __( '¡Gran trabajo,<br style="display:block;"> %s!', 'villegas-courses' ), esc_html( $debug['user_display_name'] ) )
);
?>
            </h1>

            <p style="margin:0;font-size:16px;line-height:1.5;">
              <?php printf( esc_html__( 'Completaste el Primer Quiz de %s.', 'villegas-courses' ), esc_html( $debug['quiz_title'] ) ); ?>
            </p>
          </td>
        </tr>

        <!-- SCORE CHARTS -->
        <tr>
          <td style="padding:0 32px;">
            <table id="villegas-email-graficas" role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
              style="border-top:1px solid #f1f1f1;border-bottom:1px solid #f1f1f1;padding:32px 0;text-align:center;">

              <tr>
                <td align="center">

                  <table class="villegas-circle-wrapper" role="presentation" cellpadding="0" cellspacing="0">
                    <tr>

                      <!-- USER SCORE -->
                      <td class="villegas-circle-container villegas-first-circle" style="padding:0 14px;text-align:center;">
                        <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">
                          <?php esc_html_e( 'Tu puntaje', 'villegas-courses' ); ?>
                        </h2>
                        <img src="<?php echo esc_url( $user_chart_url ); ?>"
                             alt="Tu puntaje <?php echo esc_attr( $user_display_percent ); ?>"
                             style="max-width:240px;height:auto;">
                      </td>

                      <!-- VILLEGAS AVERAGE -->
                      <td id="villegas-final-title-row" class="villegas-circle-container" style="padding:0 14px;text-align:center;">
                        <h2 style="font-size:16px;margin-bottom:12px;color:#111111;">
                          <?php esc_html_e( 'Promedio Alumnos', 'villegas-courses' ); ?>
                        </h2>
                        <img src="<?php echo esc_url( $average_chart_url ); ?>"
                             alt="Promedio Alumnos <?php echo esc_attr( $average_display_percent ); ?>"
                             style="max-width:240px;height:auto;">
                      </td>

                    </tr>
                  </table>

                </td>
              </tr>

            </table>
          </td>
        </tr>

        <!-- CTA -->
        <tr>
          <td id="villegas-email-cta" style="padding:32px 48px;text-align:center;">
            <p style="font-size:16px;color:#333333;margin-bottom:24px;">
              <?php echo esc_html__( 'Cada lección completada no solo representa un paso adelante en tu formación, sino también una oportunidad para poner a prueba tus conocimientos.', 'elvillegas' ); ?>
            </p>

            <a href="<?php echo $button_url; ?>"
               style="
                    display: inline-block;
                    background-color: #000000;
                    color: #ffffff;
                    padding: 14px 28px;
                    font-size: 16px;
                    border-radius: 6px;
                    text-decoration: none;
                    margin-bottom: 12px;
               ">
               <?php echo $button_label; ?>
            </a>

            <p style="font-size:14px;color:#666666;margin-top:8px;">
              <?php echo $button_note; ?>
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>

</table>

</body>
</html>
