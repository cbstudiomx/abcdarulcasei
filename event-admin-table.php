<?php
/**
 * Plugin Name: Event Admin Table
 * Description: WP_List_Table + frontend display (shortcodes & blocks)
 * Version: 1.2.0
 */

defined('ABSPATH') || exit;

define('EAT_OPTION_CPT', 'eat_target_cpt');
define('EAT_VER', '1.2.0');
define('EAT_OPTION_EMPTY_MESSAGE', 'eat_empty_frontend_message');


/* ======================================================
 * ACTIVARE
 * ====================================================== */

register_activation_hook(__FILE__, function () {
    add_option(EAT_OPTION_CPT, 'spectacole');
    add_option('eat_layout', 'list');
    add_option('eat_heading', 'h3');
    add_option('eat_address_tag', 'p');
    add_option(
    EAT_OPTION_EMPTY_MESSAGE,
    '<h3>Vrei la spectacol?</h3>
<p>Teatrul Roșu poate organiza evenimente private / exclusiviste, atât în sala proprie, cât și în deplasare.</p>
<p>Pentru informații, vă rugăm să ne contactați la <a href="tel:0723196376">0723 196 376</a> sau <a href="mailto:teatrulrosu@yahoo.com">teatrulrosu@yahoo.com</a>.</p>'
);

});

/* ======================================================
 * Enqueue CSS si JS
 * ====================================================== */

add_action('wp_enqueue_scripts', function () {

    wp_register_style(
        'eat-event-occurrences',
        plugin_dir_url(__FILE__) . 'assets/css/event-occurrences.css',
        [],
        EAT_VER
    );

});

/* ======================================================
 * Filtre Woo
 * ====================================================== */
 
 if (class_exists('WooCommerce')) {
 
add_action('woocommerce_before_add_to_cart_button', function () {

    global $eat_current_occurrence;

    if (empty($eat_current_occurrence)) {
        return;
    }

    foreach ($eat_current_occurrence as $key => $value) {
        echo '<input type="hidden" name="eat_event_' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
    }

});

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {

    foreach (['date','time','loc','addr'] as $field) {
        if (!empty($_POST['eat_event_' . $field])) {
            $cart_item_data['eat_event_' . $field] =
                sanitize_text_field($_POST['eat_event_' . $field]);
        }
    }

    $cart_item_data['unique_key'] = md5(microtime() . rand());
    return $cart_item_data;

}, 10, 2);


}

/* ======================================================
 * HELPER
 * ====================================================== */

function eat_format_date($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date_i18n(get_option('date_format'), $ts) : $date;
}

function eat_get_first_future_occurrence_timestamp($post_id) {
    $today = date('Y-m-d');
    $dates = [];

    $occ = maybe_unserialize(get_post_meta($post_id, '_lre_occurrence_overrides', true));
    if (is_array($occ)) {
        foreach ($occ as $data) {
            if (!empty($data['data_turneu']) && $data['data_turneu'] >= $today) {
                $dates[] = strtotime($data['data_turneu']);
            }
        }
    }

    $default = get_post_meta($post_id, 'data_turneu', true);
    if ($default && $default >= $today) {
        $dates[] = strtotime($default);
    }

    if (!$dates) return 0;
    sort($dates);
    return $dates[0];
}


/* ======================================================
 * PAGINĂ SETĂRI
 * ====================================================== */
 
add_action('admin_menu', function () {
    add_options_page(
        'Event Admin Table',
        'Event Admin Table',
        'manage_options',
        'event-admin-table-settings',
        'eat_render_settings_page'
    );
});

function eat_render_settings_page() {

    if (
        isset($_POST['eat_submit']) &&
        current_user_can('manage_options') &&
        check_admin_referer('eat_settings_save')
    ) {
        update_option(EAT_OPTION_CPT, sanitize_key($_POST['eat_cpt']));
        update_option('eat_layout', sanitize_text_field($_POST['eat_layout']));
        update_option('eat_heading', sanitize_text_field($_POST['eat_heading']));
        update_option('eat_address_tag', sanitize_text_field($_POST['eat_address_tag']));
        if ( isset($_POST['eat_empty_message']) ) {
	update_option(
		EAT_OPTION_EMPTY_MESSAGE,
		wp_kses_post($_POST['eat_empty_message'])
	);
}


        echo '<div class="updated notice"><p>Setări salvate.</p></div>';
    }

    $cpt      = esc_attr(get_option(EAT_OPTION_CPT, 'spectacole'));
    $layout   = esc_attr(get_option('eat_layout', 'list'));
    $heading  = esc_attr(get_option('eat_heading', 'h3'));
    $addr_tag = esc_attr(get_option('eat_address_tag', 'p'));
    ?>
    <div class="wrap">
        <h1>Event Admin Table – Setări</h1>

        <form method="post">
            <?php wp_nonce_field('eat_settings_save'); ?>

            <table class="form-table">
                <tr>
                    <th>Slug Custom Post Type</th>
                    <td>
                        <input type="text" name="eat_cpt" value="<?php echo $cpt; ?>">
                        <p class="description">Modifică slug-ul CPT-ului pe care vrei să-l folosești. Singura modalitate de control al acestei valori.</p>
                    </td>
                </tr>
            </table>

            <h2>Afișare frontend</h2>

            <table class="form-table">
                <tr>
                    <th>Layout</th>
                    <td>
                        <select name="eat_layout">
                            <option value="list" <?php selected($layout,'list'); ?>>Listă</option>
                            <option value="grid" <?php selected($layout,'grid'); ?>>Grid</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>Tag titlu locație</th>
                    <td>
                        <select name="eat_heading">
                            <?php foreach (['h2','h3','span'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php selected($heading,$t); ?>><?php echo strtoupper($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>Tag adresă</th>
                    <td>
                        <select name="eat_address_tag">
                            <?php foreach (['p','div','span'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php selected($addr_tag,$t); ?>><?php echo strtoupper($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h2>Mesaj afișat când nu există ocurențe</h2>

<table class="form-table">
	<tr>
		<th scope="row">Mesaj frontend</th>
		<td>
			<?php
			wp_editor(
				get_option(EAT_OPTION_EMPTY_MESSAGE),
				'eat_empty_message',
				[
					'textarea_name' => 'eat_empty_message',
					'textarea_rows' => 8,
					'media_buttons' => false,
					'teeny'         => true,
				]
			);
			?>
			<p class="description">
			Acest mesaj va fi afișat în frontend atunci când nu există ocurențe de afișat (listă, grid, load more).
			</p>
		</td>
	</tr>
</table>


            <?php submit_button('Salvează setările','primary','eat_submit'); ?>
        </form>
    </div>
    <?php
}

/* ======================================================
 * MESAJ
 * ====================================================== */


function eat_get_empty_occurrences_message() {

	$content = get_option(EAT_OPTION_EMPTY_MESSAGE);

	if ( ! $content ) {
		return '';
	}

	return '<div class="event-master-wrapper">' . do_shortcode(wpautop($content)) . '</div>';
}



/* ======================================================
 * SUBMENIU CPT (dinamic)
 * ====================================================== */
 
add_action('admin_menu', function () {

    $cpt = get_option(EAT_OPTION_CPT, 'spectacole');

    add_submenu_page(
        'edit.php?post_type=' . $cpt,
        'Tabel avansat',
        'Tabel avansat',
        'manage_options',
        'event-admin-table',
        'eat_render_table_page'
    );
});

/* ======================================================
 * LIST TABLE – ADMIN ONLY
 * ====================================================== */
 
add_action('admin_init', function () {

    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }

    class Event_Admin_List_Table extends WP_List_Table {

        public function __construct() {
            parent::__construct([
                'singular' => 'item',
                'plural'   => 'items',
                'ajax'     => false,
            ]);
        }

        public function get_columns() {
            return [
                'cb'      => '<input type="checkbox" />',
                'title'   => 'Titlu',
                'date'    => 'Data',
                'time'    => 'Ora',
                'loc'     => 'Locație',
                'addr'    => 'Adresă',
                'tickets' => 'Bilete',
            ];
        }

        protected function column_cb($item) {
            return '<input type="checkbox" name="ids[]" value="' . esc_attr($item['post']->ID) . '" />';
        }

        protected function get_sortable_columns() {
            return [
                'title' => ['title', true],
            ];
        }

        protected function get_bulk_actions() {
            return [
                'mark_expired' => 'Marchează ca expirat',
            ];
        }

        protected function process_bulk_action() {
            if ($this->current_action() === 'mark_expired' && !empty($_POST['ids'])) {
                foreach ((array) $_POST['ids'] as $id) {
                    update_post_meta((int) $id, '_manual_expired', 1);
                }
            }
        }

        public function prepare_items() {

            $this->process_bulk_action();

            $per_page    = 20;
            $current     = $this->get_pagenum();
            $search      = $_REQUEST['s'] ?? '';
            $only_future = !empty($_GET['only_future']);
            $post_type   = get_option(EAT_OPTION_CPT, 'spectacole');

            $query = new WP_Query([
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                's'              => $search,
                'posts_per_page' => -1,
            ]);

            $items = $this->expand_items($query->posts, $only_future);

            $this->items = array_slice(
                $items,
                ($current - 1) * $per_page,
                $per_page
            );

            $this->set_pagination_args([
                'total_items' => count($items),
                'per_page'    => $per_page,
            ]);

            $this->_column_headers = [
                $this->get_columns(),
                [],
                $this->get_sortable_columns(),
            ];
        }

        protected function expand_items($posts, $only_future) {

            $rows  = [];
            $today = date('Y-m-d');

            foreach ($posts as $post) {

                $defaults = [
                    'date'    => get_post_meta($post->ID, 'data_turneu', true),
                    'time'    => get_post_meta($post->ID, 'event_start_time', true),
                    'loc'     => get_post_meta($post->ID, 'nume_locatie', true),
                    'addr'    => get_post_meta($post->ID, 'adresa_locatie', true),
                    'tickets' => get_post_meta($post->ID, 'url_bilete', true),
                ];

                $occurrences = maybe_unserialize(
                    get_post_meta($post->ID, '_lre_occurrence_overrides', true)
                );

                if (!is_array($occurrences) || empty($occurrences)) {
                    $occurrences = [[]];
                }

                foreach ($occurrences as $data) {

                    $date = $data['data_turneu'] ?? $defaults['date'];

                    if ($only_future && $date && $date < $today) {
                        continue;
                    }

                    $rows[] = [
                        'post' => $post,
                        'data' => [
                            'date'    => $date,
                            'time'    => $data['event_start_time'] ?? $defaults['time'],
                            'loc'     => $data['nume_locatie']     ?? $defaults['loc'],
                            'addr'    => $data['adresa_locatie']   ?? $defaults['addr'],
                            'tickets' => $data['url_bilete']       ?? $defaults['tickets'],
                        ],
                        'expired' => $date && $date < $today,
                    ];
                }
            }

            return $rows;
        }

        protected function column_title($item) {
            return '<strong><a href="' . esc_url(get_edit_post_link($item['post']->ID)) . '">' .
                esc_html($item['post']->post_title) . '</a></strong>';
        }

        protected function column_date($item) {
    return !empty($item['data']['date'])
        ? esc_html(eat_format_date($item['data']['date']))
        : '—';
}

        protected function column_time($item)    { return esc_html($item['data']['time'] ?: '—'); }
        protected function column_loc($item)     { return esc_html($item['data']['loc'] ?: '—'); }
        protected function column_addr($item)    { return esc_html($item['data']['addr'] ?: '—'); }
        protected function column_tickets($item) {
            return $item['data']['tickets']
                ? '<a href="' . esc_url($item['data']['tickets']) . '" target="_blank">Link</a>'
                : '—';
        }

        public function single_row($item) {
            $style = !empty($item['expired']) ? ' style="background:#ffecec;"' : '';
            echo "<tr{$style}>";
            $this->single_row_columns($item);
            echo '</tr>';
        }
    }
});


/* ======================================================
 * GUTENBERG BLOCK (WRAPPER)
 * ====================================================== */
 
add_action('init', function () {

    register_block_type(
        'event-admin-table/occurrences',
        [
            'api_version'     => 2,
            'title'           => 'Event Occurrences',
            'description'     => 'Afișează datele, locația și butonul de bilete pentru eveniment.',
            'category'        => 'widgets',
            'icon'            => 'calendar-alt',
            'supports'        => [
                'html' => false,
            ],
            'render_callback' => 'eat_render_occurrences_block',
        ]
    );

});

function eat_render_occurrences_block() {
    wp_enqueue_style('eat-event-occurrences');
    return do_shortcode('[event_occurrences]');
}

/* ======================================================
 * SHORTCODE FRONTEND
 * ====================================================== */
 
 /* ================= FRONTEND: SINGLE EVENT ================= */
add_shortcode('event_occurrences', 'eat_shortcode_event_occurrences');

function eat_shortcode_event_occurrences() {
    if (!is_singular(get_option(EAT_OPTION_CPT))) {
        return '';
    }

    wp_enqueue_style('eat-event-occurrences');

    global $post;

    $layout   = get_option('eat_layout', 'list');
    $heading  = esc_attr(get_option('eat_heading','h3'));
    $addrTag  = esc_attr(get_option('eat_address_tag','p'));

$defaults = [
    'date'      => get_post_meta($post->ID,'data_turneu',true),
    'time'      => get_post_meta($post->ID,'event_start_time',true),
    'loc'       => get_post_meta($post->ID,'nume_locatie',true),
    'addr'      => get_post_meta($post->ID,'adresa_locatie',true),
    'tickets'   => get_post_meta($post->ID,'url_bilete',true),
];


    $occurrences = maybe_unserialize(
        get_post_meta($post->ID,'_lre_occurrence_overrides',true)
    );
    
$valid_occurrences = [];

foreach ($occurrences as $data) {

	if ( ! is_array($data) ) {
		continue;
	}

	$date = $data['data_turneu'] ?? '';
	$loc  = $data['nume_locatie'] ?? '';

	// criterii minime ca o ocurență să fie validă
	if ( empty($date) || empty($loc) ) {
		continue;
	}

	$valid_occurrences[] = $data;
}

if ( empty($valid_occurrences) ) {
	return eat_get_empty_occurrences_message();
}


    ob_start();

    echo '<div class="event-occurrences layout-' . esc_attr($layout) . '">';

    foreach ($valid_occurrences as $data) {

        $date = $data['data_turneu'] ?? $defaults['date'];
        $time = $data['event_start_time'] ?? $defaults['time'];
        $loc  = $data['nume_locatie']     ?? $defaults['loc'];
        $addr = $data['adresa_locatie']   ?? $defaults['addr'];
        $link = $data['url_bilete'] ?? $defaults['tickets'];


        echo '<div class="event-occurrence">';

            // COLOANA STÂNGA – TEXT
            echo '<div class="event-col-text">';

                echo '<div class="event-date">';
                echo esc_html(eat_format_date($date));

                if ($time) {
                    echo ' ora <span class="event-time">' . esc_html($time) . '</span>';
                }
                echo '</div>';

                printf(
                    '<%1$s class="wp-block-heading event-location">%2$s</%1$s>',
                    $heading,
                    esc_html($loc)
                );

                printf(
                    '<%1$s class="event-address">%2$s</%1$s>',
                    $addrTag,
                    esc_html($addr)
                );

            echo '</div>';

            // COLOANA DREAPTA – BUTON
echo '<div class="event-col-action">';

    // LINK EXTERN pentru bilete
    if (!empty($link)) {
        echo '<div class="wp-block-button">';
        echo '<a class="wp-block-button__link" href="' . esc_url($link) . '" target="_blank">Bilete</a>';
        echo '</div>';
    }

echo '</div>';

        echo '</div>';
    }

    echo '</div>';

    return ob_get_clean();
}

/* ======================================================
 * PAGINA LISTARE
 * ====================================================== */
 
function eat_render_table_page() {

    $table = new Event_Admin_List_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Tabel avansat</h1>

        <form method="get">
            <input type="hidden" name="post_type" value="<?php echo esc_attr(get_option(EAT_OPTION_CPT, 'spectacole')); ?>">
            <input type="hidden" name="page" value="event-admin-table">

            <?php $table->search_box('Caută', 'eat-search'); ?>

            <label style="margin-left:12px;">
                <input type="checkbox" name="only_future" value="1" <?php checked(!empty($_GET['only_future'])); ?>>
                Doar viitoare
            </label>

            <?php $table->display(); ?>
        </form>
    </div>
    <?php
}



/* ======================================================
 * Shortcode Loop Spectacole - SIMPLIFICAT
 * ====================================================== */

/* ================= FRONTEND: EVENT LOOP ================= */
add_shortcode('event_loop_ajax', 'eat_shortcode_event_loop');

function eat_shortcode_event_loop ($atts) {

$atts = shortcode_atts([
        'layout'   => 'list',
        'per_page' => 10,
        'image'    => 'yes',
], $atts);

    
    // CSS
    wp_enqueue_style(
        'eat-event-occurrences',
        plugin_dir_url(__FILE__) . 'assets/css/event-occurrences.css',
        [],
        EAT_VER
    );

    // JS – ENQUEUE DIRECT
    wp_enqueue_script(
        'eat-event-loop',
        plugin_dir_url(__FILE__) . 'assets/js/event-loop.js',
        [],
        EAT_VER,
        true
    );

    // LOCALIZE IMEDIAT DUPĂ
wp_localize_script(
    'eat-event-loop',
    'EAT_EventLoop',
    [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('eat_event_loop'),
        'layout'   => $atts['layout'],
        'perPage'  => (int) $atts['per_page'],
        'image'    => ($atts['image'] === 'yes'),
    ]
);
    

    ob_start();
    ?>
    <div class="eat-event-loop-wrapper" data-layout="<?php echo esc_attr($atts['layout']); ?>">
        <div class="eat-event-results event-occurrences layout-<?php echo esc_attr($atts['layout']); ?>"></div>
        <button class="wp-block-button__link wp-element-button wc-block-components-product-button__button add_to_cart_button eat-load-more">Încarcă mai multe</button>
    </div>
    <?php
    return ob_get_clean();
}


// ÎNLOCUIEȚI funcția eat_render_event_loop_item() cu aceasta:

function eat_render_event_loop_item($post_id) {
    global $eat_current_occurrence;
    
    // COLEACTĂ DATELE EXACT CA ÎN eat_shortcode_event_occurrences()
    $defaults = [
        'date'      => get_post_meta($post_id, 'data_turneu', true),
        'time'      => get_post_meta($post_id, 'event_start_time', true),
        'loc'       => get_post_meta($post_id, 'nume_locatie', true),
        'addr'      => get_post_meta($post_id, 'adresa_locatie', true),
        'tickets'   => get_post_meta($post_id, 'url_bilete', true),
    ];

    $occurrences = maybe_unserialize(
        get_post_meta($post_id, '_lre_occurrence_overrides', true)
    );

    if (!is_array($occurrences) || empty($occurrences)) {
        // Dacă nu există ocurențe multiple, folosește doar datele default
        $all_occurrences = [$defaults];
    } else {
        // Combină ocurențele multiple cu datele default pentru fiecare
        $all_occurrences = [];
        foreach ($occurrences as $data) {
            $all_occurrences[] = [
                'date'    => $data['data_turneu'] ?? $defaults['date'],
                'time'    => $data['event_start_time'] ?? $defaults['time'],
                'loc'     => $data['nume_locatie'] ?? $defaults['loc'],
                'addr'    => $data['adresa_locatie'] ?? $defaults['addr'],
                'link'    => $data['url_bilete'] ?? $defaults['tickets'],
            ];
        }
    }
    
    // SETĂRI LAYOUT
    $layout   = get_option('eat_layout', 'list');
    $heading  = esc_attr(get_option('eat_heading','h3'));
    $addrTag  = esc_attr(get_option('eat_address_tag','p'));
    
    // Titlul evenimentului (se afișează o singură dată pentru toate ocurențele)
    $event_title = get_the_title($post_id);
    $has_image = has_post_thumbnail($post_id);
    
    echo '<div class="event-master-wrapper">';
    
    // Afișează titlul și imaginea evenimentului (o singură dată)
    echo '<div class="event-header">';
    if ($has_image) {
        echo '<div class="event-col-image">';
            echo get_the_post_thumbnail(
                $post_id,
                'medium',
                [
                    'class' => 'event-image',
                    'loading' => 'lazy',
                ]
            );
        echo '</div>';
    }
    echo '<' . $heading . ' class="event-main-title">' . esc_html($event_title) . '</' . $heading . '>';
    // Buton "Detalii" către pagina evenimentului
                echo '<div class="wp-block-button">';
                echo '<a class="wp-block-button__link" href="' . esc_url(get_permalink($post_id)) . 
                     '" style="background: var(--accent-secondary);">Detalii</a>';
                echo '</div>';
    echo '</div>';
    
    // Afișează TOATE ocurențele
    foreach ($all_occurrences as $index => $occurrence) {
        if (empty($occurrence['date'])) {
            continue; // Sari peste ocurențe fără dată
        }
        
        // PENTRU LOGICA WOOCOMMERCE (dacă e nevoie)
        $eat_current_occurrence = [
            'date'  => $occurrence['date'],
            'time'  => $occurrence['time'],
            'loc'   => $occurrence['loc'],
            'addr'  => $occurrence['addr'],
        ];
        
        echo '<div class="event-occurrence layout-' . esc_attr($layout) . ' occurrence-' . ($index + 1) . '">';

            /* === TEXT (similar cu scurtcodul) === */
            echo '<div class="event-col-text">';

                echo '<div class="event-date">';
                echo esc_html(eat_format_date($occurrence['date']));

                if ($occurrence['time']) {
                    echo ' ora <span class="event-time">' . esc_html($occurrence['time']) . '</span>';
                }
                echo '</div>';

                // Locație (dacă există)
                if ($occurrence['loc']) {
                    echo '<div class="event-location-name">' . esc_html($occurrence['loc']) . '</div>';
                }

                // Adresă (dacă există)
                if ($occurrence['addr']) {
                    printf(
                        '<%1$s class="event-address">%2$s</%1$s>',
                        $addrTag,
                        esc_html($occurrence['addr'])
                    );
                }

            echo '</div>';

            /* === BUTON Bilete === */
            echo '<div class="event-col-action">';
            
                
                // Buton "Bilete" direct dacă există link
                if (!empty($occurrence['link'])) {
                    echo '<div class="wp-block-button">';
                    echo '<a class="wp-block-button__link" href="' . esc_url($occurrence['link']) . 
                         '" target="_blank">Bilete</a>';
                    echo '</div>';
                }

            echo '</div>';

        echo '</div>';
    }
    
    echo '</div>'; // .event-master-wrapper
}

// AJAX ENDPOINT

// ÎNLOCUIEȚI funcția eat_ajax_load_events() cu aceasta:
function eat_ajax_load_events() {

    check_ajax_referer('eat_event_loop', 'nonce');

    $page     = max(1, (int) $_POST['page']);
    $per_page = (int) $_POST['perPage'];

    $args = [
        'post_type'      => get_option(EAT_OPTION_CPT, 'spectacole'),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    $query = new WP_Query($args);

    $items = [];

    foreach ($query->posts as $post) {
        // Verifică dacă evenimentul are ocurențe
        $has_occurrences = false;
        
        $defaults = [
            'date'    => get_post_meta($post->ID, 'data_turneu', true),
            'time'    => get_post_meta($post->ID, 'event_start_time', true),
            'loc'     => get_post_meta($post->ID, 'nume_locatie', true),
            'addr'    => get_post_meta($post->ID, 'adresa_locatie', true),
        ];

        // Verifică data default
        if (!empty($defaults['date'])) {
            $has_occurrences = true;
        }
        
        // Verifică ocurențele multiple
        $occurrences = maybe_unserialize(
            get_post_meta($post->ID, '_lre_occurrence_overrides', true)
        );
        
        if (is_array($occurrences) && !empty($occurrences)) {
            $has_occurrences = true;
        }
        
        if ($has_occurrences) {
            $items[] = $post->ID;
        }
    }

    if (!$items) {
        wp_send_json_success([
            'html'    => '',
            'hasMore' => false,
        ]);
    }

    // Paginare simplă - afișează X evenimente pe pagină
    $paged_items = array_slice(
        $items,
        ($page - 1) * $per_page,
        $per_page
    );

    ob_start();
    foreach ($paged_items as $post_id) {
        eat_render_event_loop_item($post_id);
    }

    wp_send_json_success([
        'html'    => ob_get_clean(),
        'hasMore' => count($items) > $page * $per_page,
    ]);
}


/* ================= AJAX ================= */
add_action('wp_ajax_eat_load_events', 'eat_ajax_load_events');
add_action('wp_ajax_nopriv_eat_load_events', 'eat_ajax_load_events');