<?php
/**
 * Template pour la sous-page "Bus" du tableau de bord d'événements.
 * Affiche les statistiques, la jauge et la liste des inscriptions pour un bus sélectionné.
 */
defined('ABSPATH') || exit;

// --- 1. LOGIQUE DE RÉCUPÉRATION DES DONNÉES ---

// Récupère les IDs de tous les produits "bus".
$all_buses_ids = Event_Dashboard_Data::get_all_bus_products();

// Détermine l'ID du bus actuellement sélectionné de manière sécurisée.
$current_bus_id = 0; // Initialisation à 0 par défaut.
if (isset($_GET['bus_id'])) {
    // Si un bus est sélectionné dans l'URL, on utilise son ID.
    $current_bus_id = absint($_GET['bus_id']);
} elseif (!empty($all_buses_ids)) {
    // Sinon, si la liste des bus n'est pas vide, on prend le premier ID comme valeur par défaut.
    $current_bus_id = $all_buses_ids[0];
}

// Récupère les statistiques spécifiques (total, capacité, chapitres) pour le bus sélectionné.
$stats = Event_Dashboard_Data::get_bus_page_stats($current_bus_id);

// Récupère les paramètres de la table depuis l'URL pour le tri, la pagination et les filtres.
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
$order = (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) ? strtoupper($_GET['order']) : 'DESC';

// Prépare les arguments pour la requête qui va chercher la liste des inscrits.
$data_args = [
    'per_page'      => $per_page,
    'paged'         => $current_page,
    'orderby'       => $orderby,
    'order'         => $order,
    's'             => $search_term,
    'status_filter' => $status_filter,
    'bus_filter'    => $current_bus_id // Filtre la liste pour n'afficher que les inscrits du bus courant.
];

// Exécute la requête pour obtenir la liste des inscrits.
$table_data = Event_Dashboard_Data::get_all_registrations($data_args);
$total_items = $table_data['total'];
$total_pages = ceil($total_items / $per_page);

?>
<div class="wrap event-dashboard-container">

    <div class="dashboard-header">
        <h1>Inscriptions par Bus</h1>
    </div>

    <?php if (!empty($all_buses_ids)) : ?>
    <nav class="pill-nav">
        <?php
        $base_nav_url = admin_url('admin.php?page=event-dashboard&subpage=bus');
        foreach ($all_buses_ids as $bus_id) : ?>
            <a href="<?php echo esc_url(add_query_arg('bus_id', $bus_id, $base_nav_url)); ?>"
               class="<?php echo ($current_bus_id == $bus_id) ? 'active' : ''; ?>">
                <?php echo esc_html(get_the_title($bus_id)); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="kpi-hero-card">
        <div class="kpi-hero-content">
            <h4>Total des inscrits</h4>
            <p><?php echo esc_html($stats['total']); ?></p>
            <span>
                <?php 
                if ($current_bus_id) {
                    echo 'Pour le bus "' . esc_html(get_the_title($current_bus_id)) . '"';
                } else {
                    echo 'Aucun bus sélectionné';
                }
                ?>
            </span>
        </div>
    </div>
    
    <div class="dashboard-grid kpi-grid-new" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <?php 
        if (!empty($stats['chapters'])) : 
            $color_classes = ['route-red', 'route-green', 'route-blue', 'route-cyan', 'chapter-kpi'];
            $i = 0;
            foreach ($stats['chapters'] as $chapter_name => $count) : 
                $color_class = $color_classes[$i % count($color_classes)];
        ?>
            <div class="kpi-card-small <?php echo esc_attr($color_class); ?>">
                <h4><?php echo esc_html($chapter_name); ?></h4>
                <p><?php echo esc_html($count); ?></p>
            </div>
        <?php 
            $i++;
            endforeach;
        else : ?>
            <p style="grid-column: 1 / -1; text-align: center;">Aucune répartition par chapitre pour ce bus.</p>
        <?php endif; ?>
    </div>

  <div class="dashboard-grid" style="grid-template-columns: 1fr; margin-top: 24px;">
    <?php
    // CORRECTION : On affiche la jauge dès qu'un bus est sélectionné.
    if ($current_bus_id > 0) :
        $count = $stats['total'];
        $capacity = get_post_meta($bus_id, 'bus_capacity', true) ?: 0;
        $percentage = ($capacity > 0) ? round(($count / $capacity) * 100) : 0;

        $places_restantes = $capacity - $count;
        $gradient_class = 'gradient-ok';
        if ($capacity > 0 && $places_restantes <= 10) $gradient_class = 'gradient-warning';
        if ($capacity > 0 && $places_restantes <= 0) $gradient_class = 'gradient-full';
    ?>
        <div class="dashboard-card bus-gauge-card">
            <div class="card-header">
                <h4>Remplissage du  "<?php echo esc_html(get_the_title($current_bus_id)); ?>"</h4>
                <span class="gauge-numbers"><?php echo esc_html($count); ?> / <?php echo esc_html($capacity > 0 ? $capacity : 'N/A'); ?></span>
            </div>
            <div class="gauge-container">
                <div class="gauge-bar <?php echo esc_attr($gradient_class); ?>" style="width: <?php echo esc_attr($percentage); ?>%;">
                    <span class="gauge-percentage"><?php echo esc_html($percentage); ?>%</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

    <div class="dashboard-card full-width">
        <h3>Liste détaillée des inscrits du bus</h3>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? ''); ?>" />
            <input type="hidden" name="subpage" value="bus" />
            <input type="hidden" name="bus_id" value="<?php echo esc_attr($current_bus_id); ?>" />
            
            <div class="table-controls">
                <div class="table-filters">
                    <select name="status_filter">
                        <option value="">Tous les statuts</option>
                        <option value="paid" <?php selected($status_filter, 'paid'); ?>>Payé</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente</option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Annulé</option>
                    </select>
                    <input type="submit" class="button" value="Filtrer">
                </div>
                <div class="table-search-box">
                    <input type="search" name="s" value="<?php echo esc_attr($search_term); ?>" placeholder="Rechercher...">
                    <input type="submit" class="button" value="Rechercher">
                </div>
            </div>
        </form>

        <div class="table-wrapper">
            <table class="custom-data-table">
                <thead>
                    <tr>
                        <?php
                        $columns = ['full_name' => 'Nom Complet', 'email' => 'Email', 'chapter' => 'Chapitre', 'date' => 'Date', 'status' => 'Statut', 'actions' => 'Actions'];
                        $sortable_columns = ['full_name', 'email', 'chapter', 'date', 'status'];
                        
                        foreach ($columns as $slug => $label) {
                            if (in_array($slug, $sortable_columns)) {
                                $sort_order = ($orderby === $slug && $order === 'ASC') ? 'DESC' : 'ASC';
                                $url = add_query_arg(['orderby' => $slug, 'order' => $sort_order]);
                                $class = ($orderby === $slug) ? 'sorted ' . strtolower($order) : '';
                                echo "<th class='{$class}'><a href='{$url}'>{$label}</a></th>";
                            } else {
                                echo "<th class='column-{$slug}'>{$label}</th>";
                            }
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($table_data['items'])) : ?>
                        <?php foreach ($table_data['items'] as $item) : ?>
                            <tr>
                                <td data-colname="Nom Complet"><strong><?php echo esc_html($item['full_name']); ?></strong></td>
                                <td data-colname="Email"><?php echo esc_html($item['email']); ?></td>
                                <td data-colname="Chapitre"><?php echo esc_html($item['chapter']); ?></td>
                                <td data-colname="Date"><?php echo esc_html($item['date']); ?></td>
                                <td data-colname="Statut"><span class="status-badge status-<?php echo esc_attr($item['status']); ?>"><?php echo esc_html(ucfirst($item['status'])); ?></span></td>
                                <td data-colname="Actions" class="column-actions">
                                    <a href="#" class="action-icon-button view-registration" data-id="<?php echo esc_attr($item['id']); ?>" title="Voir le profil">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo count($columns); ?>">Aucune inscription trouvée pour ce bus.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1) : ?>
            <div class="table-pagination">
                <?php
                echo paginate_links([
                    'base'      => remove_query_arg('paged') . '%_%',
                    'format'    => '&paged=%#%',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '«',
                    'next_text' => '»',
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>