<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Invoice System</h1>
                <div class="header-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Invoice
                    </button>
                </div>
            </header>

            <div class="stats-grid">
                <div class="card stat-card">
                    <span class="stat-label">Total Billed</span>
                    <span class="stat-value">₹1,24,000</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Paid</span>
                    <span class="stat-value">₹85,000</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value" style="color: var(--danger);">₹39,000</span>
                </div>
            </div>

            <div class="card">
                <h2>Recent Invoices</h2>
                <p style="color: var(--text-muted); margin-top: 1rem;">No invoices generated yet.</p>
            </div>
        </main>
    </div>
</body>
</html>
