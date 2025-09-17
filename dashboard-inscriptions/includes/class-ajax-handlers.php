<?php
defined('ABSPATH') || exit;

class Event_Dashboard_Ajax_Handlers {

    public function __construct() {
        add_action('wp_ajax_get_registration_details', [$this, 'get_registration_details']);
        add_action('wp_ajax_update_registration_details', [$this, 'update_registration_details']);
        add_action('wp_ajax_delete_registration', [$this, 'delete_registration']);
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
            wp_send_json_success($details);
        } else {
            wp_send_json_error(['message' => 'Le plugin "Event Registration" ou une de ses fonctions est introuvable.']);
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
}