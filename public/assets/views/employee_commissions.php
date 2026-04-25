<?php
use Core\Database;
use Core\Auth;

if (!Auth::check()) {
    header("Location: " . APP_URL . "/public/index.php/login");
    exit;
}

$db = Database::getInstance();
$company_id = Auth::companyId();
$emp_id = $_GET['id'] ?? null;

if (!$emp_id) {
    echo "Employee ID required.";
    exit;
}

$employee = $db->fetchOne("SELECT * FROM employees WHERE id = ? AND company_id = ?", [$emp_id, $company_id]);
if (!$employee) {
    echo "Employee not found.";
    exit;
}

// Fetch Earnings (from employee_commission_earnings + legacy leads)
$earnings = $db->fetchAll("
    SELECT e.id, e.amount, e.created_at as date, e.invoice_payment_id, 
           l.name as lead_name, l.requirement, i.invoice_number
    FROM employee_commission_earnings e
    JOIN leads l ON e.lead_id = l.id
    LEFT JOIN invoices i ON e.invoice_id = i.id
    WHERE e.employee_id = ? AND e.company_id = ?
    
    UNION ALL
    
    SELECT NULL as id, l.commission_amount as amount, l.updated_at as date, NULL as invoice_payment_id,
           l.name as lead_name, l.requirement, NULL as invoice_number
    FROM leads l
    WHERE l.assigned_employee_id = ? AND l.company_id = ? AND l.commission_amount > 0
    AND NOT EXISTS (
        SELECT 1 FROM employee_commission_earnings e2 WHERE e2.lead_id = l.id
    )
    ORDER BY date DESC
", [$emp_id, $company_id, $emp_id, $company_id]);

// Fetch Payouts (from employee_commission_payouts)
$payouts = $db->fetchAll("
    SELECT id, amount, payout_date as date, reference_note as note 
    FROM employee_commission_payouts 
    WHERE employee_id = ? AND company_id = ? 
    ORDER BY payout_date DESC, created_at DESC
", [$emp_id, $company_id]);

$totalEarned = (float)$employee['total_commission_earned'];
$totalPaid = (float)$employee['total_commission_paid'];
$balance = $totalEarned - $totalPaid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Commission History | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 1rem; padding: 1.25rem 1.5rem; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .stat-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        .stat-label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 1.75rem; font-weight: 900; color: #0f172a; line-height: 1.1; }

        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        
        .card-header { padding: 1.25rem; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .card-title { font-size: 1rem; font-weight: 800; color: #0f172a; }
        
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .data-table th { background: #f8fafc; padding: 0.875rem 1rem; text-align: left; font-weight: 800; color: #64748b; text-transform: uppercase; font-size: 0.68rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
        .data-table td { padding: 0.875rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        @media (max-width: 900px) {
            .stat-cards { grid-template-columns: 1fr; }
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <div>
                <a href="<?= APP_URL ?>/public/index.php/employees" style="color:var(--text-muted);font-size:0.8125rem;text-decoration:none;margin-bottom:0.25rem;display:inline-block;">
                    <i class="fas fa-arrow-left"></i> Back to Employees
                </a>
                <h1 class="page-title"><?= htmlspecialchars($employee['name']) ?>'s Commissions</h1>
                <p style="color:var(--text-muted);font-size:0.8125rem;font-weight:500;">
                    <?= $employee['employee_id'] ? '#' . htmlspecialchars($employee['employee_id']) : 'No Employee ID' ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="<?= APP_URL ?>/public/index.php/commissions" class="btn btn-primary" style="height:38px;padding:0 1.25rem;display:flex;align-items:center;gap:0.5rem;text-decoration:none;">
                    <i class="fas fa-hand-holding-usd"></i> Go to Settlements
                </a>
            </div>
        </header>

        <!-- Stats -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-coins"></i></div>
                <div><div class="stat-label">Total Earned</div><div class="stat-value">₹<?= number_format($totalEarned, 2) ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-check-circle"></i></div>
                <div><div class="stat-label">Total Paid</div><div class="stat-value">₹<?= number_format($totalPaid, 2) ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-hourglass-half"></i></div>
                <div><div class="stat-label">Pending Balance</div><div class="stat-value" style="color: <?= $balance > 0 ? '#dc2626' : '#64748b' ?>">₹<?= number_format($balance, 2) ?></div></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Earnings Ledger -->
            <div style="background:white; border:1px solid var(--border); border-radius:1rem; overflow:hidden;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-arrow-down" style="color:#059669;"></i> Earned from Leads</div>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="data-table">
                        <thead style="position:sticky; top:0;">
                            <tr>
                                <th>Date</th>
                                <th>Lead / Invoice</th>
                                <th style="text-align:right;">Earned</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($earnings)): ?>
                                <tr><td colspan="4" style="text-align:center;padding:2rem;color:#94a3b8;">No commission earnings yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($earnings as $e): ?>
                                <tr>
                                    <td style="color:#64748b;font-size:0.75rem;"><?= date('d M Y', strtotime($e['date'])) ?></td>
                                    <td>
                                        <div style="font-weight:700;color:#0f172a;">
                                            <?= htmlspecialchars($e['lead_name']) ?>
                                            <?php if ($e['invoice_number']): ?>
                                                <span style="font-size: 0.65rem; color: #6366f1; background: #e0e7ff; padding: 2px 6px; border-radius: 4px; margin-left: 4px;"><?= $e['invoice_number'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($e['requirement']): ?>
                                            <div style="font-size:0.7rem;color:#94a3b8;margin-top:0.2rem;"><?= htmlspecialchars($e['requirement']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;font-weight:800;color:#059669;">+₹<?= number_format($e['amount'], 2) ?></td>
                                    <td style="text-align:right;">
                                        <?php if ($e['invoice_payment_id']): ?>
                                            <a href="<?= APP_URL ?>/public/index.php/invoice_receipt?payment_id=<?= $e['invoice_payment_id'] ?>" target="_blank" class="btn btn-ghost" style="padding: 0.25rem 0.5rem; font-size: 0.65rem; border: 1px solid #e2e8f0; color: #6366f1;">
                                                <i class="fas fa-file-invoice"></i> Receipt
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payouts Ledger -->
            <div style="background:white; border:1px solid var(--border); border-radius:1rem; overflow:hidden;">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-arrow-up" style="color:#dc2626;"></i> Payouts Settled</div>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="data-table">
                        <thead style="position:sticky; top:0;">
                            <tr>
                                <th>Date</th>
                                <th>Reference / Note</th>
                                <th style="text-align:right;">Paid Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payouts)): ?>
                                <tr><td colspan="3" style="text-align:center;padding:2rem;color:#94a3b8;">No payouts recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payouts as $p): ?>
                                <tr>
                                    <td style="color:#64748b;font-size:0.75rem;"><?= date('d M Y', strtotime($p['date'])) ?></td>
                                    <td style="color:#475569;"><?= htmlspecialchars($p['note'] ?: '—') ?></td>
                                    <td style="text-align:right;font-weight:800;color:#dc2626;">-₹<?= number_format($p['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
