<?php
/**
 * Template pour la sous-page "Chapitres" du tableau de bord.
 * Affiche les statistiques et la liste des inscriptions par chapitre en respectant le design original.
 */
defined('ABSPATH') || exit;

// --- 1. LOGIQUE DE RÉCUPÉRATION DES DONNÉES ---

// Récupère tous les produits "chapitre" (uniquement les IDs pour la performance)
$all_chapters_ids = Event_Dashboard_Data::get_all_chapter_products();

// Récupère l'ID du chapitre actuellement sélectionné depuis l'URL.
$current_chapter_id = isset($_GET['chapter_id']) ? absint($_GET['chapter_id']) : 0;

// Récupère les statistiques détaillées pour le chapitre sélectionné.
$stats = Event_Dashboard_Data::get_chapter_page_stats($current_chapter_id);

// --- 2. LOGIQUE POUR LA TABLE (TRI, PAGINATION, RECHERCHE) ---

$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
$order = (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) ? strtoupper($_GET['order']) : 'DESC';

// Préparation des arguments pour la fonction qui récupère les inscrits
$data_args = [
    'per_page'      => $per_page,
    'paged'         => $current_page,
    'orderby'       => $orderby,
    'order'         => $order,
    's'             => $search_term,
    'status_filter' => $status_filter,
    'chapter_id'    => $current_chapter_id // Filtre crucial pour cette page
];

$table_data = Event_Dashboard_Data::get_all_registrations($data_args);
$total_items = $table_data['total'];
$total_pages = ceil($total_items / $per_page);

?>
<div class="wrap event-dashboard-container">

    <div class="dashboard-header">
        <h1>Inscriptions par Chapitre</h1>
    </div>

    <nav class="pill-nav">
        <?php $base_nav_url = admin_url('admin.php?page=event-dashboard&subpage=chapitres'); ?>
        
        <a href="<?php echo esc_url($base_nav_url); ?>"
           class="<?php echo (empty($current_chapter_id)) ? 'active' : ''; ?>">
            Tous les chapitres
        </a>

        <?php
        if (!empty($all_chapters_ids)) {
            foreach ($all_chapters_ids as $chapter_id) { ?>
                <a href="<?php echo esc_url(add_query_arg('chapter_id', $chapter_id, $base_nav_url)); ?>"
                   class="<?php echo ($current_chapter_id == $chapter_id) ? 'active' : ''; ?>">
                    <?php echo esc_html(get_the_title($chapter_id)); ?>
                </a>
            <?php }
        }
        ?>
    </nav>
    
    <div class="kpi-hero-card">
        <div class="kpi-hero-content">
            <h4>Total des inscrits</h4>
            <p><?php echo esc_html($stats['total']); ?></p>
            <span>
                <?php 
                if ($current_chapter_id) {
                    echo 'Pour le chapitre "' . esc_html(get_the_title($current_chapter_id)) . '"';
                } else {
                    echo 'Pour tous les chapitres';
                }
                ?>
            </span>
        </div>
        <div class="kpi-hero-image">
            <svg viewBox="0 0 160 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                </svg>
        </div>
    </div>

    <div class="dashboard-grid kpi-grid-new" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="kpi-card-small route-red"><h4>Trajet Rouge</h4><p><?php echo esc_html($stats['rouge']); ?></p></div>
        <div class="kpi-card-small route-green"><h4>Trajet Vert</h4><p><?php echo esc_html($stats['vert']); ?></p></div>
        <div class="kpi-card-small route-blue"><h4>Trajet Bleu</h4><p><?php echo esc_html($stats['bleu']); ?></p></div>
        <div class="kpi-card-small route-cyan"><h4>Trajet Cyan</h4><p><?php echo esc_html($stats['cyan']); ?></p></div>
    </div>

    <div class="dashboard-card full-width">
        <h3>Liste des inscrits du chapitre</h3>
        
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <input type="hidden" name="subpage" value="chapitres" />
            <input type="hidden" name="chapter_id" value="<?php echo esc_attr($current_chapter_id); ?>" />
            
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
                        $columns = ['full_name' => 'Nom Complet', 'email' => 'Email', 'date' => 'Date', 'status' => 'Statut', 'actions' => 'Actions'];
                        $sortable_columns = ['full_name', 'email', 'date', 'status'];
                        
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
                                <td data-colname="Date"><?php echo esc_html($item['date']); ?></td>
                                <td data-colname="Statut"><span class="status-badge status-<?php echo esc_attr($item['status']); ?>"><?php echo esc_html(ucfirst($item['status'])); ?></span></td>
                                <td data-colname="Actions" class="column-actions">
                                    <a href="#" class="action-icon-button view-registration" data-id="<?php echo esc_attr($item['id']); ?>" title="Voir le profil">
                                        <svg></svg>
                                    </a>
                                    <a href="#" class="action-icon-button edit-registration" data-id="<?php echo esc_attr($item['id']); ?>" title="Modifier">
                                        <svg></svg>
                                    </a>
                                    <a href="#" class="action-icon-button delete-registration" data-id="<?php echo esc_attr($item['id']); ?>" data-nonce="<?php echo wp_create_nonce('event_delete_registration_' . $item['id']); ?>" title="Supprimer">
                                        <svg></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo count($columns); ?>">Aucune inscription trouvée pour ce chapitre.</td>
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
                    'type'      => 'list', // 'list' pour générer une <ul>, 'plain' pour des liens simples
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>