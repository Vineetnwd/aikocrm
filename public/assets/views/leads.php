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
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            height: calc(100vh - 140px);
            align-items: flex-start;
        }
        .pipeline-column {
            flex: 1;
            min-width: 280px;
            max-width: 300px;
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            max-height: 100%;
        }
        .pipeline-header {
            font-weight: 700;
            color: #475569;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            flex-shrink: 0;
            padding: 0 0.25rem;
        }
        .leads-list {
            overflow-y: auto;
            flex-grow: 1;
            padding-right: 0.25rem;
        }
        .leads-list::-webkit-scrollbar { width: 4px; }
        .leads-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .lead-card {
            background: white;
            padding: 0.875rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid transparent;
            transition: all 0.2s;
            position: relative;
        }
        .lead-name { font-weight: 700; color: var(--text-main); margin-bottom: 0.375rem; font-size: 0.9375rem; }
        .lead-info { font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.4rem; }
        .lead-requirement { 
            font-size: 0.75rem; 
            color: #475569; 
            background: #f8fafc; 
            padding: 0.375rem; 
            border-radius: 0.25rem;
            margin-top: 0.5rem;
            border-left: 2px solid #e2e8f0;
        }
        .card-actions {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.25rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .lead-card:hover .card-actions { opacity: 1; }
        .action-btn {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Lead Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="toggleModal('leadModal')">
                        <i class="fas fa-plus"></i> Add New Lead
                    </button>
                </div>
            </header>

            <!-- Pipeline View -->
            <div class="pipeline-container" id="kanbanBoard">
                <div class="pipeline-column" data-status="new">
                    <div class="pipeline-header">
                        <span>New Leads</span>
                        <span class="badge badge-cold counter">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="in_progress">
                    <div class="pipeline-header">
                        <span>In Progress</span>
                        <span class="badge badge-warm counter">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="won">
                    <div class="pipeline-header">
                        <span>Won Deals</span>
                        <span class="badge badge-hot counter" style="background:var(--success); color:white;">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="lost">
                    <div class="pipeline-header">
                        <span>Lost</span>
                        <span class="badge counter" style="background:#94a3b8; color:white;">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Adding Lead -->
    <div id="leadModal" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index:100; align-items:center; justify-content:center;">
        <div class="card" style="width: 500px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; font-weight: 800;">Add New Lead</h2>
                <i class="fas fa-times" onclick="toggleModal('leadModal')" style="cursor:pointer; color:#94a3b8;"></i>
            </div>
            <form id="leadForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.8125rem; font-weight: 600; color:#475569;">Lead Name</label>
                        <input type="text" name="name" required class="form-input" style="padding: 0.625rem;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.8125rem; font-weight: 600; color:#475569;">Mobile</label>
                        <input type="text" name="mobile" required class="form-input" style="padding: 0.625rem;">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.8125rem; font-weight: 600; color:#475569;">Email Address</label>
                    <input type="email" name="email" class="form-input" style="padding: 0.625rem;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.8125rem; font-weight: 600; color:#475569;">Category</label>
                        <select name="category" class="form-input" style="padding: 0.625rem;">
                            <option value="warm">Warm</option>
                            <option value="hot">Hot</option>
                            <option value="cold">Cold</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.8125rem; font-weight: 600; color:#475569;">Source</label>
                        <select name="source" class="form-input" style="padding: 0.625rem;">
                            <option value="facebook">Facebook</option>
                            <option value="website">Website</option>
                            <option value="referral">Referral</option>
                            <option value="ads">Ads</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.8125rem; font-weight: 600; color:#475569;">Lead Requirements</label>
                    <textarea name="requirement" class="form-input" style="height: 100px; padding: 0.75rem; border-radius: 0.75rem;"></textarea>
                </div>
                <div style="display:flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn" onclick="toggleModal('leadModal')" style="background:#f8fafc; color:#64748b; border: 1px solid #e2e8f0;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">Save Lead</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentEditId = null;

        function toggleModal(id) {
            const el = document.getElementById(id);
            if (el.style.display === 'flex') {
                el.style.display = 'none';
                document.getElementById('leadForm').reset();
                currentEditId = null;
                document.querySelector('#leadModal h2').textContent = 'Add New Lead';
            } else {
                el.style.display = 'flex';
            }
        }

        async function openEditModal(id) {
            try {
                const response = await fetch(`<?= APP_URL ?>/public/index.php/api/leads.php?id=${id}`);
                const lead = await response.json();
                
                currentEditId = id;
                document.querySelector('#leadModal h2').textContent = 'Edit Lead';
                
                const form = document.getElementById('leadForm');
                form.elements['name'].value = lead.name;
                form.elements['mobile'].value = lead.mobile;
                form.elements['email'].value = lead.email || '';
                form.elements['category'].value = lead.category;
                form.elements['source'].value = lead.source;
                form.elements['requirement'].value = lead.requirement || '';
                
                toggleModal('leadModal');
            } catch (error) {
                console.error('Error fetching lead details:', error);
            }
        }

        async function fetchLeads() {
            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/leads.php');
                const leads = await response.json();
                renderLeads(leads);
            } catch (error) {
                console.error('Error fetching leads:', error);
            }
        }

        async function deleteLead(id) {
            if (!confirm('Are you sure you want to delete this lead?')) return;
            try {
                const response = await fetch(`<?= APP_URL ?>/public/index.php/api/leads.php?id=${id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                if (result.success) fetchLeads();
            } catch (error) {
                console.error('Error deleting lead:', error);
            }
        }

        function renderLeads(leads) {
            document.querySelectorAll('.leads-list').forEach(list => list.innerHTML = '');
            const counters = { new: 0, in_progress: 0, won: 0, lost: 0 };

            leads.forEach(lead => {
                const column = document.querySelector(`.pipeline-column[data-status="${lead.status}"] .leads-list`);
                if (column) {
                    counters[lead.status]++;
                    const card = document.createElement('div');
                    card.className = 'lead-card';
                    card.innerHTML = `
                        <div class="card-actions">
                            <div class="action-btn" title="Edit" onclick="openEditModal(${lead.id})"><i class="fas fa-edit"></i></div>
                            <div class="action-btn delete" title="Delete" onclick="deleteLead(${lead.id})"><i class="fas fa-trash"></i></div>
                        </div>
                        <div class="lead-name">${lead.name}</div>
                        <div class="lead-info">
                            <i class="fas fa-phone" style="width:14px"></i> ${lead.mobile}
                        </div>
                        ${lead.email ? `<div class="lead-info"><i class="fas fa-envelope" style="width:14px"></i> ${lead.email}</div>` : ''}
                        <div style="margin-top:0.75rem; display:flex; justify-content: space-between; align-items: center;">
                            <span class="badge badge-${lead.category.toLowerCase()}">${lead.category.toUpperCase()}</span>
                            <span style="font-size:0.7rem; color:#94a3b8;"><i class="fas fa-bullseye"></i> ${lead.source}</span>
                        </div>
                        ${lead.requirement ? `<div class="lead-requirement">${lead.requirement.substring(0, 100)}${lead.requirement.length > 100 ? '...' : ''}</div>` : ''}
                    `;
                    column.appendChild(card);
                }
            });

            Object.keys(counters).forEach(status => {
                const badge = document.querySelector(`.pipeline-column[data-status="${status}"] .counter`);
                if (badge) badge.textContent = counters[status];
            });
        }

        document.getElementById('leadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
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
