<?php
use Core\Database;
use Core\Auth;
$db = Database::getInstance();
$company_id = Auth::companyId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 1rem; padding: 1.25rem 1.5rem; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .stat-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        .stat-label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 1.75rem; font-weight: 900; color: #0f172a; line-height: 1.1; }

        .toolbar { background: white; padding: 1rem 1.25rem; border-radius: 1rem; border: 1px solid var(--border); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .filter-select { height: 38px; padding: 0 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; color: #334155; background: #f8fafc; cursor: pointer; }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap i { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.75rem; }
        .search-wrap input { width: 100%; padding-left: 2.25rem; height: 38px; font-size: 0.8125rem; }

        .emp-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .emp-table th { background: #f8fafc; padding: 0.875rem 1rem; text-align: left; font-weight: 800; color: #64748b; text-transform: uppercase; font-size: 0.68rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .emp-table td { padding: 0.875rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .emp-table tr:hover td { background: #fcfcfd; }
        .emp-table tr:last-child td { border-bottom: none; }

        .emp-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 800; color: white; flex-shrink: 0; }
        .emp-name { font-weight: 800; color: #0f172a; }
        .emp-id { font-size: 0.7rem; color: #94a3b8; font-weight: 600; }

        .status-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.625rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .status-active   { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-on_leave { background: #fef3c7; color: #92400e; }

        .dept-tag { background: #ede9fe; color: #5b21b6; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }

        .row-actions { display: flex; gap: 0.375rem; justify-content: flex-end; }
        .icon-btn { width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; cursor: pointer; transition: all 0.2s; }
        .icon-btn:hover { border-color: var(--primary); color: var(--primary); background: #f1f5f9; }
        .icon-btn.danger:hover { border-color: #ef4444; color: #ef4444; background: #fff1f2; }

        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block; }
        .empty-state p { font-size: 0.875rem; font-weight: 600; }

        /* Modal */
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 0.375rem; text-transform: uppercase; letter-spacing: 0.04em; }

        @media (max-width: 900px) {
            .stat-cards { grid-template-columns: 1fr 1fr; }
            .modal-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php include 'partials/sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <div>
                <h1 class="page-title">Employees</h1>
                <p style="color:var(--text-muted);font-size:0.8125rem;font-weight:500;">Manage your team members and HR records</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddModal()" style="height:38px;padding:0 1.25rem;display:flex;align-items:center;gap:0.5rem;white-space:nowrap;margin:0;">
                    <i class="fas fa-plus" style="font-size:0.75rem;"></i> Add Employee
                </button>
            </div>
        </header>

        <!-- Stat Cards -->
        <div class="stat-cards" id="statCards">
            <div class="stat-card">
                <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-users"></i></div>
                <div><div class="stat-label">Total</div><div class="stat-value" id="stat-total">—</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-user-check"></i></div>
                <div><div class="stat-label">Active</div><div class="stat-value" id="stat-active">—</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-clock"></i></div>
                <div><div class="stat-label">On Leave</div><div class="stat-value" id="stat-on_leave">—</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-user-slash"></i></div>
                <div><div class="stat-label">Inactive</div><div class="stat-value" id="stat-inactive">—</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#ecfdf5;color:#059669;"><i class="fas fa-hand-holding-usd"></i></div>
                <div><div class="stat-label">Total Commission</div><div class="stat-value" id="stat-commission" style="font-size:1.25rem;">—</div></div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="empSearch" placeholder="Search name, email, ID..." class="form-input" style="margin:0;">
            </div>
            <select id="filterDept" class="filter-select">
                <option value="">All Departments</option>
            </select>
            <select id="filterStatus" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="on_leave">On Leave</option>
            </select>
        </div>

        <!-- Table -->
        <div style="background:white;border-radius:1rem;border:1px solid var(--border);overflow:auto;max-height:calc(100vh - 340px);">
            <table class="emp-table">
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Employee</th>
                        <th>Contact</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Joining Date</th>
                        <th>Salary (₹)</th>
                        <th>Commission Rate</th>
                        <th>Commission Earned</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="empTableBody">
                    <tr><td colspan="10"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div></td></tr>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Add/Edit Modal -->
<div id="empModal" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="max-width:720px;width:95%;">
        <div style="padding:1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 id="modalTitle" style="font-size:1.125rem;font-weight:800;letter-spacing:-0.02em;">Add Employee</h2>
                <p style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">Fill in the details below</p>
            </div>
            <button onclick="closeModal()" class="btn-ghost" style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0;">
                <i class="fas fa-times" style="font-size:0.875rem;"></i>
            </button>
        </div>
        <form id="empForm" style="padding:1.5rem;max-height:70vh;overflow-y:auto;">
            <input type="hidden" id="empId">
            <div class="modal-grid">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="f_name" class="form-input" placeholder="e.g. Ravi Sharma" required>
                </div>
                <div class="form-group">
                    <label>Employee ID</label>
                    <input type="text" id="f_employee_id" class="form-input" placeholder="e.g. EMP-001">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="f_email" class="form-input" placeholder="ravi@example.com">
                </div>
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" id="f_mobile" class="form-input" placeholder="9876543210">
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <input type="text" id="f_designation" class="form-input" placeholder="e.g. Sales Manager">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" id="f_department" class="form-input" placeholder="e.g. Sales">
                </div>
                <div class="form-group">
                    <label>Date of Joining</label>
                    <input type="date" id="f_date_of_joining" class="form-input">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="f_date_of_birth" class="form-input">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select id="f_gender" class="form-input" style="appearance:auto;">
                        <option value="">-- Select --</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Salary (₹)</label>
                    <input type="number" id="f_salary" class="form-input" placeholder="0.00" step="0.01">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="f_status" class="form-input" style="appearance:auto;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="on_leave">On Leave</option>
                    </select>
                </div>
            </div>
            <!-- Commission Section -->
            <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:0.75rem;padding:1rem 1.25rem;margin-bottom:1rem;">
                <div style="font-size:0.7rem;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.875rem;"><i class="fas fa-hand-holding-usd"></i> Commission Settings</div>
                <div class="modal-grid">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Commission Type</label>
                        <select id="f_commission_type" class="form-input" style="appearance:auto;" onchange="updateRateLabel()">
                            <option value="percentage">Percentage of Deal Value (%)</option>
                            <option value="fixed">Fixed Amount per Won Lead (₹)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label id="rateLabel">Commission Rate (%)</label>
                        <input type="number" id="f_commission_rate" class="form-input" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea id="f_address" class="form-input" style="height:60px;resize:none;" placeholder="Full address..."></textarea>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea id="f_notes" class="form-input" style="height:60px;resize:none;" placeholder="Additional notes..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveBtn" style="padding:0 1.5rem;">
                    <i class="fas fa-save" style="font-size:0.75rem;"></i> Save Employee
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';
const API = '<?= APP_URL ?>/public/index.php/api/employees.php';
let allEmployees = [];
let currentFilters = { search: '', department: '', status: '' };

const avatarColors = ['#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777','#2563eb'];
function getAvatarColor(name) {
    let h = 0; for (let c of name) h = c.charCodeAt(0) + ((h << 5) - h);
    return avatarColors[Math.abs(h) % avatarColors.length];
}
function initials(name) {
    return name.split(' ').slice(0,2).map(w => w[0] || '').join('').toUpperCase();
}

const statusLabel = { active: 'Active', inactive: 'Inactive', on_leave: 'On Leave' };
const statusClass = { active: 'status-active', inactive: 'status-inactive', on_leave: 'status-on_leave' };
const statusDot   = { active: '#10b981', inactive: '#ef4444', on_leave: '#f59e0b' };

async function loadStats() {
    const r = await fetch(API + '?action=stats');
    const s = await r.json();
    document.getElementById('stat-total').textContent    = s.total    || 0;
    document.getElementById('stat-active').textContent   = s.active   || 0;
    document.getElementById('stat-on_leave').textContent = s.on_leave || 0;
    document.getElementById('stat-inactive').textContent = s.inactive || 0;
    const comm = parseFloat(s.total_commission || 0);
    document.getElementById('stat-commission').textContent = comm > 0 ? '₹' + comm.toLocaleString('en-IN', {maximumFractionDigits:0}) : '₹0';
}

async function loadDepartments() {
    const r = await fetch(API + '?action=departments');
    const depts = await r.json();
    const sel = document.getElementById('filterDept');
    depts.forEach(d => {
        const o = document.createElement('option');
        o.value = d.department; o.textContent = d.department;
        sel.appendChild(o);
    });
}

async function fetchEmployees() {
    const params = new URLSearchParams();
    if (currentFilters.department) params.append('department', currentFilters.department);
    if (currentFilters.status)     params.append('status',     currentFilters.status);
    if (currentFilters.search)     params.append('search',     currentFilters.search);

    const r = await fetch(API + (params.toString() ? '?' + params : ''));
    allEmployees = await r.json();
    renderTable(allEmployees);
}

function renderTable(emps) {
    const tbody = document.getElementById('empTableBody');
    if (!emps.length) {
        tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-users"></i><p>No employees found</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = emps.map(e => `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div class="emp-avatar" style="background:${getAvatarColor(e.name)}">${initials(e.name)}</div>
                    <div>
                        <div class="emp-name">${escHtml(e.name)}</div>
                        <div class="emp-id">${e.employee_id ? '#'+escHtml(e.employee_id) : 'No ID'}</div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size:0.8rem;font-weight:600;color:#334155;">${e.mobile ? escHtml(e.mobile) : '—'}</div>
                <div style="font-size:0.72rem;color:#94a3b8;">${e.email ? escHtml(e.email) : ''}</div>
            </td>
            <td style="color:#334155;font-weight:600;">${e.designation ? escHtml(e.designation) : '—'}</td>
            <td>${e.department ? `<span class="dept-tag">${escHtml(e.department)}</span>` : '—'}</td>
            <td style="color:#64748b;font-weight:600;">${e.date_of_joining ? fmtDate(e.date_of_joining) : '—'}</td>
            <td style="font-weight:700;color:#0f172a;">${e.salary ? '₹'+Number(e.salary).toLocaleString('en-IN') : '—'}</td>
            <td>
                ${e.commission_rate > 0
                    ? `<span style="font-size:0.75rem;font-weight:700;color:#059669;background:#d1fae5;padding:0.2rem 0.5rem;border-radius:6px;">${e.commission_type === 'fixed' ? '₹'+Number(e.commission_rate).toLocaleString('en-IN')+' fixed' : e.commission_rate+'%'}</span>`
                    : '<span style="color:#cbd5e1;font-size:0.75rem;">None</span>'}
            </td>
            <td style="font-weight:800;color:#059669;">
                ${Number(e.total_commission_earned||0) > 0 ? '₹'+Number(e.total_commission_earned).toLocaleString('en-IN', {maximumFractionDigits:2}) : '—'}
            </td>
            <td>
                <span class="status-badge ${statusClass[e.status] || ''}">
                    <span style="width:6px;height:6px;border-radius:50%;background:${statusDot[e.status]||'#94a3b8'};display:inline-block;"></span>
                    ${statusLabel[e.status] || e.status}
                </span>
            </td>
            <td>
                <div class="row-actions">
                    <a href="${APP_URL}/public/index.php/employee_commissions?id=${e.id}" class="icon-btn" title="View Commission History" style="color:#059669; border-color:#d1fae5; background:#ecfdf5; display:inline-flex; align-items:center; justify-content:center; text-decoration:none;"><i class="fas fa-coins"></i></a>
                    <button class="icon-btn" onclick="openEditModal(${e.id})" title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="icon-btn danger" onclick="deleteEmployee(${e.id}, '${escHtml(e.name).replace(/'/g,"\\'")});" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

function fmtDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });
}
function escHtml(s) {
    const div = document.createElement('div'); div.textContent = s; return div.innerHTML;
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Employee';
    document.getElementById('empId').value = '';
    document.getElementById('empForm').reset();
    document.getElementById('empModal').style.display = 'flex';
}

async function openEditModal(id) {
    const r = await fetch(API + '?id=' + id);
    const e = await r.json();
    document.getElementById('modalTitle').textContent = 'Edit Employee';
    document.getElementById('empId').value                  = e.id;
    document.getElementById('f_name').value                 = e.name || '';
    document.getElementById('f_employee_id').value          = e.employee_id || '';
    document.getElementById('f_email').value                = e.email || '';
    document.getElementById('f_mobile').value               = e.mobile || '';
    document.getElementById('f_designation').value          = e.designation || '';
    document.getElementById('f_department').value           = e.department || '';
    document.getElementById('f_date_of_joining').value      = e.date_of_joining || '';
    document.getElementById('f_date_of_birth').value        = e.date_of_birth || '';
    document.getElementById('f_gender').value               = e.gender || '';
    document.getElementById('f_salary').value               = e.salary || '';
    document.getElementById('f_commission_type').value      = e.commission_type || 'percentage';
    document.getElementById('f_commission_rate').value      = e.commission_rate || '';
    document.getElementById('f_status').value               = e.status || 'active';
    document.getElementById('f_address').value              = e.address || '';
    document.getElementById('f_notes').value                = e.notes || '';
    updateRateLabel();
    document.getElementById('empModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('empModal').style.display = 'none';
}

document.getElementById('empForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const id = document.getElementById('empId').value;
    const payload = {
        name:            document.getElementById('f_name').value.trim(),
        employee_id:     document.getElementById('f_employee_id').value.trim(),
        email:           document.getElementById('f_email').value.trim(),
        mobile:          document.getElementById('f_mobile').value.trim(),
        designation:     document.getElementById('f_designation').value.trim(),
        department:      document.getElementById('f_department').value.trim(),
        date_of_joining: document.getElementById('f_date_of_joining').value,
        date_of_birth:   document.getElementById('f_date_of_birth').value,
        gender:          document.getElementById('f_gender').value,
        salary:          document.getElementById('f_salary').value,
        commission_type: document.getElementById('f_commission_type').value,
        commission_rate: document.getElementById('f_commission_rate').value,
        status:          document.getElementById('f_status').value,
        address:         document.getElementById('f_address').value.trim(),
        notes:           document.getElementById('f_notes').value.trim(),
    };
    if (id) payload.id = id;

    const method = id ? 'PUT' : 'POST';
    const res = await fetch(API, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const result = await res.json();

    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save" style="font-size:0.75rem;"></i> Save Employee';

    if (result.success) {
        closeModal();
        loadStats();
        loadDepartments();
        fetchEmployees();
    } else {
        alert('Error: ' + (result.error || 'Something went wrong'));
    }
});

async function deleteEmployee(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    const res = await fetch(API + '?id=' + id, { method: 'DELETE' });
    const result = await res.json();
    if (result.success) { loadStats(); fetchEmployees(); }
    else alert('Error: ' + result.error);
}

// Rate label updater
function updateRateLabel() {
    const type = document.getElementById('f_commission_type').value;
    document.getElementById('rateLabel').textContent = type === 'fixed' ? 'Fixed Amount per Win (₹)' : 'Commission Rate (%)';
}

// Filters
let searchTimer;
document.getElementById('empSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentFilters.search = this.value; fetchEmployees(); }, 350);
});
document.getElementById('filterDept').addEventListener('change', function() {
    currentFilters.department = this.value; fetchEmployees();
});
document.getElementById('filterStatus').addEventListener('change', function() {
    currentFilters.status = this.value; fetchEmployees();
});

// Close modal on backdrop click
document.getElementById('empModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Init
loadStats();
loadDepartments();
fetchEmployees();
</script>
</body>
</html>
