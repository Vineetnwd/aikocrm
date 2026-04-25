<?php
use Core\Database;
use Core\Auth;

$db = Database::getInstance();
$company_id = Auth::companyId();

$filter = $_GET['filter'] ?? 'all';
$today = date('Y-m-d');

// Fetch Tasks (Leads acting as Tasks)
// We show leads that are assigned to an employee and aren't lost.
// Task Status is managed via task_status (pending, done, delay).
$all_items = $db->fetchAll("
    SELECT l.*, e.name as assigned_user,
    (SELECT remark FROM lead_followups WHERE lead_id = l.id AND remark LIKE 'Task Status changed to %' ORDER BY created_at DESC LIMIT 1) as latest_task_remark
    FROM leads l
    LEFT JOIN employees e ON l.assigned_employee_id = e.id
    WHERE l.company_id = ? AND l.assigned_employee_id IS NOT NULL AND l.status != 'lost'
    ORDER BY l.follow_up_date ASC, l.created_at DESC
", [$company_id]);

// If there's no follow-up date, we just consider it an open task, but for today/overdue filters we use follow_up_date.
$today_items = array_filter($all_items, fn($t) => $t['follow_up_date'] == $today && $t['task_status'] != 'done');
$overdue_items = array_filter($all_items, fn($t) => $t['follow_up_date'] != null && $t['follow_up_date'] < $today && $t['task_status'] != 'done');
$upcoming_items = array_filter($all_items, fn($t) => $t['follow_up_date'] != null && $t['follow_up_date'] > $today && $t['task_status'] != 'done');

$display_items = $all_items;
if ($filter === 'today')
    $display_items = $today_items;
elseif ($filter === 'overdue')
    $display_items = $overdue_items;
elseif ($filter === 'upcoming')
    $display_items = $upcoming_items;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .task-row {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .task-row:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .filter-tab {
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .filter-tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .avatar {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #4338ca;
        }

        /* Remark Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
        }

        .modal-container {
            background: white;
            border-radius: 1rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .modal-footer {
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Task Management</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Monitor employee tasks
                        and lead requirements</p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <div
                        style="background: #fef2f2; border: 1px solid #fee2e2; padding: 0.5rem 1rem; border-radius: 0.5rem; display:flex; align-items:center; gap:0.5rem;">
                        <span style="height: 8px; width:8px; background: #ef4444; border-radius: 50%;"></span>
                        <span style="font-size: 0.75rem; font-weight: 800; color: #991b1b;"><?= count($overdue_items) ?>
                            OVERDUE</span>
                    </div>
                    <div
                        style="background: #f0fdf4; border: 1px solid #dcfce7; padding: 0.5rem 1rem; border-radius: 0.5rem; display:flex; align-items:center; gap:0.5rem;">
                        <span style="height: 8px; width:8px; background: #22c55e; border-radius: 50%;"></span>
                        <span style="font-size: 0.75rem; font-weight: 800; color: #166534;"><?= count($today_items) ?>
                            FOR TODAY</span>
                    </div>
                </div>
            </header>

            <div
                style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.5rem; padding-top: 0.5rem;">
                <a href="?filter=all" class="btn filter-tab"
                    style="padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 700; border: 1px solid <?= $filter == 'all' ? 'var(--primary)' : 'var(--border)' ?>; background: <?= $filter == 'all' ? 'var(--primary)' : 'white' ?>; color: <?= $filter == 'all' ? 'white' : 'var(--text)' ?>;">
                    <i class="fas fa-list" style="margin-right: 4px;"></i> All Tasks (<?= count($all_items) ?>)
                </a>
                <a href="?filter=today" class="btn filter-tab"
                    style="padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 700; border: 1px solid <?= $filter == 'today' ? '#10b981' : 'var(--border)' ?>; background: <?= $filter == 'today' ? '#10b981' : 'white' ?>; color: <?= $filter == 'today' ? 'white' : 'var(--text)' ?>;">
                    <i class="fas fa-calendar-day" style="margin-right: 4px;"></i> Today (<?= count($today_items) ?>)
                </a>
                <a href="?filter=overdue" class="btn filter-tab"
                    style="padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 700; border: 1px solid <?= $filter == 'overdue' ? '#ef4444' : 'var(--border)' ?>; background: <?= $filter == 'overdue' ? '#ef4444' : 'white' ?>; color: <?= $filter == 'overdue' ? 'white' : 'var(--text)' ?>;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i> Overdue
                    (<?= count($overdue_items) ?>)
                </a>
                <a href="?filter=upcoming" class="btn filter-tab"
                    style="padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8125rem; font-weight: 700; border: 1px solid <?= $filter == 'upcoming' ? '#f59e0b' : 'var(--border)' ?>; background: <?= $filter == 'upcoming' ? '#f59e0b' : 'white' ?>; color: <?= $filter == 'upcoming' ? 'white' : 'var(--text)' ?>;">
                    <i class="fas fa-calendar-alt" style="margin-right: 4px;"></i> Upcoming
                    (<?= count($upcoming_items) ?>)
                </a>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Lead / Task Requirement</th>
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Assigned To</th>
                            <th
                                style="text-align: left; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Task Status</th>
                            <th
                                style="text-align: right; padding: 1rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($display_items)): ?>
                            <tr>
                                <td colspan="5" style="padding: 4rem; text-align: center; color: #94a3b8;">
                                    <i class="fas fa-tasks"
                                        style="font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.2;"></i>
                                    <p style="font-size: 0.875rem; font-weight: 600;">No records found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($display_items as $item):
                                $is_overdue = $item['follow_up_date'] != null && $item['follow_up_date'] < $today && $item['task_status'] != 'done';
                                $is_today = $item['follow_up_date'] == $today;
                                ?>
                                <tr class="task-row">
                                    <td style="padding: 1.25rem 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom: 0.25rem;">
                                            <div style="font-weight: 800; color: #1e293b; font-size: 0.875rem;">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </div>
                                            <?php if ($item['follow_up_date']): ?>
                                                <span style="font-size: 0.65rem; background: <?= $is_overdue ? '#fee2e2' : ($is_today ? '#dcfce7' : '#f1f5f9') ?>; color: <?= $is_overdue ? '#ef4444' : ($is_today ? '#166534' : '#64748b') ?>; padding: 2px 6px; border-radius: 4px; font-weight: 800;">
                                                    <i class="far fa-calendar-alt"></i> <?= date('d M', strtotime($item['follow_up_date'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #4f46e5; font-weight: 600; margin-top: 0.2rem;">
                                            <?= htmlspecialchars($item['requirement'] ?: 'No requirement specified') ?>
                                        </div>
                                        <?php if (!empty($item['latest_task_remark'])): ?>
                                            <div
                                                style="font-size: 0.7rem; color: #64748b; background: #f0fdf4; padding: 0.4rem 0.6rem; border-radius: 0.4rem; border: 1px solid #dcfce7; margin-top: 0.75rem; line-height: 1.4;">
                                                <i class="fas fa-history" style="color: #22c55e; margin-right: 4px;"></i>
                                                <strong>Latest Update:</strong> <?= htmlspecialchars($item['latest_task_remark']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <div class="avatar"
                                                style="width:28px; height:28px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:800; border: 2px solid white; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                                <?= strtoupper(substr($item['assigned_user'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <span
                                                style="font-size: 0.8125rem; font-weight: 700; color: #475569;"><?= $item['assigned_user'] ?: 'Unassigned' ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <select onchange="updateTaskStatus(<?= $item['id'] ?>, this.value)" class="form-input"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.75rem; width: 110px; font-weight: 600; border-radius: 4px; border: 1px solid #cbd5e1; background: <?= $item['task_status'] == 'done' ? '#d1fae5' : ($item['task_status'] == 'delay' ? '#fef3c7' : '#f8fafc') ?>; color: <?= $item['task_status'] == 'done' ? '#065f46' : ($item['task_status'] == 'delay' ? '#92400e' : '#334155') ?>;">
                                            <option value="pending" <?= $item['task_status'] == 'pending' ? 'selected' : '' ?>>
                                                Pending</option>
                                            <option value="done" <?= $item['task_status'] == 'done' ? 'selected' : '' ?>>Done
                                            </option>
                                            <option value="delay" <?= $item['task_status'] == 'delay' ? 'selected' : '' ?>>Delayed
                                            </option>
                                        </select>
                                    </td>
                                    <td style="padding: 1rem; text-align: right;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                            <button class="btn btn-ghost"
                                                onclick="location.href='leads#lead-<?= $item['id'] ?>'"
                                                style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border: 1px solid #e2e8f0; height: 32px; color: #4f46e5; font-weight: 700; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <i class="fas fa-external-link-alt" style="margin-right: 2px;"></i> View Lead
                                            </button>
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

    <!-- Remark Modal -->
    <div id="remarkModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 style="font-size: 1rem; font-weight: 800; color: #0f172a;">Task Update Remark</h2>
                <button onclick="closeRemarkModal()" class="btn-ghost" style="padding: 0.25rem;"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.75rem; font-weight: 600;">Please provide
                    a quick note about this status update:</p>
                <textarea id="remarkText" class="form-input"
                    style="width: 100%; height: 100px; resize: none; padding: 0.75rem; font-size: 0.875rem;"
                    placeholder="e.g. Discussed with client, they agreed to..."></textarea>
            </div>
            <div class="modal-footer">
                <button onclick="closeRemarkModal()" class="btn btn-ghost"
                    style="font-size: 0.8125rem; font-weight: 700;">Cancel</button>
                <button id="submitRemarkBtn" class="btn btn-primary"
                    style="font-size: 0.8125rem; font-weight: 700;">Update Status</button>
            </div>
        </div>
    </div>

    <script>
        let currentTaskId = null;
        let currentTargetStatus = null;

        function closeRemarkModal() {
            document.getElementById('remarkModal').style.display = 'none';
            document.getElementById('remarkText').value = '';
            // If cancelled, we might want to revert the select value, but easier to just reload if needed or leave as is since the user can re-select.
        }

        async function updateTaskStatus(id, status) {
            currentTaskId = id;
            currentTargetStatus = status;
            document.getElementById('remarkModal').style.display = 'flex';
            document.getElementById('remarkText').focus();
        }

        document.getElementById('submitRemarkBtn').addEventListener('click', async function () {
            const remark = document.getElementById('remarkText').value.trim();
            if (!remark) {
                alert("Please enter a remark.");
                return;
            }

            const id = currentTaskId;
            const status = currentTargetStatus;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            try {
                const res = await fetch('<?= APP_URL ?>/public/index.php/api/tasks.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status, remark })
                });

                if (res.ok) {
                    location.reload();
                } else {
                    alert('Error updating status');
                    this.disabled = false;
                    this.innerHTML = 'Update Status';
                }
            } catch (error) {
                alert('Error updating status');
                this.disabled = false;
                this.innerHTML = 'Update Status';
            }
        });
    </script>
</body>

</html>