<?php
defined('ABSPATH') || exit;

// --- 1. LOGIQUE SPÉCIFIQUE À CETTE PAGE ---

// Récupérer tous les chapitres pour la navigation "pills"
$all_chapters = Event_Dashboard_Data::get_all_chapter_products();

// Déterminer le chapitre actuellement sélectionné depuis l'URL
$current_chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;

// Si aucun chapitre n'est sélectionné dans l'URL, on prend le premier de la liste par défaut
if (empty($current_chapter_id) && !empty($all_chapters)) {
    $current_chapter_id = $all_chapters[0]->ID;
}

// --- NOUVELLE LOGIQUE POUR LE TEXTE DYNAMIQUE DU KPI ---
$kpi_subtitle = '';
if ($current_chapter_id) {
    $current_chapter_name = get_the_title($current_chapter_id);
    $kpi_subtitle = 'Pour le ' . $current_chapter_name;
} else {
    $kpi_subtitle = 'Aucun chapitre sélectionné';
}

// Récupérer les statistiques pour le chapitre sélectionné
$stats = Event_Dashboard_Data::get_chapter_page_stats($current_chapter_id);

// --- 2. LOGIQUE POUR LA TABLE (identique à la page principale, mais DÉFINIE ICI) ---
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
$order = (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) ? strtoupper($_GET['order']) : 'DESC';

// On prépare les arguments pour la fonction qui récupère les inscrits
$data_args = [
    'per_page'      => $per_page,
    'paged'         => $current_page,
    'orderby'       => $orderby,
    'order'         => $order,
    's'             => $search_term,
    'status_filter' => $status_filter,
    'chapter_id'    => $current_chapter_id // On ajoute le filtre du chapitre courant
];
$table_data = Event_Dashboard_Data::get_all_registrations($data_args);
$total_items = $table_data['total'];
$total_pages = ceil($total_items / $per_page);

?>
<div class="wrap event-dashboard-container">
    <div class="dashboard-header">
        <h1>Inscriptions par Chapitre</h1>
    </div>

  <div class="kpi-hero-card">
        <div class="kpi-hero-content">
            <h4>Total des inscrits</h4>
            <p><?php echo esc_html($stats['total']); ?></p>
            <span><?php echo esc_html($kpi_subtitle); ?></span>
        </div>
        <div class="kpi-hero-image">
            <svg viewBox="0 0 160 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M80 0L0 40V120L80 160L160 120V40L80 0Z" fill="url(#paint0_linear_1_2)" fill-opacity="0.1"/>
                <path d="M80 80V160L0 120V40L80 80Z" fill="url(#paint1_linear_1_2)" fill-opacity="0.3"/>
                <path d="M80 80L160 40V120L80 160V80Z" fill="url(#paint2_linear_1_2)" fill-opacity="0.5"/>
                <path d="M80 0L0 40L80 80L160 40L80 0Z" fill="url(#paint3_linear_1_2)"/>
                <defs>
                    <linearGradient id="paint0_linear_1_2" x1="80" y1="0" x2="80" y2="160" gradientUnits="userSpaceOnUse"><stop stop-color="white"/><stop offset="1" stop-color="white" stop-opacity="0"/></linearGradient>
                    <linearGradient id="paint1_linear_1_2" x1="40" y1="40" x2="40" y2="160" gradientUnits="userSpaceOnUse"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#2563EB"/></linearGradient>
                    <linearGradient id="paint2_linear_1_2" x1="120" y1="40" x2="120" y2="160" gradientUnits="userSpaceOnUse"><stop stop-color="#60A5FA"/><stop offset="1" stop-color="#3B82F6"/></linearGradient>
                    <linearGradient id="paint3_linear_1_2" x1="80" y1="0" x2="80" y2="80" gradientUnits="userSpaceOnUse"><stop stop-color="white" stop-opacity="0.7"/><stop offset="1" stop-color="white"/></linearGradient>
                </defs>
            </svg>
        </div>
    </div>

    <div class="dashboard-grid kpi-grid-new" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="kpi-card-small route-red"><h4>Trajet Rouge</h4><p><?php echo esc_html($stats['rouge']); ?></p></div>
        <div class="kpi-card-small route-green"><h4>Trajet Vert</h4><p><?php echo esc_html($stats['vert']); ?></p></div>
        <div class="kpi-card-small route-blue"><h4>Trajet Bleu</h4><p><?php echo esc_html($stats['bleu']); ?></p></div>
        <div class="kpi-card-small route-cyan"><h4>Trajet Cyan</h4><p><?php echo esc_html($stats['cyan']); ?></p></div>
    </div>

    <?php if (!empty($all_chapters)) : ?>
    <nav class="pill-nav">
        <?php 
        // On garde les paramètres actuels (tri, recherche) quand on change de chapitre
        $base_nav_url = remove_query_arg('chapter_id'); 
        foreach ($all_chapters as $chapter) : ?>
            <a href="<?php echo esc_url(add_query_arg('chapter_id', $chapter->ID, $base_nav_url)); ?>" 
               class="<?php echo ($current_chapter_id == $chapter->ID) ? 'active' : ''; ?>">
               <?php echo esc_html($chapter->post_title); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

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
                                    <a href="#" class="action-icon-button view-registration" data-id="<?php echo $item['id']; ?>" title="Voir le profil">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                    <a href="#" class="action-icon-button edit-registration" data-id="<?php echo $item['id']; ?>" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    <a href="#" class="action-icon-button delete-registration" data-id="<?php echo $item['id']; ?>" data-nonce="<?php echo wp_create_nonce('event_delete_registration_' . $item['id']); ?>" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.134-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.067-2.09 1.02-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
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
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '« Précédent',
                    'next_text' => 'Suivant »',
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>