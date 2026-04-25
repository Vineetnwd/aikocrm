<?php
use Core\Database;
use Core\Auth;

$db = Database::getInstance();
$company_id = Auth::companyId();

// Handle Date Range
$range = $_GET['range'] ?? 'today';
$start_date = $_GET['start'] ?? date('Y-m-d');
$end_date = $_GET['end'] ?? date('Y-m-d');

if ($range === 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
} elseif ($range === 'month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
} elseif ($range === 'all') {
    $start_date = '2020-01-01';
    $end_date = date('Y-m-d', strtotime('+10 years'));
}

$params = [$company_id, $start_date, $end_date];

// Fetch Metrics
$metrics = $db->fetchOne("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_billed,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(due_amount), 0) as total_pending
    FROM invoices 
    WHERE company_id = ? AND invoice_date BETWEEN ? AND ?
", $params);

// Fetch Recent Invoices
$invoices = $db->fetchAll("
    SELECT i.*, l.name as client_name, l.mobile as client_mobile, l.email as client_email
    FROM invoices i 
    LEFT JOIN leads l ON i.lead_id = l.id 
    WHERE i.company_id = ? AND i.invoice_date BETWEEN ? AND ?
    ORDER BY i.created_at DESC
", $params);

// Fetch Aging Report (Due Report)
$aging = $db->fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoice_date) <= 30 THEN due_amount ELSE 0 END), 0) as 'days_0_30',
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoice_date) BETWEEN 31 AND 60 THEN due_amount ELSE 0 END), 0) as 'days_31_60',
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), invoice_date) > 60 THEN due_amount ELSE 0 END), 0) as 'days_60_plus'
    FROM invoices 
    WHERE company_id = ? AND payment_status != 'paid'
", [$company_id]);

// Fetch Leads as potential clients
$leads = $db->fetchAll("SELECT id, name FROM leads WHERE company_id = ? ORDER BY name ASC", [$company_id]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-bar {
            background: white;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Invoice System</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Real-time financial
                        performance</p>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="position:relative;">
                        <i class="fas fa-search"
                            style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); font-size: 0.75rem; color:#94a3b8;"></i>
                        <input type="text" id="invoiceSearch" placeholder="Search invoices..." class="form-input"
                            style="padding-left: 2.25rem; width: 240px; font-size: 0.75rem; height: 36px; margin:0;">
                    </div>
                    <button class="btn btn-primary" onclick="openInvoiceModal()"
                        style="height: 36px; padding: 0 1rem; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; margin:0;">
                        <i class="fas fa-file-invoice" style="font-size: 0.75rem;"></i> Create Invoice
                    </button>
                </div>
            </header>

            <form action="" method="GET" class="filter-bar">
                <div class="filter-item">
                    <i class="fas fa-calendar-alt"></i>
                    <select name="range" class="form-input" style="padding: 0.4rem; font-size: 0.75rem; width: 120px;"
                        onchange="this.form.submit()">
                        <option value="today" <?= $range == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $range == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="month" <?= $range == 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="all" <?= $range == 'all' ? 'selected' : '' ?>>All Time</option>
                        <option value="custom" <?= $range == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                <?php if ($range == 'custom'): ?>
                    <div class="filter-item">
                        <input type="date" name="start" value="<?= $start_date ?>" class="form-input"
                            style="padding: 0.4rem; font-size: 0.75rem;">
                        <span>to</span>
                        <input type="date" name="end" value="<?= $end_date ?>" class="form-input"
                            style="padding: 0.4rem; font-size: 0.75rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.875rem;">Go</button>
                    </div>
                <?php endif; ?>
            </form>

            <div class="stats-grid">
                <div class="card stat-card">
                    <div
                        style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span class="stat-label">TOTAL BILLED</span>
                        <div
                            style="width: 28px; height: 28px; border-radius: 6px; background: #e0f2fe; color: #0ea5e9; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-invoice-dollar" style="font-size: 0.75rem;"></i>
                        </div>
                    </div>
                    <span class="stat-value">₹<?= number_format($metrics['total_billed'], 2) ?></span>
                </div>
                <div class="card stat-card">
                    <div
                        style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span class="stat-label" style="color: var(--success);">PAID</span>
                        <div
                            style="width: 28px; height: 28px; border-radius: 6px; background: #d1fae5; color: var(--success); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check-circle" style="font-size: 0.75rem;"></i>
                        </div>
                    </div>
                    <span class="stat-value">₹<?= number_format($metrics['total_paid'], 2) ?></span>
                </div>
                <div class="card stat-card">
                    <div
                        style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span class="stat-label" style="color: var(--danger);">PENDING</span>
                        <div
                            style="width: 28px; height: 28px; border-radius: 6px; background: #fee2e2; color: var(--danger); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 0.75rem;"></i>
                        </div>
                    </div>
                    <span class="stat-value">₹<?= number_format($metrics['total_pending'], 2) ?></span>
                </div>
            </div>

            <!-- Aging Report -->
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 2rem;">
                <div style="display:flex; align-items:center; gap:0.5rem; font-weight:800; color:#475569; font-size:0.8125rem;">
                    <i class="fas fa-chart-pie" style="color:var(--primary);"></i> Due Aging Report:
                </div>
                <div style="display:flex; gap: 2rem;">
                    <div>
                        <div style="font-size:0.65rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">0-30 Days</div>
                        <div style="font-weight:800; color:#0f172a; font-size:1rem;">₹<?= number_format($aging['days_0_30'], 2) ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.65rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">31-60 Days</div>
                        <div style="font-weight:800; color:#d97706; font-size:1rem;">₹<?= number_format($aging['days_31_60'], 2) ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.65rem; color:#94a3b8; font-weight:700; text-transform:uppercase;">> 60 Days</div>
                        <div style="font-weight:800; color:#dc2626; font-size:1rem;">₹<?= number_format($aging['days_60_plus'], 2) ?></div>
                    </div>
                </div>
            </div>

            <div class="card" style="padding: 1rem;">
                <h2 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem;">Recent Invoices</h2>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th
                                    style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                                    ID & Date</th>
                                <th
                                    style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                                    Client Name</th>
                                <th
                                    style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                                    Amount</th>
                                <th
                                    style="padding: 0.75rem 0.5rem; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-muted);">No
                                        invoices found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr style="border-bottom: 1px solid #f8fafc;">
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <div style="font-size: 0.8125rem; font-weight: 700; color:var(--primary);">
                                                <?= $invoice['invoice_number'] ?></div>
                                            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.2rem;">
                                                <?= date('d M Y', strtotime($invoice['invoice_date'])) ?></div>
                                        </td>
                                        <td style="padding: 0.875rem 0.5rem; font-size: 0.8125rem; font-weight: 600;">
                                            <?= htmlspecialchars($invoice['client_name']) ?></td>
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <div style="font-size: 0.8125rem; font-weight: 700;">
                                                ₹<?= number_format($invoice['total_amount'], 2) ?></div>
                                            <div
                                                style="font-size: 0.65rem; color: <?= $invoice['due_amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                                <?= $invoice['due_amount'] > 0 ? 'Due: ₹' . number_format($invoice['due_amount'], 2) : 'Paid Full' ?>
                                            </div>
                                        </td>
                                        <td style="padding: 0.875rem 0.5rem;">
                                            <span class="badge"
                                                style="background: <?= $invoice['payment_status'] == 'paid' ? '#d1fae5; color:#059669;' : ($invoice['payment_status'] == 'partial' ? '#fef3c7; color:#d97706;' : '#fee2e2; color:#dc2626;') ?>; font-size: 0.65rem; font-weight: 800;">
                                                <?= strtoupper($invoice['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 0.4rem; justify-content: flex-end; flex-wrap:wrap;">
                                                <?php if ($invoice['payment_status'] != 'paid'): ?>
                                                    <button
                                                        onclick="openPaymentModal(<?= $invoice['id'] ?>, <?= $invoice['due_amount'] ?>)"
                                                        class="btn btn-primary"
                                                        style="padding: 0.375rem 0.6rem; font-size: 0.7rem; height: 30px; background: #10b981; border: none; display:flex; align-items:center; gap:0.4rem;" title="Record Payment">
                                                        <i class="fas fa-wallet"></i> Pay
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?= APP_URL ?>/public/index.php/invoice_ledger?id=<?= $invoice['id'] ?>" class="btn btn-ghost"
                                                    style="padding: 0.375rem 0.6rem; font-size: 0.7rem; border: 1px solid #e2e8f0; height: 30px; display:flex; align-items:center; gap:0.4rem; color: #8b5cf6;" title="Payment Ledger">
                                                    <i class="fas fa-history"></i> Ledger
                                                </a>
                                                <a href="<?= APP_URL ?>/public/assets/views/invoice_print.php?id=<?= $invoice['id'] ?>"
                                                    target="_blank" class="btn btn-ghost"
                                                    style="padding: 0.375rem 0.6rem; font-size: 0.7rem; border: 1px solid #e2e8f0; height: 30px; display:flex; align-items:center; gap:0.4rem;">
                                                    <i class="fas fa-file-pdf"></i> PDF/Print
                                                </a>

                                                <?php if ($invoice['client_email']): ?>
                                                    <a href="mailto:<?= htmlspecialchars($invoice['client_email']) ?>?subject=Invoice <?= $invoice['invoice_number'] ?> from Aikaa CRM&body=Dear <?= htmlspecialchars($invoice['client_name']) ?>,%0D%0A%0D%0AAttached is your invoice <?= $invoice['invoice_number'] ?>.%0D%0AAmount Due: Rs. <?= number_format($invoice['due_amount'], 2) ?>%0D%0A%0D%0AThank you."
                                                        class="btn btn-ghost"
                                                        style="padding: 0.375rem 0.6rem; font-size: 0.7rem; border: 1px solid #e2e8f0; height: 30px; display:flex; align-items:center; gap:0.4rem; color: #3b82f6;">
                                                        <i class="fas fa-envelope"></i> Email
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($invoice['client_mobile']): ?>
                                                    <?php 
                                                        $waNum = preg_replace('/[^0-9]/', '', $invoice['client_mobile']);
                                                        if (strlen($waNum) == 10) $waNum = '91' . $waNum; 
                                                        $waMsg = urlencode("Dear " . $invoice['client_name'] . ",\nThis is a gentle reminder for Invoice " . $invoice['invoice_number'] . ".\nAmount Due: ₹" . number_format($invoice['due_amount'], 2) . "\nPlease process the payment at your earliest convenience. Thank you.");
                                                    ?>
                                                    <a href="https://wa.me/<?= $waNum ?>?text=<?= $waMsg ?>"
                                                        target="_blank"
                                                        class="btn btn-ghost"
                                                        style="padding: 0.375rem 0.6rem; font-size: 0.7rem; border: 1px solid #e2e8f0; height: 30px; display:flex; align-items:center; gap:0.4rem; color: #22c55e;">
                                                        <i class="fab fa-whatsapp"></i> Remind
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Record Payment Modal -->
    <div id="paymentModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid var(--border); display:flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.125rem; font-weight: 800;">Record Payment</h2>
                    <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Update the outstanding
                        balance</p>
                </div>
                <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="paymentForm" style="padding: 1.5rem;">
                <input type="hidden" name="id" id="payment_inv_id">
                <div style="margin-bottom: 1.25rem;">
                    <label>Remaining Due</label>
                    <div id="payment_due_display"
                        style="font-size: 1.25rem; font-weight: 800; color: var(--danger); margin-bottom: 0.5rem;">₹0.00
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label>Payment Amount (₹)</label>
                    <input type="number" step="0.01" name="payment_amount" id="payment_amount_input" required
                        class="form-input" placeholder="0.00"
                        style="font-weight: 800; color: var(--success); font-size: 1rem;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label>Date</label>
                        <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required class="form-input">
                    </div>
                    <div>
                        <label>Mode</label>
                        <select name="payment_mode" required class="form-input" style="appearance: auto;">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer / NEFT</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label>Reference # / UTR (Optional)</label>
                    <input type="text" name="reference_number" class="form-input" placeholder="e.g. UTR123456789">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-input" placeholder="Any notes...">
                </div>
                <div style="display:flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('paymentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.625rem 1.5rem;">
                        <i class="fas fa-check"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div id="invoiceModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 580px;">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid var(--border); display:flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.125rem; font-weight: 800; letter-spacing: -0.02em;">Create New Invoice</h2>
                    <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Secure financial record
                        generation</p>
                </div>
                <button onclick="closeInvoiceModal()" class="btn-ghost"
                    style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; padding:0;">
                    <i class="fas fa-times" style="font-size: 0.875rem;"></i>
                </button>
            </div>

            <form id="invoiceForm" style="padding: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label>Invoice Number</label>
                        <input type="text" name="invoice_number" id="invoice_number_input" required class="form-input"
                            readonly style="background: #f8fafc; cursor: not-allowed; border-style: dashed;">
                    </div>
                    <div>
                        <label>Client (Lead Name)</label>
                        <select name="lead_id" id="lead_select" required class="form-input" style="appearance: auto;"
                            onchange="handleLeadSelect(this.value)">
                            <option value="">-- Choose Client --</option>
                            <?php foreach ($leads as $lead): ?>
                                <option value="<?= $lead['id'] ?>"><?= htmlspecialchars($lead['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="draft_selector_container"
                    style="display: none; margin-bottom: 1rem; border: 1.5px dashed var(--primary); padding: 1rem; border-radius: 0.75rem; background: #f5f3ff;">
                    <label
                        style="color: var(--primary); font-weight: 800; display:flex; align-items:center; gap:0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-magic"></i> Proposals Found
                    </label>
                    <select id="draft_select" class="form-input" style="appearance: auto;"
                        onchange="applyDraft(this.value)">
                        <option value="">-- Select a Proposal to Auto-Fill --</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label><i class="fas fa-calendar-day" style="margin-right: 4px; font-size: 0.7rem;"></i> Invoice
                            Date</label>
                        <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required class="form-input">
                    </div>
                    <div>
                        <label><i class="fas fa-fingerprint" style="margin-right: 4px; font-size: 0.7rem;"></i> GST
                            Number</label>
                        <input type="text" name="gst_number" class="form-input" placeholder="e.g. 29AAAAA0000A1Z5">
                    </div>
                </div>

                <div
                    style="background: #f8fafc; padding: 1.25rem; border-radius: 0.75rem; margin-bottom: 1.25rem; border: 1px solid #e2e8f0; position:relative;">
                    <div
                        style="margin-bottom: 1rem; display:flex; gap: 1rem; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                        <label style="margin-bottom:0; cursor:pointer; display:flex; align-items:center; gap:0.5rem;">
                            <input type="radio" name="gst_type" value="gst" checked onclick="toggleTaxFields('gst')">
                            CGST + SGST
                        </label>
                        <label style="margin-bottom:0; cursor:pointer; display:flex; align-items:center; gap:0.5rem;">
                            <input type="radio" name="gst_type" value="igst" onclick="toggleTaxFields('igst')"> IGST
                            (Inter-state)
                        </label>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                        <div>
                            <label>Base Amount (₹)</label>
                            <input type="number" step="0.01" name="subtotal" id="subtotal_input" required
                                class="form-input" placeholder="0.00" style="font-weight: 700; font-size: 0.9rem;">
                        </div>
                        <div id="cgst_sgst_fields" style="display: contents;">
                            <div>
                                <label>SGST (%)</label>
                                <input type="number" step="0.01" name="sgst_percent" id="sgst_percent" value="9"
                                    class="form-input">
                            </div>
                            <div>
                                <label>CGST (%)</label>
                                <input type="number" step="0.01" name="cgst_percent" id="cgst_percent" value="9"
                                    class="form-input">
                            </div>
                        </div>
                        <div id="igst_fields" style="display: none;">
                            <div style="grid-column: span 2;">
                                <label>IGST (%)</label>
                                <input type="number" step="0.01" name="igst_percent" id="igst_percent" value="18"
                                    class="form-input">
                            </div>
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1.5px solid #e2e8f0;">
                        <span style="font-size: 0.8125rem; font-weight: 700; color: #64748b;">GRAND TOTAL:</span>
                        <span id="total_display"
                            style="font-size: 1.25rem; font-weight: 800; color: var(--primary); letter-spacing: -0.02em;">₹0.00</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label>Payment Received (₹)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount_input" value="0.00"
                            class="form-input" style="color: var(--success); font-weight: 700;">
                    </div>
                    <div>
                        <label>Outstanding Balance</label>
                        <input type="text" id="due_display" value="₹0.00" class="form-input" readonly
                            style="background: #fff1f2; border-color: #fecaca; font-weight: 800; color: var(--danger); font-size: 0.9rem;">
                    </div>
                </div>

                <div
                    style="display:flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="closeInvoiceModal()"
                        style="font-weight: 600;">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 0.625rem 1.5rem; font-size: 0.875rem;">
                        <i class="fas fa-check-circle" style="font-size: 0.75rem;"></i> Generate & Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentLeadDrafts = [];

        async function handleLeadSelect(id) {
            const container = document.getElementById('draft_selector_container');
            const select = document.getElementById('draft_select');
            const baseInput = document.getElementById('subtotal_input');

            if (!id) {
                container.style.display = 'none';
                return;
            }

            fetch(`<?= APP_URL ?>/public/index.php/api/leads.php?id=${id}`)
                .then(r => r.json())
                .then(lead => {
                    if (lead && lead.deal_value > 0) {
                        baseInput.value = lead.deal_value;
                        updateCalculations();
                    }
                });

            fetch(`<?= APP_URL ?>/public/index.php/api/quotations.php?lead_id=${id}`)
                .then(r => r.json())
                .then(drafts => {
                    currentLeadDrafts = drafts;
                    if (drafts && drafts.length > 0) {
                        container.style.display = 'block';
                        select.innerHTML = '<option value="">-- Select a Proposal Draft --</option>';
                        drafts.forEach(d => {
                            select.innerHTML += `<option value="${d.id}">${d.quotation_number} - ₹${parseFloat(d.total_amount).toLocaleString('en-IN')}</option>`;
                        });
                        if (drafts.length === 1) {
                            select.value = drafts[0].id;
                            applyDraft(drafts[0].id);
                        }
                    } else {
                        container.style.display = 'none';
                    }
                });
        }

        function applyDraft(id) {
            if (!id) return;
            const draft = currentLeadDrafts.find(d => d.id == id);
            if (draft) {
                document.getElementById('subtotal_input').value = draft.subtotal;
                updateCalculations();
            }
        }

        function openPaymentModal(id, due) {
            document.getElementById('payment_inv_id').value = id;
            document.getElementById('payment_due_display').textContent = '₹' + due.toLocaleString('en-IN', { minimumFractionDigits: 2 });
            document.getElementById('payment_amount_input').value = due;
            document.getElementById('paymentModal').style.display = 'flex';
        }

        document.getElementById('paymentForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/invoices.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Payment Error:', error);
            }
        });

        function openInvoiceModal() {
            fetch('<?= APP_URL ?>/public/index.php/api/invoices.php?next_num=1')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('invoice_number_input').value = data.next_number;
                    document.getElementById('invoiceModal').style.display = 'flex';
                });
        }

        function closeInvoiceModal() {
            document.getElementById('invoiceModal').style.display = 'none';
            document.getElementById('invoiceForm').reset();
            document.getElementById('draft_selector_container').style.display = 'none';
            updateCalculations();
        }

        function toggleTaxFields(type) {
            const gstFields = document.getElementById('cgst_sgst_fields');
            const igstFields = document.getElementById('igst_fields');
            if (type === 'igst') {
                gstFields.style.display = 'none';
                igstFields.style.display = 'block';
            } else {
                gstFields.style.display = 'contents';
                igstFields.style.display = 'none';
            }
            updateCalculations();
        }

        function updateCalculations() {
            const subtotal = parseFloat(document.getElementById('subtotal_input').value) || 0;
            const paid = parseFloat(document.getElementById('paid_amount_input').value) || 0;
            const taxRadio = document.querySelector('input[name="gst_type"]:checked');
            const taxType = taxRadio ? taxRadio.value : 'gst';

            let taxAmt = 0;
            let sgstAmt = 0;
            let cgstAmt = 0;
            let igstAmt = 0;

            if (taxType === 'gst') {
                const sgstP = parseFloat(document.getElementById('sgst_percent').value) || 0;
                const cgstP = parseFloat(document.getElementById('cgst_percent').value) || 0;
                sgstAmt = (subtotal * sgstP) / 100;
                cgstAmt = (subtotal * cgstP) / 100;
                taxAmt = sgstAmt + cgstAmt;
            } else {
                const igstP = parseFloat(document.getElementById('igst_percent').value) || 0;
                igstAmt = (subtotal * igstP) / 100;
                taxAmt = igstAmt;
            }

            const total = subtotal + taxAmt;
            const due = total - paid;

            document.getElementById('total_display').textContent = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
            document.getElementById('due_display').value = '₹' + due.toLocaleString('en-IN', { minimumFractionDigits: 2 });

            return { total, due, sgstAmt, cgstAmt, igstAmt, subtotal, paid, taxType };
        }

        ['subtotal_input', 'sgst_percent', 'cgst_percent', 'igst_percent', 'paid_amount_input'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updateCalculations);
        });

        document.getElementById('invoiceForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const calc = updateCalculations();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            data.total_amount = calc.total;
            data.due_amount = calc.due;
            data.sgst = calc.sgstAmt;
            data.cgst = calc.cgstAmt;
            data.igst = calc.igstAmt;
            data.subtotal = calc.subtotal;
            data.paid_amount = calc.paid;
            data.gst_type = calc.taxType;

            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/invoices.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Invoice Error:', error);
            }
        });

        document.getElementById('invoiceSearch').addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>
</body>

</html>