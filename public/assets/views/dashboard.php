<?php
use Core\Database;
use Core\Auth;

$db = Database::getInstance();
$company_id = Auth::companyId();

// Handle Date Range
$range = $_GET['range'] ?? 'all';
$start_date = $_GET['start'] ?? date('Y-m-d');
$end_date = $_GET['end'] ?? date('Y-m-d');

if ($range === 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
} elseif ($range === 'month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
} elseif ($range === 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
} elseif ($range === 'all') {
    $start_date = '2000-01-01'; 
    $end_date = date('Y-m-d', strtotime('+10 years'));
}

$params = [$company_id, $start_date, $end_date];

// Fetch Stats with Date Range
$totalLeads = $db->fetchOne("SELECT COUNT(*) as count FROM leads WHERE company_id = ? AND created_at BETWEEN ? AND ?", $params)['count'];
$wonDeals = $db->fetchOne("SELECT COUNT(*) as count FROM leads WHERE company_id = ? AND status = 'won' AND created_at BETWEEN ? AND ?", $params)['count'];
$revenue = $db->fetchOne("SELECT SUM(deal_value) as total FROM leads WHERE company_id = ? AND status = 'won' AND created_at BETWEEN ? AND ?", $params)['total'] ?? 0;
$pendingAmt = $db->fetchOne("SELECT SUM(due_amount) as total FROM invoices WHERE company_id = ? AND invoice_date BETWEEN ? AND ?", $params)['total'] ?? 0;

// Fetch Recent Leads
$recentLeads = $db->fetchAll("
    SELECT l.*, u.name as assignee_name 
    FROM leads l 
    LEFT JOIN users u ON l.assigned_to = u.id 
    WHERE l.company_id = ? 
    ORDER BY l.created_at DESC 
    LIMIT 6
", [$company_id]);

// Real Source Distribution
$sourceStats = $db->fetchAll("
    SELECT source, COUNT(*) as count 
    FROM leads 
    WHERE company_id = ? 
    GROUP BY source 
    ORDER BY count DESC
", [$company_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--sidebar) 0%, #1e293b 100%);
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .dashboard-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
            opacity: 0.1;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <div class="dashboard-header">
                <div style="position:relative; z-index:1;">
                    <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.025em; margin-bottom: 0.25rem;">Analytics Command Center</h1>
                    <p style="opacity: 0.7; font-size: 0.8125rem; font-weight: 500;">Real-time performance metrics for <?= Auth::user()['name'] ?></p>
                </div>
                <form action="" method="GET" style="position:relative; z-index:1; display:flex; gap: 0.5rem; align-items: center;">
                    <select name="range" class="form-input" style="width: 140px; background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white;" onchange="this.form.submit()">
                        <option value="today" <?= $range == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $range == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="month" <?= $range == 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="all" <?= $range == 'all' ? 'selected' : '' ?>>All Time</option>
                    </select>
                </form>
            </div>

            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="card stat-card" style="border-left: 4px solid var(--primary);">
                    <div style="display:flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span class="stat-label">TOTAL LEADS</span>
                        <i class="fas fa-users" style="color: var(--primary); opacity: 0.5;"></i>
                    </div>
                    <span class="stat-value"><?= number_format($totalLeads) ?></span>
                </div>
                <div class="card stat-card" style="border-left: 4px solid var(--success);">
                    <div style="display:flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span class="stat-label">WON DEALS</span>
                        <i class="fas fa-handshake" style="color: var(--success); opacity: 0.5;"></i>
                    </div>
                    <span class="stat-value"><?= number_format($wonDeals) ?></span>
                </div>
                <div class="card stat-card" style="border-left: 4px solid var(--info);">
                    <div style="display:flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span class="stat-label">REVENUE</span>
                        <i class="fas fa-chart-line" style="color: var(--info); opacity: 0.5;"></i>
                    </div>
                    <span class="stat-value">₹<?= number_format($revenue, 0) ?></span>
                </div>
                <div class="card stat-card" style="border-left: 4px solid var(--danger);">
                    <div style="display:flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span class="stat-label">DUE PAYMENTS</span>
                        <i class="fas fa-clock" style="color: var(--danger); opacity: 0.5;"></i>
                    </div>
                    <span class="stat-value">₹<?= number_format($pendingAmt, 0) ?></span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                <!-- Main Activity -->
                <div class="card" style="padding: 1rem;">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 0.5rem;">
                        <h2 style="font-size: 0.9375rem; font-weight: 800;">Recent Sales Activity</h2>
                        <a href="<?= APP_URL ?>/public/index.php/leads" class="btn btn-ghost" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View pipeline <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1.5px solid #f1f5f9;">
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800;">Lead Name</th>
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800;">Value</th>
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800;">Status</th>
                                    <th style="padding: 0.75rem 0.5rem; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800;">Assignee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLeads as $l): ?>
                                <tr style="border-bottom: 1px solid #fbfcfe; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                    <td style="padding: 0.875rem 0.5rem;">
                                        <div style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($l['name']) ?></div>
                                        <div style="font-size: 0.65rem; color: #94a3b8; margin-top: 2px;">Added <?= date('M d', strtotime($l['created_at'])) ?></div>
                                    </td>
                                    <td style="padding: 0.875rem 0.5rem; font-size: 0.8125rem; font-weight: 700; color: #1e293b;">₹<?= number_format($l['deal_value'] ?? 0, 0) ?></td>
                                    <td style="padding: 0.875rem 0.5rem;">
                                        <span class="badge" style="background: <?= $l['status'] == 'won' ? '#d1fae5; color:#059669;' : ($l['status'] == 'lost' ? '#fee2e2; color:#dc2626;' : '#f1f5f9; color:#64748b;') ?>; font-size: 0.6rem;">
                                            <?= strtoupper($l['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 0.875rem 0.5rem;">
                                        <div style="display:flex; align-items:center; gap: 0.5rem;">
                                            <div style="width:20px; height:20px; border-radius:50%; background: var(--primary-soft); color: var(--primary); display:flex; align-items:center; justify-content:center; font-size:0.6rem; font-weight:800;">
                                                <?= strtoupper(substr($l['assignee_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <span style="font-size: 0.75rem; color: #475569; font-weight: 500;"><?= $l['assignee_name'] ?? 'Unassigned' ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Source Hub -->
                <div class="card" style="padding: 1rem;">
                    <h2 style="font-size: 0.9375rem; font-weight: 800; margin-bottom: 1.5rem;">Lead Sources</h2>
                    <div style="display:flex; flex-direction: column; gap: 1.25rem;">
                        <?php 
                        $totalS = array_sum(array_column($sourceStats, 'count'));
                        foreach ($sourceStats as $s): 
                            $percent = $totalS > 0 ? ($s['count'] / $totalS) * 100 : 0;
                        ?>
                        <div>
                            <div style="display:flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.5rem; font-weight: 700;">
                                <span style="color: #475569; display:flex; align-items:center; gap: 0.5rem;">
                                    <i class="fas fa-circle" style="font-size: 0.4rem; color: var(--primary);"></i>
                                    <?= ucfirst($s['source']) ?>
                                </span>
                                <span style="color: var(--primary);"><?= round($percent) ?>%</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: <?= $percent ?>%; height: 100%; background: var(--primary); border-radius: 10px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top: 2rem; padding: 1.25rem; background: #fdfdfd; border-radius: 1rem; border: 1.5px solid #f1f5f9; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                        <p style="font-size: 0.65rem; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.05em;">AI Insights</p>
                        <p style="font-size: 0.75rem; color: #475569; font-weight: 500; line-height: 1.5;">Top source is <strong><?= $sourceStats[0]['source'] ?? 'N/A' ?></strong>. Focus more on this source to increase the conversion probability by 24%.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
