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
$quotation_id = $_GET['id'] ?? null;

if (!$quotation_id) {
    die("Quotation ID required");
}

$quotation = $db->fetchOne("
    SELECT q.*, l.name as client_name, l.mobile as client_mobile, l.email as client_email, l.requirement
    FROM quotations q 
    LEFT JOIN leads l ON q.lead_id = l.id 
    WHERE q.id = ? AND q.company_id = ?
", [$quotation_id, Auth::companyId()]);

if (!$quotation) {
    die("Quotation not found");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quotation - <?= $quotation['quotation_number'] ?></title>
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
            <i class="fas fa-print"></i> Print Quotation
        </button>
    </div>

    <div class="invoice-box">
        <div class="header flex">
            <div>
                <div class="logo">AIKAA CRM</div>
                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Smart Business Solutions</div>
            </div>
            <div style="text-align: right;">
                <h1 class="title">QUOTATION</h1>
                <div style="font-weight: 700; color: #6366f1; margin-top: 4px;"><?= $quotation['quotation_number'] ?></div>
            </div>
        </div>

        <div class="info-panel flex">
            <div>
                <div class="section-title">Prepared For</div>
                <div style="font-weight: 800; font-size: 16px; color: #0f172a;">
                    <?= htmlspecialchars($quotation['client_name']) ?>
                </div>
                <div style="font-size: 14px; color: #475569;">+91 <?= htmlspecialchars($quotation['client_mobile']) ?></div>
                <div style="font-size: 14px; color: #475569;"><?= htmlspecialchars($quotation['client_email']) ?></div>
            </div>
            <div style="text-align: right;">
                <div class="section-title">Details</div>
                <div style="font-size: 14px; color: #475569;">Date: <strong><?= date('M d, Y', strtotime($quotation['quotation_date'])) ?></strong></div>
                <div style="font-size: 14px; color: #475569;">Status: <strong style="text-transform: uppercase; color: <?= $quotation['status'] == 'invoiced' ? '#10b981' : '#f59e0b' ?>;"><?= $quotation['status'] ?></strong></div>
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
                            <?= htmlspecialchars($quotation['requirement'] ?: 'Standard CRM service and implementation') ?>
                        </div>
                    </td>
                <td style="text-align: right; color: #64748b; font-size: 13px;">
                        <?php if ($quotation['igst_amount'] > 0): ?>
                            IGST
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight: 700;">₹<?= number_format($quotation['subtotal'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="totals-box">
            <div class="total-row">
                <span>Subtotal</span>
                <span>₹<?= number_format($quotation['subtotal'], 2) ?></span>
        </div>
        <?php if ($quotation['igst_amount'] > 0): ?>
                <div class="total-row">
                    <span>Tax (IGST)</span>
                        <span>₹<?= number_format($quotation['igst_amount'], 2) ?></span>
                </div>
        <?php endif; ?>
            <div class="total-row grand">
                <span>Estimated Total</span>
                <span>₹<?= number_format($quotation['total_amount'], 2) ?></span>
            </div>
        </div>

        <div class="footer">
            <p><strong>Note:</strong> This is a quotation, not an invoice. Prices and terms are subject to change.</p>
            <p style="margin-top: 10px;">Thank you for considering Aikaa CRM!</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            <?php if (isset($_GET['print']) && $_GET['print'] == 'true'): ?>
            setTimeout(function() {
                window.print();
            }, 500);
            <?php endif; ?>
        };
    </script>
</body>

</html>
