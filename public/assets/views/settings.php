<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Settings</h1>
            </header>

            <div class="card" style="max-width: 600px;">
                <h3 style="margin-bottom: 1.5rem;">Company Profile</h3>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">Company Name</label>
                    <input type="text" value="Aikaa Tech" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-size: 0.875rem;">GST Number</label>
                    <input type="text" placeholder="Optional" style="width:100%; padding:0.625rem; border:1px solid var(--border); border-radius:0.5rem;">
                </div>
                <button class="btn btn-primary">Update Settings</button>
            </div>
        </main>
    </div>
</body>
</html>
