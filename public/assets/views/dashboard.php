<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Aikaa CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Dashboard Overview</h1>
                <div class="header-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Lead
                    </button>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="card stat-card">
                    <span class="stat-label">Total Leads</span>
                    <span class="stat-value">124</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Won Deals</span>
                    <span class="stat-value">32</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Pending Invoices</span>
                    <span class="stat-value">₹45,200</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Active Tasks</span>
                    <span class="stat-value">12</span>
                </div>
            </div>

            <!-- Recent Activity / Table -->
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="font-size: 1.25rem;">Recent Leads</h2>
                    <a href="leads" style="color: var(--primary); text-decoration: none; font-size: 0.875rem;">View All</a>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Name</th>
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Category</th>
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Status</th>
                            <th style="padding: 1rem 0; color: var(--text-muted); font-size: 0.875rem;">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem 0;">John Doe</td>
                            <td style="padding: 1rem 0;"><span style="background: #fee2e2; color: #ef4444; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;">HOT</span></td>
                            <td style="padding: 1rem 0;">New</td>
                            <td style="padding: 1rem 0;">Facebook Ads</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem 0;">Jane Smith</td>
                            <td style="padding: 1rem 0;"><span style="background: #fef3c7; color: #f59e0b; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;">WARM</span></td>
                            <td style="padding: 1rem 0;">In Progress</td>
                            <td style="padding: 1rem 0;">Referral</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
