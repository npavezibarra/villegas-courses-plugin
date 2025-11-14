<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Villegas_Course' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'class-villegas-course.php';
}

if ( ! class_exists( 'Villegas_Quiz_Attempts_Shortcode' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'class-villegas-quiz-attempts-shortcode.php';
}

if ( ! class_exists( 'Villegas_Quiz_Stats' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../classes/class-villegas-quiz-stats.php';
}

if ( ! class_exists( 'Villegas_Quiz_Emails' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'class-villegas-quiz-emails.php';
}

if ( ! function_exists( 'villegas_normalize_percentage_value' ) ) {
    function villegas_normalize_percentage_value( $value ): ?float {
        if ( null === $value ) {
            return null;
        }

        if ( is_numeric( $value ) ) {
            return (float) $value;
        }

        if ( is_string( $value ) ) {
            $filtered = preg_replace( '/[^0-9.,-]/', '', $value );

            if ( '' === $filtered ) {
                return null;
            }

            $has_comma = false !== strpos( $filtered, ',' );
            $has_dot   = false !== strpos( $filtered, '.' );

            if ( $has_comma && ! $has_dot ) {
                $filtered = str_replace( ',', '.', $filtered );
            } else {
                $filtered = str_replace( ',', '', $filtered );
            }

            if ( is_numeric( $filtered ) ) {
                return (float) $filtered;
            }
        }

        return null;
    }
}

if ( ! function_exists( 'villegas_generate_quickchart_url' ) ) {
    function villegas_generate_quickchart_url( float $value, ?float $display_value = null ): string {
        $clamped_value   = max( 0, min( 100, (float) $value ) );
        $chart_value     = round( $clamped_value, 2 );
        $label_source    = null !== $display_value ? max( 0, min( 100, (float) $display_value ) ) : $clamped_value;
        $label_value_int = (int) round( $label_source );
        $remaining       = max( 0, round( 100 - $chart_value, 2 ) );

        $config = [
            'type'    => 'doughnut',
            'data'    => [
                'datasets' => [
                    [
                        'data'            => [ $chart_value, $remaining ],
                        'backgroundColor' => [ '#f9c600', '#f2f2f2' ],
                        'borderWidth'     => 0,
                        'cutout'          => '70%',
                        'rotation'        => 270,
                    ],
                ],
            ],
            'options' => [
                'plugins' => [
                    'legend'        => [ 'display' => false ],
                    'tooltip'       => [ 'enabled' => false ],
                    'datalabels'    => [ 'display' => false ],
                    'doughnutlabel' => [
                        'labels' => [
                            [
                                'text'  => sprintf( '%d%%', $label_value_int ),
                                'font'  => [ 'size' => 34, 'weight' => 'bold', 'family' => 'Helvetica, Arial, sans-serif' ],
                                'color' => '#222222',
                            ],
                        ],
                    ],
                ],
                'cutoutPercentage' => 70,
                'layout'           => [
                    'padding' => 8,
                ],
            ],
            'plugins' => [ 'doughnutlabel' ],
        ];

        return 'https://quickchart.io/chart?c=' . urlencode( wp_json_encode( $config ) );
    }
}

if ( ! function_exists( 'villegas_normalize_email_asset_url' ) ) {
    function villegas_normalize_email_asset_url( string $url, ?string $fallback = null ): string {
        $url       = trim( $url );
        $fallback  = null !== $fallback ? trim( $fallback ) : '';

        if ( '' === $url ) {
            return $fallback;
        }

        $normalized_url = set_url_scheme( $url, 'https' );

        $parts = wp_parse_url( $normalized_url );

        if ( ! $parts || empty( $parts['host'] ) ) {
            return '' !== $fallback ? $fallback : $normalized_url;
        }

        $host = strtolower( $parts['host'] );

        $local_suffixes = [ '.local', '.test', '.invalid' ];
        $is_local_host  = 'localhost' === $host;

        if ( ! $is_local_host ) {
            foreach ( $local_suffixes as $suffix ) {
                $suffix_length = strlen( $suffix );

                if ( $suffix_length && strlen( $host ) >= $suffix_length && $suffix === substr( $host, - $suffix_length ) ) {
                    $is_local_host = true;
                    break;
                }
            }
        }

        if ( $is_local_host ) {
            $path = $parts['path'] ?? '';

            if ( $path ) {
                $public_host = apply_filters( 'villegas_email_public_asset_host', 'elvillegas.cl', $url );

                $normalized_url = 'https://' . $public_host . $path;

                if ( ! empty( $parts['query'] ) ) {
                    $normalized_url .= '?' . $parts['query'];
                }
            }
        }

        if ( function_exists( 'wp_remote_head' ) ) {
            $response = wp_remote_head( $normalized_url, [ 'timeout' => 5 ] );

            if ( is_wp_error( $response ) ) {
                return $fallback;
            }

            $status_code = (int) wp_remote_retrieve_response_code( $response );

            if ( $status_code < 200 || $status_code >= 400 ) {
                return $fallback;
            }
        }

        return $normalized_url;
    }
}

if ( ! function_exists( 'villegas_resolve_quiz_pro_id' ) ) {
    function villegas_resolve_quiz_pro_id( int $quiz_post_id ): int {
        $quiz_post_id = absint( $quiz_post_id );

        if ( ! $quiz_post_id ) {
            return 0;
        }

        $pro_id = 0;

        if ( function_exists( 'learndash_get_setting' ) ) {
            $setting = learndash_get_setting( $quiz_post_id, 'quiz_pro' );

            if ( is_numeric( $setting ) ) {
                $pro_id = (int) $setting;
            } elseif ( is_array( $setting ) ) {
                foreach ( [ 'quiz_pro', 'quiz_pro_id' ] as $key ) {
                    if ( isset( $setting[ $key ] ) && is_numeric( $setting[ $key ] ) ) {
                        $pro_id = (int) $setting[ $key ];
                        break;
                    }
                }
            }
        }

        if ( ! $pro_id ) {
            $meta = get_post_meta( $quiz_post_id, '_sfwd-quiz', true );

            if ( is_string( $meta ) ) {
                $meta = maybe_unserialize( $meta );
            }

            if ( is_array( $meta ) ) {
                foreach ( [ 'quiz_pro', 'quiz_pro_id', 'sfwd-quiz_quiz_pro', 'sfwd-quiz_quiz_pro_id' ] as $meta_key ) {
                    if ( isset( $meta[ $meta_key ] ) && is_numeric( $meta[ $meta_key ] ) ) {
                        $pro_id = (int) $meta[ $meta_key ];
                        break;
                    }
                }
            }
        }

        if ( ! $pro_id ) {
            $pro_id = (int) get_post_meta( $quiz_post_id, 'quiz_pro_id', true );
        }

        return $pro_id > 0 ? $pro_id : 0;
    }
}

if ( ! function_exists( 'villegas_merge_quiz_attempt_data' ) ) {
    function villegas_merge_quiz_attempt_data( array $base_attempt, array $candidate_attempt ): array {
        $base_percentage      = array_key_exists( 'percentage', $base_attempt ) ? $base_attempt['percentage'] : null;
        $base_timestamp       = array_key_exists( 'timestamp', $base_attempt ) ? (int) $base_attempt['timestamp'] : null;
        $candidate_percentage = array_key_exists( 'percentage', $candidate_attempt ) ? $candidate_attempt['percentage'] : null;
        $candidate_timestamp  = array_key_exists( 'timestamp', $candidate_attempt ) ? (int) $candidate_attempt['timestamp'] : null;

        $has_candidate_percentage = null !== $candidate_percentage;

        if ( $has_candidate_percentage ) {
            if ( null === $base_percentage ) {
                $base_attempt['percentage'] = $candidate_percentage;
            } elseif ( $candidate_timestamp && $base_timestamp && $candidate_timestamp > $base_timestamp ) {
                $base_attempt['percentage'] = $candidate_percentage;
            } elseif ( $candidate_timestamp && ! $base_timestamp ) {
                $base_attempt['percentage'] = $candidate_percentage;
            }
        }

        if ( $candidate_timestamp ) {
            if ( ! $base_timestamp || $candidate_timestamp > $base_timestamp ) {
                $base_attempt['timestamp'] = $candidate_timestamp;

                if ( $has_candidate_percentage ) {
                    $base_attempt['percentage'] = $candidate_percentage;
                }
            }
        }

        if ( ! array_key_exists( 'percentage', $base_attempt ) ) {
            $base_attempt['percentage'] = $has_candidate_percentage ? $candidate_percentage : null;
        }

        if ( ! array_key_exists( 'timestamp', $base_attempt ) ) {
            $base_attempt['timestamp'] = $candidate_timestamp ?: null;
        }

        return $base_attempt;
    }
}

if ( ! function_exists( 'villegas_get_latest_quiz_attempt_from_usermeta' ) ) {
    function villegas_get_latest_quiz_attempt_from_usermeta( int $user_id, int $quiz_id ): array {
        $user_id = absint( $user_id );
        $quiz_id = absint( $quiz_id );

        if ( ! $user_id || ! $quiz_id ) {
            return [ 'percentage' => null, 'timestamp' => null ];
        }

        $raw_meta = get_user_meta( $user_id, '_sfwd-quizzes', true );

        if ( empty( $raw_meta ) ) {
            return [ 'percentage' => null, 'timestamp' => null ];
        }

        if ( is_string( $raw_meta ) ) {
            $raw_meta = maybe_unserialize( $raw_meta );
        }

        if ( ! is_array( $raw_meta ) ) {
            return [ 'percentage' => null, 'timestamp' => null ];
        }

        $latest_attempt = [
            'percentage' => null,
            'timestamp'  => null,
        ];

        foreach ( $raw_meta as $attempt ) {
            if ( ! is_array( $attempt ) ) {
                continue;
            }

            $candidate_ids = [];

            if ( isset( $attempt['quiz'] ) ) {
                $candidate_ids[] = absint( $attempt['quiz'] );
            }

            if ( isset( $attempt['quiz_post_id'] ) ) {
                $candidate_ids[] = absint( $attempt['quiz_post_id'] );
            }

            if ( isset( $attempt['quiz_id'] ) ) {
                $candidate_ids[] = absint( $attempt['quiz_id'] );
            }

            if ( isset( $attempt['quiz_pro_id'] ) ) {
                $candidate_ids[] = absint( $attempt['quiz_pro_id'] );
            }

            if ( isset( $attempt['pro_quiz_id'] ) ) {
                $candidate_ids[] = absint( $attempt['pro_quiz_id'] );
            }

            $candidate_ids = array_filter( $candidate_ids );

            if ( empty( $candidate_ids ) || ! in_array( $quiz_id, $candidate_ids, true ) ) {
                continue;
            }

            $timestamp = isset( $attempt['time'] ) ? (int) $attempt['time'] : 0;

            $percentage = null;

            if ( isset( $attempt['percentage'] ) ) {
                $percentage = villegas_normalize_percentage_value( $attempt['percentage'] );
            }

            if ( null === $percentage && isset( $attempt['score'], $attempt['count'] ) ) {
                $total_questions = (float) $attempt['count'];

                if ( $total_questions > 0 ) {
                    $percentage = ( (float) $attempt['score'] / $total_questions ) * 100;
                }
            }

            if ( null === $percentage && isset( $attempt['points'], $attempt['total_points'] ) ) {
                $total_points = (float) $attempt['total_points'];

                if ( $total_points > 0 ) {
                    $percentage = ( (float) $attempt['points'] / $total_points ) * 100;
                }
            }

            $percentage = null !== $percentage ? max( 0.0, min( 100.0, (float) $percentage ) ) : null;

            $should_replace = false;

            if ( null === $latest_attempt['timestamp'] ) {
                $should_replace = true;
            } elseif ( null !== $percentage && null === $latest_attempt['percentage'] ) {
                $should_replace = true;
            } elseif ( $timestamp > (int) $latest_attempt['timestamp'] ) {
                $should_replace = null === $latest_attempt['percentage'] || $percentage !== null;
            }

            if ( $should_replace ) {
                $latest_attempt['percentage'] = $percentage;
                $latest_attempt['timestamp']  = $timestamp ?: null;
            }
        }

        return $latest_attempt;
    }
}

if ( ! function_exists( 'villegas_get_latest_quiz_attempt' ) ) {
    function villegas_get_latest_quiz_attempt( int $user_id, int $quiz_id ): array {
        global $wpdb;

        $user_id = absint( $user_id );
        $quiz_id = absint( $quiz_id );

        if ( ! $user_id || ! $quiz_id ) {
            return [ 'percentage' => null, 'timestamp' => null ];
        }

        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        $meta_table     = $wpdb->prefix . 'learndash_user_activity_meta';

        $attempt         = [];
        $query_templates = [
            "SELECT ua.activity_id, ua.activity_completed"
            . " FROM {$activity_table} AS ua"
            . " WHERE ua.user_id = %d"
            . "   AND ua.activity_type = 'quiz'"
            . "   AND ua.activity_completed IS NOT NULL"
            . "   AND ua.post_id = %d"
            . " ORDER BY ua.activity_completed DESC"
            . " LIMIT 1",
            "SELECT ua.activity_id, ua.activity_completed"
            . " FROM {$activity_table} AS ua"
            . " WHERE ua.user_id = %d"
            . "   AND ua.activity_type = 'quiz'"
            . "   AND ua.activity_completed IS NOT NULL"
            . "   AND ua.post_id = %d"
            . " ORDER BY ua.activity_completed DESC"
            . " LIMIT 1",
            "SELECT ua.activity_id, ua.activity_completed"
            . " FROM {$activity_table} AS ua"
            . " INNER JOIN {$meta_table} AS quiz_meta"
            . "    ON quiz_meta.activity_id = ua.activity_id"
            . "   AND quiz_meta.activity_meta_key = 'quiz'"
            . "   AND quiz_meta.activity_meta_value+0 = %d"
            . " WHERE ua.user_id = %d"
            . "   AND ua.activity_type = 'quiz'"
            . "   AND ua.activity_completed IS NOT NULL"
            . " ORDER BY ua.activity_completed DESC"
            . " LIMIT 1",
        ];

        foreach ( $query_templates as $index => $sql ) {
            if ( 2 === $index ) {
                $prepared = $wpdb->prepare( $sql, $quiz_id, $user_id );
            } else {
                $prepared = $wpdb->prepare( $sql, $user_id, $quiz_id );
            }

            $attempt = $wpdb->get_row( $prepared, ARRAY_A );

            if ( ! empty( $attempt ) && ! empty( $attempt['activity_id'] ) ) {
                break;
            }
        }

        if ( empty( $attempt ) || empty( $attempt['activity_id'] ) ) {
            return villegas_get_latest_quiz_attempt_from_usermeta( $user_id, $quiz_id );
        }

        $percentage = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT activity_meta_value+0
                 FROM {$meta_table}
                 WHERE activity_id = %d
                   AND activity_meta_key = 'percentage'
                 LIMIT 1",
                (int) $attempt['activity_id']
            )
        );

        $result = [
            'percentage' => villegas_normalize_percentage_value( $percentage ),
            'timestamp'  => ! empty( $attempt['activity_completed'] ) ? (int) $attempt['activity_completed'] : null,
        ];

        if ( null === $result['percentage'] || null === $result['timestamp'] ) {
            $meta_attempt = villegas_get_latest_quiz_attempt_from_usermeta( $user_id, $quiz_id );

            if ( null === $result['percentage'] && null !== $meta_attempt['percentage'] ) {
                $result['percentage'] = $meta_attempt['percentage'];
            }

            if ( ( null === $result['timestamp'] || 0 === $result['timestamp'] ) && null !== $meta_attempt['timestamp'] ) {
                $result['timestamp'] = $meta_attempt['timestamp'];
            }
        }

        return $result;
    }
}

if ( ! function_exists( 'villegas_get_quiz_debug_data' ) ) {
    function villegas_get_quiz_debug_data( array $quiz_data, WP_User $user ): array {
        $quiz_raw    = isset( $quiz_data['quiz'] ) ? $quiz_data['quiz'] : 0;
        $quiz_post_id = 0;
        $quiz_pro_id  = 0;
        $quiz_title   = '';

        if ( $quiz_raw instanceof WP_Post ) {
            $quiz_post_id = (int) $quiz_raw->ID;
        } elseif ( is_object( $quiz_raw ) ) {
            if ( method_exists( $quiz_raw, 'getId' ) ) {
                $quiz_pro_id = (int) $quiz_raw->getId();
            }

            if ( method_exists( $quiz_raw, 'getPostId' ) ) {
                $quiz_post_id = (int) $quiz_raw->getPostId();
            }

            if ( method_exists( $quiz_raw, 'getName' ) ) {
                $quiz_title = (string) $quiz_raw->getName();
            }
        } else {
            $quiz_post_id = absint( $quiz_raw );
        }

        if ( ! $quiz_post_id && isset( $quiz_data['quiz_post_id'] ) ) {
            $quiz_post_id = absint( $quiz_data['quiz_post_id'] );
        }

        if ( ! $quiz_post_id && isset( $quiz_data['quiz_id'] ) ) {
            $quiz_post_id = absint( $quiz_data['quiz_id'] );
        }

        if ( ! $quiz_pro_id && isset( $quiz_data['quiz_pro_id'] ) ) {
            $quiz_pro_id = absint( $quiz_data['quiz_pro_id'] );
        }

        if ( ! $quiz_post_id && $quiz_pro_id && function_exists( 'learndash_get_quiz_id_by_pro_quiz_id' ) ) {
            $quiz_post_id = (int) learndash_get_quiz_id_by_pro_quiz_id( $quiz_pro_id );
        }

        if ( $quiz_post_id && ! $quiz_pro_id ) {
            $quiz_pro_id = villegas_resolve_quiz_pro_id( $quiz_post_id );
        }

        $quiz_id = $quiz_post_id ? $quiz_post_id : $quiz_pro_id;
        $quiz_id = absint( $quiz_id );
        $user_id = absint( $user->ID );

        $resolved_title = $quiz_post_id ? get_the_title( $quiz_post_id ) : '';

        if ( ! $resolved_title && $quiz_title ) {
            $resolved_title = $quiz_title;
        }

        $course_id = 0;

        if ( $quiz_post_id ) {
            $course_id = Villegas_Course::get_course_from_quiz( $quiz_post_id );

            if ( ! $course_id && function_exists( 'learndash_get_course_id' ) ) {
                $course_id = (int) learndash_get_course_id( $quiz_post_id );
            }
        }

        $first_quiz_id = $course_id ? Villegas_Course::get_first_quiz_id( $course_id ) : 0;
        $final_quiz_id = $course_id ? Villegas_Course::get_final_quiz_id( $course_id ) : 0;

        $first_quiz_pro_id = $first_quiz_id ? villegas_resolve_quiz_pro_id( $first_quiz_id ) : 0;
        $final_quiz_pro_id = $final_quiz_id ? villegas_resolve_quiz_pro_id( $final_quiz_id ) : 0;

        $is_first_quiz = $quiz_id && $first_quiz_id && (int) $quiz_id === (int) $first_quiz_id;
        $is_final_quiz = $quiz_id && $final_quiz_id && (int) $quiz_id === (int) $final_quiz_id;

        $first_attempt = $first_quiz_id ? villegas_get_latest_quiz_attempt( $user_id, $first_quiz_id ) : [ 'percentage' => null, 'timestamp' => null ];

        if ( $first_quiz_pro_id ) {
            $first_attempt = villegas_merge_quiz_attempt_data(
                $first_attempt,
                villegas_get_latest_quiz_attempt( $user_id, $first_quiz_pro_id )
            );
        }

        $final_attempt = $final_quiz_id ? villegas_get_latest_quiz_attempt( $user_id, $final_quiz_id ) : [ 'percentage' => null, 'timestamp' => null ];

        if ( $final_quiz_pro_id ) {
            $final_attempt = villegas_merge_quiz_attempt_data(
                $final_attempt,
                villegas_get_latest_quiz_attempt( $user_id, $final_quiz_pro_id )
            );
        }

        if ( $first_quiz_id ) {
            $legacy_first = Villegas_Quiz_Emails::get_last_attempt_data( $user_id, $first_quiz_id );

            if ( $first_quiz_pro_id && ( 'None' === ( $legacy_first['percentage'] ?? 'None' ) || empty( $legacy_first['activity_id'] ) ) ) {
                $legacy_first_pro = Villegas_Quiz_Emails::get_last_attempt_data( $user_id, $first_quiz_pro_id );

                if ( 'None' !== ( $legacy_first_pro['percentage'] ?? 'None' ) || ! empty( $legacy_first_pro['activity_id'] ) ) {
                    $legacy_first = $legacy_first_pro;
                }
            }

            $legacy_first_percentage = villegas_normalize_percentage_value( $legacy_first['percentage'] ?? null );

            if ( null !== $legacy_first_percentage ) {
                $first_attempt['percentage'] = $legacy_first_percentage;
            }

            if ( ! empty( $legacy_first['timestamp'] ) ) {
                $first_attempt['timestamp'] = (int) $legacy_first['timestamp'];
            }
        }

        if ( $final_quiz_id ) {
            $legacy_final = Villegas_Quiz_Emails::get_last_attempt_data( $user_id, $final_quiz_id );

            if ( $final_quiz_pro_id && ( 'None' === ( $legacy_final['percentage'] ?? 'None' ) || empty( $legacy_final['activity_id'] ) ) ) {
                $legacy_final_pro = Villegas_Quiz_Emails::get_last_attempt_data( $user_id, $final_quiz_pro_id );

                if ( 'None' !== ( $legacy_final_pro['percentage'] ?? 'None' ) || ! empty( $legacy_final_pro['activity_id'] ) ) {
                    $legacy_final = $legacy_final_pro;
                }
            }

            $legacy_final_percentage = villegas_normalize_percentage_value( $legacy_final['percentage'] ?? null );

            if ( null !== $legacy_final_percentage ) {
                $final_attempt['percentage'] = $legacy_final_percentage;
            }

            if ( ! empty( $legacy_final['timestamp'] ) ) {
                $final_attempt['timestamp'] = (int) $legacy_final['timestamp'];
            }
        }

        $current_percentage = null;

        if ( array_key_exists( 'percentage', $quiz_data ) ) {
            $current_percentage = villegas_normalize_percentage_value( $quiz_data['percentage'] );
        }

        if ( null === $current_percentage && isset( $quiz_data['score'], $quiz_data['total'] ) ) {
            $total_questions = (float) $quiz_data['total'];

            if ( $total_questions > 0 ) {
                $current_percentage = ( (float) $quiz_data['score'] / $total_questions ) * 100;
            }
        }

        if ( null === $current_percentage && $quiz_post_id ) {
            $current_attempt = villegas_get_latest_quiz_attempt( $user_id, $quiz_post_id );

            if ( null !== ( $current_attempt['percentage'] ?? null ) ) {
                $current_percentage = $current_attempt['percentage'];
            }
        }

        if ( null === $current_percentage && $quiz_pro_id ) {
            $current_attempt = villegas_get_latest_quiz_attempt( $user_id, $quiz_pro_id );

            if ( null !== ( $current_attempt['percentage'] ?? null ) ) {
                $current_percentage = $current_attempt['percentage'];
            }
        }

        if ( $is_first_quiz && null !== $current_percentage ) {
            $first_attempt['percentage'] = $current_percentage;
            $first_attempt['timestamp']  = $first_attempt['timestamp'] ?: time();
        }

        if ( null === $current_percentage && $is_first_quiz ) {
            $current_percentage = villegas_normalize_percentage_value( $first_attempt['percentage'] ?? null );
        }

        if ( $is_final_quiz && null !== $current_percentage ) {
            $final_attempt['percentage'] = $current_percentage;
            $final_attempt['timestamp']  = $final_attempt['timestamp'] ?: time();
        }

        if ( $is_final_quiz && null === $current_percentage ) {
            $current_percentage = villegas_normalize_percentage_value( $final_attempt['percentage'] ?? null );
        }

        return [
            'quiz_id'             => $quiz_id,
            'quiz_post_id'        => $quiz_post_id,
            'quiz_pro_id'         => $quiz_pro_id,
            'quiz_title'          => $resolved_title,
            'course_id'           => $course_id,
            'course_title'        => $course_id ? get_the_title( $course_id ) : '',
            'first_quiz_id'       => $first_quiz_id,
            'first_quiz_pro_id'   => $first_quiz_pro_id,
            'final_quiz_id'       => $final_quiz_id,
            'final_quiz_pro_id'   => $final_quiz_pro_id,
            'is_first_quiz'       => $is_first_quiz,
            'is_final_quiz'       => $is_final_quiz,
            'first_attempt'       => $first_attempt,
            'final_attempt'       => $final_attempt,
            'user_id'             => $user_id,
            'user_display_name'   => $user->display_name,
            'user_email'          => $user->user_email,
            'current_percentage'  => $current_percentage,
        ];
    }
}

require_once plugin_dir_path( __FILE__ ) . '../emails/first-quiz-email.php';
require_once plugin_dir_path( __FILE__ ) . '../emails/final-quiz-email-template.php';

if ( ! function_exists( 'villegas_quiz_completed_handler' ) ) {
    function villegas_quiz_completed_handler( $quiz_data, $user ) {
        error_log( '[FinalQuizEmail] villegas_quiz_completed_handler START' );
        error_log( '[FinalQuizEmail] quiz_data: ' . print_r( $quiz_data, true ) );

        if ( ! ( $user instanceof WP_User ) ) {
            return;
        }

        $debug = villegas_get_quiz_debug_data( $quiz_data, $user );

        if ( $debug['is_first_quiz'] ) {
            $email = villegas_get_first_quiz_email_content( $quiz_data, $user );
        } elseif ( $debug['is_final_quiz'] ) {
            error_log( '[FinalQuizEmail] This quiz is detected as FINAL. Preparing email...' );
            $email = villegas_get_final_quiz_email_content( $quiz_data, $user );
        } else {
            return;
        }

        if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );

        if ( $admin_email && ! empty( $user->user_email ) && 0 === strcasecmp( $admin_email, $user->user_email ) ) {
            $admin_email = '';
        }

        if ( ! $admin_email ) {
            return;
        }

        if ( ! empty( $debug['is_final_quiz'] ) ) {
            error_log( '[FinalQuizEmail] About to call wp_mail() for Final Quiz email' );
            error_log( '[FinalQuizEmail] To: ' . $admin_email . ' | Subject: ' . ( $email['subject'] ?? '' ) );
        }
        $mail_sent = wp_mail(
            $admin_email,
            $email['subject'],
            $email['body'],
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );

        if ( ! empty( $debug['is_final_quiz'] ) ) {
            if ( $mail_sent ) {
                error_log( '[FinalQuizEmail] wp_mail() returned TRUE (Final Quiz email sent)' );
            } else {
                error_log( '[FinalQuizEmail] wp_mail() returned FALSE (Final Quiz email NOT sent)' );
            }
        }
    }
}
add_action( 'learndash_quiz_completed', 'villegas_quiz_completed_handler', 10, 2 );

if ( ! function_exists( 'villegas_send_first_quiz_email_handler' ) ) {
    function villegas_send_first_quiz_email_handler() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'villegas_send_first_quiz_email' ) ) {
            wp_send_json_error( 'invalid_nonce', 403 );
        }

        $quiz_id      = isset( $_POST['quiz_id'] ) ? (int) $_POST['quiz_id'] : 0;
        $user_id      = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
        $percentage   = isset( $_POST['quiz_percentage'] ) ? (float) $_POST['quiz_percentage'] : null;
        $current_user = get_current_user_id();

        if ( ! $quiz_id || ! $user_id ) {
            wp_send_json_error( 'missing_parameters', 400 );
        }

        if ( $current_user && $current_user !== $user_id && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'forbidden', 403 );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'not_logged_in', 403 );
        }

        $user = get_userdata( $user_id );

        if ( ! $user ) {
            wp_send_json_error( 'user_not_found', 404 );
        }

        $quiz_data = [
            'quiz'       => $quiz_id,
            'percentage' => $percentage,
        ];

        $email = villegas_get_first_quiz_email_content( $quiz_data, $user );

        if ( empty( $email['subject'] ) || empty( $email['body'] ) ) {
            wp_send_json_error( 'empty_email', 500 );
        }

        $sent = wp_mail(
            $user->user_email,
            $email['subject'],
            $email['body'],
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );

        if ( $sent ) {
            wp_send_json_success( 'email_sent' );
        }

        wp_send_json_error( 'mail_failed', 500 );
    }
}
add_action( 'wp_ajax_villegas_send_first_quiz_email', 'villegas_send_first_quiz_email_handler' );
add_action( 'wp_ajax_nopriv_villegas_send_first_quiz_email', 'villegas_send_first_quiz_email_handler' );
