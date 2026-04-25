<?php
use Core\Database;
use Core\Auth;

$db = Database::getInstance();
$company_id = Auth::companyId();
$users = $db->fetchAll("SELECT id, name FROM users WHERE company_id = ?", [$company_id]);
$employees = $db->fetchAll("SELECT id, name, designation FROM employees WHERE company_id = ? AND status = 'active' ORDER BY name", [$company_id]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pipeline-container {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            height: calc(100vh - 160px);
            align-items: flex-start;
        }

        .pipeline-column {
            flex: 1;
            min-width: 300px;
            max-width: 320px;
            background: rgba(241, 245, 249, 0.5);
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            max-height: 100%;
            border: 1px solid var(--border);
        }

        .pipeline-header {
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.075em;
            font-size: 0.7rem;
            flex-shrink: 0;
            padding: 0 0.5rem;
        }

        .leads-list {
            overflow-y: auto;
            flex-grow: 1;
            padding-right: 0.25rem;
        }

        .leads-list::-webkit-scrollbar {
            width: 4px;
        }

        .leads-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .lead-card {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1.5px solid #f1f5f9;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .lead-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .lead-name {
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            letter-spacing: -0.01em;
        }

        .lead-info {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .lead-followup {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            margin-top: 0.75rem;
            font-weight: 700;
        }

        .card-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.375rem;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .lead-card:hover .card-actions {
            opacity: 1;
        }

        .action-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--bg-app);
            color: var(--primary);
            border-color: var(--primary);
        }

        .action-btn.followup:hover {
            background: #ecfdf5;
            color: #059669;
            border-color: #059669;
        }

        /* Filter Tabs Styling */
        .filter-section {
            background: white;
            padding: 1.25rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .filter-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            min-width: 80px;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .filter-tab:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f1f5f9;
        }

        .filter-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 6px -1px rgba(var(--primary-rgb), 0.2);
        }

        .filter-tab.active[data-value="hot"] {
            background: #ef4444;
            border-color: #ef4444;
        }

        .filter-tab.active[data-value="warm"] {
            background: #f59e0b;
            border-color: #f59e0b;
        }

        .filter-tab.active[data-value="cold"] {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .filter-tab.active[data-value="won"] {
            background: #10b981;
            border-color: #10b981;
        }

        .filter-tab.active[data-value="lost"] {
            background: #64748b;
            border-color: #64748b;
        }

        .filter-tab.active[data-value="in_progress"] {
            background: #8b5cf6;
            border-color: #8b5cf6;
        }

        /* Table View Styling */
        .view-switcher {
            display: flex;
            background: #f1f5f9;
            padding: 0.25rem;
            border-radius: 0.625rem;
            gap: 0.25rem;
        }

        .view-btn {
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .leads-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .leads-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }

        .leads-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .leads-table tr:hover {
            background: #fcfcfd;
        }

        .table-lead-name {
            font-weight: 800;
            color: #0f172a;
            display: block;
            margin-bottom: 0.125rem;
        }

        .table-lead-sub {
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Sales Pipeline</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Manage your leads and
                        deal conversions</p>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="view-switcher">
                        <div class="view-btn" data-view="kanban">
                            <i class="fas fa-columns"></i> Pipeline
                        </div>
                        <div class="view-btn active" data-view="table">
                            <i class="fas fa-list"></i> Table
                        </div>
                    </div>
                    <div style="position:relative;">
                        <i class="fas fa-search"
                            style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); font-size: 0.75rem; color:#94a3b8;"></i>
                        <input type="text" id="leadSearch" placeholder="Filter by name or mobile..." class="form-input"
                            style="padding-left: 2.25rem; width: 260px; font-size: 0.75rem; height: 38px; margin:0;">
                    </div>
                    <button class="btn btn-primary" onclick="toggleModal('leadModal')"
                        style="height: 38px; padding: 0 1.25rem; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; margin:0;">
                        <i class="fas fa-plus" style="font-size: 0.75rem;"></i> New Opportunity
                    </button>
                </div>
            </header>

            <!-- Filter Bar -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-label">Pipeline</div>
                    <div class="filter-tabs" data-filter="status">
                        <div class="filter-tab active" data-value="all">All Statuses</div>
                        <div class="filter-tab" data-value="new">🆕 New</div>
                        <div class="filter-tab" data-value="in_progress">⚡ In Progress</div>
                        <div class="filter-tab" data-value="won">✅ Won</div>
                        <div class="filter-tab" data-value="lost">❌ Lost</div>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-label">Category</div>
                    <div class="filter-tabs" data-filter="category">
                        <div class="filter-tab active" data-value="all">All Types</div>
                        <div class="filter-tab" data-value="hot">Hot 🔥</div>
                        <div class="filter-tab" data-value="warm">Warm</div>
                        <div class="filter-tab" data-value="cold">Cold</div>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-label">Source</div>
                    <div class="filter-tabs" data-filter="source">
                        <div class="filter-tab active" data-value="all">All Sources</div>
                        <div class="filter-tab" data-value="facebook">Facebook</div>
                        <div class="filter-tab" data-value="website">Website</div>
                        <div class="filter-tab" data-value="referral">Referral</div>
                        <div class="filter-tab" data-value="ads">Ads</div>
                    </div>
                </div>
            </div>

            <!-- Pipeline View -->
            <div class="pipeline-container" id="kanbanBoard" style="display: none;">
                <div class="pipeline-column" data-status="new">
                    <div class="pipeline-header">
                        <span>🆕 New</span>
                        <span class="badge counter" style="background:#cbd5e1; color:#334155;">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="in_progress">
                    <div class="pipeline-header">
                        <span>⚡ In Progress</span>
                        <span class="badge counter" style="background:#fef3c7; color:#92400e;">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="won">
                    <div class="pipeline-header">
                        <span>✅ Won</span>
                        <span class="badge counter" style="background:#d1fae5; color:#065f46;">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="lost">
                    <div class="pipeline-header">
                        <span>❌ Lost</span>
                        <span class="badge counter" style="background:#fee2e2; color:#991b1b;">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
            </div>

            <!-- Table View -->
            <div id="tableView"
                style="display: block; background: white; border-radius: 1rem; border: 1px solid var(--border); overflow: auto; max-height: calc(100vh - 280px);">
                <table class="leads-table">
                    <thead>
                        <tr>
                            <th>Lead Info</th>
                            <th>Requirement</th>
                            <th>Category</th>
                            <th>Source</th>
                            <th>Assigned To</th>
                            <th>Deal Value</th>
                            <th>Pipeline</th>
                            <th>Task Status</th>
                            <th>Next Call</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit/Add Lead Modal -->
    <div id="leadModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 900px; width: 95%;">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid var(--border); display:flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.125rem; font-weight: 800; letter-spacing: -0.02em;">Lead Command Center</h2>
                    <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Manage details and
                        engagement history</p>
                </div>
                <button onclick="toggleModal('leadModal')" class="btn-ghost"
                    style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; padding:0;">
                    <i class="fas fa-times" style="font-size: 0.875rem;"></i>
                </button>
            </div>

            <div style="display: flex; gap: 0; min-height: 500px;">
                <!-- Left: Form -->
                <form id="leadForm"
                    style="flex: 1.2; padding: 1.5rem; border-right: 1px solid var(--border); max-height: 70vh; overflow-y: auto;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Business Name / Client</label>
                            <input type="text" name="name" required class="form-input" placeholder="e.g. John Smith">
                        </div>
                        <div>
                            <label>Mobile Number</label>
                            <input type="text" name="mobile" id="leadMobile" required class="form-input"
                                placeholder="e.g. 9876543210">
                            <div id="duplicateWarning"
                                style="display:none; margin-top:0.5rem; padding:0.5rem 0.75rem; background:#fff7ed; border:1.5px solid #fed7aa; border-radius:0.5rem; font-size:0.72rem; font-weight:700; color:#c2410c;">
                                <i class="fas fa-exclamation-triangle"></i> <span id="duplicateMsg"></span>
                                <a id="duplicateLink" href="#"
                                    style="color:var(--primary); margin-left:0.5rem; text-decoration:underline;">View
                                    Lead →</a>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Email ID</label>
                            <input type="email" name="email" class="form-input" placeholder="john@example.com">
                        </div>
                        <div>
                            <label>Referral By (Employee)</label>
                            <select name="referral_person" class="form-input" style="appearance: auto;">
                                <option value="">— None / Walk-in —</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= htmlspecialchars($emp['name']) ?>">
                                        <?= htmlspecialchars($emp['name']) ?>
                                        <?= $emp['designation'] ? '(' . htmlspecialchars($emp['designation']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display:flex;align-items:center;gap:0.5rem;">
                                Assigned Staff
                            </label>
                            <select name="assigned_employee_id" class="form-input" style="appearance: auto;">
                                <option value="">-- No Employee Assigned --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>">
                                        <?= htmlspecialchars($emp['name']) ?>
                                        <?= $emp['designation'] ? '(' . htmlspecialchars($emp['designation']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Pipeline Status</label>
                            <select name="status" class="form-input" style="appearance: auto;">
                                <option value="new">🆕 New</option>
                                <option value="in_progress">⚡ In Progress</option>
                                <option value="won">✅ Won</option>
                                <option value="lost">❌ Lost</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Lead Category</label>
                            <select name="category" class="form-input" style="appearance: auto;">
                                <option value="warm">Warm</option>
                                <option value="hot">Hot 🔥</option>
                                <option value="cold">Cold</option>
                            </select>
                        </div>
                        <div>
                            <label>Task Status</label>
                            <select name="task_status" class="form-input" style="appearance: auto;">
                                <option value="pending">Pending</option>
                                <option value="done">Done</option>
                                <option value="delay">Delay</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Source</label>
                            <select name="source" class="form-input" style="appearance: auto;">
                                <option value="facebook">Facebook</option>
                                <option value="website">Website</option>
                                <option value="referral">Referral</option>
                                <option value="ads">Ads</option>
                            </select>
                        </div>
                        <div>
                            <!-- Empty for alignment -->
                        </div>
                    </div>

                    <div
                        style="background: #f8fafc; padding: 1.25rem; border-radius: 0.75rem; margin-bottom: 1.25rem; border: 1.5px solid #e2e8f0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label>Deal Value (₹)</label>
                                <input type="number" step="0.01" name="deal_value" class="form-input" placeholder="0.00"
                                    style="font-weight: 700;">
                            </div>
                            <div>
                                <label>Comm. Percent (%)</label>
                                <input type="number" step="0.01" name="commission_percent" class="form-input"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label>Requirement</label>
                        <textarea name="requirement" class="form-input" style="height: 80px; resize: vertical;"
                            placeholder="Client's specific requirements, product interest, notes..."></textarea>
                    </div>

                    <div
                        style="display:flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                        <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px;">
                            <i class="fas fa-save" style="font-size: 0.75rem;"></i> Save Lead Details
                        </button>
                    </div>
                </form>

                <!-- Right: Follow-up Engine -->
                <div id="followupSection"
                    style="flex: 1; padding: 1.5rem; background: #fcfcfd; display: none; flex-direction: column;">
                    <div
                        style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                        <h3
                            style="font-size: 0.875rem; font-weight: 800; color: var(--primary); margin: 0; display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-history"></i> Engagement History
                        </h3>
                        <span
                            style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted); background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px;">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars(Auth::userName()) ?>
                        </span>
                    </div>

                    <!-- Add Follow-up Form -->
                    <form id="followupForm"
                        style="background: white; border: 1px solid var(--border); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <div
                            style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <input type="date" name="follow_up_date" required class="form-input"
                                style="height: 36px; font-size: 0.75rem;">
                            <input type="time" name="follow_up_time" value="11:00" class="form-input"
                                style="height: 36px; font-size: 0.75rem;">
                        </div>
                        <input type="text" name="remark" required class="form-input"
                            placeholder="Next action or remark..."
                            style="height: 36px; font-size: 0.75rem; margin-bottom: 0.75rem;">
                        <!-- Pipeline Status Change -->
                        <div style="margin-bottom: 0.75rem;">
                            <label
                                style="font-size:0.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; display:block; margin-bottom:0.3rem;"><i
                                    class="fas fa-exchange-alt"></i> Move Pipeline Stage (optional)</label>
                            <select name="lead_status" class="form-input"
                                style="appearance:auto; height:36px; font-size:0.75rem;">
                                <option value="">— Keep current status —</option>
                                <option value="new">🆕 New</option>
                                <option value="in_progress">⚡ In Progress</option>
                                <option value="won">✅ Won</option>
                                <option value="lost">❌ Lost</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"
                            style="width: 100%; height: 36px; font-size: 0.75rem;">
                            <i class="fas fa-plus"></i> Schedule Follow-up
                        </button>
                    </form>

                    <!-- Follow-up List -->
                    <div id="followupList" style="flex-grow: 1; overflow-y: auto; max-height: 35vh;">
                        <!-- Follow-up items will be injected here -->
                    </div>
                </div>

                <!-- Placeholder for New Lead -->
                <div id="followupPlaceholder"
                    style="flex: 1; padding: 1.5rem; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #94a3b8;">
                    <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p style="font-size: 0.8125rem; font-weight: 600;">Save lead details first to enable history
                        tracking</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Follow-up Modal -->
    <div id="quickFollowModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 320px;">
            <div
                style="padding: 1rem; border-bottom: 1px solid var(--border); display:flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 0.95rem; font-weight: 800; color: var(--primary); margin-bottom: 0.1rem;">
                        Quick Follow-up</h2>
                    <p style="font-size: 0.65rem; color: var(--text-muted); font-weight: 500; margin: 0;">Record
                        interaction for
                        <span id="qf_lead_name" style="color:var(--text-main); font-weight:700;">Client</span>
                    </p>
                </div>
                <button onclick="document.getElementById('quickFollowModal').style.display='none'" class="btn-ghost"
                    style="width: 24px; height: 24px; font-size: 0.75rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="quickFollowForm" style="padding: 1rem;">
                <input type="hidden" name="lead_id" id="qf_lead_id">

                <div
                    style="margin-bottom: 0.75rem; background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 0.5rem; display:flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0;">
                    <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;">Follow-up by:</span>
                    <span style="font-size: 0.7rem; font-weight: 800; color: var(--primary);"><i
                            class="fas fa-user-circle"></i> <?= htmlspecialchars(Auth::userName()) ?></span>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="font-size: 0.65rem;">Next Follow-up Date</label>
                        <input type="date" name="follow_up_date" id="qf_date" required class="form-input"
                            style="height: 32px; font-size: 0.75rem; padding: 0 0.5rem;">
                    </div>
                    <div>
                        <label style="font-size: 0.65rem;">Time</label>
                        <input type="time" name="follow_up_time" id="qf_time" value="11:00" class="form-input"
                            style="height: 32px; font-size: 0.75rem; padding: 0 0.5rem;">
                    </div>
                </div>

                <div style="margin-bottom: 0.75rem;">
                    <label style="font-size: 0.65rem;">Interaction Remark</label>
                    <input type="text" name="remark" required class="form-input"
                        placeholder="e.g. Called and scheduled visit"
                        style="height: 32px; font-size: 0.75rem; padding: 0 0.5rem;">
                </div>

                <!-- Pipeline Status Change -->
                <div
                    style="margin-bottom: 1rem; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:0.5rem; padding:0.6rem;">
                    <label
                        style="font-size:0.65rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; display:block; margin-bottom:0.3rem;"><i
                            class="fas fa-exchange-alt" style="color:var(--primary);"></i> Move Pipeline Stage</label>
                    <select name="lead_status" id="qf_lead_status" class="form-input"
                        style="appearance:auto; font-size:0.75rem; height: 32px; padding: 0 0.5rem;">
                        <option value="">— Keep current status —</option>
                        <option value="new">🆕 New</option>
                        <option value="in_progress">⚡ In Progress</option>
                        <option value="won">✅ Won</option>
                        <option value="lost">❌ Lost</option>
                    </select>
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('quickFollowModal').style.display='none'"
                        style="padding: 0.4rem 0.75rem; font-size: 0.75rem; height: 32px;">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 0.4rem 1rem; font-size: 0.75rem; height: 32px;">
                        <i class="fas fa-check"></i> Record Action
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentEditId = null;

        // --- Duplicate Detection ---
        document.getElementById('leadMobile').addEventListener('blur', async function () {
            const mobile = this.value.trim();
            const requirement = (document.querySelector('[name="requirement"]')?.value || '').trim();
            const warn = document.getElementById('duplicateWarning');
            const msg = document.getElementById('duplicateMsg');
            const link = document.getElementById('duplicateLink');
            if (!mobile || mobile.length < 7) { warn.style.display = 'none'; return; }

            const url = `<?= APP_URL ?>/public/index.php/api/leads.php?action=check_duplicate&mobile=${encodeURIComponent(mobile)}&requirement=${encodeURIComponent(requirement)}${currentEditId ? '&exclude_id=' + currentEditId : ''}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.duplicate && data.lead) {
                msg.textContent = `Duplicate! "${data.lead.name}" already has same mobile + requirement (${data.lead.status.replace('_', ' ').toUpperCase()}).`;
                link.onclick = (e) => { e.preventDefault(); openEditModal(data.lead.id); };
                warn.style.display = 'block';
            } else {
                warn.style.display = 'none';
            }
        });

        function toggleModal(id) {
            const el = document.getElementById(id);
            if (el.style.display === 'flex') {
                el.style.display = 'none';
                document.getElementById('leadForm').reset();
                document.getElementById('followupForm').reset();
                currentEditId = null;
                document.querySelector('#leadModal h2').textContent = 'Add New Lead';
                document.getElementById('followupSection').style.display = 'none';
                document.getElementById('followupPlaceholder').style.display = 'flex';
            } else {
                el.style.display = 'flex';
            }
        }

        async function openEditModal(id) {
            try {
                const response = await fetch(`<?= APP_URL ?>/public/index.php/api/leads.php?id=${id}`);
                const lead = await response.json();

                currentEditId = id;
                document.querySelector('#leadModal h2').textContent = 'Engagement Command Center';

                const form = document.getElementById('leadForm');
                form.elements['name'].value = lead.name;
                form.elements['mobile'].value = lead.mobile;
                form.elements['email'].value = lead.email || '';
                form.elements['category'].value = lead.category;
                form.elements['source'].value = lead.source;
                form.elements['requirement'].value = lead.requirement || '';
                // Set referral_person select by matching stored name
                const refSelect = form.elements['referral_person'];
                const refVal = lead.referral_person || '';
                let refMatched = false;
                for (let opt of refSelect.options) {
                    if (opt.value === refVal) { opt.selected = true; refMatched = true; break; }
                }
                if (!refMatched) refSelect.value = '';
                form.elements['assigned_employee_id'].value = lead.assigned_employee_id || '';
                form.elements['status'].value = lead.status || 'new';
                form.elements['task_status'].value = lead.task_status || 'pending';
                form.elements['commission_percent'].value = lead.commission_percent || '0.00';
                form.elements['deal_value'].value = lead.deal_value || '0.00';

                // Enable Followup Section
                document.getElementById('followupSection').style.display = 'flex';
                document.getElementById('followupPlaceholder').style.display = 'none';
                fetchFollowups(id);

                toggleModal('leadModal');
            } catch (error) {
                console.error('Logic Error:', error);
            }
        }

        async function fetchFollowups(lead_id) {
            const list = document.getElementById('followupList');
            list.innerHTML = '<div style="text-align:center; padding: 2rem; color:#94a3b8;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

            try {
                const response = await fetch(`<?= APP_URL ?>/public/index.php/api/lead_followups.php?lead_id=${lead_id}`);
                const followups = await response.json();

                if (followups.length === 0) {
                    list.innerHTML = '<div style="text-align:center; padding: 2rem; color:#94a3b8; font-size: 0.75rem;">No previous follow-ups found.</div>';
                    return;
                }

                list.innerHTML = followups.map(f => `
                    <div style="background: white; border: 1px solid #f1f5f9; padding: 0.875rem; border-radius: 0.625rem; margin-bottom: 0.75rem; border-left: 3px solid ${f.status === 'pending' ? 'var(--secondary)' : 'var(--success)'};">
                        <div style="display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.25rem;">
                            <span style="font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">
                                ${new Date(f.follow_up_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })} @ ${f.follow_up_time.substring(0, 5)}
                            </span>
                            <span class="badge" style="font-size: 0.55rem; background: ${f.status === 'pending' ? '#fff7ed; color:#c2410c;' : '#d1fae5; color:#065f46;'}">${f.status.toUpperCase()}</span>
                        </div>
                        <p style="font-size: 0.75rem; color: #334155; font-weight: 600; line-height: 1.4; margin: 0;">${f.remark}</p>
                    </div>
                `).join('');
            } catch (error) {
                list.innerHTML = '<div style="text-align:center; color: var(--danger); font-size: 0.75rem;">Failed to load history.</div>';
            }
        }

        document.getElementById('followupForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!currentEditId) return;

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            const triggerDateTime = new Date(data.follow_up_date + 'T' + (data.follow_up_time || '00:00'));
            if (triggerDateTime < new Date()) {
                alert("Reminder date and time cannot be in the past.");
                return;
            }

            data.lead_id = currentEditId;
            const newStatus = data.lead_status || '';
            delete data.lead_status; // not a followup field

            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/lead_followups.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    // Also update lead pipeline status if changed
                    if (newStatus) {
                        await fetch('<?= APP_URL ?>/public/index.php/api/leads.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: currentEditId, status: newStatus })
                        });
                        // Reflect new status in the open form
                        document.getElementById('leadForm').elements['status'].value = newStatus;
                    }
                    this.reset();
                    fetchFollowups(currentEditId);
                    fetchLeads();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Follow-up Error:', error);
            }
        });

        async function openQuickFollowup(id, name) {
            document.getElementById('qf_lead_id').value = id;
            document.getElementById('qf_lead_name').textContent = name;
            document.getElementById('qf_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('quickFollowModal').style.display = 'flex';
        }

        document.getElementById('quickFollowForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            const triggerDateTime = new Date(data.follow_up_date + 'T' + (data.follow_up_time || '00:00'));
            if (triggerDateTime < new Date()) {
                alert("Reminder date and time cannot be in the past.");
                return;
            }

            const newStatus = data.lead_status || '';
            const leadId = data.lead_id;
            delete data.lead_status; // not a followup field

            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/lead_followups.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    // Also update lead pipeline status if changed
                    if (newStatus) {
                        await fetch('<?= APP_URL ?>/public/index.php/api/leads.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: leadId, status: newStatus })
                        });
                    }
                    document.getElementById('quickFollowModal').style.display = 'none';
                    this.reset();
                    fetchLeads();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Core Logic Error:', error);
            }
        });

        let allLeads = [];

        let currentFilters = {
            status: 'all',
            category: 'all',
            source: 'all',
            view: 'table'
        };

        // View Switcher Logic
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilters.view = this.dataset.view;

                if (currentFilters.view === 'kanban') {
                    document.getElementById('kanbanBoard').style.display = 'flex';
                    document.getElementById('tableView').style.display = 'none';
                } else {
                    document.getElementById('kanbanBoard').style.display = 'none';
                    document.getElementById('tableView').style.display = 'block';
                }
                renderLeads(allLeads);
            });
        });

        // Initialize Filter Listeners
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                const group = this.parentElement;
                const filterType = group.dataset.filter;
                const value = this.dataset.value;

                // Update UI
                group.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Update State
                currentFilters[filterType] = value;
                renderLeads(allLeads);
            });
        });

        async function fetchLeads() {
            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/leads.php');
                allLeads = await response.json();
                renderLeads(allLeads);
            } catch (error) {
                console.error('Board Error:', error);
            }
        }

        async function deleteLead(id) {
            if (!confirm('Are you sure you want to delete this lead from the pipeline?')) return;
            try {
                const response = await fetch(`<?= APP_URL ?>/public/index.php/api/leads.php?id=${id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                if (result.success) fetchLeads();
            } catch (error) {
                console.error('Logic Error:', error);
            }
        }

        function renderLeads(leads) {
            const query = document.getElementById('leadSearch').value.toLowerCase();
            document.querySelectorAll('.leads-list').forEach(list => list.innerHTML = '');
            const tableBody = document.getElementById('tableBody');
            tableBody.innerHTML = '';
            const counters = { new: 0, in_progress: 0, won: 0, lost: 0 };

            // True duplicate = same mobile AND same requirement
            const comboCounts = {};
            leads.forEach(l => {
                const key = (l.mobile || '') + '||' + (l.requirement || '').trim().toLowerCase();
                comboCounts[key] = (comboCounts[key] || 0) + 1;
            });
            const duplicateKeys = new Set(Object.keys(comboCounts).filter(k => comboCounts[k] > 1));
            const isDup = l => duplicateKeys.has((l.mobile || '') + '||' + (l.requirement || '').trim().toLowerCase());

            leads.forEach(lead => {
                // Search Filter
                const reqText = (lead.requirement || '').toLowerCase();
                if (query && !lead.name.toLowerCase().includes(query) && !lead.mobile.includes(query) && !reqText.includes(query)) {
                    return;
                }

                // Tab Filters
                if (currentFilters.status !== 'all' && lead.status !== currentFilters.status) return;
                if (currentFilters.category !== 'all' && lead.category !== currentFilters.category) return;
                if (currentFilters.source !== 'all' && lead.source !== currentFilters.source) return;

                const isValidMobile = /^[0-9]{10}$/.test(lead.mobile || '');
                const mobileDisplay = isValidMobile ? lead.mobile : `<span style="color:#ef4444;" title="Invalid Mobile Number">${lead.mobile} <i class="fas fa-exclamation-circle"></i></span>`;

                const column = document.querySelector(`.pipeline-column[data-status="${lead.status}"] .leads-list`);
                if (column) {
                    counters[lead.status]++;
                    const card = document.createElement('div');
                    card.className = 'lead-card';
                    card.innerHTML = `
                        <div class="card-actions">
                            <div class="action-btn followup" title="Quick Follow-up" onclick="openQuickFollowup(${lead.id}, '${lead.name}')"><i class="fas fa-calendar-plus"></i></div>
                            <div class="action-btn" title="Edit Command Center" onclick="openEditModal(${lead.id})"><i class="fas fa-edit"></i></div>
                            <div class="action-btn delete" title="Remove" onclick="deleteLead(${lead.id})"><i class="fas fa-trash"></i></div>
                        </div>
                        <div class="lead-name">${lead.name} ${isDup(lead) ? `<span style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:4px;font-size:0.6rem;font-weight:800;padding:0.1rem 0.4rem;"><i class="fas fa-copy"></i> DUP</span>` : ''}</div>
                        <div class="lead-info">
                            <i class="fas fa-phone" style="width:14px"></i> ${mobileDisplay}
                        </div>
                        ${lead.requirement ? `<div class="lead-info" style="color:#64748b; font-size:0.72rem; font-style:italic; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:240px;" title="${lead.requirement.replace(/"/g, '&quot;')}"><i class="fas fa-file-alt" style="width:14px"></i> ${lead.requirement.length > 50 ? lead.requirement.substring(0, 50) + '…' : lead.requirement}</div>` : ''}
                        ${lead.assigned_to_name ? `<div class="lead-info" style="color:var(--primary); font-weight:700;"><i class="fas fa-user-tie" style="width:14px"></i> ${lead.assigned_to_name}</div>` : ''}
                        ${lead.assigned_employee_name ? `<div class="lead-info" style="color:#7c3aed; font-weight:700;"><i class="fas fa-id-badge" style="width:14px"></i> ${lead.assigned_employee_name}</div>` : ''}
                        ${lead.referral_person ? `<div class="lead-info" style="color:#d97706; font-size:0.71rem; font-weight:600;"><i class="fas fa-share-alt" style="width:14px"></i> Ref: ${lead.referral_person}</div>` : ''}
                        <div style="margin-top:0.75rem; display:flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
                            <span class="badge" style="background:${lead.task_status === 'done' ? '#d1fae5; color:#065f46;' : (lead.task_status === 'delay' ? '#fef3c7; color:#92400e;' : '#f1f5f9; color:#475569;')}; font-size:0.6rem; font-weight:800;">TASK: ${lead.task_status.toUpperCase()}</span>
                            <span style="font-size:0.7rem; color:#94a3b8;"><i class="fas fa-bullseye"></i> ${lead.source}</span>
                        </div>
                        ${lead.follow_up_date ? `<div class="lead-tag lead-followup"><i class="fas fa-clock"></i> Next: ${new Date(lead.follow_up_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })}</div>` : ''}
                        ${lead.commission_amount > 0 ? `<div style="font-size:0.75rem; color:var(--success); font-weight:800; margin-top:0.5rem;"><i class="fas fa-hand-holding-usd"></i> Comm: ₹${parseFloat(lead.commission_amount).toLocaleString('en-IN')}</div>` : ''}
                    `;
                    column.appendChild(card);
                }

                // Table Row Rendering
                const isDuplicate = isDup(lead);
                const row = document.createElement('tr');
                if (isDuplicate) row.style.background = '#fff8f8';
                row.innerHTML = `
                    <td>
                        <span class="table-lead-name">${lead.name}</span>
                        ${isDuplicate ? `<span style="display:inline-block;margin-left:0.4rem;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:4px;font-size:0.6rem;font-weight:800;padding:0.1rem 0.4rem;letter-spacing:0.04em;vertical-align:middle;"><i class="fas fa-copy"></i> DUPLICATE</span>` : ''}
                        <span class="table-lead-sub"><i class="fas fa-phone-alt"></i> ${mobileDisplay}</span>
                    </td>
                    <td style="max-width:200px;">
                        ${lead.requirement
                        ? `<span style="font-size:0.75rem;color:#475569;font-weight:500;" title="${lead.requirement.replace(/"/g, '&quot;')}">${lead.requirement.length > 60 ? lead.requirement.substring(0, 60) + '…' : lead.requirement}</span>`
                        : '<span style="color:#cbd5e1;font-size:0.75rem;">—</span>'}
                    </td>
                    <td><span class="badge badge-${lead.category.toLowerCase()}">${lead.category.toUpperCase()}</span></td>
                    <td><span style="font-size:0.75rem; color:#64748b;"><i class="fas fa-bullseye"></i> ${lead.source}</span></td>
                    <td>
                        <div style="font-weight:600; color:var(--primary); font-size:0.8rem;">
                            <i class="fas fa-user-tie"></i> ${lead.assigned_to_name || '—'}
                        </div>
                        ${lead.assigned_employee_name ? `<div style="font-weight:700; color:#7c3aed; font-size:0.75rem; margin-top:2px;"><i class="fas fa-id-badge"></i> ${lead.assigned_employee_name}</div>` : ''}
                        ${lead.referral_person ? `<div style="font-size:0.72rem; color:#d97706; font-weight:600; margin-top:2px;"><i class="fas fa-share-alt"></i> Ref: ${lead.referral_person}</div>` : ''}
                    </td>
                    <td><strong style="color:#0f172a;">₹${parseFloat(lead.deal_value || 0).toLocaleString('en-IN')}</strong></td>
                    <td><span class="badge" style="background:#f1f5f9; color:#475569; font-weight:800;">${lead.status.toUpperCase().replace('_', ' ')}</span></td>
                    <td><span class="badge" style="background:${lead.task_status === 'done' ? '#d1fae5; color:#065f46;' : (lead.task_status === 'delay' ? '#fef3c7; color:#92400e;' : '#f1f5f9; color:#475569;')}; font-weight:800;">${lead.task_status.toUpperCase()}</span></td>
                    <td>
                        ${lead.follow_up_date ? `
                            <div style="color:#c2410c; font-weight:700; font-size:0.75rem;">
                                <i class="fas fa-calendar-alt"></i> ${new Date(lead.follow_up_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })}
                            </div>
                        ` : '<span style="color:#cbd5e1;">-</span>'}
                    </td>
                    <td style="text-align: right;">
                        <div style="display:flex; gap:0.5rem; justify-content: flex-end;">
                            <button class="action-btn followup" onclick="openQuickFollowup(${lead.id}, '${lead.name}')"><i class="fas fa-calendar-plus"></i></button>
                            <button class="action-btn" onclick="openEditModal(${lead.id})"><i class="fas fa-edit"></i></button>
                            <button class="action-btn" onclick="deleteLead(${lead.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            Object.keys(counters).forEach(status => {
                const badge = document.querySelector(`.pipeline-column[data-status="${status}"] .counter`);
                if (badge) badge.textContent = counters[status];
            });
        }

        document.getElementById('leadSearch').addEventListener('input', () => {
            renderLeads(allLeads);
        });

        document.getElementById('leadForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            const mobileRegex = /^[0-9]{10}$/;
            if (data.mobile && !mobileRegex.test(data.mobile)) {
                alert('Please enter a valid 10-digit mobile number.');
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (data.email && !emailRegex.test(data.email)) {
                alert('Please enter a valid email address.');
                return;
            }

            if (currentEditId) {
                data.id = currentEditId;
            }

            try {
                const url = '<?= APP_URL ?>/public/index.php/api/leads.php';
                const response = await fetch(url, {
                    method: currentEditId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    toggleModal('leadModal');
                    fetchLeads();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error saving lead:', error);
            }
        });

        fetchLeads();
    </script>
</body>

</html>