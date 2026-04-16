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
            gap: 1.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .pipeline-column {
            flex: 1;
            min-width: 300px;
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 1rem;
        }
        .pipeline-header {
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
        }
        .lead-card {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            box-shadow: var(--shadow-sm);
            cursor: move;
        }
        .lead-name { font-weight: 600; margin-bottom: 0.25rem; }
        .lead-meta { font-size: 0.75rem; color: var(--text-muted); }
        .badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 1rem;
            font-weight: 700;
        }
        .badge-hot { background: #fee2e2; color: #ef4444; }
        .badge-warm { background: #fef3c7; color: #f59e0b; }
        .badge-cold { background: #e0f2fe; color: #0ea5e9; }
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
                        <i class="fas fa-plus"></i> Add Lead
                    </button>
                </div>
            </header>

            <!-- Pipeline View -->
            <div class="pipeline-container" id="kanbanBoard">
                <div class="pipeline-column" data-status="new">
                    <div class="pipeline-header">
                        <span>NEW</span>
                        <span class="badge badge-cold counter">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="in_progress">
                    <div class="pipeline-header">
                        <span>IN PROGRESS</span>
                        <span class="badge badge-cold counter">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="won">
                    <div class="pipeline-header">
                        <span>WON</span>
                        <span class="badge badge-cold counter">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
                <div class="pipeline-column" data-status="lost">
                    <div class="pipeline-header">
                        <span>LOST</span>
                        <span class="badge badge-cold counter">0</span>
                    </div>
                    <div class="leads-list"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Adding Lead -->
    <div id="leadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;">
        <div class="card" style="width: 500px;">
            <h2 style="margin-bottom: 1.5rem;">Add New Lead</h2>
            <form id="leadForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Lead Name</label>
                        <input type="text" name="name" required style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Mobile</label>
                        <input type="text" name="mobile" required style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Email (Optional)</label>
                    <input type="email" name="email" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Category</label>
                        <select name="category" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                            <option value="warm">Warm</option>
                            <option value="hot">Hot</option>
                            <option value="cold">Cold</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Source</label>
                        <select name="source" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                            <option value="facebook">Facebook</option>
                            <option value="website">Website</option>
                            <option value="referral">Referral</option>
                            <option value="ads">Ads</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Requirement</label>
                    <textarea name="requirement" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem; height: 80px;"></textarea>
                </div>
                <div style="display:flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="btn" onclick="toggleModal('leadModal')" style="background:#f1f5f9;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lead</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const el = document.getElementById(id);
            el.style.display = el.style.display === 'none' ? 'flex' : 'none';
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

        function renderLeads(leads) {
            // Clear existing
            document.querySelectorAll('.leads-list').forEach(list => list.innerHTML = '');
            const counters = { new: 0, in_progress: 0, won: 0, lost: 0 };

            leads.forEach(lead => {
                const column = document.querySelector(`.pipeline-column[data-status="${lead.status}"] .leads-list`);
                if (column) {
                    counters[lead.status]++;
                    const card = document.createElement('div');
                    card.className = 'lead-card';
                    card.innerHTML = `
                        <div class="lead-name">${lead.name}</div>
                        <div class="lead-meta">
                            Source: ${lead.source} • 
                            <span class="badge badge-${lead.category}">${lead.category.toUpperCase()}</span>
                        </div>
                        <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.5rem;">
                            <i class="fas fa-phone"></i> ${lead.mobile}
                        </div>
                    `;
                    column.appendChild(card);
                }
            });

            // Update counters
            Object.keys(counters).forEach(status => {
                const badge = document.querySelector(`.pipeline-column[data-status="${status}"] .counter`);
                if (badge) badge.textContent = counters[status];
            });
        }

        document.getElementById('leadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('<?= APP_URL ?>/public/index.php/api/leads.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    toggleModal('leadModal');
                    this.reset();
                    fetchLeads();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error saving lead:', error);
            }
        });

        // Initial Load
        fetchLeads();
    </script>
</body>
</html>
