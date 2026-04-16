<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads | Aikaa CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        <!-- Sidebar same as dashboard -->
        <aside class="sidebar">
            <a href="dashboard" class="sidebar-logo">
                <i class="fas fa-rocket"></i> Aikaa CRM
            </a>
            <nav class="nav-menu">
                <div class="nav-item"><a href="dashboard" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></div>
                <div class="nav-item"><a href="leads" class="nav-link active"><i class="fas fa-user-friends"></i> Leads</a></div>
                <div class="nav-item"><a href="invoices" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></div>
                <div class="nav-item"><a href="tasks" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a></div>
                <div class="nav-item"><a href="reports" class="nav-link"><i class="fas fa-chart-pie"></i> Reports</a></div>
                <div class="nav-item"><a href="settings" class="nav-link"><i class="fas fa-cog"></i> Settings</a></div>
            </nav>
        </aside>

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
            <div class="pipeline-container">
                <div class="pipeline-column">
                    <div class="pipeline-header">
                        <span>NEW</span>
                        <span class="badge badge-cold">3</span>
                    </div>
                    <div class="lead-card">
                        <div class="lead-name">Alice Cooper</div>
                        <div class="lead-meta">Source: Facebook • <span class="badge badge-hot">HOT</span></div>
                    </div>
                    <div class="lead-card">
                        <div class="lead-name">Bob Marley</div>
                        <div class="lead-meta">Source: Website • <span class="badge badge-warm">WARM</span></div>
                    </div>
                </div>
                <div class="pipeline-column">
                    <div class="pipeline-header">
                        <span>IN PROGRESS</span>
                        <span class="badge badge-cold">2</span>
                    </div>
                    <div class="lead-card">
                        <div class="lead-name">Charlie Brown</div>
                        <div class="lead-meta">Source: Referral • <span class="badge badge-warm">WARM</span></div>
                    </div>
                </div>
                <div class="pipeline-column">
                    <div class="pipeline-header">
                        <span>WON</span>
                        <span class="badge badge-cold">5</span>
                    </div>
                </div>
                <div class="pipeline-column">
                    <div class="pipeline-header">
                        <span>LOST</span>
                        <span class="badge badge-cold">1</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Mockup -->
    <div id="leadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
        <div class="card" style="width: 450px;">
            <h2 style="margin-bottom: 1.5rem;">Add New Lead</h2>
            <form id="leadForm">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Lead Name</label>
                    <input type="text" name="name" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Mobile</label>
                    <input type="text" name="mobile" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
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

        document.getElementById('leadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // API call would go here
            alert('Lead saved successfully (Mock)');
            toggleModal('leadModal');
        });

        // Hide modal initially (fixing CSS display:flex override)
        document.getElementById('leadModal').style.display = 'none';
    </script>
</body>
</html>
