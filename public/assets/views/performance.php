<?php
use Core\Database;
use Core\Auth;

if (!Auth::check()) {
    header("Location: " . APP_URL . "/public/index.php/login");
    exit;
}

$db = Database::getInstance();
$company_id = Auth::companyId();

// If user is admin, they can view any employee. If employee, only themselves.
$is_admin = Auth::user()['role'] === 'admin';
$employees = $db->fetchAll("SELECT id, name FROM employees WHERE company_id = ? AND status = 'active'", [$company_id]);

$selected_emp_id = $_GET['employee_id'] ?? ($is_admin ? ($employees[0]['id'] ?? null) : Auth::user()['employee_id']);

if (!$selected_emp_id) {
    die("No active employees found.");
}

$employee = $db->fetchOne("SELECT * FROM employees WHERE id = ? AND company_id = ?", [$selected_emp_id, $company_id]);

// Calculate Metrics for selected employee
$metrics = [
    'total_leads' => 0,
    'won_leads' => 0,
    'conversion_rate' => 0,
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'task_completion_rate' => 0,
    'revenue_generated' => 0
];

// Leads Metrics
$leads_data = $db->fetchOne("
    SELECT COUNT(*) as total, SUM(IF(status = 'won', 1, 0)) as won
    FROM leads 
    WHERE assigned_employee_id = ? AND company_id = ?
", [$selected_emp_id, $company_id]);

$metrics['total_leads'] = $leads_data['total'] ?? 0;
$metrics['won_leads'] = $leads_data['won'] ?? 0;
if ($metrics['total_leads'] > 0) {
    $metrics['conversion_rate'] = round(($metrics['won_leads'] / $metrics['total_leads']) * 100, 1);
}

// Tasks Metrics (Leads acting as tasks)
$tasks_data = $db->fetchOne("
    SELECT COUNT(*) as total, SUM(IF(task_status = 'done', 1, 0)) as done
    FROM leads 
    WHERE assigned_employee_id = ? AND company_id = ?
", [$selected_emp_id, $company_id]);

$metrics['total_tasks'] = $tasks_data['total'] ?? 0;
$metrics['completed_tasks'] = $tasks_data['done'] ?? 0;
if ($metrics['total_tasks'] > 0) {
    $metrics['task_completion_rate'] = round(($metrics['completed_tasks'] / $metrics['total_tasks']) * 100, 1);
}

// Revenue Generated
$revenue_data = $db->fetchOne("
    SELECT SUM(p.amount) as revenue
    FROM invoice_payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN leads l ON i.lead_id = l.id
    WHERE l.assigned_employee_id = ? AND p.company_id = ?
", [$selected_emp_id, $company_id]);
$metrics['revenue_generated'] = $revenue_data['revenue'] ?? 0;

// Daily Work Report (Today's activities)
$today = date('Y-m-d');
$activities = [];

// 1. Tasks (Lead Requirements) completed today
$completed_today = $db->fetchAll("
    SELECT 'task' as type, CONCAT('Completed Task: ', requirement) as description, task_completed_at as time
    FROM leads
    WHERE assigned_employee_id = ? AND company_id = ? AND task_status = 'done' AND DATE(task_completed_at) = ?
", [$selected_emp_id, $company_id, $today]);
foreach ($completed_today as $act) $activities[] = $act;

// 2. Leads won today
$leads_won_today = $db->fetchAll("
    SELECT 'lead_won' as type, CONCAT('Won Lead: ', name) as description, updated_at as time
    FROM leads
    WHERE assigned_employee_id = ? AND company_id = ? AND status = 'won' AND DATE(updated_at) = ?
", [$selected_emp_id, $company_id, $today]);
foreach ($leads_won_today as $act) $activities[] = $act;

// 3. Payments collected today
$payments_today = $db->fetchAll("
    SELECT 'payment' as type, CONCAT('Collected ₹', amount, ' for Invoice ', i.invoice_number) as description, p.created_at as time
    FROM invoice_payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN leads l ON i.lead_id = l.id
    WHERE l.assigned_employee_id = ? AND p.company_id = ? AND p.payment_date = ?
", [$selected_emp_id, $company_id, $today]);
foreach ($payments_today as $act) $activities[] = $act;

// Sort activities by time desc
usort($activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Performance | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .metric-card { background: white; border-radius: 1rem; padding: 1.5rem; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.05); cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: var(--primary); }
        .metric-value { font-size: 2rem; font-weight: 900; color: #0f172a; margin-top: 0.5rem; }
        .metric-label { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .timeline { position: relative; padding-left: 2rem; margin-top: 1rem; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
        .timeline-item { position: relative; margin-bottom: 1.5rem; }
        .timeline-icon { position: absolute; left: -2.45rem; top: 0; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; color: white; border: 2px solid white; box-shadow: 0 0 0 1px #e2e8f0; }
        
        /* Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
        .modal-container { background: white; border-radius: 1rem; width: 100%; max-width: 800px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: white; z-index: 10; }
        .modal-body { padding: 1.5rem; }
        .details-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .details-table th { text-align: left; padding: 0.75rem; background: #f8fafc; font-weight: 700; color: #64748b; border-bottom: 1px solid var(--border); }
        .details-table td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Employee Performance</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Metrics and daily work reports</p>
                </div>
                <?php if ($is_admin): ?>
                <form action="" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                    <select name="employee_id" class="form-input" style="padding: 0.5rem; width: 200px;" onchange="this.form.submit()">
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $selected_emp_id == $emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php endif; ?>
            </header>

            <!-- Metrics Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div class="metric-card" onclick="showDetails('leads')">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div class="metric-label">Lead Conversion</div>
                        <div style="background: #e0e7ff; color: #4f46e5; width: 32px; height: 32px; border-radius: 8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= $metrics['conversion_rate'] ?>%</div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;"><?= $metrics['won_leads'] ?> / <?= $metrics['total_leads'] ?> Leads Won</div>
                </div>

                <div class="metric-card" onclick="showDetails('tasks')">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div class="metric-label">Task Completion</div>
                        <div style="background: #d1fae5; color: #059669; width: 32px; height: 32px; border-radius: 8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= $metrics['task_completion_rate'] ?>%</div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;"><?= $metrics['completed_tasks'] ?> / <?= $metrics['total_tasks'] ?> Tasks Done</div>
                </div>

                <div class="metric-card" onclick="showDetails('revenue')">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div class="metric-label">Revenue Generated</div>
                        <div style="background: #fef3c7; color: #d97706; width: 32px; height: 32px; border-radius: 8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                    <div class="metric-value" style="font-size: 1.75rem;">₹<?= number_format($metrics['revenue_generated'], 2) ?></div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Total collected from assigned leads</div>
                </div>
            </div>

            <!-- Daily Work Report -->
            <div class="card" style="padding: 1.5rem;">
                <h2 style="font-size: 1.125rem; font-weight: 800; margin-bottom: 1.5rem; color: #0f172a; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                    Daily Work Report (<?= date('d M Y') ?>)
                </h2>
                
                <?php if (empty($activities)): ?>
                    <div style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <i class="fas fa-mug-hot" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <div style="font-weight: 600;">No activities recorded today yet.</div>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activities as $act): 
                            $icon = 'fa-check';
                            $color = '#4f46e5';
                            if ($act['type'] == 'lead_won') { $icon = 'fa-trophy'; $color = '#059669'; }
                            if ($act['type'] == 'payment') { $icon = 'fa-rupee-sign'; $color = '#d97706'; }
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-icon" style="background: <?= $color ?>;"><i class="fas <?= $icon ?>"></i></div>
                            <div style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 1rem; border-radius: 0.5rem;">
                                <div style="font-weight: 700; color: #1e293b; font-size: 0.875rem;"><?= htmlspecialchars($act['description']) ?></div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($act['time'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalTitle" style="font-size: 1.25rem; font-weight: 800; color: #0f172a;">Performance Details</h2>
                <button onclick="document.getElementById('detailsModal').style.display='none'" class="btn-ghost" style="padding: 0.5rem;"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Data will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function closeModal(e) {
            if (e.target.id === 'detailsModal') {
                document.getElementById('detailsModal').style.display = 'none';
            }
        }

        async function showDetails(type) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            const title = document.getElementById('modalTitle');
            const empId = '<?= $selected_emp_id ?>';
            
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align:center; padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:1rem;">Loading details...</p></div>';
            
            let titleText = '';
            if (type === 'leads') titleText = 'Lead Conversion Details';
            else if (type === 'tasks') titleText = 'Task Completion Details';
            else if (type === 'revenue') titleText = 'Revenue Contribution Details';
            title.textContent = titleText;

            try {
                const res = await fetch(`<?= APP_URL ?>/public/index.php/api/performance_details.php?employee_id=${empId}&type=${type}`);
                const data = await res.json();
                
                if (data.error) {
                    content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">${data.error}</div>`;
                    return;
                }

                if (data.length === 0) {
                    content.innerHTML = '<div style="text-align:center; padding:2rem; color:#94a3b8;">No records found for this metric.</div>';
                    return;
                }

                let html = '<table class="details-table"><thead><tr>';
                
                if (type === 'leads') {
                    html += '<th>Lead Name</th><th>Status</th><th>Category</th><th>Follow-up</th></tr></thead><tbody>';
                    data.forEach(row => {
                        html += `<tr>
                            <td style="font-weight:700;">${row.name}</td>
                            <td><span class="badge" style="background:${row.status === 'won' ? '#d1fae5; color:#065f46' : (row.status === 'lost' ? '#fee2e2; color:#991b1b' : '#f1f5f9; color:#475569')}">${row.status.toUpperCase()}</span></td>
                            <td>${row.category.toUpperCase()}</td>
                            <td>${row.follow_up_date || '—'}</td>
                        </tr>`;
                    });
                } else if (type === 'tasks') {
                    html += '<th>Lead/Task</th><th>Requirement</th><th>Status</th><th>Due Date</th></tr></thead><tbody>';
                    data.forEach(row => {
                        html += `<tr>
                            <td style="font-weight:700;">${row.name}</td>
                            <td style="font-size:0.75rem; color:#64748b;">${row.requirement || '—'}</td>
                            <td><span class="badge" style="background:${row.task_status === 'done' ? '#d1fae5; color:#065f46' : (row.task_status === 'delay' ? '#fef3c7; color:#92400e' : '#f1f5f9; color:#475569')}">${row.task_status.toUpperCase()}</span></td>
                            <td>${row.due_date || '—'}</td>
                        </tr>`;
                    });
                } else if (type === 'revenue') {
                    html += '<th>Client</th><th>Invoice</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
                    data.forEach(row => {
                        html += `<tr>
                            <td style="font-weight:700;">${row.client_name}</td>
                            <td>${row.invoice_number}</td>
                            <td style="font-weight:800; color:#059669;">₹${parseFloat(row.amount).toLocaleString('en-IN')}</td>
                            <td>${row.payment_date}</td>
                        </tr>`;
                    });
                }

                html += '</tbody></table>';
                content.innerHTML = html;

            } catch (err) {
                content.innerHTML = `<div style="color:red; text-align:center; padding:2rem;">Error loading data.</div>`;
            }
        }
    </script>
</body>
</html>
