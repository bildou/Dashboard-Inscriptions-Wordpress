<?php
defined('ABSPATH') || exit;

class Event_Dashboard_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'create_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function create_admin_menu() {
        add_menu_page(
            __('Tableau de Bord Événement', 'event-dashboard'),
            '📊 Événement',
            'read',
            'event-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-chart-area',
            20
        );
    }

    /**
     * Gère la mise en page globale (sidebar + contenu) et choisit quelle "sous-page" afficher.
     */
    public function render_dashboard_page() {
        // On récupère la sous-page depuis l'URL, avec 'dashboard' comme valeur par défaut.
        $subpage = isset($_GET['subpage']) ? sanitize_key($_GET['subpage']) : 'dashboard';

        // On crée la structure principale de la page
        echo '<div class="dashboard-layout-wrapper">';

        // 1. On inclut la sidebar (elle sera toujours présente)
        require_once EVENT_DASHBOARD_PATH . 'includes/templates/template-parts/sidebar.php';

        // 2. On charge le template de contenu principal en fonction de la sous-page
        echo '<main class="dashboard-main-content">';
        
        switch ($subpage) {
            case 'chapitres':
                require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-chapitres.php';
                break;
            
            case 'parcours':
                require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-parcours.php';
                break;
				
				 case 'bus':
                require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-bus.php';
                break;
				
				case 'navettes': 
                require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-navettes.php';
                break;

            case 'dashboard':
            default:
                require_once EVENT_DASHBOARD_PATH . 'includes/templates/page-dashboard-home.php';
                break;
        }

        echo '</main>';
        echo '</div>';
        
        // 3. La modale reste en dehors pour s'afficher correctement par-dessus tout
        require_once EVENT_DASHBOARD_PATH . 'includes/templates/template-parts/modal-edit-registration.php';
        }

    public function enqueue_assets($hook) {
        if ('toplevel_page_event-dashboard' !== $hook) {
            return;
        }
		// UNIQUEMENT pour la page principale du dashboard
if ($hook === 'toplevel_page_event-dashboard') {
    // Charger les CSS et JS de Leaflet depuis un CDN
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
	
	  // NOUVEAU : Charger Font Awesome depuis un CDN
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', // Lien vers la dernière version
        [],
        '6.5.2'
    );

    // Notre nouveau script pour la carte
    wp_enqueue_script(
        'event-dashboard-map',
        EVENT_DASHBOARD_URL . 'assets/js/france-map-leaflet.js',
        ['leaflet-js'], // Dépend de leaflet-js
        EVENT_DASHBOARD_VERSION,
        true
    );
	 // Notre nouveau script pour le widget d'activité
    wp_enqueue_script(
        'event-dashboard-activity-widget',
        EVENT_DASHBOARD_URL . 'assets/js/activity-widget.js',
        ['jquery'],
        EVENT_DASHBOARD_VERSION,
        true
    );
	   // NOUVEAU : Localisation du script pour l'AJAX
    wp_localize_script(
        'event-dashboard-activity-widget', // Le handle du script auquel on lie les données
        'eventDashboardAjax',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('event_dashboard_nonce'), // Le nonce de sécurité
        ]
    );
}

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true );
        wp_enqueue_script('event-dashboard-scripts', EVENT_DASHBOARD_URL . 'assets/js/dashboard-scripts.js', ['jquery', 'chart-js'], EVENT_DASHBOARD_VERSION, true);
        wp_enqueue_style('event-dashboard-styles', EVENT_DASHBOARD_URL . 'assets/css/dashboard-styles.css', [], EVENT_DASHBOARD_VERSION);
        
        $chart_data = Event_Dashboard_Data::get_data_for_charts();
        wp_localize_script('event-dashboard-scripts', 'eventDashboard', [
            'chartData'         => $chart_data,
            'ajax_url'          => admin_url('admin-ajax.php'),
            'get_details_nonce' => wp_create_nonce('event_get_details_nonce')
        ]);
    }
}