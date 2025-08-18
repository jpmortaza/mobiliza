<?php
require_once '../config.php';

// Se já estiver logado, redirecionar
if (verificar_login()) {
    header("Location: dashboard.php");
    exit();
}

$erro = $_GET['erro'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mobiliza+</title>
    <link rel="stylesheet" href="../estilo_global.css">
    <style>
        body {
            background: linear-gradient(135deg, #e4f5e9 0%, #c8e6c9 50%, #a5d6a7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: var(--espacamento-md);
        }
        
        .login-card {
            background: var(--cor-fundo-card);
            padding: var(--espacamento-xxl);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--sombra-forte);
            text-align: center;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--cor-primaria);
            margin-bottom: var(--espacamento-sm);
        }
        
        .subtitle {
            color: var(--cor-texto-secundario);
            margin-bottom: var(--espacamento-xxl);
        }
        
        .form-group {
            text-align: left;
        }
        
        .forgot-password {
            display: block;
            margin-top: var(--espacamento-md);
            font-size: 0.9rem;
            color: var(--cor-texto-secundario);
        }
        
        .version {
            position: fixed;
            bottom: var(--espacamento-md);
            right: var(--espacamento-md);
            font-size: 0.8rem;
            color: var(--cor-texto-secundario);
            background: rgba(255, 255, 255, 0.8);
            padding: var(--espacamento-xs) var(--espacamento-sm);
            border-radius: var(--border-radius);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">Mobiliza+</div>
            <p class="subtitle">Plataforma de Mobilização Política</p>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>
            
            <form action="processa_login.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <span class="btn-text">Entrar</span>
                    <span class="spinner d-none"></span>
                </button>
                
                <a href="#" class="forgot-password text-secondary">Esqueceu sua senha?</a>
            </form>
        </div>
    </div>
    
    <div class="version">v1.0.0</div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            const text = button.querySelector('.btn-text');
            const spinner = button.querySelector('.spinner');
            
            button.disabled = true;
            text.textContent = 'Entrando...';
            spinner.classList.remove('d-none');
            
            // Se houver erro, reabilitar o botão após um tempo
            setTimeout(() => {
                button.disabled = false;
                text.textContent = 'Entrar';
                spinner.classList.add('d-none');
            }, 5000);
        });
        
        // Focus automático no primeiro campo
        document.getElementById('email').focus();
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                const form = e.target.closest('form');
                const inputs = form.querySelectorAll('input');
                const currentIndex = Array.from(inputs).indexOf(e.target);
                
                if (currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                } else {
                    form.submit();
                }
            }
        });
    </script>
</body>
</html>