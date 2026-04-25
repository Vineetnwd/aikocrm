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
                <div>
                    <h1 class="page-title">Enterprise Settings</h1>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; font-weight: 500;">Core system and company configuration</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="alert('Settings saved!')">
                        <i class="fas fa-check"></i> Save All Changes
                    </button>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; align-items: start;">
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div class="card" style="padding: 1.5rem;">
                        <h3 style="font-size: 0.9375rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-building" style="color: var(--primary);"></i> Company Profile
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                            <div>
                                <label>Legal Business Name</label>
                                <input type="text" value="Aikaa Tech" class="form-input">
                            </div>
                            <div>
                                <label>GSTIN / Tax ID</label>
                                <input type="text" placeholder="e.g. 29AAAAA0000A1Z5" class="form-input">
                            </div>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label>Support Email Address</label>
                            <input type="email" value="<?= \Core\Auth::user()['email'] ?? 'admin@aikaa.in' ?>" class="form-input">
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label>Business Address</label>
                            <textarea class="form-input" style="height: 60px; resize: none;">Main Tech Hub, Silicon Valley, CA</textarea>
                        </div>

                        <div style="display:flex; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid var(--border);">
                            <button class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Update Profile</button>
                        </div>
                    </div>

                    <div class="card" style="padding: 1.5rem;">
                        <h3 style="font-size: 0.9375rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-envelope-open-text" style="color: var(--primary);"></i> Email & WhatsApp Integration
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                            <div>
                                <label>SMTP Host</label>
                                <input type="text" value="smtp.gmail.com" class="form-input">
                            </div>
                            <div>
                                <label>SMTP Port</label>
                                <input type="text" value="587" class="form-input">
                            </div>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label>WhatsApp Reminder Template</label>
                            <textarea class="form-input" style="height: 80px; resize: none;">Dear {name}, this is a reminder regarding your overdue payment of ₹{amount} for Invoice {inv}. Please settle as soon as possible. Team Aikaa.</textarea>
                            <p style="font-size: 0.65rem; color: #94a3b8; margin-top: 0.5rem;">Available placeholders: {name}, {amount}, {inv}</p>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.5rem; background: linear-gradient(135deg, white 0%, #f1f5f9 100%);">
                    <h3 style="font-size: 0.9375rem; font-weight: 800; margin-bottom: 1.5rem;">System Status</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 500; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 0.75rem;">
                            <span style="color: #64748b;">API Status</span>
                            <span style="color: var(--success); font-weight: 700;">Operational</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 500; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 0.75rem;">
                            <span style="color: #64748b;">Database Connection</span>
                            <span style="color: var(--success); font-weight: 700;">Active</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 500;">
                            <span style="color: #64748b;">CRM Version</span>
                            <span style="color: var(--primary); font-weight: 700;">v2.1.0-Elite</span>
                        </div>
                    </div>
                    <div style="margin-top: 2.5rem; padding: 1.25rem; background: white; border-radius: 0.75rem; border: 1.5px dashed var(--primary); text-align: center;">
                        <p style="font-size: 0.75rem; color: #475569; font-weight: 600; line-height: 1.5;">Your enterprise instance has no pending security updates.</p>
                        <button class="btn btn-ghost" style="margin-top: 1rem; font-size: 0.7rem; color: var(--primary); font-weight: 800;">SECURITY AUDIT LOG</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
