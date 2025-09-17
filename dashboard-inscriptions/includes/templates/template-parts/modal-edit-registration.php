<!-- MODALE MISE À JOUR AVEC LES DEUX SETS DE BOUTONS -->
<div id="edit-registration-modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <form id="edit-registration-form">
            <div class="modal-dynamic-content">
                <!-- Le contenu sera injecté ici -->
            </div>
            <div class="modal-actions">
                <!-- Boutons pour le mode ÉDITION -->
                <div class="modal-footer-edit">
                    <button type="button" class="button button-secondary close-modal">Annuler</button>
                    <button type="submit" class="button button-primary">Enregistrer les modifications</button>
                </div>
                <!-- Bouton pour le mode VUE -->
                <div class="modal-footer-view">
                    <button type="button" class="button button-primary close-modal">Fermer</button>
                </div>
            </div>
            <input type="hidden" id="edit-registration-id" name="registration_id" value="">
            <?php wp_nonce_field('event_update_registration_nonce', 'update_nonce'); ?>
        </form>
    </div>
</div>