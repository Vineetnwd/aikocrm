<?php
$current_path = basename($_SERVER['REQUEST_URI']);
if ($current_path == 'public' || $current_path == 'index.php' || $current_path == '') {
    $current_path = 'dashboard';
}
?>
<aside class="sidebar">
    <a href="dashboard" class="sidebar-logo">
        <i class="fas fa-rocket"></i> Aikaa CRM
    </a>
    <nav class="nav-menu">
        <div class="nav-item">
            <a href="dashboard" class="nav-link <?= ($path == 'dashboard' || $path == '') ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="leads" class="nav-link <?= $path == 'leads' ? 'active' : '' ?>">
                <i class="fas fa-user-friends"></i> Leads
            </a>
        </div>
        <div class="nav-item">
            <a href="invoices" class="nav-link <?= $path == 'invoices' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
        </div>
        <div class="nav-item">
            <a href="tasks" class="nav-link <?= $path == 'tasks' ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i> Tasks
            </a>
        </div>
        <div class="nav-item">
            <a href="reports" class="nav-link <?= $path == 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Reports
            </a>
        </div>
        <div class="nav-item">
            <a href="settings" class="nav-link <?= $path == 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </nav>
</aside>
