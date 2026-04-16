<?php
$current_path = basename($_SERVER['REQUEST_URI']);
if ($current_path == 'public' || $current_path == 'index.php' || $current_path == '') {
    $current_path = 'dashboard';
}
?>
<aside class="sidebar">
    <a href="<?= APP_URL ?>/public/index.php/dashboard" class="sidebar-logo">
        <i class="fas fa-rocket"></i> Aikaa CRM
    </a>
    <nav class="nav-menu">
        <div class="nav-item">
            <a href="<?= APP_URL ?>/public/index.php/dashboard" class="nav-link <?= $path == 'dashboard' || $path == '' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/public/index.php/leads" class="nav-link <?= $path == 'leads' ? 'active' : '' ?>">
                <i class="fas fa-user-friends"></i> Leads
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/public/index.php/invoices" class="nav-link <?= $path == 'invoices' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/public/index.php/tasks" class="nav-link <?= $path == 'tasks' ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i> Tasks
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/public/index.php/reports" class="nav-link <?= $path == 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Reports
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= APP_URL ?>/public/index.php/settings" class="nav-link <?= $path == 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </nav>
    <div class="sidebar-footer" style="padding: 1rem; border-top: 1px solid #1e293b; margin-top: auto;">
        <div style="font-size: 0.875rem; color: #94a3b8; margin-bottom: 0.5rem;">
            Logged in as: <strong><?= \Core\Auth::user()['name'] ?? 'Admin' ?></strong>
        </div>
        <a href="<?= APP_URL ?>/public/index.php/logout" class="nav-link" style="padding: 0.5rem 0; color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>
