<?php
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';

use Core\Database;
use Core\Auth;

$db = Database::getInstance();
$company_id = Auth::companyId();

// Fetch leads that are 'won' or already have a quotation
$leads = $db->fetchAll(
    "SELECT l.id, l.name, l.mobile, l.requirement 
     FROM leads l 
     WHERE l.company_id = ? AND (l.status = 'won' OR l.id IN (SELECT lead_id FROM quotations WHERE company_id = ?))
     ORDER BY l.name",
    [$company_id, $company_id]
);

// Fetch Quotations with full lead detail
$quotations = $db->fetchAll("
    SELECT q.*, l.name as client_name, l.mobile as client_mobile, l.requirement as client_requirement
    FROM quotations q
    LEFT JOIN leads l ON q.lead_id = l.id
    WHERE q.company_id = ?
    ORDER BY q.created_at DESC
", [$company_id]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Sales Quotations</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Draft and send
                        proposals to prospects</p>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="position:relative;">
                        <i class="fas fa-search"
                            style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); font-size: 0.75rem; color:#94a3b8;"></i>
                        <input type="text" id="quoSearch" placeholder="Search quotations..." class="form-input"
                            style="padding-left: 2.25rem; width: 240px; font-size: 0.75rem; height: 36px; margin:0;">
                    </div>
                    <button class="btn btn-primary" onclick="toggleModal('quotationModal')"
                        style="height: 36px; padding: 0 1rem; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; margin:0;">
                        <i class="fas fa-file-contract" style="font-size: 0.75rem;"></i> Create Quotation
                    </button>
                </div>
            </header>

            <div class="card" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Quotation #</th>
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Client</th>
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Total Value</th>
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Status</th>
                            <th
                                style="text-align: right; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quotations)): ?>
                            <tr>
                                <td colspan="5" style="padding: 4rem; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-file-invoice"
                                        style="font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                                    <p style="font-size: 0.875rem; font-weight: 600;">No quotations drafted yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quotations as $q): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s;"
                                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                    <td style="padding: 1rem; font-weight: 700; color: var(--primary); font-size: 0.875rem;">
                                        <?= $q['quotation_number'] ?>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="font-weight: 800; color: #1e293b; font-size: 0.875rem;">
                                            <?= htmlspecialchars($q['client_name'] ?? '—') ?>
                                        </div>
                                        <?php if (!empty($q['client_mobile'])): ?>
                                            <div style="font-size: 0.72rem; color: #64748b; font-weight:600;"><i
                                                    class="fas fa-phone-alt"></i> <?= htmlspecialchars($q['client_mobile']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($q['client_requirement'])): ?>
                                            <div style="font-size: 0.72rem; color: #475569; margin-top:2px; font-style:italic;"
                                                title="<?= htmlspecialchars($q['client_requirement']) ?>">
                                                <i class="fas fa-file-alt"></i>
                                                <?= mb_strimwidth(htmlspecialchars($q['client_requirement']), 0, 50, '…') ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="font-size: 0.7rem; color: #94a3b8; margin-top:2px;">
                                            <?= date('M d, Y', strtotime($q['quotation_date'])) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; font-weight: 800; color: #1e293b;">
                                        ₹<?= number_format($q['total_amount'], 2) ?></td>
                                    <td style="padding: 1rem;">
                                        <span class="badge"
                                            style="background: <?= $q['status'] == 'invoiced' ? '#dcfce7; color: #166534' : '#fef3c7; color: #92400e' ?>; font-size: 0.65rem; font-weight: 800; padding: 0.25rem 0.625rem; border-radius: 20px; text-transform: uppercase;">
                                            <?= $q['status'] ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; text-align: right;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                            <?php if ($q['status'] != 'invoiced'): ?>
                                                <button onclick="convertToInvoice(<?= $q['id'] ?>)" class="btn btn-primary"
                                                    style="padding: 0.375rem 0.75rem; font-size: 0.7rem; height: 32px; background: #10b981; border: none;">
                                                    <i class="fas fa-exchange-alt"></i> Convert to Invoice
                                                </button>
                                                <button onclick="editQuotation(<?= $q['id'] ?>)" class="btn btn-ghost"
                                                    style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border: 1px solid #e2e8f0; height: 32px; color: #64748b;"
                                                    title="Edit Quotation">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= APP_URL ?>/public/index.php/quotation/print?id=<?= $q['id'] ?>&print=true"
                                                target="_blank" class="btn btn-ghost"
                                                style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border: 1px solid #e2e8f0; height: 32px;"
                                                title="Print Quotation">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Create Quotation Modal -->
    <div id="quotationModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 520px;">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid var(--border); display:flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.125rem; font-weight: 800;">Draft New Quotation</h2>
                    <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Set your proposal terms
                        for the prospect</p>
                </div>
                <button onclick="toggleModal('quotationModal')" class="btn-ghost">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="quotationForm" style="padding: 1.5rem;">
                <input type="hidden" name="id" id="quo_id_input">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label>Quotation #</label>
                        <input type="text" name="quotation_number" value="QUO-<?= time() ?>" required
                            class="form-input">
                    </div>
                    <div>
                        <label>Date</label>
                        <input type="date" name="quotation_date" value="<?= date('Y-m-d') ?>" required
                            class="form-input">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label>Select Won Lead / Client</label>
                    <select name="lead_id" id="quotLeadSelect" class="form-input" style="appearance: auto;"
                        onchange="showLeadRequirement(this)">
                        <option value="">-- Select a Won Lead --</option>
                        <?php if (empty($leads)): ?>
                            <option disabled>No Won leads yet — mark leads as ✅ Won first</option>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <option value="<?= $lead['id'] ?>" data-mobile="<?= htmlspecialchars($lead['mobile'] ?? '') ?>"
                                    data-requirement="<?= htmlspecialchars($lead['requirement'] ?? '') ?>">
                                    <?= htmlspecialchars($lead['name']) ?>
                                    <?= $lead['mobile'] ? '(' . htmlspecialchars($lead['mobile']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div id="leadRequirementPreview"
                        style="display:none; margin-top:0.5rem; padding:0.75rem; background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:0.5rem; font-size:0.75rem; color:#15803d; line-height:1.5;">
                        <div
                            style="font-weight:800; font-size:0.65rem; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem;">
                            <i class="fas fa-user-check"></i> Won Client Details
                        </div>
                        <div id="leadPreviewMobile" style="font-weight:700; color:#1e293b; margin-bottom:0.2rem;"></div>
                        <div id="leadRequirementText" style="color:#334155; font-weight:500;"></div>
                    </div>
                </div>

                <div
                    style="background: #f8fafc; padding: 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 1px solid #e2e8f0;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Base Value (₹)</label>
                            <input type="number" step="0.01" name="subtotal" id="subtotal_quo" required
                                class="form-input" placeholder="0.00">
                        </div>
                        <div>
                            <label>Applicable Tax (%)</label>
                            <input type="number" step="0.01" name="tax_percent" id="tax_quo" value="18"
                                class="form-input">
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 1.5px solid #e2e8f0;">
                        <span style="font-weight: 700; color: #64748b;">ESTIMATED TOTAL:</span>
                        <span id="quo_total_display" style="font-weight: 800; color: var(--primary);">₹0.00</span>
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn btn-ghost" onclick="toggleModal('quotationModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.625rem 1.75rem;">
                        <i class="fas fa-save"></i> Save Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal.style.display === 'flex') {
                modal.style.display = 'none';
                document.getElementById('quotationForm').reset();
                document.getElementById('quo_id_input').value = '';
                document.querySelector('#quotationModal h2').textContent = 'Draft New Quotation';
                document.getElementById('leadRequirementPreview').style.display = 'none';
                document.getElementById('quo_total_display').innerText = '₹0.00';
            } else {
                modal.style.display = 'flex';
            }
        }

        async function editQuotation(id) {
            try {
                const response = await fetch('<?= APP_URL ?>/api/quotations.php?id=' + id);
                const quotation = await response.json();

                document.querySelector('#quotationModal h2').textContent = 'Edit Quotation';
                document.getElementById('quo_id_input').value = quotation.id;

                const form = document.getElementById('quotationForm');
                form.elements['quotation_number'].value = quotation.quotation_number;
                form.elements['quotation_date'].value = quotation.quotation_date;
                form.elements['lead_id'].value = quotation.lead_id;
                form.elements['subtotal'].value = quotation.subtotal;
                form.elements['tax_percent'].value = (quotation.igst_amount / quotation.subtotal * 100) || 18; // approx

                showLeadRequirement(form.elements['lead_id']);
                calculateQuoTotal();

                document.getElementById('quotationModal').style.display = 'flex';
            } catch (error) {
                console.error('Error fetching quotation:', error);
            }
        }

        function showLeadRequirement(sel) {
            const opt = sel.options[sel.selectedIndex];
            const mobile = opt ? opt.getAttribute('data-mobile') : '';
            const req = opt ? opt.getAttribute('data-requirement') : '';
            const preview = document.getElementById('leadRequirementPreview');
            if (mobile || req) {
                document.getElementById('leadPreviewMobile').textContent = mobile ? '📞 ' + mobile : '';
                document.getElementById('leadRequirementText').textContent = req ? '📋 ' + req : 'No requirement noted';
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        document.getElementById('subtotal_quo').addEventListener('input', calculateQuoTotal);
        document.getElementById('tax_quo').addEventListener('input', calculateQuoTotal);

        function calculateQuoTotal() {
            const sub = parseFloat(document.getElementById('subtotal_quo').value) || 0;
            const tax = parseFloat(document.getElementById('tax_quo').value) || 0;
            const total = sub + (sub * tax / 100);
            document.getElementById('quo_total_display').innerText = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        }

        document.getElementById('quotationForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('<?= APP_URL ?>/api/quotations.php', {
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
                console.error('Logic Error:', error);
            }
        });

        async function convertToInvoice(id) {
            if (!confirm('Are you sure you want to convert this quotation to a live invoice?')) return;

            try {
                const response = await fetch('<?= APP_URL ?>/api/quotations.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'convert', id: id })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Successfully converted to Invoice!');
                    window.location.href = '<?= APP_URL ?>/public/index.php/invoices';
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Conversion Error:', error);
            }
        }

        document.getElementById('quoSearch').addEventListener('input', function (e) {
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