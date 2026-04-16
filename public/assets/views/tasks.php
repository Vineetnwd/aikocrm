<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks | Aikaa CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Tasks & Performance</h1>
                <div class="header-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i> Assign Task
                    </button>
                </div>
            </header>

            <div class="stats-grid">
                <div class="card stat-card">
                    <span class="stat-label">Pending Tasks</span>
                    <span class="stat-value">8</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value">24</span>
                </div>
            </div>

            <div class="card">
                <h2>Task List</h2>
                <p style="color: var(--text-muted); margin-top: 1rem;">No tasks assigned yet.</p>
            </div>
        </main>
    </div>
</body>
</html>
