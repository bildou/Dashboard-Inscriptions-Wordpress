<?php
/**
 * Plugin Name:         Event Dashboard
 * Description:         Un tableau de bord moderne et complet pour visualiser et gérer les inscriptions du plugin Event Registration.
 * Version:             1.5.0
 * Author:              Votre Nom
 * Author URI:          https://votre-site.com
 * Text Domain:         event-dashboard
 * License:             GPL-2.0+
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined('ABSPATH') || exit;

// Définir les constantes du plugin
define('EVENT_DASHBOARD_VERSION', '1.5.0');
define('EVENT_DASHBOARD_PATH', plugin_dir_path(__FILE__));
define('EVENT_DASHBOARD_URL', plugin_dir_url(__FILE__));

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
        // La meilleure pratique est de tout initialiser sur le hook 'init'
        add_action('init', [$this, 'init_plugin']);
    }

    public function init_plugin() {
        $this->load_dependencies();
        $this->initialize_hooks();
    }

    private function load_dependencies() {
        // La classe WP_List_Table n'est plus nécessaire
        require_once EVENT_DASHBOARD_PATH . 'includes/class-admin-menu.php';
        require_once EVENT_DASHBOARD_PATH . 'includes/class-dashboard-data.php';
        require_once EVENT_DASHBOARD_PATH . 'includes/class-ajax-handlers.php';
    }

    private function initialize_hooks() {
        new Event_Dashboard_Admin_Menu();
        new Event_Dashboard_Ajax_Handlers();
    }
}

/**
 * Point de départ du plugin.
 * On utilise le hook 'plugins_loaded' avec une priorité tardive (ex: 20)
 * pour s'assurer que le CPT de l'autre plugin est déjà enregistré.
 */
function event_dashboard_run() {
    return Event_Dashboard::get_instance();
}
add_action('plugins_loaded', 'event_dashboard_run', 50);