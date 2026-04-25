<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';

use Core\Database;
use Core\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!Auth::check()) {
    header("Location: " . APP_URL . "/public/index.php/login");
    exit;
}

$db = Database::getInstance();
$invoice_id = $_GET['id'] ?? null;

if (!$invoice_id) {
    die("Invoice ID required");
}

$invoice = $db->fetchOne("
    SELECT i.*, l.name as client_name, l.mobile as client_mobile, l.email as client_email, l.requirement
    FROM invoices i 
    LEFT JOIN leads l ON i.lead_id = l.id 
    WHERE i.id = ? AND i.company_id = ?
", [$invoice_id, Auth::companyId()]);

if (!$invoice) {
    die("Invoice not found");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= $invoice['invoice_number'] ?></title>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            padding: 40px;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 0;
        }

        .flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #6366f1;
            border-left: 4px solid #6366f1;
            padding-left: 12px;
        }

        .header {
            margin-bottom: 40px;
        }

        .title {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.04em;
            color: #0f172a;
            margin: 0;
        }

        .section-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
            letter-spacing: 0.1em;
        }

        .info-panel {
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #f8fafc;
            text-align: left;
            padding: 12px;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .totals-box {
            width: 300px;
            margin-left: auto;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.grand {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            font-weight: 900;
            font-size: 18px;
            color: #6366f1;
        }

        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()"
            style="background: #6366f1; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 700;">
            <i class="fas fa-print"></i> Print Invoice
        </button>
    </div>

    <div class="invoice-box">
        <div class="header flex">
            <div>
                <div class="logo">AIKAA CRM</div>
                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Smart Business Solutions</div>
            </div>
            <div style="text-align: right;">
                <h1 class="title">INVOICE</h1>
                <div style="font-weight: 700; color: #6366f1; margin-top: 4px;"><?= $invoice['invoice_number'] ?></div>
            </div>
        </div>

        <div class="info-panel flex">
            <div>
                <div class="section-title">Billed To</div>
                <div style="font-weight: 800; font-size: 16px; color: #0f172a;">
                    <?= htmlspecialchars($invoice['client_name']) ?>
                </div>
                <div style="font-size: 14px; color: #475569;">+91 <?= htmlspecialchars($invoice['client_mobile']) ?></div>
                <div style="font-size: 14px; color: #475569;"><?= htmlspecialchars($invoice['client_email']) ?></div>
            </div>
            <div style="text-align: right;">
                <div class="section-title">Details</div>
                <div style="font-size: 14px; color: #475569;">Date: <strong><?= date('M d, Y', strtotime($invoice['invoice_date'])) ?></strong></div>
                <div style="font-size: 14px; color: #475569;">Status: <strong style="text-transform: uppercase; color: <?= $invoice['payment_status'] == 'paid' ? '#10b981' : '#f59e0b' ?>;"><?= $invoice['payment_status'] ?></strong></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Tax Detail</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="font-weight: 700; color: #334155;">Consultancy / Service Charges</div>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                            <?= htmlspecialchars($invoice['requirement'] ?: 'Standard CRM service and implementation') ?>
                        </div>
                    </td>
                <td style="text-align: right; color: #64748b; font-size: 13px;">
                        <?php if ($invoice['igst'] > 0): ?>
                            IGST (18%)
                        <?php else: ?>
                                CGST (9%) + SGST (9%)
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight: 700;">₹<?= number_format($invoice['subtotal'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="totals-box">
            <div class="total-row">
                <span>Subtotal</span>
                <span>₹<?= number_format($invoice['subtotal'], 2) ?></span>
        </div>
        <?php if ($invoice['igst'] > 0): ?>
                <div class="total-row">
                    <span>IGST</span>
                        <span>₹<?= number_format($invoice['igst'], 2) ?></span>
                </div>
        <?php else: ?>
                <div class="total-row">
                    <span>CGST</span>
                    <span>₹<?= number_format($invoice['cgst'], 2) ?></span>
                </div>
                <div class="total-row">
                    <span>SGST</span>
                        <span>₹<?= number_format($invoice['sgst'], 2) ?></span>
                    </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span>Total Due</span>
                <span>₹<?= number_format($invoice['total_amount'], 2) ?></span>
            </div>
            <div class="total-row" style="margin-top: 10px; color: #10b981; font-weight: 700;">
                <span>Amount Paid</span>
                <span>- ₹<?= number_format($invoice['paid_amount'], 2) ?></span>
            </div>
            <div class="total-row" style="color: #ef4444; font-weight: 700; border-top: 1px solid #f1f5f9;">
                <span>Balance Due</span>
                <span>₹<?= number_format($invoice['due_amount'], 2) ?></span>
            </div>
        </div>

        <div class="footer">
            <p><strong>Payment Terms:</strong> Please pay within 7 days of receiving this invoice.</p>
            <p style="margin-top: 10px;">Thank you for choosing Aikaa CRM!</p>
        </div>
    </div>

    <script>
        // Automatically trigger PDF/Print dialogue when the page finishes loading
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>