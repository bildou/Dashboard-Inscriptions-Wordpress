<?php
defined('ABSPATH') || exit;

class Event_Dashboard_Ajax_Handlers {

    public function __construct() {
        add_action('wp_ajax_get_registration_details', [$this, 'get_registration_details']);
        add_action('wp_ajax_update_registration_details', [$this, 'update_registration_details']);
        add_action('wp_ajax_delete_registration', [$this, 'delete_registration']);
		add_action('wp_ajax_event_dashboard_get_recent_activities', [$this, 'handle_get_recent_activities']);
        add_action('wp_ajax_event_dashboard_get_order_details', [$this, 'handle_get_order_details']);
        }

    public function get_registration_details() {
        check_ajax_referer('event_get_details_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }

        $post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$post_id || get_post_type($post_id) !== 'event_registration') {
            wp_send_json_error(['message' => 'ID manquant ou invalide.']);
        }
        
        if (class_exists('Event_Registration') && method_exists('Event_Registration', 'get_participant_details')) {
            $details = Event_Registration::get_participant_details($post_id);
            } 
          // 1. Récupérer et ajouter le Statut
        if (!isset($details['status'])) {
            $details['status'] = get_post_meta($post_id, '_event_status', true) ?: 'inconnu';
        }

        // 2. Récupérer et ajouter l'ID de la Commande
        if (!isset($details['order_id'])) {
            $details['order_id'] = get_post_meta($post_id, '_event_order_id', true) ?: 'N/A';
        }

        // 3. Récupérer et ajouter "Inscrit par"
        if (!isset($details['registered_by'])) {
            $details['registered_by'] = get_post_meta($post_id, '_event_registered_by', true) ?: 'Lui-même';
        }
        
        // 4. On s'assure que les champs de base sont là (au cas où la fonction principale échouerait)
        if (!isset($details['first_name'])) $details['first_name'] = get_post_meta($post_id, '_event_first_name', true);
        if (!isset($details['last_name'])) $details['last_name'] = get_post_meta($post_id, '_event_last_name', true);
        if (!isset($details['full_name'])) $details['full_name'] = trim($details['first_name'] . ' ' . $details['last_name']);

        // On renvoie le tableau de détails, maintenant complet.
        if (!empty($details)) {
            wp_send_json_success($details);
        } else {
            wp_send_json_error(['message' => 'Impossible de récupérer les détails de l\'inscription.']);
        }
        
        }
    
    public function update_registration_details() {
        check_ajax_referer('event_update_registration_nonce', 'update_nonce');
         if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        
        $post_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(['message' => 'ID invalide.']);
        }

        // Liste des champs que l'on autorise à modifier depuis la modale
        $editable_fields = [
            'first_name', 'last_name', 'email', 
            'phone', 'city', 'department'
        ];

        foreach($editable_fields as $field) {
            if(isset($_POST[$field])) {
                $meta_key = '_event_' . $field;
                $value = ($field === 'email') ? sanitize_email($_POST[$field]) : sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        wp_send_json_success(['message' => 'Inscription mise à jour avec succès !']);
    }
    
    public function delete_registration() {
        $post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        check_ajax_referer('event_delete_registration_' . $post_id, 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }

        if (!$post_id) {
            wp_send_json_error(['message' => 'ID invalide.']);
        }
        
        $result = wp_delete_post($post_id, true);
        
        if ($result) {
            wp_send_json_success(['message' => 'Inscription supprimée.']);
        } else {
            wp_send_json_error(['message' => 'Erreur lors de la suppression.']);
        }
    }
	
	/**
     * NOUVEAU GESTIONNAIRE AJAX
     * Gère la requête pour récupérer les activités récentes.
     */
    public function handle_get_recent_activities() {
        check_ajax_referer('event_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        $activities = Event_Dashboard_Data::get_recent_activities($limit);
        
        wp_send_json_success($activities);
    }
    
    /**
     * AJOUTEZ CETTE NOUVELLE FONCTION COMPLÈTE
     *
     * Gère la requête AJAX pour récupérer et formater les détails d'une commande WooCommerce.
     */
    public function handle_get_order_details() {
        // Pour la sécurité, on peut réutiliser le nonce de la modale principale
        check_ajax_referer('event_get_details_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if (!$order_id || !in_array(get_post_type($order_id), wc_get_order_types())) {
            wp_send_json_error(['message' => 'ID de commande invalide.']);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Commande introuvable.']);
        }

        // On formate les données pour les envoyer au JavaScript
        $line_items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $line_items[] = [
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total'    => wp_strip_all_tags(wc_price($item->get_total()))
            ];
        }

        $data = [
            'id'              => $order->get_order_number(),
            'status'          => wc_get_order_status_name($order->get_status()),
            'status_slug'     => $order->get_status(),
            'date'            => $order->get_date_created()->format('d/m/Y H:i'),
            'total'           => $order->get_formatted_order_total(),
            'payment_method'  => $order->get_payment_method_title(),
            'customer'        => [
                'name'    => $order->get_formatted_billing_full_name(),
                'email'   => $order->get_billing_email(),
            ],
            'items'           => $line_items,
            'edit_url'        => $order->get_edit_order_url(),
        ];

        wp_send_json_success($data);
    }
}