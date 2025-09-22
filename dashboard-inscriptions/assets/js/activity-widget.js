jQuery(function($) {
    const widgetContainer = $('#recent-activity-widget');
    const feedContainer = $('#recent-activity-feed');
    // Si le conteneur n'existe pas, on arrête tout.
    if (!widgetContainer.length) {
        return;
    }

    // --- Constantes de configuration ---
    const CYCLE_INTERVAL = 4000; // 4 secondes entre chaque cycle
    const ANIMATION_DURATION = 800; // 0.8 seconde, doit correspondre à la transition CSS
    const MAX_POOL_SIZE = 10; // Nombre max d'activités à garder en mémoire

    // --- Variables d'état ---
    let activityPool = []; // La "réserve" d'activités
    let currentIndex = 0; // Pointeur pour la prochaine activité à afficher
    let cycleIntervalId = null; // Pour pouvoir mettre en pause et relancer

    /**
     * Calcule le temps écoulé depuis une date donnée.
     * @param {string} dateString - La date au format 'YYYY-MM-DD HH:MM:SS'.
     * @returns {string} Le temps relatif (ex: "Il y a 5 min").
     */
    function timeAgo(dateString) {
        if (!dateString) return "Date inconnue";
        try {
            const past = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now.getTime() - past.getTime()) / 1000);

            if (seconds < 60) return "À l'instant";
            if (seconds < 3600) return `Il y a ${Math.floor(seconds / 60)} min`;
            if (seconds < 86400) return `Il y a ${Math.floor(seconds / 3600)} h`;
         // CORRECTION DE LA LOGIQUE : La condition était inversée
        const days = Math.floor(seconds / 86400);
        if (days < 30) return `Il y a ${days} j`;

        return past.toLocaleDateString('fr-FR'); // Affiche la date complète pour ce qui est plus vieux
    } catch (e) {
        console.error("Erreur de parsing de date :", dateString, e);
        return "Date invalide"; 
    }
}

    /**
     * Crée le HTML pour un nouvel élément d'activité.
     * @param {object} activity - L'objet d'activité.
     * @returns {jQuery} L'objet jQuery représentant le nouvel élément <li>.
     */
    function createActivityItemHTML(activity) {
        if (!activity || !activity.id) return null;

        let iconClass = 'fa-solid fa-bell',
            typeClass = 'type-defaut';
        switch (activity.type) {
            case 'inscription':
                iconClass = 'fa-solid fa-user-plus';
                typeClass = 'type-inscription';
                break;
            case 'paiement':
                iconClass = 'fa-solid fa-receipt';
                typeClass = 'type-paiement';
                break;
        }
        const timestampText = timeAgo(activity.date);
        const textContent = activity.text;

        return $(`
            <li id="activity-${activity.id}" class="activity-item ${typeClass}">
                <span class="activity-icon ${typeClass}"><i class="${iconClass}"></i></span>
                <span class="activity-text">${textContent}</span>
                <span class="activity-time">${timestampText}</span>
            </li>
        `);
    }

    /**
     * Fonction principale du cycle d'animation.
     */
    function cycleActivity() {
        if (activityPool.length <= 2) {
            stopTicker();
            return;
        }

        const topItem = feedContainer.children().first();
        const bottomItem = feedContainer.children().last();

        // Étape 1 : L'élément du haut commence à sortir.
        topItem.addClass('is-exiting');

        // Étape 2 : On attend la fin de son animation pour la suite.
        setTimeout(() => {
            // Une fois l'animation de sortie terminée, on supprime l'élément.
            topItem.remove();

            // Étape 3 : L'élément qui était en bas monte à la première position.
            // La transition CSS gère le mouvement fluide.
            bottomItem.removeClass('is-visible-bottom').addClass('is-visible-top');

            // Étape 4 : On prépare et on ajoute le nouvel élément.
            currentIndex = (currentIndex + 1) % activityPool.length;
            const newItem = createActivityItemHTML(activityPool[currentIndex]);
            if (!newItem) return;

            newItem.addClass('is-entering'); // Il est positionné en bas et invisible
            feedContainer.append(newItem);

            // Étape 5 : On déclenche son animation d'entrée.
            requestAnimationFrame(() => {
                newItem.removeClass('is-entering').addClass('is-visible-bottom');
            });

        }, ANIMATION_DURATION);
    }

    /**
     * Démarre le cycle d'animation.
     */
    function startTicker() {
        // Nettoyer tout intervalle précédent pour éviter les doublons.
        if (cycleIntervalId) clearInterval(cycleIntervalId);
        // Ne démarre que s'il y a assez d'éléments pour cycler.
        if (activityPool.length > 2) {
            cycleIntervalId = setInterval(cycleActivity, CYCLE_INTERVAL);
        }
    }

    /**
     * Met en pause le cycle d'animation.
     */
    function stopTicker() {
        clearInterval(cycleIntervalId);
    }

    /**
     * Charge les données initiales au chargement de la page.
     */
    function initialLoad() {
        // Affiche l'état de chargement.
        feedContainer.html('<li class="loading-state">Chargement des activités...</li>');

        $.post(eventDashboardAjax.ajax_url, {
            action: 'event_dashboard_get_recent_activities',
            nonce: eventDashboardAjax.nonce,
            limit: MAX_POOL_SIZE
        }).done(function(response) {
            if (response.success && response.data && response.data.length > 0) {
                activityPool = response.data;
                feedContainer.empty();

                // Afficher le premier élément.
                if (activityPool.length > 0) {
                    const item1 = createActivityItemHTML(activityPool[0]);
                    if (item1) {
                        item1.addClass('is-visible-top');
                        feedContainer.append(item1);
                    }
                }

                // Afficher le deuxième élément.
                if (activityPool.length > 1) {
                    const item2 = createActivityItemHTML(activityPool[1]);
                    if (item2) {
                        item2.addClass('is-visible-bottom');
                        feedContainer.append(item2);
                        currentIndex = 1;
                    }
                }

                // Démarrer le cycle d'animation.
                startTicker();
            } else {
                feedContainer.html('<li class="no-activity">Aucune activité récente.</li>');
            }
        }).fail(() => {
            feedContainer.html('<li class="error-state">Erreur de chargement.</li>');
        });
    }

    // --- Initialisation et Événements ---

    // Lancer le chargement initial.
    initialLoad();

    // Mettre en pause/reprendre l'animation au survol de la souris.
    widgetContainer.on('mouseenter', stopTicker).on('mouseleave', startTicker);
});