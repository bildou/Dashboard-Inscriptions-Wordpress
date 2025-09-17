<?php
/**
 * Template pour la barre de navigation latérale (Sidebar) du tableau de bord.
 */
defined('ABSPATH') || exit;

// On récupère le nom de la sous-page active pour le style du menu.
// Si aucune n'est définie, on utilise 'dashboard' par défaut.
$active_page = isset($_GET['subpage']) ? sanitize_key($_GET['subpage']) : 'dashboard';

// On construit l'URL de base pour nos liens de navigation.
$base_url = admin_url('admin.php?page=event-dashboard');

?>
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <!-- Vous pouvez remplacer ce SVG par votre propre logo -->
        <svg class="sidebar-logo" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.3333 42.6667C17.3333 36.855 22.0216 32.1667 27.8333 32.1667C33.645 32.1667 38.3333 27.4783 38.3333 21.6667C38.3333 15.855 33.645 11.1667 27.8333 11.1667C22.0216 11.1667 17.3333 6.47833 17.3333 0.666664" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M30.6667 5.33333C30.6667 11.145 25.9783 15.8333 20.1667 15.8333C14.355 15.8333 9.66666 20.5217 9.66666 26.3333C9.66666 32.145 14.355 36.8333 20.1667 36.8333C25.9783 36.8333 30.6667 41.5217 30.6667 47.3333" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <nav class="sidebar-menu">
        <ul>
            <li class="<?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url($base_url); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12M3.75 3h16.5M3.75 3v.375c0 .621.504 1.125 1.125 1.125h14.25c.621 0 1.125-.504 1.125-1.125V3M3.75 16.5v2.25A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25v-2.25M16.5 7.5l-3.75 3.75-3.75-3.75" /></svg>
                    <span>Dashboard</span>
                </a>
            </li>
			<li class="<?php echo ($active_page === 'chapitres') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('subpage', 'chapitres', $base_url)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.092 1.21-.138 2.43-.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7Zm-15.75 0a48.663 48.663 0 0 1 7.324 0" /></svg>
                    <span>Chapitres</span>
                </a>
            </li>
             <li class="<?php echo ($active_page === 'parcours') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('subpage', 'parcours', $base_url)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122" /></svg>
                    <span>Trajets</span>
                </a>
            </li>
			<li class="<?php echo ($active_page === 'bus') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('subpage', 'bus', $base_url)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V14.25m-17.25 4.5v-1.875a3.375 3.375 0 0 1 3.375-3.375h9.75a3.375 3.375 0 0 1 3.375 3.375v1.875m-17.25 4.5h16.5M5.625 13.5a1.875 1.875 0 1 0 0-3.75 1.875 1.875 0 0 0 0 3.75z" /></svg>
                    <span>Bus</span>
                </a>
            </li>
			<li class="<?php echo ($active_page === 'navettes') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('subpage', 'navettes', $base_url)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.82a4.5 4.5 0 0 1 5.84-2.56ZM12 12a4.5 4.5 0 0 1 4.5-4.5h1.88a6 6 0 0 1-7.38 5.84v-1.34Z" /></svg>
                    <span>Navettes</span>
                </a>
            </li>
             <li class="<?php echo ($active_page === 'stats') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('subpage', 'stats', $base_url)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12M3.75 3h16.5M3.75 3v.375c0 .621.504 1.125 1.125 1.125h14.25c.621 0 1.125-.504 1.125-1.125V3M3.75 16.5v2.25A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25v-2.25M16.5 7.5l-3.75 3.75-3.75-3.75" /></svg>
                    <span>Statistiques</span>
                </a>
            </li>
            <li class="<?php echo ($active_page === 'settings') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('subpage', 'settings', $base_url)); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.438.995s.145.755.438.995l1.003.827c.447.368.623.98.26 1.431l-1.296 2.247a1.125 1.125 0 0 1-1.37.49l-1.217-.456c-.355-.133-.75-.072-1.075.124a6.57 6.57 0 0 1-.22.127c-.331.184-.581.496-.645.87l-.213 1.281c-.09.542-.56.94-1.11.94h-2.593c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a6.52 6.52 0 0 1-.22-.127c-.324-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.37-.49l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.437-.995s-.145-.755-.437-.995l-1.004-.827a1.125 1.125 0 0 1-.26-1.431l1.296-2.247a1.125 1.125 0 0 1 1.37-.49l1.217.456c.355.133.75.072 1.075-.124.072-.044.146-.087.22-.127.332-.184.582-.496.645-.87l.213-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <span>Réglages</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>