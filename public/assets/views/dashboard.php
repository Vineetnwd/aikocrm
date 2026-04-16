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
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Welcome back, <?= Auth::user()['name'] ?></p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.location.href='<?= APP_URL ?>/public/index.php/leads'">
                        <i class="fas fa-plus"></i> New Lead
                    </button>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="card stat-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-users" style="font-size: 0.875rem;"></i>
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 700; color: var(--success);">+12%</span>
                    </div>
                    <span class="stat-label">Total Leads</span>
                    <span class="stat-value"><?= $totalLeads ?></span>
                </div>

                <div class="card stat-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #d1fae5; color: var(--success); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-handshake" style="font-size: 0.875rem;"></i>
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 700; color: var(--success);">+2%</span>
                    </div>
                    <span class="stat-label">Won Deals</span>
                    <span class="stat-value"><?= $wonDeals ?></span>
                </div>

                <div class="card stat-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef3c7; color: var(--warning); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-invoice" style="font-size: 0.875rem;"></i>
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 700; color: var(--danger);">- ₹4.2k</span>
                    </div>
                    <span class="stat-label">Pending Invoices</span>
                    <span class="stat-value">₹<?= number_format($pendingInvoices, 2) ?></span>
                </div>

                <div class="card stat-card">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: var(--info); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-tasks" style="font-size: 0.875rem;"></i>
                        </div>
                        <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;">Active</span>
                    </div>
                    <span class="stat-label">Active Tasks</span>
                    <span class="stat-value"><?= $activeTasks ?></span>
                </div>
            </div>

            <!-- Content Grid -->
            <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 1.5rem;">
                <!-- Recent Leads Table -->
                <div class="card" style="padding: 1.25rem;">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="font-size: 1rem; font-weight: 800;">Recent Leads</h2>
                        <a href="<?= APP_URL ?>/public/index.php/leads" style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-decoration: none;">View All</a>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Lead Name</th>
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Priority</th>
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Status</th>
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLeads)): ?>
                                    <tr><td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">No leads found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentLeads as $lead): ?>
                                    <tr style="border-bottom: 1px solid #f8fafc;">
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <div style="display:flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800;">
                                                    <?= strtoupper(substr($lead['name'], 0, 1)) ?>
                                                </div>
                                                <span style="font-size: 0.875rem; font-weight: 600;"><?= htmlspecialchars($lead['name']) ?></span>
                                            </div>
                                        </td>
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <span class="badge badge-<?= $lead['category'] == 'hot' ? 'danger' : ($lead['category'] == 'warm' ? 'warning' : 'success') ?>" style="font-size: 0.65rem; padding: 0.2rem 0.5rem;">
                                                <?= strtoupper($lead['category']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <span style="font-size: 0.8125rem; font-weight: 500; color: var(--text-muted);"><?= ucfirst(str_replace('_', ' ', $lead['status'])) ?></span>
                                        </td>
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <div style="display:flex; align-items: center; gap: 0.5rem; color: #64748b; font-size: 0.8125rem;">
                                                <i class="fab fa-<?= strtolower($lead['source']) == 'facebook' ? 'facebook' : 'chrome' ?>" style="font-size: 0.75rem;"></i>
                                                <?= ucfirst($lead['source']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Widget: Distribution Card -->
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem;">Source Distribution</h2>
                    <div style="display:flex; flex-direction: column; gap: 1rem;">
                        <?php 
                        $sources = ['Facebook' => 45, 'Website' => 30, 'Ads' => 15, 'Referral' => 10];
                        foreach ($sources as $name => $percent): ?>
                        <div>
                            <div style="display:flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.4rem; font-weight: 600;">
                                <span><?= $name ?></span>
                                <span style="color: var(--text-muted);"><?= $percent ?>%</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: <?= $percent ?>%; height: 100%; background: var(--primary); border-radius: 10px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 2rem; padding: 1rem; background: var(--primary-soft); border-radius: 0.75rem; border: 1px dashed var(--primary);">
                        <p style="font-size: 0.7rem; color: var(--primary); font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem;">Pro Tip</p>
                        <p style="font-size: 0.75rem; color: #475569; font-weight: 500;">Your Facebook ads are performing 3x better than organic search this week.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
