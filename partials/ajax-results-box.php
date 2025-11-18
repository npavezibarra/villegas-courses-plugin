<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$modal_data = isset( $modal_data ) && is_array( $modal_data ) ? $modal_data : [];

$course_title = isset( $modal_data['course']['title'] ) ? $modal_data['course']['title'] : '';
$first        = isset( $modal_data['first'] ) ? $modal_data['first'] : [];
$final        = isset( $modal_data['final'] ) ? $modal_data['final'] : [];
$metrics      = isset( $modal_data['metrics'] ) ? $modal_data['metrics'] : [];

$messages = [];

if ( empty( $first['quiz_id'] ) ) {
    $messages[] = __( 'Este curso no tiene configurada una Prueba Inicial.', 'villegas-courses' );
}

if ( empty( $final['quiz_id'] ) ) {
    $messages[] = __( 'Este curso no tiene configurada una Prueba Final.', 'villegas-courses' );
}

if ( ! empty( $first['quiz_id'] ) && empty( $first['has_attempt'] ) ) {
    $messages[] = __( 'Aún no registramos resultados de tu Prueba Inicial.', 'villegas-courses' );
}

if ( ! empty( $final['quiz_id'] ) && empty( $final['has_attempt'] ) ) {
    $messages[] = __( 'Aún no registramos resultados de tu Prueba Final.', 'villegas-courses' );
}

$show_comparison = empty( $messages ) && ! empty( $first['has_attempt'] ) && ! empty( $final['has_attempt'] );

$first_percentage = isset( $first['percentage'] ) ? $first['percentage'] : null;
$final_percentage = isset( $final['percentage'] ) ? $final['percentage'] : null;
$variation        = isset( $metrics['delta'] ) ? $metrics['delta'] : null;
$days_elapsed     = isset( $metrics['days_elapsed'] ) ? $metrics['days_elapsed'] : null;
?>
<style>
    .politeia-modal-results {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        max-width: 640px;
        margin: 0 auto;
        position: relative;
    }
    .politeia-modal-results h3 {
        margin-top: 0;
        text-align: center;
        font-size: 22px;
    }
    .politeia-modal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }
    .politeia-modal-card {
        background: #f8f9fa;
        padding: 18px;
        border-radius: 10px;
    }
    .politeia-modal-card span {
        display: block;
        color: #728188;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .politeia-modal-card strong {
        font-size: 30px;
        font-weight: 700;
        display: block;
    }
    .politeia-modal-meta {
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: center;
    }
    .politeia-modal-chip {
        background: #eef0f2;
        padding: 10px 16px;
        border-radius: 30px;
        font-weight: 600;
        color: #333333;
    }
    .politeia-modal-messages {
        background: #fff4e5;
        border: 1px solid #ff9800;
        padding: 16px;
        border-radius: 10px;
        margin-top: 20px;
    }
    .politeia-modal-messages p {
        margin: 0 0 8px;
        color: #7a4b00;
        font-weight: 600;
    }
    .politeia-modal-messages ul {
        margin: 0;
        padding-left: 20px;
        color: #7a4b00;
    }
</style>

<div class="politeia-modal-results">
    <p style="font-size:10px;text-align:center;margin-bottom:10px;letter-spacing:10px;">CURSO</p>
    <h3><?php echo esc_html( $course_title ); ?></h3>

    <div class="politeia-modal-grid">
        <div class="politeia-modal-card">
            <span><?php esc_html_e( 'Prueba Inicial', 'villegas-courses' ); ?></span>
            <strong>
                <?php
                if ( is_numeric( $first_percentage ) ) {
                    echo esc_html( villegas_round_half_up( $first_percentage ) ) . '%';
                } else {
                    echo '--';
                }
                ?>
            </strong>
            <div style="color:#5f6b75; font-size: 14px; font-weight: 500;">
                <?php echo $first['formatted_date'] ? esc_html( $first['formatted_date'] ) : esc_html__( 'Sin fecha registrada', 'villegas-courses' ); ?>
            </div>
            <div style="color:#5f6b75; font-size: 13px; margin-top:6px;">
                <?php
                if ( isset( $first['score'] ) && $first['score'] > 0 ) {
                    printf(
                        /* translators: %d: quiz score */
                        esc_html__( 'Puntaje: %d pts.', 'villegas-courses' ),
                        intval( $first['score'] )
                    );
                } else {
                    esc_html_e( 'Puntaje no disponible.', 'villegas-courses' );
                }
                ?>
            </div>
        </div>
        <div class="politeia-modal-card">
            <span><?php esc_html_e( 'Prueba Final', 'villegas-courses' ); ?></span>
            <strong>
                <?php
                if ( is_numeric( $final_percentage ) ) {
                    echo esc_html( villegas_round_half_up( $final_percentage ) ) . '%';
                } else {
                    echo '--';
                }
                ?>
            </strong>
            <div style="color:#5f6b75; font-size: 14px; font-weight: 500;">
                <?php echo $final['formatted_date'] ? esc_html( $final['formatted_date'] ) : esc_html__( 'Sin fecha registrada', 'villegas-courses' ); ?>
            </div>
            <div style="color:#5f6b75; font-size: 13px; margin-top:6px;">
                <?php
                if ( isset( $final['score'] ) && $final['score'] > 0 ) {
                    printf(
                        /* translators: %d: quiz score */
                        esc_html__( 'Puntaje: %d pts.', 'villegas-courses' ),
                        intval( $final['score'] )
                    );
                } else {
                    esc_html_e( 'Puntaje no disponible.', 'villegas-courses' );
                }
                ?>
            </div>
        </div>
    </div>

    <?php if ( $show_comparison ) : ?>
        <div class="politeia-modal-meta">
            <?php if ( null !== $variation ) : ?>
                <span class="politeia-modal-chip">
                    <?php
                    $sign = $variation > 0 ? '+' : '';
                    printf(
                        /* translators: %s: variation value */
                        esc_html__( 'Variación: %s%d%%', 'villegas-courses' ),
                        esc_html( $sign ),
                        intval( $variation )
                    );
                    ?>
                </span>
            <?php endif; ?>

            <?php if ( null !== $days_elapsed ) : ?>
                <span class="politeia-modal-chip">
                    <?php
                    printf(
                        /* translators: %d: number of days */
                        esc_html__( 'Días entre intentos: %d', 'villegas-courses' ),
                        intval( $days_elapsed )
                    );
                    ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $messages ) ) : ?>
        <div class="politeia-modal-messages">
            <p><?php esc_html_e( 'Necesitas completar ambas pruebas para ver la comparación.', 'villegas-courses' ); ?></p>
            <ul>
                <?php foreach ( $messages as $message ) : ?>
                    <li><?php echo esc_html( $message ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
