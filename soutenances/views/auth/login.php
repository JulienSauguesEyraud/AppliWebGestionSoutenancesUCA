<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service d'authentification - Université Clermont Auvergne</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --uca-blue: #0055a4;
            --uca-light-blue: #e6f0fa;
            --uca-dark-blue: #003366;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        
        .univ-header {
            background: linear-gradient(135deg, var(--uca-blue) 0%, var(--uca-dark-blue) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .univ-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .service-name {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--uca-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 85, 164, 0.25);
        }
        
        .btn-login {
            background: var(--uca-blue);
            border: none;
            padding: 12px;
            font-weight: 500;
            margin-top: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: var(--uca-dark-blue);
            transform: translateY(-1px);
        }
        
        .input-group-text {
            background: white;
            border-right: none;
            border: 2px solid #e0e0e0;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--uca-blue);
            color: var(--uca-blue);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px;
        }
        
        .logos-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }
        
        .logos-footer img {
            height: 50px;
            width: auto;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .logos-footer img:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="univ-header">
            <div class="univ-name">Université Clermont Auvergne</div>
            <div class="service-name">Service d'authentification</div>
        </div>
        
        <div class="login-form">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?action=login-process">
                <div class="mb-4">
                    <label class="form-label">Identifiant</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="email" placeholder="Adresse email" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                </button>
            </form>
        </div>
        
        <div class="logos-footer">
            <img src="assets/logo/logo_UCA.png" alt="Logo UCA">
            <img src="assets/logo/IUT.png" alt="Logo IUT">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>
</html>