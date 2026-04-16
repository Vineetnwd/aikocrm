<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Aikaa CRM</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 800;
            color: #4f46e5;
            text-decoration: none;
            display: block;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 0.875rem;
            background: #4f46e5; /* Fallback hex */
            background: var(--primary, #4f46e5);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 1rem;
            display: block;
        }
        .login-btn:hover {
            background: #4338ca;
        }
        .error-msg {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: none;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <a href="#" class="login-logo">
            <i class="fas fa-rocket"></i> Aikaa CRM
        </a>
        <h2 style="text-align:center; margin-bottom: 0.5rem; font-size: 1.5rem;">Welcome back</h2>
        <p style="text-align:center; color: #64748b; margin-bottom: 2rem; font-size: 0.875rem;">Enter your credentials to access your account</p>
        
        <div id="errorBox" class="error-msg" <?= isset($loginError) ? 'style="display:block"' : '' ?>>
            <?= $loginError ?? '' ?>
        </div>

        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="admin@aikocrm.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="login-btn">
                Log In
            </button>
        </form>

        <p style="text-align:center; margin-top: 2rem; font-size: 0.875rem; color: #64748b;">
            Don't have an account? <a href="#" style="color: #4f46e5; font-weight: 600; text-decoration: none;">Contact Admin</a>
        </p>
    </div>
</body>
</html>
