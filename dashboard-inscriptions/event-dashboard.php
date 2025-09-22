<?php
/**
 * Plugin Name:         Event Dashboard
 * Description:         Un tableau de bord moderne et complet pour visualiser et gérer les inscriptions.
 * Version:             1.6.0
 * Author:              Votre Nom
 * Author URI:          https://votre-site.com
 * Text Domain:         event-dashboard
 * License:             GPL-2.0+
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined('ABSPATH') || exit;

// Définir les constantes du plugin
define('EVENT_DASHBOARD_VERSION', '1.6.0');
define('EVENT_DASHBOARD_PATH', plugin_dir_path(__FILE__));
define('EVENT_DASHBOARD_URL', plugin_dir_url(__FILE__));

/**
 * Fonction à exécuter à l'activation du plugin pour créer la table des villes.
 */
function event_dashboard_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_city_coords';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        city_name varchar(255) NOT NULL,
        latitude decimal(10, 8) NOT NULL,
        longitude decimal(11, 8) NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY city_name (city_name)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'event_dashboard_activate');


/**
 * Classe principale du plugin.
 */
final class Event_Dashboard {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        add_action('plugins_loaded', [$this, 'initialize_hooks']);
    }

    /**
     * Charge tous les fichiers de classe nécessaires.
     */
    private function load_dependencies() {
        require_once EVENT_DASHBOARD_PATH . 'includes/class-admin-menu.php';
        require_once EVENT_DASHBOARD_PATH . 'includes/class-dashboard-data.php';
        require_once EVENT_DASHBOARD_PATH . 'includes/class-ajax-handlers.php';
    }

    /**
     * Initialise tous les hooks et les classes nécessaires.
     * C'est ici que la logique admin/front-end est séparée.
     */
    public function initialize_hooks() {
        new Event_Dashboard_Ajax_Handlers(); // Nécessaire partout
        $this->add_shortcodes(); // Enregistre le shortcode

        if (is_admin()) {
            // Uniquement pour le back-office
            new Event_Dashboard_Admin_Menu();
        } else {
            // Uniquement pour le site public (front-end)
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        }
    }

    /**
     * Enregistre le shortcode du plugin.
     */
    public function add_shortcodes() {
        add_shortcode('mon_dashboard_evenement', [$this, 'render_shortcode']);
    }

    /**
     * Gère l'affichage du dashboard via le shortcode [mon_dashboard_evenement].
     */
    public function render_shortcode() {
        if (!current_user_can('manage_options')) {
            return '<p>Vous n\'avez pas les permissions nécessaires pour voir ce contenu.</p>';
        }

        ob_start();
        $subpage = isset($_GET['subpage']) ? sanitize_key($_GET['subpage']) : 'dashboard';

        echo '<div class="dashboard-layout-wrapper front-end-dashboard">';
        require_once EVENT_DASHBOARD_PATH . 'includes/templates/template-parts/sidebar.php';
        echo '<main class="dashboard-main-content">';
        
        switch ($subpage) {
            case 'chapitres': require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-chapitres.php'; break;
            case 'bus': require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-bus.php'; break;
            case 'parcours': require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-parcours.php'; break;
            default: require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-dashboard-home.php'; break;
        }

        echo '</main>';
        echo '</div>';
        
        require_once EVENT_DASHBOARD_PATH . 'includes/templates/template-parts/modal-edit-registration.php';

        return ob_get_clean();
    }

    /**
     * Charge les assets (CSS/JS) sur le front-end si la page contient le shortcode.
     */
    public function enqueue_frontend_assets() {
        global $post;

        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'mon_dashboard_evenement')) {
            // --- Styles ---
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', [], '6.5.2');
            wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
            wp_enqueue_style('event-dashboard-styles', EVENT_DASHBOARD_URL . 'assets/css/dashboard-styles.css', [], EVENT_DASHBOARD_VERSION);

            // --- Scripts ---
            wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
            wp_enqueue_script('event-dashboard-map', EVENT_DASHBOARD_URL . 'assets/js/france-map-leaflet.js', ['jquery', 'leaflet-js'], EVENT_DASHBOARD_VERSION, true);
            wp_enqueue_script('activity-widget', EVENT_DASHBOARD_URL . 'assets/js/activity-widget.js', ['jquery'], EVENT_DASHBOARD_VERSION, true);

            // --- Données pour JS ---
            $department_data = Event_Dashboard_Data::get_registrations_by_department();
            $city_data = Event_Dashboard_Data::get_top_cities_with_coords();
            wp_localize_script('event-dashboard-map', 'mapData', [
                'departments' => $department_data,
                'cities'      => $city_data,
            ]);
            
            wp_localize_script('activity-widget', 'eventDashboardAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('event_dashboard_nonce'),
            ]);
        }
    }
}

/**
 * Point de départ du plugin.
 */
function event_dashboard_run() {
    return Event_Dashboard::get_instance();
}
event_dashboard_run();


// Dans event-dashboard.php (à la fin du fichier)

/**
 * Redirige les "Gestionnaires de boutique" vers le dashboard personnalisé
 * après leur connexion.
 */
function redirect_shop_manager_to_dashboard( $redirect_to, $user ) {
    // Vérifie si l'utilisateur est bien un "Gestionnaire de boutique"
    if ( isset($user->roles) && is_array($user->roles) && in_array('shop_manager', $user->roles) ) {
        // Force la redirection vers votre dashboard
        return admin_url('admin.php?page=event-dashboard');
    }
    // Pour tous les autres utilisateurs (admins, etc.), on ne change rien.
    return $redirect_to;
}
add_filter( 'woocommerce_login_redirect', 'redirect_shop_manager_to_dashboard', 999, 2 );


/**
 * Masque l'interface WordPress pour les utilisateurs qui ne sont pas administrateurs.
 */
function event_dashboard_clean_admin_ui() {
    // Si l'utilisateur est un admin, on ne masque rien.
    if ( current_user_can('manage_options') ) {
        return;
    }

    // Pour tous les autres (y compris notre 'shop_manager'), on nettoie l'interface.
    // On s'assure qu'on est bien sur la page du dashboard pour ne pas affecter d'autres pages
    if ( isset($_GET['page']) && strpos($_GET['page'], 'event-dashboard') !== false ) {
        echo '<style>
            #wpadminbar, #adminmenumain, #wpfooter, .notice, .update-nag, .wrap h1 { display: none !important; }
            #wpcontent, #wpfooter { margin-left: 0 !important; }
            #wpbody-content { padding-bottom: 0 !important; }
            /* Ajustement pour que le contenu commence bien en haut à gauche */
            html.wp-toolbar { padding-top: 0 !important; }
        </style>';
    }
}
add_action( 'admin_head', 'event_dashboard_clean_admin_ui' );