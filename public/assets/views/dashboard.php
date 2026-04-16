<?php
use Core\Database;
use Core\Auth;

$db = Database::getInstance();
$company_id = Auth::companyId();

// If not logged in, we might want to redirect, but for MVP demo we'll assume a company_id
if (!$company_id) {
    // Fallback for demo if no session exists
    $company_id = 1; 
}

// Fetch Stats
$totalLeads = $db->fetchOne("SELECT COUNT(*) as count FROM leads WHERE company_id = ?", [$company_id])['count'];
$wonDeals = $db->fetchOne("SELECT COUNT(*) as count FROM leads WHERE company_id = ? AND status = 'won'", [$company_id])['count'];
$pendingInvoices = $db->fetchOne("SELECT SUM(due_amount) as total FROM invoices WHERE company_id = ? AND payment_status != 'paid'", [$company_id])['total'] ?? 0;
$activeTasks = $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE company_id = ? AND status = 'pending'", [$company_id])['count'];

// Fetch Recent Leads
$recentLeads = $db->fetchAll("SELECT * FROM leads WHERE company_id = ? ORDER BY created_at DESC LIMIT 5", [$company_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Aikaa CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Dashboard Overview</h1>
                <div class="header-actions">
                    <a href="leads" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Lead
                    </a>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="card stat-card">
                    <span class="stat-label">Total Leads</span>
                    <span class="stat-value"><?= $totalLeads ?></span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Won Deals</span>
                    <span class="stat-value text-success"><?= $wonDeals ?></span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Pending Invoices</span>
                    <span class="stat-value">₹<?= number_format($pendingInvoices, 2) ?></span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Active Tasks</span>
                    <span class="stat-value"><?= $activeTasks ?></span>
                </div>
            </div>

            <!-- Recent Activity / Table -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="font-size: 1.25rem;">Recent Leads</h2>
                    <a href="leads" style="color: var(--primary); text-decoration: none; font-size: 0.875rem;">View All</a>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Name</th>
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Category</th>
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Status</th>
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLeads)): ?>
                            <tr>
                                <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted);">No leads found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLeads as $lead): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem 0;"><?= htmlspecialchars($lead['name']) ?></td>
                                <td style="padding: 1rem 0;">
                                    <span class="badge badge-<?= $lead['category'] ?>">
                                        <?= strtoupper($lead['category']) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem 0;"><?= ucfirst(str_replace('_', ' ', $lead['status'])) ?></td>
                                <td style="padding: 1rem 0;"><?= htmlspecialchars($lead['source']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
