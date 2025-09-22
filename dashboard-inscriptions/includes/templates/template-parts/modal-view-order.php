<?php
/**
 * Template pour la modale d'aperçu de la commande.
 */
defined('ABSPATH') || exit;
?>
<div id="view-order-modal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Détails de la Commande</h2>
            <button type="button" class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="order-details-content">
                <p class="loading-message">Chargement...</p>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" id="full-order-link" target="_blank" class="button button-primary">Voir la commande complète</a>
            <button type="button" class="button button-secondary close-modal">Fermer</button>
        </div>
    </div>
</div>