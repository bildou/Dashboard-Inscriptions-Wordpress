<?php
defined('ABSPATH') || exit;

// --- 1. RÉCUPÉRATION DE TOUTES LES DONNÉES NÉCESSAIRES ---
$kpi_stats = Event_Dashboard_Data::get_kpi_stats();
$chart_data = Event_Dashboard_Data::get_data_for_charts();
$export_url = class_exists('Event_Registration') ? wp_nonce_url(admin_url('admin-post.php?action=event_reg_export_csv'), 'event_reg_export_nonce') : '#';

// Données pour les classements
$top_chapters = Event_Dashboard_Data::get_rankings('_event_chapter_id', 5);
$top_cities = Event_Dashboard_Data::get_rankings('_event_city', 5);
$top_departments = Event_Dashboard_Data::get_rankings('_event_department', 5);

// Vérifications pour l'affichage conditionnel des graphiques
$has_evolution_data = !empty($chart_data['evolution']['data']) && max($chart_data['evolution']['data']) > 0;
$has_chapters_data = !empty($chart_data['chapters']['data']);
$has_routes_data = !empty($chart_data['routes']['data']);

// --- 2. LOGIQUE POUR LA TABLE PERSONNALISÉE (TRI, PAGINATION, RECHERCHE) ---
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
$order = (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) ? strtoupper($_GET['order']) : 'DESC';

$data_args = [
    'per_page' => $per_page, 'paged' => $current_page,
    'orderby'  => $orderby, 'order' => $order,
    's'        => $search_term, 'status_filter' => $status_filter
];
$table_data = Event_Dashboard_Data::get_all_registrations($data_args);
$total_items = $table_data['total'];
$total_pages = ceil($total_items / $per_page);

?>
  <div class="wrap event-dashboard-container">
            <div class="dashboard-header">
                <h1><?php _e('Dashboard', 'event-dashboard'); ?></h1>
                <a href="<?php echo esc_url($export_url); ?>" class="button button-primary">📥 <?php _e('Exporter tout en CSV', 'event-dashboard'); ?></a>
            </div>

            <!-- Section 1: KPIs -->
            <div class="dashboard-grid wow-kpi-grid">
                <div class="wow-kpi-card registrations">
                    <div class="card-main-content">
                        <div class="icon-wrapper color-blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m-7.5-2.962A3.75 3.75 0 0 1 9 10.5a3.75 3.75 0 0 1 9 0c0 2.072-1.678 3.75-3.75 3.75S9 12.572 9 10.5m4.5 0c0-2.072-1.678-3.75-3.75-3.75S6 8.428 6 10.5c0 2.072 1.678 3.75 3.75 3.75m0 0c0 2.072 1.678 3.75 3.75 3.75s3.75-1.678 3.75-3.75M9 10.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg></div>
                        <div class="text-wrapper">
                            <p class="kpi-value"><?php echo number_format_i18n($kpi_stats['registrations']['total']); ?></p>
                            <p class="kpi-title">Inscriptions totales</p>
                        </div>
                    </div>
                    <div class="card-footer-change">
                        <svg class="arrow-up" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                        <p><span class="change-value positive">+<?php echo number_format_i18n($kpi_stats['registrations']['change']); ?></span> cette semaine</p>
                    </div>
                </div>
                <div class="wow-kpi-card orders">
                    <div class="card-main-content">
                        <div class="icon-wrapper color-green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c.51 0 .962-.328 1.093-.828l2.857-9.643A.75.75 0 0 0 21 3H5.25v.083l-1.28-4.832A.75.75 0 0 0 3.22 0H1.5a.75.75 0 0 0 0 1.5h.53l3.54 13.357A.75.75 0 0 0 6 15H4.5a.75.75 0 0 0 0 1.5h15a.75.75 0 0 0 0-1.5H9.75a.75.75 0 0 1-.75-.75Z" /></svg></div>
                        <div class="text-wrapper">
                            <p class="kpi-value"><?php echo number_format_i18n($kpi_stats['orders']['total']); ?></p>
                            <p class="kpi-title">Commandes validées</p>
                        </div>
                    </div>
                    <div class="card-footer-change">
                        <svg class="arrow-up" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                        <p><span class="change-value positive">+<?php echo number_format_i18n($kpi_stats['orders']['change']); ?></span> cette semaine</p>
                    </div>
                </div>
                <div class="wow-kpi-card revenue">
                    <div class="card-main-content">
                        <div class="icon-wrapper color-orange"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V6.375c0-.621.504-1.125 1.125-1.125h.375m18 3.75-1.5-1.5m-1.5 1.5-1.5-1.5m-1.5 1.5-1.5-1.5m-1.5 1.5-1.5-1.5m-6.75 1.5-1.5-1.5m-1.5 1.5-1.5-1.5m1.5-1.5-1.5 1.5m3-1.5-1.5 1.5m1.5-1.5-1.5 1.5m1.5-1.5-1.5 1.5m3-1.5-1.5 1.5M9 12l1.5 1.5" /></svg></div>
                        <div class="text-wrapper">
                            <p class="kpi-value"><?php echo wp_strip_all_tags(wc_price($kpi_stats['revenue']['total'])); ?></p>
                            <p class="kpi-title">Revenu total</p>
                        </div>
                    </div>
                    <div class="card-footer-change">
                         <?php if ($kpi_stats['revenue']['change'] >= 0): ?>
                            <svg class="arrow-up" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                            <p><span class="change-value positive">+<?php echo wp_strip_all_tags(wc_price($kpi_stats['revenue']['change'])); ?></span> cette semaine</p>
                         <?php else: ?>
                            <svg class="arrow-down" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            <p><span class="change-value negative"><?php echo wp_strip_all_tags(wc_price($kpi_stats['revenue']['change'])); ?></span> cette semaine</p>
                         <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Section 2: Graphiques -->
            <div class="dashboard-grid chart-grid">
                <div class="dashboard-card"><h3>Inscriptions (30 derniers jours)</h3><?php if ($has_evolution_data): ?><canvas id="evolutionChart" style="max-height: 250px;"></canvas><?php else: ?><p>Aucune inscription au cours des 30 derniers jours.</p><?php endif; ?></div>
                <div class="dashboard-card"><h3>Répartition par Chapitre</h3><?php if ($has_chapters_data): ?><canvas id="chaptersChart" style="max-height: 250px;"></canvas><?php else: ?><p>Pas de données de chapitre à afficher.</p><?php endif; ?></div>
                <div class="dashboard-card"><h3>Répartition par Parcours</h3><?php if ($has_routes_data): ?><canvas id="routesChart" style="max-height: 250px;"></canvas><?php else: ?><p>Pas de données de parcours à afficher.</p><?php endif; ?></div>
            </div>
            
            <!-- Section 3: Classements -->
            <div class="dashboard-grid list-grid">
                <div class="dashboard-card"><h3>Top 5 Chapitres</h3><ul class="dashboard-list"><?php if (!empty($top_chapters)): foreach($top_chapters as $item) : ?><li><?php echo esc_html($item->meta_value); ?> <span><?php echo esc_html($item->count); ?> inscrits</span></li><?php endforeach; else: ?><p>Pas de données.</p><?php endif; ?></ul></div>
                <div class="dashboard-card"><h3>Top 5 Villes</h3><ul class="dashboard-list"><?php if (!empty($top_cities)): foreach($top_cities as $item) : ?><li><?php echo esc_html($item->meta_value); ?> <span><?php echo esc_html($item->count); ?> inscrits</span></li><?php endforeach; else: ?><p>Pas de données.</p><?php endif; ?></ul></div>
                <div class="dashboard-card"><h3>Top 5 Départements</h3><ul class="dashboard-list"><?php if (!empty($top_departments)): foreach($top_departments as $item) : ?><li><?php echo esc_html($item->meta_value); ?> <span><?php echo esc_html($item->count); ?> inscrits</span></li><?php endforeach; else: ?><p>Pas de données.</p><?php endif; ?></ul></div>
            </div>
            
            <!-- Section 4: Liste complète des inscriptions -->
            <div class="dashboard-card full-width">
                <h3>Toutes les Inscriptions</h3>
                <form method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
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
                                    <td colspan="<?php echo count($columns); ?>">Aucune inscription trouvée.</td>
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