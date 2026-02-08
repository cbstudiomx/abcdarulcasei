<?php
class eventer_stage2_generate_shortcode {
    function __construct() {
        add_shortcode( 'eventer_grid', array( $this, 'eventer_grid_style_stage2' ) );
        add_shortcode( 'eventer_list', array( $this, 'eventer_list_style_stage2' ) );
        add_shortcode( 'eventer_slider', array( $this, 'eventer_slider_style_stage2' ) );
        add_filter( 'eventer_get_prepared_data_list', array( $this, 'eventer_prepare_list_data' ), 10, 4 );
    }

    function eventer_prepare_list_data( $parameters = array(), $key = '', $value = '', $settings = array() ) {
        if ( empty( $value ) || empty( $settings ) ) return array();
        $parameters[ 'date' ] = $key;
        $set_date = $key;
        $featured_class = $multi_day = '';
        if ( !is_array( $value ) && $settings[ 'featured' ] ) {
            $featured_class = $settings[ 'featured' ];
        }
        $featured_span = ( empty( $featured_class ) ) ? '' : '<span class="eventer-featured-label eventer-status-featured eventer-status-badge"><i class="eventer-icon-star"></i> ' . esc_html__( 'Featured', 'eventer' ) . '</span>';
        $direct_url = get_post_meta( $value, 'eventer_event_custom_permalink', true );
        $event_permalink_target = ( !is_array( $value ) ) ? get_post_meta( $value, 'eventer_event_custom_permalink_target', true ) : '';
        if ( $direct_url != '' ) {
            $parameters[ 'target' ] = $event_permalink_target;
        } else {
            $parameters[ 'target' ] = '_self';
        }
        $parameters[ 'featured_span' ] = $featured_span;
        $parameters[ 'organizer' ] = '';
        $parameters[ 'organizer_link' ] = '';
        $parameters[ 'featured_class' ] = $featured_class;

        if ( !is_array( $value ) && has_excerpt( $value ) ) {
            $parameters[ 'excerpt' ] = get_the_excerpt( $value );
        } elseif ( !is_array( $value ) ) {
            $parameters[ 'excerpt' ] = '';
        } else {
            $parameters[ 'excerpt' ] = $value[ 'desc' ];
        }
        $parameters[ 'eventer' ] = ( !is_array( $value ) ) ? $value : '';
        $original_event = eventer_wpml_original_post_id( $value );
        $title_data_passed[ 'event_cdate' ] = strtotime( $key );
        $title_data_passed[ 'all_dates' ] = get_post_meta( $value, 'eventer_all_dates', true );
        $title_data_passed[ 'booked_tickets' ] = eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array(), 2 );
        $parameters[ 'event' ] = ( !is_array( $value ) ) ? apply_filters( 'eventer_styled_listing_title', $title = '', $value, $title_data_passed ) : $value[ 'title' ];
        $parameters[ 'event_title' ] = ( !is_array( $value ) ) ? get_the_title( $value ) : $value[ 'title' ];
        $locations = ( !is_array( $value ) ) ? eventer_get_terms_front( 'eventer-venue', $value, array( 'venue_address' ) ) : '';
        $categories = ( !is_array( $value ) ) ? eventer_get_terms_front( 'eventer-category', $value, array( 'category_color' ) ) : '';
        $organizers = ( !is_array( $value ) ) ? eventer_get_terms_front( 'eventer-organizer', $value, array( 'organizer_phone', 'organizer_email', 'organizer_website' ) ) : '';
        $parameters[ 'color' ] = ( !empty( $categories ) && isset( $categories[ 0 ][ 'metas' ][ 'category_color' ] ) ) ? $categories[ 0 ][ 'metas' ][ 'category_color' ] : '';
        $venue_address = ( !empty( $locations ) && $settings[ 'venue' ] != 'no' && $settings[ 'venue' ] == '' ) ? $locations[ 0 ][ 'metas' ][ 'venue_address' ] : '';
        $parameters[ 'addresses' ] = [];
        if ( !empty( $locations ) && $venue_address == 'name' ) {
            $venue_address = $locations[ 0 ][ 'name' ];
            foreach ( $locations as $loc ) {
                $parameters[ 'addresses' ][] = $loc[ 'name' ];
            }
        }
        if ( !empty( $organizers ) ) {
            $parameters[ 'organizer' ] = ( isset( $organizers[ 0 ] ) ) ? $organizers[ 0 ][ 'name' ] : '';
            $parameters[ 'organizer_link' ] = ( isset( $organizers[ 0 ] ) ) ? get_term_link( $organizers[ 0 ][ 'slug' ], 'eventer-organizer' ) : '';
        }
        if ( $settings[ 'venue' ] == 'no' ) {
            $parameters[ 'address' ] = '';
        } else {
            $parameters[ 'address' ] = $venue_address;
        }
        $event_ymd = date_i18n( 'Y-m-d', strtotime( $key ) );
        $eventer_url = ( !is_array( $value ) ) ? eventer_generate_endpoint_url( 'edate', $event_ymd, get_permalink( $value ) ) : $value[ 'link' ];
        $parameters[ 'raw_url' ] = $eventer_url;
        $eventer_url = apply_filters( 'eventer_permalink_setup', '', $eventer_url, $value );

        $eventer_url = ( $direct_url ) ? $direct_url : $eventer_url;
        $parameters[ 'details' ] = $eventer_url;
        $event_reg_custom_url = ( !is_array( $value ) ) ? get_post_meta( $value, 'eventer_event_custom_registration_url', true ) : '';
        $event_reg_custom_target = ( !is_array( $value ) ) ? get_post_meta( $value, 'eventer_event_registration_target', true ) : '';
        $virtual_status = ( !is_array( $value ) ) ? get_post_meta( $value, 'eventer_event_virtual', true ) : '';
        $event_new_status = ( !is_array( $value ) ) ? get_post_meta( $value, 'eventer_event_custom_status', true ) : '';
        if ( $event_reg_custom_url != '' ) {
            $parameters[ 'regcustom' ] = $event_reg_custom_url;
        } else {
            $parameters[ 'regcustom' ] = $eventer_url;
        }
        if ( $event_reg_custom_target != '' ) {
            $parameters[ 'regcustomtarget' ] = $event_reg_custom_target;
        } else {
            $parameters[ 'regcustomtarget' ] = '_self';
        }
        $parameters[ 'registration' ] = ( !is_array( $value ) ) ? get_post_meta( $value, 'eventer_event_registration_swtich', true ) : '';
        if ( $virtual_status ) {
            $parameters[ 'virtual' ] = 'mark_virtual';
        }
        if ( $event_new_status != '' ) {
            $parameters[ 'new_status' ] = $event_new_status;
        } else {
            $parameters[ 'new_status' ] = '';
        }

        $parameters[ 'event_id' ] = $value;
        
        return $parameters;
    }

    function eventer_calendar_tabs_data( $status, $get, $date ) {
        switch ( $status ) {
            case 'monthly':
                $set_cal = 'month';
                break;
            case 'yearly':
                $set_cal = 'year';
                break;
            case 'weekly':
                $set_cal = 'week';
                break;
            case 'daily':
                $set_cal = 'day';
                break;
        }
        if ( $get == 'current' ) {
            if ( $status == 'monthly' && $get = "current" ) {
                return date_i18n( 'Y-m' );
            } elseif ( $status == 'yearly' && $get = "current" ) {
                return date_i18n( 'Y' );
            } elseif ( $status == 'daily' && $get = "current" ) {
                return date_i18n( 'Y-m-d' );
            } elseif ( $status == 'weekly' && $get = "current" ) {
                return date_i18n( 'Y-m-d', strtotime( 'last Sunday', date_i18n( 'U' ) ) );
            }
        } elseif ( $get == 'prev' ) {
            if ( $set_cal == 'week' ) {
                $set_cal = 'sunday';
            }

            return date_i18n( 'Y-m-d', strtotime( 'last ' . $set_cal, strtotime( $date ) ) );
        } elseif ( $get == 'next' ) {
            if ( $set_cal == 'week' ) {
                $set_cal = 'saturday';
            }
            return date_i18n( 'Y-m-d', strtotime( 'next ' . $set_cal, strtotime( $date ) ) );
        } elseif ( $get == 'format' ) {
            if ( $status == 'monthly' ) {
                return date_i18n( 'Y-m-01', strtotime( $date ) );
            } elseif ( $status == 'yearly' ) {
                return date_i18n( 'Y-01-01', strtotime( $date ) );
            } elseif ( $status == 'daily' ) {
                return date_i18n( 'Y-m-d', strtotime( $date ) );
            } elseif ( $status == 'weekly' ) {
                return date_i18n( 'Y-m-d', strtotime( $date ) );
            }
        }
    }

    function eventer_filters_view( $link = '', $status = '', $series = '' ) {
        $link = ( $link == '' ) ? $_SERVER[ 'QUERY_STRING' ] : $link;
        $event_url = eventer_query_to_array( $link );
        //code added for weekly view
        $jumped_date = ( isset( $event_url[ 'jump_date' ] ) ) ? $event_url[ 'jump_date' ] : '';
        $todays_date = date_i18n( 'd' );
        $todays_date = ( strlen( $jumped_date ) > 7 ) ? date_i18n( 'd', strtotime( $jumped_date ) ) : $todays_date;
        $status = ( isset( $event_url[ 'calview' ] ) ) ? $event_url[ 'calview' ] : $status;

        $jumped_date = ( $status == 'weekly' ) ? date_i18n( 'Y-m-' . $todays_date, strtotime( $jumped_date ) ) : $jumped_date;
        //Ending here
        $set_date_status = $this->eventer_calendar_tabs_data( $status, 'current', "" );

        $status_date = ( $jumped_date != '' ) ? $jumped_date : $set_date_status;
        $status_date_prev = $status_date_next = $status_date;
        if ( $status == 'weekly' ) {
            $status_date_prev = date_i18n( 'Y-m-d', strtotime( 'last Sunday', strtotime( $status_date ) ) );
            $status_date_next = date_i18n( 'Y-m-d', strtotime( 'next saturday', strtotime( $status_date_prev ) ) );
        }

        $prev_date_full = $this->eventer_calendar_tabs_data( $status, "prev", $status_date );
        $prev_date_set = $this->eventer_calendar_tabs_data( $status, "format", $prev_date_full );


        $next_date_full = $this->eventer_calendar_tabs_data( $status, "next", $status_date );
        $next_date_set = $this->eventer_calendar_tabs_data( $status, "format", $next_date_full );
        $parameters[ 'jump_date' ] = $status_date;
        $parameters[ 'calview' ] = $status;
        $parameters[ 'prev' ] = $prev_date_set;
        $parameters[ 'next' ] = $next_date_set;
        $parameters[ 'current' ] = $status_date;
        $parameters[ 'span' ] = '';
        if ( $status == 'monthly' ) {
            $parameters[ 'span' ] = date_i18n( 'Y', strtotime( $status_date ) );
            $parameters[ 'view' ] = date_i18n( 'F', strtotime( $status_date ) );
            $series_status = date_i18n( 'Y-m-01', strtotime( $status_date ) ) . ',' . date_i18n( 'Y-m-t', strtotime( $status_date ) );
            $parameters[ 'steps' ] = 11;
            $parameters[ 'type' ] = 'month';
            $parameters[ 'view_format' ] = 'M';
        } elseif ( $status == 'daily' ) {
            $parameters[ 'span' ] = date_i18n( 'F', strtotime( $status_date ) );
            $parameters[ 'view' ] = date_i18n( 'l', strtotime( $status_date ) );
            $series_status = date_i18n( 'Y-m-d', strtotime( $status_date ) ) . ',' . date_i18n( 'Y-m-d', strtotime( $status_date ) );
            $parameters[ 'steps' ] = 6;
            $parameters[ 'type' ] = 'day';
            $parameters[ 'view_format' ] = 'D';
        } elseif ( $status == 'yearly' ) {
            $parameters[ 'view' ] = date_i18n( 'Y', strtotime( $status_date ) );
            $series_status = date_i18n( 'Y-01-01', strtotime( $status_date ) ) . ',' . date_i18n( 'Y-12-t', strtotime( $status_date ) );
            $parameters[ 'steps' ] = 6;
            $parameters[ 'type' ] = 'year';
            $parameters[ 'view_format' ] = 'Y';
        } elseif ( $status == 'weekly' ) {
            $parameters[ 'view' ] = date_i18n( get_option( 'date_format' ), strtotime( $status_date_prev ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $status_date_next ) );
            $series_status = date_i18n( 'Y-m-d', strtotime( $status_date_prev ) ) . ',' . date_i18n( 'Y-m-d', strtotime( $status_date_next ) );
        }
        $series_set = $series;
        if ( $series_set != '' ) {
            $date_array = ( $series_set ) ? explode( ',', $series_set ) : '';
            $parameters[ 'view' ] = date_i18n( get_option( 'date_format' ), strtotime( $date_array[ 0 ] ) ) . ' - ' . date_i18n( get_option( 'date_format' ), strtotime( $date_array[ 1 ] ) );
            $parameters[ 'span' ] = '';
        }
        $parameters[ 'max' ] = get_option( 'eventer_extreme_last_event_date' );
        $parameters[ 'min' ] = get_option( 'eventer_extreme_first_event_date' );
        return array( 'params' => $parameters, 'series_set' => $series_status );
    }

    function eventer_grid_style_stage2( $atts ) {
        $atts = shortcode_atts( array(
            'ids' => '',
            'terms_cats' => ( isset($_REQUEST['terms_cats']) ? sanitize_text_field($_REQUEST['terms_cats']) : '' ),
            'terms_tags' => '',
            'terms_venue' => '',
            'terms_organizer' => '',
            'type' => '',
            'status' => 'future',
            'lang' => EVENTER__LANGUAGE_CODE,
            'series' => '',
            'layout' => 'plain',
            'background' => '',
            'column' => '3',
            'path' => $_SERVER[ 'QUERY_STRING' ],
            'venue' => '',
            'filters' => '',
            'month_filter' => '',
            'name' => 'eventer_grid',
            'calview' => '',
            'pagination' => '',
            'operator' => '',
            'ajax' => '',
            'pass' => '',
            'carousel' => 'yes,3000,yes,yes,no',
            'occurrence' => '',
            'featured' => '',
            'count' => get_option( 'posts_per_page' ),
            'event_until' => '1',
            'pagin' => '1'
        ), $atts, 'eventer_grid' );

        if ( $atts[ 'pagination' ] != 'carousel' ) {
            wp_enqueue_style( 'eventer-owl-carousel' );
            wp_enqueue_style( 'eventer-owl-theme' );
            wp_enqueue_script( 'owl-carousel-min' );
            wp_enqueue_script( 'eventer-carousel-init' );
        }
        $date_array = $time_icon = '';
        $event_count = $atts[ 'count' ];
        $pagination = $atts[ 'pagination' ];
        $grid_output = $pagination_set = '';
        $default_date_format = get_option( 'date_format' );
        $parameters = array();
        $event_date_format = eventer_get_settings( 'eventer_date_format' );
        $event_date_format = ( $event_date_format ) ? $event_date_format : $default_date_format;
        $event_date_format_big = eventer_get_settings( 'eventer_date_format_big' );
        $event_date_format_big = ( $event_date_format_big ) ? $event_date_format_big : $default_date_format;
        $event_start_time_format = eventer_get_settings( 'start_time_format' );
        $event_start_time_format = ( $event_start_time_format ) ? $event_start_time_format : get_option( 'time_format' );
        $event_end_time_format = eventer_get_settings( 'end_time_format' );
        $event_end_time_format = ( $event_end_time_format ) ? $event_end_time_format : get_option( 'time_format' );
        $date_separator = eventer_get_settings( 'time_separator' );
        $settings = array( 'venue' => $atts[ 'venue' ] );
        $grid_class = $atts[ 'layout' ];
        if ( $atts[ 'layout' ] == 'hidden' ) {
            $grid_class = 'featured';
        } elseif ( $atts[ 'layout' ] == 'featured' ) {
            $grid_class = 'featured eventer-nhcontent';
        }
        ob_start();
        if ( $atts[ 'ajax' ] == '' ) {
            $grid_id = 'eventer-dynamic-filters-' . esc_attr( wp_rand( 1000, 999999 ) );
            echo '<div class="eventer-dynamic-list-set" id="' . $grid_id . '">';
        }
        $grid_id = 'eventer-dynamic-filters-area-121';
        echo '<div id="' . esc_attr( $grid_id ) . '" class="eventer eventer-dynamic-listings-main eventer-grid eventer-grid-' . esc_attr( $grid_class ) . ' eventer-grid-col' . esc_attr( $atts[ 'column' ] ) . '"  data-shortcode="' . esc_attr( json_encode( $atts ) ) . '">';
        if ( $atts[ 'layout' ] == 'plain' ) {
            echo '<div class="eventer-grid">';
        }
        echo '<div class="eventer-loader-wrap" style="display:none"><div class="eventer-loader"></div></div>';
        $series_status = '';
        if ( $atts[ 'month_filter' ] == '1' ) {
            echo '<div class="eventer-month-switcher-wrap">';
            if ( $atts[ 'status' ] != 'future' && $atts[ 'status' ] != 'past' ) {
                $event_url = eventer_query_to_array( $atts[ 'path' ] );
                $status = ( isset( $event_url[ 'calview' ] ) ) ? $event_url[ 'calview' ] : $atts[ 'status' ];
                $vals_filter = $this->eventer_filters_view( $atts[ 'path' ], $atts[ 'status' ], $atts[ 'series' ] );
                $new_val_filters = $vals_filter[ 'params' ];
                $new_val_filters[ 'calview_set' ] = $atts[ 'calview' ];
                $series_status = $vals_filter[ 'series_set' ];
                $event_count = 1000;
                echo '<div class="eventer-month-switcher">';
                eventer_append_template_with_arguments( 'eventers/filters/filter', "arrows", $new_val_filters );
                eventer_append_template_with_arguments( 'eventers/filters/filter', "calendar", $new_val_filters );
                eventer_append_template_with_arguments( 'eventers/filters/filter', "series", $new_val_filters );
                if ( $status != 'weekly' ) {
                    eventer_append_template_with_arguments( 'eventers/filters/filter', "tabs", $new_val_filters );
                }

                echo '</div>';
            }
            if ( $atts[ 'filters' ] != '' ) {
                $new_params = isset( $vals_filter[ 'params' ] ) ? $vals_filter[ 'params' ] : [];
                $new_params[ 'term_filters' ] = explode( ',', $atts[ 'filters' ] );
                $new_params[ 'terms_cats' ] = $atts[ 'terms_cats' ];
                $new_params[ 'terms_tags' ] = $atts[ 'terms_tags' ];
                $new_params[ 'terms_venue' ] = $atts[ 'terms_venue' ];
                $new_params[ 'terms_organizer' ] = $atts[ 'terms_organizer' ];
                echo '<div class="eventer-filter-wrap">';
                $new_params[ 'lang' ] = $atts[ 'lang' ];
                eventer_append_template_with_arguments( 'eventers/filters/filter', "terms", $new_params );
                echo '</div>';
            }

            echo '    </div>';
        }
        $series_set = $atts[ 'series' ];
        $series_set = ( $series_set == '' ) ? $series_status : $series_set;
        $date_array = ( $series_set ) ? explode( ',', $series_set ) : '';
        $date_array = ( is_array( $date_array ) && count( $date_array ) > 1 ) ? $date_array : '';
        $pagin = ( $pagination ) ? get_query_var( 'pagin' ) : 1;
        $pagin = ( $atts[ 'pagin' ] > 1 ) ? $atts[ 'pagin' ] : $pagin;
        if ( $atts[ 'operator' ] != 'or' ) {
            $event_ids = eventer_merge_all_ids( $atts[ 'ids' ], $atts[ 'terms_cats' ], $atts[ 'terms_tags' ], $atts[ 'terms_venue' ], $atts[ 'terms_organizer' ] );
        } else {
            $event_ids = eventer_merge_all_ids_or( $atts[ 'ids' ], $atts[ 'terms_cats' ], $atts[ 'terms_tags' ], $atts[ 'terms_venue' ], $atts[ 'terms_organizer' ] );
        }

        if ( is_search() ) {
            $get_eventer_grid = eventer_search_result_data( $event_ids, $atts[ 'status' ], $date_array, $pagin, $event_count, $atts[ 'type' ], $atts[ 'event_until' ], $atts[ 'pass' ] );
        } else {
            $get_eventer_grid = eventer_get_events_array( $event_ids, $atts[ 'status' ], $date_array, $pagin, $event_count, $atts[ 'type' ], $atts[ 'event_until' ], $atts[ 'lang' ], $atts[ 'pass' ], $atts[ 'occurrence' ], $atts[ 'featured' ] );
        }
        if ( $get_eventer_grid[ 'results' ] <= 0 ) {
            echo '<div class="eventer-no-event-found">';
            echo esc_html__( 'No events to display.', 'eventer' );
            echo '</div>';
            //return $no_event_msg;
        } else {
            $eventer = $get_eventer_grid[ 'events' ];
            if ( $pagination != 'carousel' ) {
                echo '<ul class="equah">';
            } else {
                $carousel_params = $atts[ 'carousel' ];
                $carousel_settings = explode( ',', $carousel_params );
                $carousel_settings = ( count( $carousel_settings ) == 5 ) ? $carousel_settings : $carousel_params;
                echo '<ul class="equah eventer-carousel" data-columns="' . esc_attr( $atts[ 'column' ] ) . '" data-columns-small-desktop="3" data-columns-tablet="2" data-columns-mobile="1" data-autoplay="' . esc_attr( $carousel_settings[ 0 ] ) . '" data-autoplay-timeout="' . esc_attr( $carousel_settings[ 1 ] ) . '" data-pagination="' . esc_attr( $carousel_settings[ 2 ] ) . '" data-arrows="' . esc_attr( $carousel_settings[ 3 ] ) . '" data-rtl="' . esc_attr( $carousel_settings[ 4 ] ) . '" data-loop="no" data-margin="30">';
            }

            $grid_attach = $atts[ 'layout' ];
            $grid_attach = ( $grid_attach == 'featured' ) ? 'hidden' : $grid_attach;
            foreach ( $eventer as $event_data ) {
                $key = $event_data[ 'start' ];
                $until_end = $event_data[ 'end' ];
                $value = $event_data[ 'id' ];
                $multi_event = $event_data[ 'multi' ];
                if ( ( $atts[ 'status' ] != 'past' ) && date_i18n( 'U' ) > strtotime( $until_end ) && $atts[ 'ajax' ] == '' && !is_array( $value ) ) continue;
                eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array() );
                $updated_tickets_new = eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array(), 2 );
                $settings[ 'featured' ] = $event_data[ 'featured' ];
                $parameters = apply_filters( 'eventer_get_prepared_data_list', array(), $key, $value, $settings );
                if ( !empty( $parameters ) ) {
                    $parameters[ 'status' ] = '';
                    $badge_switch = eventer_get_settings( 'eventer_show_badges' );
                    if ( $badge_switch == 'on' ) {
                        if ( date_i18n( 'U' ) < strtotime( $key ) ) {
                            $parameters[ 'status' ] = '<span class="eventer-status-badge eventer-status-upcoming">' . esc_attr__( ' Upcoming', 'eventer' ) . '</span>';
                        } elseif ( date_i18n( 'U' ) < strtotime( $until_end ) && date_i18n( 'U' ) > strtotime( $key ) ) {
                            $parameters[ 'status' ] = '<span class="eventer-status-badge eventer-status-ongoing">' . esc_attr__( ' Ongoing', 'eventer' ) . '</span>';
                        } else {
                            $parameters[ 'status' ] = '<span class="eventer-status-badge eventer-status-passed">' . esc_attr__( ' Expired', 'eventer' ) . '</span>';
                        }
                    }
                    $parameters[ 'tickets' ] = $updated_tickets_new;
                    $parameters[ 'background' ] = $atts[ 'background' ];
                    $parameters[ 'multi' ] = $multi_event;
                    $parameters[ 'end' ] = $event_data[ 'end' ];

                    $parameters[ 'default_date_format' ] = $default_date_format;
                    $allday = ( isset( $event_data[ 'allday' ] ) && $event_data[ 'allday' ] != '' ) ? $event_data[ 'allday' ] : '';
                    $parameters[ 'allday' ] = $allday;
                    $parameters[ 'show_time' ] = ( $allday == '' ) ? date_i18n( $event_start_time_format, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_end_time_format, strtotime( $event_data[ 'end' ] ) ): $allday;
                    $parameters[ 'raw_start' ] = $event_data[ 'start' ];
                    $parameters[ 'raw_end' ] = $event_data[ 'end' ];
                    if ( $event_data[ 'multi' ] != '1' ) {
                        $parameters[ 'show_date' ] = date_i18n( $event_date_format, strtotime( $event_data[ 'start' ] ) );
                    } elseif ( $allday == '' ) {
                        $parameters[ 'show_date' ] = date_i18n( $event_date_format_big, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_date_format_big, strtotime( $event_data[ 'end' ] ) );
                        $parameters[ 'show_time' ] = date_i18n( get_option( 'time_format' ), strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $event_data[ 'end' ] ) );
                    } else {
                        $parameters[ 'show_date' ] = date_i18n( $event_date_format_big, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_date_format_big, strtotime( $event_data[ 'end' ] ) );
                        $parameters[ 'show_time' ] = $allday;
                    }

                    eventer_append_template_with_arguments( 'eventers/grid/grid', $grid_attach, $parameters );
                }
            }
            if ( $pagination == 'yes' && $get_eventer_grid[ 'results' ] >= 0 ) {
                $total_results = $get_eventer_grid[ 'results' ];
                $current_page_count = $event_count;
                $pages_pagination = $total_results / $current_page_count;
                $pages_pagination_floor = floor( $pages_pagination );
                $pages = ( $pages_pagination > $pages_pagination_floor ) ? $pages_pagination_floor + 1 : $pages_pagination_floor;
                $pagin_paged = ( get_query_var( 'pagin' ) ) ? get_query_var( 'pagin' ) : 1;
                $pagin_paged = ( $atts[ 'pagin' ] > 1 ) ? $atts[ 'pagin' ] : $pagin_paged;
                $pagination_set = eventer_pagination( $pages, $pagin_paged, 4, '#' . $grid_id );
            }
            echo '</ul>';
        }

        echo $pagination_set;
        if ( $atts[ 'layout' ] == 'plain' ) {
            echo '</div>';
        }
        echo '</div>';
        if ( $atts[ 'ajax' ] == '' ) {
            echo '</div>';
        }
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    function eventer_list_style_stage2( $atts ) {
        $atts = shortcode_atts( array(
            'ids' => '',
            'terms_cats' => ( isset($_REQUEST['terms_cats']) ? sanitize_text_field($_REQUEST['terms_cats']) : '' ),
            'terms_tags' => ( isset( $_REQUEST[ 'terms_tags' ] ) ) ? $_REQUEST[ 'terms_tags' ] : '',
            'terms_venue' => ( isset( $_REQUEST[ 'terms_venue' ] ) ) ? $_REQUEST[ 'terms_venue' ] : '',
            'terms_organizer' => ( isset( $_REQUEST[ 'terms_organizer' ] ) ) ? $_REQUEST[ 'terms_organizer' ] : '',
            'type' => '',
            'status' => 'future',
            'lang' => EVENTER__LANGUAGE_CODE,
            'series' => '',
            'view' => 'compact',
            'background' => '',
            'column' => '3',
            'path' => $_SERVER[ 'QUERY_STRING' ],
            'venue' => '',
            'filters' => '',
            'month_filter' => '',
            'name' => 'eventer_list',
            'calview' => '',
            'pagination' => '',
            'operator' => '',
            'ajax' => '',
            'pass' => '',
            'occurrence' => '',
            'featured' => '',
            'count' => get_option( 'posts_per_page' ),
            'event_until' => '1',
        ), $atts, 'eventer_list' );

        $date_array = $time_icon = '';
        $event_count = ( empty( $atts[ 'count' ] ) ) ? get_option( 'posts_per_page' ) : $atts[ 'count' ];
        $pagination = $atts[ 'pagination' ];
        $grid_output = $pagination_set = '';
        $default_date_format = get_option( 'date_format' );
        $parameters = array();
        $event_date_format = eventer_get_settings( 'eventer_date_format' );
        $event_date_format = ( $event_date_format ) ? $event_date_format : $default_date_format;
        $event_date_format_big = eventer_get_settings( 'eventer_date_format_big' );
        $event_date_format_big = ( $event_date_format_big ) ? $event_date_format_big : $default_date_format;
        $event_start_time_format = eventer_get_settings( 'start_time_format' );
        $event_start_time_format = ( $event_start_time_format ) ? $event_start_time_format : get_option( 'time_format' );
        $event_end_time_format = eventer_get_settings( 'end_time_format' );
        $event_end_time_format = ( $event_end_time_format ) ? $event_end_time_format : get_option( 'time_format' );
        $date_separator = eventer_get_settings( 'time_separator' );
        $settings = array( 'date_format' => $event_date_format, 'date_format_big' => $event_date_format_big, 'start_time_format' => $event_start_time_format, 'end_time_format' => $event_end_time_format, 'time_separator' => $date_separator, 'venue' => $atts[ 'venue' ] );
        ob_start();
        if ( $atts[ 'ajax' ] == '' ) {
            echo '<div class="eventer-dynamic-list-set" id="eventer-dynamic-filters-' . esc_attr( wp_rand( 1000, 999999 ) ) . '">';
        }
        $view = isset($atts['view']) ? sanitize_title($atts['view']) : 'compact';
        echo '<div class="eventer eventer-dynamic-listings-main eventer-list eventer-' . esc_attr($view) . '-list" data-shortcode="' . esc_attr(json_encode($atts)) . '">';
        echo '<div class="eventer-loader-wrap" style="display:none"><div class="eventer-loader"></div></div>';
        $series_status = '';
        if ( $atts[ 'status' ] != 'future' && $atts[ 'status' ] != 'past' && $atts[ 'month_filter' ] == '1' ) {
            $event_url = eventer_query_to_array( $atts[ 'path' ] );
            $status = ( isset( $event_url[ 'calview' ] ) ) ? $event_url[ 'calview' ] : $atts[ 'status' ];
            $vals_filter = $this->eventer_filters_view( $atts[ 'path' ], $atts[ 'status' ], $atts[ 'series' ] );
            $new_val_filters = $vals_filter[ 'params' ];
            $new_val_filters[ 'calview_set' ] = $atts[ 'calview' ];
            $series_status = $vals_filter[ 'series_set' ];
            $event_count = 1000;
            echo '<div class="eventer-month-switcher-wrap">
                <div class="eventer-month-switcher">';
            eventer_append_template_with_arguments( 'eventers/filters/filter', "arrows", $new_val_filters );
            eventer_append_template_with_arguments( 'eventers/filters/filter', "calendar", $new_val_filters );
            eventer_append_template_with_arguments( 'eventers/filters/filter', "series", $new_val_filters );
            if ( $status != 'weekly' ) {
                eventer_append_template_with_arguments( 'eventers/filters/filter', "tabs", $new_val_filters );
            }

            echo '</div>';
            if ( $atts[ 'filters' ] != '' ) {
                $new_params = $vals_filter[ 'params' ];
                $new_params[ 'term_filters' ] = explode( ',', $atts[ 'filters' ] );
                $new_params[ 'terms_cats' ] = $atts[ 'terms_cats' ];
                $new_params[ 'terms_tags' ] = $atts[ 'terms_tags' ];
                $new_params[ 'terms_venue' ] = $atts[ 'terms_venue' ];
                $new_params[ 'terms_organizer' ] = $atts[ 'terms_organizer' ];
                echo '<div class="eventer-filter-wrap">';
                $new_params[ 'lang' ] = $atts[ 'lang' ];
                eventer_append_template_with_arguments( 'eventers/filters/filter', "terms", $new_params );
                echo '</div>';
            }

            echo '</div>';
        }
        $series_set = $atts[ 'series' ];
        $series_set = ( $series_set == '' ) ? $series_status : $series_set;
        $date_array = ( $series_set ) ? explode( ',', $series_set ) : '';
        $date_array = ( is_array( $date_array ) && count( $date_array ) > 1 ) ? $date_array : '';
        $pagin = ( $pagination ) ? get_query_var( 'pagin' ) : 1;

        if ( $atts[ 'operator' ] != 'or' ) {
            $event_ids = eventer_merge_all_ids( $atts[ 'ids' ], $atts[ 'terms_cats' ], $atts[ 'terms_tags' ], $atts[ 'terms_venue' ], $atts[ 'terms_organizer' ] );
        } else {
            $event_ids = eventer_merge_all_ids_or( $atts[ 'ids' ], $atts[ 'terms_cats' ], $atts[ 'terms_tags' ], $atts[ 'terms_venue' ], $atts[ 'terms_organizer' ] );
        }


        if ( is_search() ) {
            $get_eventer_list = eventer_search_result_data( $event_ids, $atts[ 'status' ], $date_array, $pagin, $event_count, $atts[ 'type' ], $atts[ 'event_until' ], $atts[ 'pass' ] );
        } else {
            $get_eventer_list = eventer_get_events_array( $event_ids, $atts[ 'status' ], $date_array, $pagin, $event_count, $atts[ 'type' ], $atts[ 'event_until' ], $atts[ 'lang' ], $atts[ 'pass' ], $atts[ 'occurrence' ], $atts[ 'featured' ] );
        }
        if ( $get_eventer_list[ 'results' ] <= 0 ) {
            echo '<div class="eventer-no-event-found">';
            echo esc_html__( 'No events to display.', 'eventer' );
            echo '</div>';
            //return $no_event_msg;
        } else {
            $eventer = $get_eventer_list[ 'events' ];
            echo '<ul>';

            $list_attach = $atts[ 'view' ];
            foreach ( $eventer as $event_data ) {
                $key = $event_data[ 'start' ];
                $until_end = $event_data[ 'end' ];
                $value = $event_data[ 'id' ];
                $multi_event = $event_data[ 'multi' ];
                if ( ( $atts[ 'status' ] != 'past' ) && date_i18n( 'U' ) > strtotime( $until_end ) && $atts[ 'ajax' ] == '' && !is_array( $value ) ) continue;
                eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array() );
                $updated_tickets_new = eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array(), 2 );
                $settings[ 'featured' ] = $event_data[ 'featured' ];
                $parameters = apply_filters( 'eventer_get_prepared_data_list', array(), $key, $value, $settings );
                if ( !empty( $parameters ) ) {

                    $parameters[ 'tickets' ] = $updated_tickets_new;
                    $parameters[ 'background' ] = $atts[ 'background' ];
                    $parameters[ 'multi' ] = $multi_event;
                    $parameters[ 'end' ] = $event_data[ 'end' ];
                    $parameters[ 'start' ] = $event_data[ 'start' ];
                    $parameters[ 'default_date_format' ] = $default_date_format;
                    $allday = ( isset( $event_data[ 'allday' ] ) && $event_data[ 'allday' ] != '' ) ? $event_data[ 'allday' ] : '';
                    $allday_code = ( isset( $event_data[ 'allday_code' ] ) && $event_data[ 'allday_code' ] != '' ) ? $event_data[ 'allday_code' ] : '';
                    $parameters[ 'status' ] = '';

                    $badge_switch = eventer_get_settings( 'eventer_show_badges' );
                    if ( $badge_switch == 'on' ) {
                        if ( date_i18n( 'U' ) < strtotime( $key ) ) {
                            $parameters[ 'status' ] = '<span class="eventer-status-badge eventer-status-upcoming">' . esc_attr__( ' Upcoming', 'eventer' ) . '</span>';
                        } elseif ( date_i18n( 'U' ) < strtotime( $until_end ) && date_i18n( 'U' ) > strtotime( $key ) ) {
                            $parameters[ 'status' ] = '<span class="eventer-status-badge eventer-status-ongoing">' . esc_attr__( ' Ongoing', 'eventer' ) . '</span>';
                        } else {
                            $parameters[ 'status' ] = '<span class="eventer-status-badge eventer-status-passed">' . esc_attr__( ' Expired', 'eventer' ) . '</span>';
                        }
                    }
                    $parameters[ 'allday' ] = $allday;
                    $parameters[ 'show_time' ] = ( $allday == '' ) ? date_i18n( $event_start_time_format, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_end_time_format, strtotime( $event_data[ 'end' ] ) ): $allday;
                    if ( $event_data[ 'multi' ] != '1' ) {
                        $parameters[ 'show_date' ] = date_i18n( $event_date_format, strtotime( $event_data[ 'start' ] ) );
                    } elseif ( $allday_code == '1' ) {
                        $parameters[ 'show_date' ] = date_i18n( $event_date_format_big, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_date_format_big, strtotime( $event_data[ 'end' ] ) );
                    } else {
                        $parameters[ 'show_date' ] = date_i18n( $event_date_format_big, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_date_format_big, strtotime( $event_data[ 'end' ] ) );
                        $parameters[ 'show_time' ] = date_i18n( get_option( 'time_format' ), strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $event_data[ 'end' ] ) );
                    }
                    eventer_append_template_with_arguments( 'eventers/list/list', $list_attach, $parameters );
                }
            }
            if ( $pagination == 'yes' && $get_eventer_list[ 'results' ] >= 0 ) {
                $total_results = $get_eventer_list[ 'results' ];
                $current_page_count = $event_count;
                $pages_pagination = $total_results / $current_page_count;
                $pages_pagination_floor = floor( $pages_pagination );
                $pages = ( $pages_pagination > $pages_pagination_floor ) ? $pages_pagination_floor + 1 : $pages_pagination_floor;
                $pagin_paged = ( get_query_var( 'pagin' ) ) ? get_query_var( 'pagin' ) : 1;
                $pagination_set = eventer_pagination( $pages, $pagin_paged );
            }
            echo '</ul>';
        }

        echo $pagination_set;
        echo '</div>';
        if ( $atts[ 'ajax' ] == '' ) {
            echo '</div>';
        }
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    function eventer_slider_style_stage2( $atts ) {
        $atts = shortcode_atts( array(
            'ids' => '',
            'terms_cats' => ( isset( $_REQUEST[ 'terms_cats' ] ) ) ? $_REQUEST[ 'terms_cats' ] : '',
            'terms_tags' => ( isset( $_REQUEST[ 'terms_tags' ] ) ) ? $_REQUEST[ 'terms_tags' ] : '',
            'terms_venue' => ( isset( $_REQUEST[ 'terms_venue' ] ) ) ? $_REQUEST[ 'terms_venue' ] : '',
            'terms_organizer' => ( isset( $_REQUEST[ 'terms_organizer' ] ) ) ? $_REQUEST[ 'terms_organizer' ] : '',
            'operator' => '',
            'layout' => 'type1',
            'carousel' => 'yes,3000,yes,yes,no',
            'count' => get_option( 'posts_per_page' ),
        ), $atts, 'eventer_slider' );

        wp_enqueue_style( 'eventer-owl-carousel' );
        wp_enqueue_style( 'eventer-owl-theme' );
        wp_enqueue_script( 'owl-carousel-min' );
        wp_enqueue_script( 'eventer-carousel-init' );

        $date_array = $time_icon = '';
        $event_count = $atts[ 'count' ];
        $grid_output = $pagination_set = '';
        $event_date_format = eventer_get_settings( 'eventer_date_format' );
        $event_date_format = ( $event_date_format ) ? $event_date_format : get_option( 'date_format' );
        $event_date_format_big = eventer_get_settings( 'eventer_date_format_big' );
        $event_date_format_big = ( $event_date_format_big ) ? $event_date_format_big : get_option( 'date_format' );
        $event_start_time_format = eventer_get_settings( 'start_time_format' );
        $event_start_time_format = ( $event_start_time_format ) ? $event_start_time_format : get_option( 'time_format' );
        $event_end_time_format = eventer_get_settings( 'end_time_format' );
        $event_end_time_format = ( $event_end_time_format ) ? $event_end_time_format : get_option( 'time_format' );
        $date_separator = eventer_get_settings( 'time_separator' );
        $settings = array( 'date_format' => $event_date_format, 'date_format_big' => $event_date_format_big, 'start_time_format' => $event_start_time_format, 'end_time_format' => $event_end_time_format, 'time_separator' => $date_separator, 'venue' => '' );
        $parameters = array();
        $list_attach = $list_attach_class = $atts[ 'layout' ];
        if ( $list_attach == 'type3' ) {
            $list_attach = 'type2';
            $list_attach_class = 'type2 eventer-slider-type3';
        }
        $carousel_params = $atts[ 'carousel' ];
        $carousel_settings = explode( ',', $carousel_params );
        $carousel_settings = ( count( $carousel_settings ) == 5 ) ? $carousel_settings : $carousel_params;
        ob_start();
        echo '<div class="eventer-carousel-wrapper">
                <div class="eventer eventer-event-slider eventer-slider-' . esc_attr( $list_attach_class ) . '">
                    <ul class="eventer-carousel" data-columns="1" data-columns-small-desktop="1" data-columns-tablet="1" data-columns-mobile="1" data-autoplay="' . esc_attr( $carousel_settings[ 0 ] ) . '" data-autoplay-timeout="' . esc_attr( $carousel_settings[ 1 ] ) . '" data-pagination="' . esc_attr( $carousel_settings[ 2 ] ) . '" data-arrows="' . esc_attr( $carousel_settings[ 3 ] ) . '" data-rtl="' . esc_attr( $carousel_settings[ 4 ] ) . '" data-loop="yes" data-margin="0" data-auto-height="yes">';

        if ( $atts[ 'operator' ] != 'or' ) {
            $event_ids = eventer_merge_all_ids( $atts[ 'ids' ], $atts[ 'terms_cats' ], $atts[ 'terms_tags' ], $atts[ 'terms_venue' ], $atts[ 'terms_organizer' ] );
        } else {
            $event_ids = eventer_merge_all_ids_or( $atts[ 'ids' ], $atts[ 'terms_cats' ], $atts[ 'terms_tags' ], $atts[ 'terms_venue' ], $atts[ 'terms_organizer' ] );
        }

        $get_eventer_list = eventer_get_events_array( $event_ids, 'future', $date_array, 1, $event_count, '1', '1', EVENTER__LANGUAGE_CODE, '0', '', '' );
        $eventer = $get_eventer_list[ 'events' ];

        foreach ( $eventer as $event_data ) {
            $key = $event_data[ 'start' ];
            $value = $event_data[ 'id' ];
            $multi_event = $event_data[ 'multi' ];
            eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array() );
            $updated_tickets_new = eventer_update_date_wise_bookings_table( $value, date_i18n( 'Y-m-d 00:00:00', strtotime( $key ) ), array(), 2 );
            $settings[ 'featured' ] = $event_data[ 'featured' ];
            $parameters = apply_filters( 'eventer_get_prepared_data_list', array(), $key, $value, $settings );
            $parameters[ 'multi' ] = $multi_event;
            $parameters[ 'end' ] = $event_data[ 'end' ];
            $parameters[ 'start' ] = $event_data[ 'start' ];
            $parameters[ 'show_time' ] = date_i18n( $event_start_time_format, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_end_time_format, strtotime( $event_data[ 'end' ] ) );
            if ( $event_data[ 'multi' ] != '1' ) {
                $parameters[ 'show_date' ] = date_i18n( $event_date_format, strtotime( $event_data[ 'start' ] ) );
            } else {
                $parameters[ 'show_date' ] = date_i18n( $event_date_format_big, strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( $event_date_format_big, strtotime( $event_data[ 'end' ] ) );
                $parameters[ 'show_time' ] = date_i18n( get_option( 'time_format' ), strtotime( $event_data[ 'start' ] ) ) . ' ' . $date_separator . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $event_data[ 'end' ] ) );
            }
            if ( !empty( $parameters ) ) {
                $parameters[ 'tickets' ] = $updated_tickets_new;
                eventer_append_template_with_arguments( 'eventers/slider/slider', $list_attach, $parameters );
            }
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}

function eventer_initialize_shortcodes() {
    new eventer_stage2_generate_shortcode;
}
add_action( 'init', 'eventer_initialize_shortcodes' );