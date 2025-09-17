<?php
defined('ABSPATH') || exit;

class Event_Dashboard_Data {

    public static function get_kpi_stats() {
        global $wpdb;
        $total_registrations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'event_registration' AND post_status = 'publish'");
        $completed_orders_query = new WC_Order_Query(['limit' => -1, 'status' => ['completed', 'processing'], 'meta_key' => '_event_payer_index', 'return' => 'ids']);
        $completed_orders_ids = $completed_orders_query->get_orders();
        $completed_orders_count = count($completed_orders_ids);
        $total_revenue = 0;
        if(!empty($completed_orders_ids)){
            $total_revenue = $wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}wc_orders WHERE id IN (" . implode(',', $completed_orders_ids) . ")");
        }
        $date_query_week = [['after' => '7 days ago', 'inclusive' => true]];
        $new_registrations_week = count(get_posts(['post_type' => 'event_registration', 'post_status' => 'publish', 'date_query' => $date_query_week, 'fields' => 'ids']));
        $new_orders_week_query = new WC_Order_Query(['limit' => -1, 'status' => ['completed', 'processing'], 'date_created' => '>=' . date('Y-m-d', strtotime('-7 days')), 'meta_key' => '_event_payer_index']);
        $new_orders_week = $new_orders_week_query->get_orders();
        $new_revenue_week = 0;
        foreach($new_orders_week as $order){ $new_revenue_week += $order->get_total(); }
        return [
            'registrations' => ['total' => $total_registrations ?: 0, 'change' => $new_registrations_week],
            'orders' => ['total' => $completed_orders_count, 'change' => count($new_orders_week)],
            'revenue' => ['total' => $total_revenue ?: 0, 'change' => $new_revenue_week],
        ];
    }

    public static function get_data_for_charts() {
        $all_registrations_query = new WP_Query(['post_type' => 'event_registration', 'posts_per_page' => -1, 'post_status' => 'publish', 'suppress_filters' => true]);
        $chapters = []; $routes = [];
        if ($all_registrations_query->have_posts()) {
            while ($all_registrations_query->have_posts()) {
                $all_registrations_query->the_post();
                $post_id = get_the_ID();
                $chapter_id = get_post_meta($post_id, '_event_chapter_id', true);
                if ($chapter_id && $chapter_title = get_the_title($chapter_id)) { $chapters[$chapter_title] = ($chapters[$chapter_title] ?? 0) + 1; }
                $route = get_post_meta($post_id, '_event_route', true);
                if ($route) { $routes[ucfirst($route)] = ($routes[ucfirst($route)] ?? 0) + 1; }
            }
        }
        wp_reset_postdata();
        arsort($chapters); arsort($routes);
        $evolution = [];
        for ($i = 29; $i >= 0; $i--) { $date = date('Y-m-d', strtotime("-$i days")); $evolution[$date] = 0; }
        $daily_registrations_query = new WP_Query(['post_type' => 'event_registration', 'posts_per_page' => -1, 'post_status' => 'publish', 'date_query' => [['after' => '30 days ago', 'inclusive' => true]], 'suppress_filters' => true]);
        if ($daily_registrations_query->have_posts()) {
            while ($daily_registrations_query->have_posts()) {
                $daily_registrations_query->the_post();
                $date = get_the_date('Y-m-d');
                if (isset($evolution[$date])) { $evolution[$date]++; }
            }
        }
        wp_reset_postdata();
        $evolution_labels = array_map(function($date_str) { return date('d M', strtotime($date_str)); }, array_keys($evolution));
        return [
            'evolution' => ['labels' => $evolution_labels, 'data' => array_values($evolution)],
            'chapters' => ['labels' => array_keys($chapters), 'data' => array_values($chapters)],
            'routes' => ['labels' => array_keys($routes), 'data' => array_values($routes)],
        ];
    }
    
    public static function get_all_chapter_products() {
        $chapter_query = new WP_Query(['post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'product_cat', 'field'    => 'slug', 'terms'    => 'chapitres']], 'orderby' => 'title', 'order' => 'ASC']);
        return $chapter_query->posts;
    }

    public static function get_chapter_page_stats($chapter_id) {
        $stats = ['total' => 0, 'rouge' => 0, 'vert' => 0, 'bleu' => 0, 'cyan' => 0];
        if (empty($chapter_id)) return $stats;
        $registrations = new WP_Query(['post_type' => 'event_registration', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => [['key' => '_event_chapter_id', 'value' => $chapter_id]], 'suppress_filters' => true]);
        $stats['total'] = $registrations->found_posts;
        if ($registrations->have_posts()) {
            while ($registrations->have_posts()) {
                $registrations->the_post();
                $route = get_post_meta(get_the_ID(), '_event_route', true);
                if (isset($stats[$route])) { $stats[$route]++; }
            }
        }
        wp_reset_postdata();
        return $stats;
    }

    public static function get_parcours_page_stats($parcours_slug) {
        $stats = ['total' => 0, 'chapters' => []];
        if (empty($parcours_slug)) return $stats;
        $registrations = new WP_Query(['post_type' => 'event_registration', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => [['key' => '_event_route', 'value' => $parcours_slug]], 'suppress_filters' => true]);
        $stats['total'] = $registrations->found_posts;
        if ($registrations->have_posts()) {
            while ($registrations->have_posts()) {
                $registrations->the_post();
                $chapter_id = get_post_meta(get_the_ID(), '_event_chapter_id', true);
                if ($chapter_id && $chapter_title = get_the_title($chapter_id)) {
                    if (!isset($stats['chapters'][$chapter_title])) { $stats['chapters'][$chapter_title] = 0; }
                    $stats['chapters'][$chapter_title]++;
                }
            }
        }
        wp_reset_postdata();
        arsort($stats['chapters']);
        return $stats;
    }
    
    public static function get_all_bus_products() {
        $bus_query = new WP_Query(['post_type' => 'product', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'bus']], 'orderby' => 'title', 'order' => 'ASC']);
        return $bus_query->posts;
    }

    public static function get_bus_page_stats($bus_id) {
        $stats = ['total' => 0, 'chapters' => []];
        if (empty($bus_id)) return $stats;
        $registrations_with_any_bus = new WP_Query(['post_type' => 'event_registration', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => [['key' => '_event_bus_ids', 'compare' => 'EXISTS'], ['key' => '_event_bus_ids', 'value' => 'a:0:{}', 'compare' => '!=']], 'suppress_filters' => true]);
        if ($registrations_with_any_bus->have_posts()) {
            while ($registrations_with_any_bus->have_posts()) {
                $registrations_with_any_bus->the_post();
                $post_id = get_the_ID();
                $bus_ids_for_this_user = get_post_meta($post_id, '_event_bus_ids', true);
                if (is_array($bus_ids_for_this_user) && in_array($bus_id, $bus_ids_for_this_user)) {
                    $stats['total']++;
                    $chapter_id = get_post_meta($post_id, '_event_chapter_id', true);
                    if ($chapter_id && $chapter_title = get_the_title($chapter_id)) {
                        if (!isset($stats['chapters'][$chapter_title])) { $stats['chapters'][$chapter_title] = 0; }
                        $stats['chapters'][$chapter_title]++;
                    }
                }
            }
        }
        wp_reset_postdata();
        arsort($stats['chapters']);
        return $stats;
    }

    public static function get_navette_page_stats($navette_type) {
        $stats = ['total' => 0, 'chapters' => []];
        if (empty($navette_type) || !in_array($navette_type, ['bleu', 'vert'])) return $stats;
        $meta_key = '_event_need_transport_' . $navette_type;
        $registrations = new WP_Query(['post_type' => 'event_registration', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => [['key' => $meta_key, 'value' => 'oui']], 'suppress_filters' => true]);
        $stats['total'] = $registrations->found_posts;
        if ($registrations->have_posts()) {
            while ($registrations->have_posts()) {
                $registrations->the_post();
                $chapter_id = get_post_meta(get_the_ID(), '_event_chapter_id', true);
                if ($chapter_id && $chapter_title = get_the_title($chapter_id)) {
                    if (!isset($stats['chapters'][$chapter_title])) { $stats['chapters'][$chapter_title] = 0; }
                    $stats['chapters'][$chapter_title]++;
                }
            }
        }
        wp_reset_postdata();
        arsort($stats['chapters']);
        return $stats;
    }
    
    public static function get_rankings($meta_key, $limit = 5) {
        global $wpdb;
        $query = $wpdb->get_results($wpdb->prepare("SELECT meta_value, COUNT(*) as count FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'event_registration' AND p.post_status = 'publish' AND pm.meta_value != '' GROUP BY pm.meta_value ORDER BY count DESC LIMIT %d", $meta_key, $limit));
        if ($meta_key === '_event_chapter_id') {
            foreach($query as $item) { $item->meta_value = get_the_title($item->meta_value); }
        }
        return $query;
    }

    public static function get_all_registrations($args = []) {
        $defaults = ['per_page' => 20, 'paged' => 1, 'orderby' => 'date', 'order' => 'DESC', 's' => '', 'status_filter' => '', 'chapter_id' => 0, 'parcours_filter' => '', 'bus_filter' => 0, 'navette_filter' => ''];
        $args = wp_parse_args($args, $defaults);
        $query_args = ['post_type' => 'event_registration', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => true];
        $all_posts = get_posts($query_args);
        $formatted_items = [];
        foreach ($all_posts as $post_object) {
            $post_id = $post_object->ID;
            $chapter_id = get_post_meta($post_id, '_event_chapter_id', true);
            $formatted_items[] = [
                'id' => $post_id,
                'full_name' => get_post_meta($post_id, '_event_first_name', true) . ' ' . get_post_meta($post_id, '_event_last_name', true),
                'last_name_sort' => get_post_meta($post_id, '_event_last_name', true),
                'email' => get_post_meta($post_id, '_event_email', true),
                'chapter' => $chapter_id ? get_the_title($chapter_id) : 'N/A',
                'chapter_id' => $chapter_id,
                'route_slug' => get_post_meta($post_id, '_event_route', true),
                'bus_ids' => get_post_meta($post_id, '_event_bus_ids', true),
                'need_transport_bleu' => get_post_meta($post_id, '_event_need_transport_bleu', true),
                'need_transport_vert' => get_post_meta($post_id, '_event_need_transport_vert', true),
                'date' => $post_object->post_date,
                'status' => get_post_meta($post_id, '_event_status', true),
            ];
        }
        $filtered_items = $formatted_items;
        if (!empty($args['chapter_id'])) { $filtered_items = array_filter($filtered_items, function($item) use ($args) { return $item['chapter_id'] == $args['chapter_id']; }); }
        if (!empty($args['status_filter'])) { $filtered_items = array_filter($filtered_items, function($item) use ($args) { return $item['status'] === $args['status_filter']; }); }
        if (!empty($args['parcours_filter'])) { $filtered_items = array_filter($filtered_items, function($item) use ($args) { return $item['route_slug'] === $args['parcours_filter']; }); }
        if (!empty($args['bus_filter'])) { $filtered_items = array_filter($filtered_items, function($item) use ($args) { return is_array($item['bus_ids']) && in_array($args['bus_filter'], $item['bus_ids']); }); }
        if (!empty($args['navette_filter']) && in_array($args['navette_filter'], ['bleu', 'vert'])) {
            $key_to_check = 'need_transport_' . $args['navette_filter'];
            $filtered_items = array_filter($filtered_items, function($item) use ($key_to_check) {
                return isset($item[$key_to_check]) && trim(strtolower($item[$key_to_check])) == 'oui';
            });
        }
        if (!empty($args['s'])) {
            $search_term = strtolower($args['s']);
            $filtered_items = array_filter($filtered_items, function($item) use ($search_term) { return (strpos(strtolower($item['full_name']), $search_term) !== false || strpos(strtolower($item['email']), $search_term) !== false); });
        }
        $orderby = $args['orderby'];
        $order = $args['order'];
        usort($filtered_items, function($a, $b) use ($orderby, $order) {
            $key_a = ($orderby === 'full_name') ? $a['last_name_sort'] : $a[$orderby];
            $key_b = ($orderby === 'full_name') ? $b['last_name_sort'] : $b[$orderby];
            $comparison = strnatcasecmp((string)$key_a, (string)$key_b);
            return (strtoupper($order) === 'ASC') ? $comparison : -$comparison;
        });
        $total_items = count($filtered_items);
        $offset = ($args['paged'] - 1) * $args['per_page'];
        $paginated_items = array_slice($filtered_items, $offset, $args['per_page']);
        foreach ($paginated_items as &$item) { $item['date'] = date('d/m/Y H:i', strtotime($item['date'])); }
        return ['items' => $paginated_items, 'total' => $total_items];
    }
}