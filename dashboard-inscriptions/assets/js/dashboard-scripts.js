jQuery(document).ready(function($) {

    if (typeof window.eventDashboard !== 'undefined' && window.eventDashboard.chartData) {
        const chartData = window.eventDashboard.chartData;

        // Graphique 1: Évolution des inscriptions
        const evoCtx = document.getElementById('evolutionChart');
        if (evoCtx && chartData.evolution.labels.length > 0) {
            new Chart(evoCtx, {
                type: 'line',
                data: {
                    labels: chartData.evolution.labels,
                    datasets: [{
                        label: 'Inscriptions par jour',
                        data: chartData.evolution.data,
                        borderColor: '#38bdf8',
                        backgroundColor: '#38bdf840',
                        fill: true,
                        tension: 0.3
                    }]
                }
            });
        }

        // Graphique 2: Répartition par chapitre
        const chapCtx = document.getElementById('chaptersChart');
        if (chapCtx && chartData.chapters.labels.length > 0) {
            new Chart(chapCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.chapters.labels,
                    datasets: [{
                        label: 'Inscriptions par chapitre',
                        data: chartData.chapters.data,
                        backgroundColor: ['#38bdf8', '#f87171', '#1d4ed8', '#0e7490', '#db2777', '#ea580c'],
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } } }
            });
        }

        // Graphique 3: Répartition par parcours
        const routesCtx = document.getElementById('routesChart');
        if (routesCtx && chartData.routes.labels.length > 0) {
            new Chart(routesCtx, {
                type: 'pie',
                data: {
                    labels: chartData.routes.labels,
                    datasets: [{
                        label: 'Inscriptions par parcours',
                        data: chartData.routes.data,
                        backgroundColor: ['#38bdf8', '#f87171', '#4ade80', '#06b6d4', '#f59e0b'],
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } } }
            });
        }
    }

    // --- LOGIQUE POUR LA MODALE (VUE & ÉDITION) ---
    const modal = $('#edit-registration-modal');
    const formContainer = modal.find('.modal-content');
    const registrationIdInput = modal.find('#edit-registration-id');

    // Fonction pour construire le contenu HTML de la modale
    function buildModalHtml(data, isViewMode) {
        const personalInfoTitle = isViewMode ? 'Informations Personnelles' : 'Informations Personnelles (modifiables)';
        let personalInfoHtml;

        if (isViewMode) {
            // Mode VUE : Affiche des textes simples, non modifiables
            personalInfoHtml = `
                <div class="data-grid">
                    <div class="data-group"><span class="data-label">Prénom</span><span class="data-value">${data.first_name}</span></div>
                    <div class="data-group"><span class="data-label">Nom</span><span class="data-value">${data.last_name}</span></div>
                    <div class="data-group"><span class="data-label">Email</span><span class="data-value">${data.email}</span></div>
                    <div class="data-group"><span class="data-label">Téléphone</span><span class="data-value">${data.phone}</span></div>
                    <div class="data-group"><span class="data-label">Ville</span><span class="data-value">${data.city}</span></div>
                    <div class="data-group"><span class="data-label">Département</span><span class="data-value">${data.department}</span></div>
                </div>
            `;
        } else {
            // Mode ÉDITION : Affiche des champs de formulaire
            personalInfoHtml = `
                <div class="data-grid">
                    <div class="data-group"><label>Prénom</label><input type="text" name="first_name" value="${data.first_name}"></div>
                    <div class="data-group"><label>Nom</label><input type="text" name="last_name" value="${data.last_name}"></div>
                    <div class="data-group"><label>Email</label><input type="email" name="email" value="${data.email}"></div>
                    <div class="data-group"><label>Téléphone</label><input type="tel" name="phone" value="${data.phone}"></div>
                    <div class="data-group"><label>Ville</label><input type="text" name="city" value="${data.city}"></div>
                    <div class="data-group"><label>Département</label><input type="text" name="department" value="${data.department}"></div>
                </div>
            `;
        }
        
        // Retourne le HTML complet pour le contenu dynamique de la modale
        return `
            <div class="modal-profile-header">
                <div class="profile-avatar">${data.first_name.charAt(0)}${data.last_name.charAt(0)}</div>
                <div class="profile-info">
                    <h2>${data.full_name}</h2>
                    <p>Inscrit par : ${data.registered_by}</p>
                </div>
                <span class="status-badge status-${data.status}">${data.status}</span>
            </div>

            <div class="modal-quick-actions">
                <a href="mailto:${data.email}" class="modal-action-button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    Envoyer un mail
                </a>
                <a href="tel:${data.phone}" class="modal-action-button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                    Appeler
                </a>
            </div>

            <div class="modal-profile-body">
                <div class="modal-section">
                    <h3>${personalInfoTitle}</h3>
                    ${personalInfoHtml}
                </div>
                <div class="modal-section">
                    <h3>Détails de l'inscription</h3>
                    <div class="details-list">
                        <div class="data-item"><div class="data-item-label"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.092 1.21-.138 2.43-.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7Zm-15.75 0a48.663 48.663 0 0 1 7.324 0" /></svg><span>Chapitre</span></div><span class="data-item-value">${data.chapter_title}</span></div>
                        <div class="data-item"><div class="data-item-label"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" /></svg><span>Parcours</span></div><span class="data-item-value">${data.route_label}</span></div>
                        <div class="data-item"><div class="data-item-label"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V14.25m-17.25 4.5v-1.875a3.375 3.375 0 013.375-3.375h9.75a3.375 3.375 0 013.375 3.375v1.875m-17.25 4.5h16.5M5.625 13.5a1.875 1.875 0 100-3.75 1.875 1.875 0 000 3.75z" /></svg><span>Transport / Bus</span></div><span class="data-item-value">${data.bus_display !== 'Aucun bus sélectionné' ? data.bus_display : data.own_transport_display}</span></div>
                        <div class="data-item"><div class="data-item-label"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg><span>Don</span></div><span class="data-item-value">${data.donation_display}</span></div>
                        <div class="data-item"><div class="data-item-label"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.174C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.174 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" /></svg><span>Droit à l'image</span></div><span class="data-item-value">${data.image_rights_display.includes('J’accepte') ? `<span class="value-pill positive">${data.image_rights_display}</span>` : `<span class="value-pill negative">${data.image_rights_display}</span>`}</span></div>
                        <div class="data-item"><div class="data-item-label"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12v.75m0 3v.75m0 3v.75m0 3V18m-9-12h18M3 20.25h18M3 6.75h18M3 13.5h18M6 20.25v-1.5m12 1.5v-1.5" /></svg><span>Commande N°</span></div><span class="data-item-value">#${data.order_id}</span></div>
                    </div>
                </div>
            </div>
        `;
    }

    // Gestionnaire de clic générique pour ouvrir la modale
    function openModal(regId, isViewMode) {
        registrationIdInput.val(regId);
        
        if (isViewMode) {
            modal.removeClass('edit-mode').addClass('view-mode');
        } else {
            modal.removeClass('view-mode').addClass('edit-mode');
        }
        
        formContainer.find('.modal-dynamic-content').html('<p class="loading-message">Chargement du profil...</p>');
        modal.css('display', 'flex');

        $.post(eventDashboard.ajax_url, {
            action: 'get_registration_details',
            nonce: eventDashboard.get_details_nonce,
            id: regId
        }, function(response) {
            if(response.success) {
                const modalHtml = buildModalHtml(response.data, isViewMode);
                formContainer.find('.modal-dynamic-content').html(modalHtml);
            } else {
                 formContainer.find('.modal-dynamic-content').html('<p style="color:red;">Erreur: ' + response.data.message + '</p>');
            }
        });
    }

    // Clic sur l'icône "VOIR"
    $(document).on('click', '.view-registration', function(e) {
        e.preventDefault();
        openModal($(this).data('id'), true);
    });

    // Clic sur l'icône "MODIFIER"
    $(document).on('click', '.edit-registration', function(e) {
        e.preventDefault();
        openModal($(this).data('id'), false);
    });

    // Fermer la modale
    modal.on('click', '.close-modal, .modal-backdrop', function(e) {
        e.preventDefault();
        modal.fadeOut();
    });
    modal.on('click', '.modal-content', function(e) {
        e.stopPropagation();
    });

    // Soumettre le formulaire de modification
    $('#edit-registration-form').on('submit', function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.text('Sauvegarde...').prop('disabled', true);
        
        const formData = $(this).serialize() + '&action=update_registration_details';

        $.post(eventDashboard.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                modal.fadeOut();
                location.reload(); 
            } else {
                alert('Erreur: ' + response.data.message);
                submitButton.text('Enregistrer').prop('disabled', false);
            }
        });
    });

    // Supprimer une inscription
    $(document).on('click', '.delete-registration', function(e) {
        e.preventDefault();
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette inscription ? Cette action est irréversible.')) {
            return;
        }

        const link = $(this);
        const regId = link.data('id');
        const nonce = link.data('nonce');

        $.post(eventDashboard.ajax_url, {
            action: 'delete_registration',
            nonce: nonce,
            id: regId
        }, function(response) {
            if (response.success) {
                link.closest('tr').css('background-color', '#ffb8b8').fadeOut(400, function() { $(this).remove(); });
            } else {
                alert('Erreur: ' + response.data.message);
            }
        });
    });
});