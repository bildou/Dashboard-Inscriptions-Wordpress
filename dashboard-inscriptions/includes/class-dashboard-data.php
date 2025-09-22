<?php
defined('ABSPATH') || exit;

class Event_Dashboard_Data {

    /**
     * Récupère les statistiques KPI (Key Performance Indicators).
     * Optimisé avec des requêtes directes pour la performance.
     *
     * @return array
     */
    public static function get_kpi_stats() {
        global $wpdb;

        // Total des inscriptions (post_type 'event_registration')
        $total_registrations = $wpdb->get_var("
            SELECT COUNT(ID) FROM {$wpdb->posts}
            WHERE post_type = 'event_registration'
            AND post_status = 'publish'
        ");

        // Stats des commandes WooCommerce (terminées/en cours)
        $completed_orders_data = $wpdb->get_row("
            SELECT
                COUNT(id) AS count,
                SUM(total_amount) AS total_revenue
            FROM {$wpdb->prefix}wc_orders
            WHERE status IN ('wc-completed', 'wc-processing')
            AND id IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_event_payer_index'
            )
        ");
        $completed_orders_count = $completed_orders_data ? intval($completed_orders_data->count) : 0;
        $total_revenue = $completed_orders_data ? floatval($completed_orders_data->total_revenue) : 0;

        // Nouvelles inscriptions sur les 7 derniers jours
        $new_registrations_week = $wpdb->get_var("
            SELECT COUNT(ID) FROM {$wpdb->posts}
            WHERE post_type = 'event_registration'
            AND post_status = 'publish'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");

        // Nouvelles commandes et revenus sur les 7 derniers jours
        $new_orders_week_data = $wpdb->get_row("
            SELECT
                COUNT(id) AS count,
                SUM(total_amount) AS total_revenue
            FROM {$wpdb->prefix}wc_orders
            WHERE status IN ('wc-completed', 'wc-processing')
            AND date_created_gmt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
            AND id IN (
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_event_payer_index'
            )
        ");
        $new_orders_week_count = $new_orders_week_data ? intval($new_orders_week_data->count) : 0;
        $new_revenue_week = $new_orders_week_data ? floatval($new_orders_week_data->total_revenue) : 0;

        return [
            'registrations' => ['total' => $total_registrations ?: 0, 'change' => $new_registrations_week ?: 0],
            'orders'        => ['total' => $completed_orders_count, 'change' => $new_orders_week_count],
            'revenue'       => ['total' => $total_revenue, 'change' => $new_revenue_week],
        ];
    }

    /**
     * Récupère les données pour les graphiques (évolution, chapitres, parcours).
     *
     * @return array
     */
    public static function get_data_for_charts() {
        global $wpdb;

        // Stats par chapitres
        $chapters_results = $wpdb->get_results("
            SELECT
                (SELECT post_title FROM {$wpdb->posts} WHERE ID = pm.meta_value) as label,
                COUNT(pm.post_id) as value
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_event_chapter_id'
            AND p.post_type = 'event_registration' AND p.post_status = 'publish'
            AND pm.meta_value IS NOT NULL AND pm.meta_value != ''
            GROUP BY label ORDER BY value DESC LIMIT 10
        ");
        $chapters = [
            'labels' => wp_list_pluck($chapters_results, 'label'),
            'data'   => wp_list_pluck($chapters_results, 'value')
        ];

        // Stats par parcours (routes)
        $routes_results = $wpdb->get_results("
            SELECT
                pm.meta_value as label,
                COUNT(pm.post_id) as value
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_event_route'
            AND p.post_type = 'event_registration' AND p.post_status = 'publish'
            AND pm.meta_value IS NOT NULL AND pm.meta_value != ''
            GROUP BY label ORDER BY value DESC LIMIT 10
        ");
        $routes = [
            'labels' => array_map('ucfirst', wp_list_pluck($routes_results, 'label')),
            'data'   => wp_list_pluck($routes_results, 'value')
        ];

        // Évolution des inscriptions sur 30 jours
        $evolution_results = $wpdb->get_results("
            SELECT DATE(post_date) as date, COUNT(ID) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'event_registration' AND post_status = 'publish'
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY date ORDER BY date ASC
        ");

        $evolution = [];
        for ($i = 29; $i >= 0; $i--) {
            $date_obj = new DateTime("-$i days");
            $date_key = $date_obj->format('Y-m-d');
            $date_label = $date_obj->format('d M');
            $evolution[$date_key] = ['label' => $date_label, 'count' => 0];
        }

        foreach ($evolution_results as $result) {
            if (isset($evolution[$result->date])) {
                $evolution[$result->date]['count'] = intval($result->count);
            }
        }

        return [
            'evolution' => [
                'labels' => array_column($evolution, 'label'),
                'data'   => array_column($evolution, 'count')
            ],
            'chapters'  => $chapters,
            'routes'    => $routes,
        ];
    }

    /**
     * Récupère tous les produits "chapitre".
     *
     * @return array
     */
    public static function get_all_chapter_products() {
        $chapter_query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'chapitres']],
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids'
        ]);
        return $chapter_query->posts;
    }
    
     /**
     * MODIFIÉE : Récupère les statistiques pour un chapitre donné,
     * ou pour TOUS les chapitres si l'ID est vide.
     *
     * @param int $chapter_id
     * @return array
     */
    public static function get_chapter_page_stats($chapter_id) {
        global $wpdb;
        $stats = ['total' => 0, 'rouge' => 0, 'vert' => 0, 'bleu' => 0, 'cyan' => 0];
        $route_counts = [];

        if (empty($chapter_id)) {
            // --- CAS "TOUS LES CHAPITRES" ---
            $stats['total'] = $wpdb->get_var("
                SELECT COUNT(ID) FROM {$wpdb->posts}
                WHERE post_type = 'event_registration' AND post_status = 'publish'
            ");

            // CORRECTION : Requête plus robuste avec un JOIN
            $route_counts = $wpdb->get_results("
                SELECT pm.meta_value as route, COUNT(pm.post_id) as count
                FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = '_event_route'
                AND p.post_type = 'event_registration'
                AND p.post_status = 'publish'
                GROUP BY pm.meta_value
            ");

        } else {
            // --- CAS "UN SEUL CHAPITRE" (logique existante, qui est correcte) ---
            $chapter_id = absint($chapter_id);
            $stats['total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(pm.post_id) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_event_chapter_id' AND pm.meta_value = %d AND p.post_type = 'event_registration' AND p.post_status = 'publish'", $chapter_id));
            $route_counts = $wpdb->get_results($wpdb->prepare("SELECT pm_route.meta_value as route, COUNT(pm_route.post_id) as count FROM {$wpdb->postmeta} pm_chapter JOIN {$wpdb->postmeta} pm_route ON pm_chapter.post_id = pm_route.post_id JOIN {$wpdb->posts} p ON p.ID = pm_chapter.post_id WHERE pm_chapter.meta_key = '_event_chapter_id' AND pm_chapter.meta_value = %d AND pm_route.meta_key = '_event_route' AND p.post_type = 'event_registration' AND p.post_status = 'publish' GROUP BY pm_route.meta_value", $chapter_id));
        }

        // Traitement commun des résultats
        if (!empty($route_counts)) {
            foreach ($route_counts as $row) {
                // Assure que la clé existe dans notre tableau de stats avant de l'assigner
                if (isset($stats[strtolower($row->route)])) {
                    $stats[strtolower($row->route)] = intval($row->count);
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Récupère les statistiques pour une page parcours.
     *
     * @param string $parcours_slug
     * @return array
     */
    public static function get_parcours_page_stats($parcours_slug) {
        global $wpdb;
        $stats = ['total' => 0, 'chapters' => []];
        if (empty($parcours_slug)) return $stats;

        $stats['total'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(pm.post_id) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_event_route' AND pm.meta_value = %s AND p.post_type = 'event_registration' AND p.post_status = 'publish'", $parcours_slug));

        $chapter_counts = $wpdb->get_results($wpdb->prepare("SELECT (SELECT post_title FROM {$wpdb->posts} WHERE ID = pm_chapter.meta_value) as chapter_title, COUNT(pm_chapter.post_id) as count FROM {$wpdb->postmeta} pm_route JOIN {$wpdb->postmeta} pm_chapter ON pm_route.post_id = pm_chapter.post_id JOIN {$wpdb->posts} p ON p.ID = pm_route.post_id WHERE pm_route.meta_key = '_event_route' AND pm_route.meta_value = %s AND pm_chapter.meta_key = '_event_chapter_id' AND p.post_type = 'event_registration' AND p.post_status = 'publish' GROUP BY chapter_title ORDER BY count DESC", $parcours_slug));

        foreach ($chapter_counts as $row) {
            $stats['chapters'][$row->chapter_title] = intval($row->count);
        }
        return $stats;
    }

    
    /**
     * Récupère les IDs de tous les produits "bus".
     *
     * @return array
     */
    public static function get_all_bus_products() {
        $bus_query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'bus',
            ]],
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids' // On garde bien 'ids'
        ]);
        return $bus_query->posts;
    }
	
    /**
     * Récupère les statistiques pour une page bus.
     *
     * @param int $bus_id
     * @return array
     */
    public static function get_bus_page_stats($bus_id) {
        global $wpdb;
        $stats = ['total' => 0, 'chapters' => []];
        if (empty($bus_id)) return $stats;

         $bus_id_pattern = '%i:' . absint($bus_id) . ';%';
		
        $stats['total'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(pm.post_id) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_event_bus_ids' AND pm.meta_value LIKE %s AND p.post_type = 'event_registration' AND p.post_status = 'publish'", $bus_id_pattern));

        $chapter_counts = $wpdb->get_results($wpdb->prepare("SELECT (SELECT post_title FROM {$wpdb->posts} WHERE ID = pm_chapter.meta_value) as chapter_title, COUNT(pm_chapter.post_id) as count FROM {$wpdb->postmeta} pm_bus JOIN {$wpdb->postmeta} pm_chapter ON pm_bus.post_id = pm_chapter.post_id JOIN {$wpdb->posts} p ON p.ID = pm_bus.post_id WHERE pm_bus.meta_key = '_event_bus_ids' AND pm_bus.meta_value LIKE %s AND pm_chapter.meta_key = '_event_chapter_id' AND p.post_type = 'event_registration' AND p.post_status = 'publish' GROUP BY chapter_title ORDER BY count DESC", $bus_id_pattern));

        foreach ($chapter_counts as $row) {
            $stats['chapters'][$row->chapter_title] = intval($row->count);
        }
        return $stats;
    }

    /**
     * Récupère les statistiques pour une page navette.
     *
     * @param string $navette_type
     * @return array
     */
    public static function get_navette_page_stats($navette_type) {
        global $wpdb;
        $stats = ['total' => 0, 'chapters' => []];
        if (empty($navette_type) || !in_array($navette_type, ['bleu', 'vert'])) return $stats;

        $meta_key = '_event_need_transport_' . sanitize_key($navette_type);

        $stats['total'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(pm.post_id) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value = 'oui' AND p.post_type = 'event_registration' AND p.post_status = 'publish'", $meta_key));

        $chapter_counts = $wpdb->get_results($wpdb->prepare("SELECT (SELECT post_title FROM {$wpdb->posts} WHERE ID = pm_chapter.meta_value) as chapter_title, COUNT(pm_chapter.post_id) as count FROM {$wpdb->postmeta} pm_navette JOIN {$wpdb->postmeta} pm_chapter ON pm_navette.post_id = pm_chapter.post_id JOIN {$wpdb->posts} p ON p.ID = pm_navette.post_id WHERE pm_navette.meta_key = %s AND pm_navette.meta_value = 'oui' AND pm_chapter.meta_key = '_event_chapter_id' AND p.post_type = 'event_registration' AND p.post_status = 'publish' GROUP BY chapter_title ORDER BY count DESC", $meta_key));

        foreach ($chapter_counts as $row) {
            $stats['chapters'][$row->chapter_title] = intval($row->count);
        }
        return $stats;
    }

    /**
     * Récupère les classements (rankings) par meta_key.
     *
     * @param string $meta_key
     * @param int $limit
     * @return array
     */
    public static function get_rankings($meta_key, $limit = 5) {
        global $wpdb;
        $query = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_value, COUNT(*) as count FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'event_registration' AND p.post_status = 'publish' AND pm.meta_value != '' GROUP BY pm.meta_value ORDER BY count DESC LIMIT %d", sanitize_key($meta_key), absint($limit)));

        if ($meta_key === '_event_chapter_id') {
            foreach($query as $item) {
                $item->meta_value = get_the_title(absint($item->meta_value)) ?: __('Inconnu', 'event-dashboard');
            }
        }
        return $query;
    }
	
	
	
    /**
     * Récupère toutes les inscriptions avec filtres, tri et pagination.
     *
     * @param array $args
     * @return array
     */
    public static function get_all_registrations($args = []) {
        global $wpdb;

        $defaults = [ 'per_page' => 20, 'paged' => 1, 'orderby' => 'date', 'order' => 'DESC', 's' => '', 'status_filter' => '', 'chapter_id' => 0, 'parcours_filter' => '', 'bus_filter' => 0, 'navette_filter' => ''];
        $args = wp_parse_args($args, $defaults);

        $query_args = [ 'post_type' => 'event_registration', 'post_status' => 'publish', 'posts_per_page' => absint($args['per_page']), 'paged' => absint($args['paged']), 'order' => sanitize_key($args['order']), 'fields' => 'ids'];

        $meta_query = ['relation' => 'AND'];
        if (!empty($args['status_filter'])) { $meta_query[] = [ 'key' => '_event_status', 'value' => sanitize_text_field($args['status_filter']) ]; }
        if (!empty($args['chapter_id'])) { $meta_query[] = [ 'key' => '_event_chapter_id', 'value' => absint($args['chapter_id']) ]; }
        if (!empty($args['parcours_filter'])) { $meta_query[] = [ 'key' => '_event_route', 'value' => sanitize_text_field($args['parcours_filter']) ]; }
        if (!empty($args['bus_filter'])) {
            $meta_query[] = [
                'key'     => '_event_bus_ids',
                // On cherche la chaîne `i:ID;` qui est plus fiable pour un entier sérialisé.
                'value'   => 'i:' . absint($args['bus_filter']) . ';',
                'compare' => 'LIKE'
            ];
        }
        if (!empty($args['navette_filter'])) { $meta_query[] = [ 'key' => '_event_need_transport_' . sanitize_key($args['navette_filter']), 'value' => 'oui' ]; }
        if (!empty($args['s'])) { $meta_query[] = [ 'relation' => 'OR', [ 'key' => '_event_first_name', 'value' => sanitize_text_field($args['s']), 'compare' => 'LIKE' ], [ 'key' => '_event_last_name', 'value' => sanitize_text_field($args['s']), 'compare' => 'LIKE' ], [ 'key' => '_event_email', 'value' => sanitize_text_field($args['s']), 'compare' => 'LIKE' ] ]; }
        if (count($meta_query) > 1) { $query_args['meta_query'] = $meta_query; }

        switch (sanitize_key($args['orderby'])) {
            case 'full_name': $query_args['orderby'] = 'meta_value'; $query_args['meta_key'] = '_event_last_name'; break;
            case 'email': $query_args['orderby'] = 'meta_value'; $query_args['meta_key'] = '_event_email'; break;
            case 'chapter': $query_args['orderby'] = 'meta_value_num'; $query_args['meta_key'] = '_event_chapter_id'; break;
            case 'status': $query_args['orderby'] = 'meta_value'; $query_args['meta_key'] = '_event_status'; break;
            default: $query_args['orderby'] = 'date'; break;
        }

        $registrations_query = new WP_Query($query_args);
        $formatted_items = [];
        if (!empty($registrations_query->posts)) {
            $post_ids = $registrations_query->posts;
            $all_post_metas = self::get_multiple_post_meta($post_ids, ['_event_first_name', '_event_last_name', '_event_email', '_event_chapter_id', '_event_status']);
            $chapter_ids = array_filter(array_unique(wp_list_pluck($all_post_metas, '_event_chapter_id')));
            $chapter_titles = [];
                    if (!empty($chapter_ids)) {
                
                // --- CORRECTION CI-DESSOUS ---
                // On utilise une requête SQL directe, plus simple et infaillible
                $chapter_ids_string = implode(',', array_map('absint', $chapter_ids));
                $results = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ($chapter_ids_string)");

                if ($results) {
                    foreach ($results as $post) {
                        $chapter_titles[$post->ID] = $post->post_title;
                    }
                }
                // --- FIN DE LA CORRECTION ---
            }
            foreach ($post_ids as $post_id) {
                $metas = $all_post_metas[$post_id] ?? [];
                $chapter_id = $metas['_event_chapter_id'] ?? 0;
                $post_date = get_the_date('Y-m-d H:i:s', $post_id);
                $formatted_items[] = [
                    'id' => $post_id,
                    'full_name' => ($metas['_event_first_name'] ?? '') . ' ' . ($metas['_event_last_name'] ?? ''),
                    'email' => $metas['_event_email'] ?? '',
                    'chapter' => $chapter_titles[$chapter_id] ?? 'N/A',
                    'date' => date_i18n(get_option('date_format') . ' H:i', strtotime($post_date)),
                    'status' => $metas['_event_status'] ?? '',
                ];
            }
        }
        wp_reset_postdata();

        return [
            'items' => $formatted_items,
            'total' => $registrations_query->found_posts,
        ];
    }

    /**
     * Helper pour récupérer plusieurs post meta pour une liste d'IDs en une seule requête.
     *
     * @param array $post_ids
     * @param array $meta_keys
     * @return array
     */
    private static function get_multiple_post_meta(array $post_ids, array $meta_keys): array {
        global $wpdb;

        if (empty($post_ids) || empty($meta_keys)) return [];

        $post_ids_in = implode(',', array_map('absint', $post_ids));
        $meta_keys_in = "'" . implode("','", array_map('sanitize_key', $meta_keys)) . "'";

        $results = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ({$post_ids_in}) AND meta_key IN ({$meta_keys_in})", ARRAY_A);

        $processed_metas = [];
        foreach ($results as $row) {
            $post_id = intval($row['post_id']);
            $meta_key = $row['meta_key'];
            $meta_value = maybe_unserialize($row['meta_value']);
            if (!isset($processed_metas[$post_id])) $processed_metas[$post_id] = [];
            $processed_metas[$post_id][$meta_key] = $meta_value;
        }
        return $processed_metas;
    }
	
	/**
     * VERSION FINALE
     * Récupère le top des villes avec leurs coordonnées, en utilisant notre BDD de cache.
     */
  /**
     * NOUVELLE FONCTION (RÉ-AJOUTÉE)
     * Récupère le nombre d'inscrits par code de département.
     * @return array ['code_departement' => count] ex: ['34' => 125, '75' => 300]
     */
  public static function get_registrations_by_department() {
        global $wpdb;

        // CORRECTION : Remplacez '_event_department_code' par le VRAI nom de votre champ personnalisé
        $department_meta_key = '_event_department'; // <-- MODIFIEZ CETTE LIGNE

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT pm.meta_value as code, COUNT(p.ID) as count
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
            AND p.post_type = 'event_registration' AND p.post_status = 'publish'
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
        ", $department_meta_key), OBJECT_K);

        // Transforme le tableau d'objets en un simple tableau [code => count]
        if (empty($results)) {
            return []; // Retourne un tableau vide si aucun résultat
        }
        return array_map(function($value) { return $value->count; }, $results);
    }
    public static function get_top_cities_with_coords($limit = 50) {
        global $wpdb;
        
        $top_cities_counts = $wpdb->get_results($wpdb->prepare("
            SELECT pm.meta_value as city, COUNT(p.ID) as count
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_event_city'
            AND p.post_type = 'event_registration' AND p.post_status = 'publish'
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
            ORDER BY count DESC
            LIMIT %d
        ", $limit));

        $cities_with_coords = [];
        foreach ($top_cities_counts as $city_data) {
            // On utilise notre moteur de géocodage intelligent
            $coords = self::get_or_fetch_coords_for_city($city_data->city);
            
            if ($coords) {
                $cities_with_coords[] = [
                    'city'  => $city_data->city,
                    'count' => (int)$city_data->count,
                    'lat'   => $coords['lat'],
                    'lng'   => $coords['lng'],
                ];
            }
        }
        return $cities_with_coords;
    }

    /**
     * NOUVEAU MOTEUR DE GÉOCODAGE
     * Cherche les coordonnées d'une ville dans notre table custom.
     * Si non trouvées, appelle une API externe et sauvegarde le résultat.
     *
     * @param string $city_name
     * @return array|null ['lat' => float, 'lng' => float]
     */
    private static function get_or_fetch_coords_for_city($city_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_city_coords';
        $city_name_sanitized = sanitize_text_field($city_name);

        // 1. Chercher dans notre base de données (le cache)
        $coords = $wpdb->get_row($wpdb->prepare(
            "SELECT latitude, longitude FROM $table_name WHERE city_name = %s",
            $city_name_sanitized
        ));

        if ($coords) {
            return ['lat' => (float)$coords->latitude, 'lng' => (float)$coords->longitude];
        }

        // 2. Si non trouvé, appeler l'API de géocodage (Nominatim, gratuit)
        $api_url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($city_name_sanitized . ', France') . '&format=json&limit=1';
        
        $response = wp_remote_get($api_url, [
            'timeout'    => 10,
            'user-agent' => 'EventDashboardPlugin/1.0; ' . home_url() // L'user-agent est OBLIGATOIRE pour Nominatim
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null; // L'API a échoué
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (empty($data) || !isset($data[0]->lat) || !isset($data[0]->lon)) {
            // L'API n'a rien trouvé, on sauvegarde un "null" pour ne pas la réinterroger sans cesse (optionnel)
            return null;
        }
        
        $new_coords = [
            'lat' => (float)$data[0]->lat,
            'lng' => (float)$data[0]->lon,
        ];

        // 3. Sauvegarder le résultat dans notre table pour la prochaine fois
        $wpdb->insert(
            $table_name,
            [
                'city_name'    => $city_name_sanitized,
                'latitude'     => $new_coords['lat'],
                'longitude'    => $new_coords['lng'],
                'last_updated' => current_time('mysql'),
            ],
            ['%s', '%f', '%f', '%s']
        );

        return $new_coords;
    }
	/**
     * NOUVELLE FONCTION
     * Récupère les dernières activités (inscriptions et commandes)
     *
     * @param int $limit Le nombre d'activités à retourner.
     * @return array La liste des activités triées par date.
     */
    public static function get_recent_activities($limit = 10) {
        global $wpdb;
        $activities = [];

        // 1. Récupérer les 10 dernières inscriptions
        $registrations = get_posts([
            'post_type' => 'event_registration',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($registrations as $post) {
            $first_name = get_post_meta($post->ID, '_event_first_name', true);
            $city = get_post_meta($post->ID, '_event_city', true);
            $activities[] = [
                'id' => 'reg-' . $post->ID,
                'type' => 'inscription',
                'timestamp' => strtotime($post->post_date_gmt),
                'date' => $post->post_date,
                'text' => sprintf(
                    '%s (%s) vient de finaliser son inscription.',
                    esc_html($first_name),
                    esc_html($city ?: 'Ville inconnue')
                )
            ];
        }

        // 2. Récupérer les 10 dernières commandes payées
        $order_query = new WC_Order_Query([
            'limit' => $limit,
            'orderby' => 'date_created',
            'order' => 'DESC',
            'status' => ['completed', 'processing'],
            'meta_key' => '_event_payer_index', // S'assurer que ce sont bien des commandes pour l'événement
        ]);
        $orders = $order_query->get_orders();

        foreach ($orders as $order) {
            $activities[] = [
                'id' => 'order-' . $order->get_id(),
                'type' => 'paiement',
                'timestamp' => $order->get_date_created()->getTimestamp(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'text' => sprintf(
                    '%s (%s) a payé sa commande de %s.',
                    esc_html($order->get_billing_first_name()),
                    esc_html($order->get_billing_city() ?: 'Ville inconnue'),
                    wp_strip_all_tags(wc_price($order->get_total()))
                )
            ];
        }

        // 3. Fusionner et trier les activités par date
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        // 4. Garder seulement les X plus récentes
        return array_slice($activities, 0, $limit);
    }

	
}