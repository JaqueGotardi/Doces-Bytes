<?php
// login.php
session_start();
require_once 'catalogofuncoes.php';
$pdo = getConnection();

$msg = "";

// Se a ação for logout, destrói a sessão e redireciona para o login.
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Se o usuário já estiver logado, exibe mensagem de boas-vindas e botão de logout.
if (isset($_SESSION['user'])) {
    iniciaPagina("Login - Docesebytes");
    echo "<h2>Bem-vindo, " . htmlspecialchars($_SESSION['user']['username']) . "</h2>";
    echo "<p>Você está logado com o perfil de acesso <strong>" . htmlspecialchars($_SESSION['user']['nivel_acesso_id']) . "</strong>.</p>";
    echo "<p><a href='login.php?action=logout'>Sair</a></p>";
    terminaPagina();
    exit;
}

// Processamento do formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['usuario']);
    $password = $_POST['senha'];

    if (empty($username) || empty($password)) {
        $msg = "Preencha todos os campos.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            // Verifica se a conta está bloqueada ou inativa
            if ($userData['status'] === 'bloqueado' || $userData['status'] === 'inativo') {
                $msg = "Sua conta está bloqueada ou inativa. Contate o administrador.";
            } else {
                // Verifica se a senha digitada corresponde ao hash armazenado
                if (password_verify($password, $userData['password'])) {
                    // Login bem-sucedido: armazena os dados do usuário na sessão
                    $_SESSION['user'] = $userData;
                    
                    // Redireciona conforme o nível de acesso (ex.: 1=Admin, 2=Atendente, 3=Caixa)
                    $nivel = $userData['nivel_acesso_id'];
                    if ($nivel == 1) {
                        header("Location: docesebytes.php");
                    } elseif ($nivel == 2) {
                        header("Location: atendente_dashboard.php");
                    } elseif ($nivel == 3) {
                        header("Location: caixa_dashboard.php");
                    } else {
                        header("Location: docesebytes.php");
                    }
                    exit;
                } else {
                    $msg = "Nome de usuário ou senha incorretos.";
                }
            }
        } else {
            $msg = "Nome de usuário ou senha incorretos.";
        }
    }
}

iniciaPagina("Login - Docesebytes");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Docesebytes</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="logo-container"style="background-color: #f0f0f0;">
        <img src="logoteste.png" alt="Logo do site" class="logo">
    </div>
    <div class="container">
        
        <?php if (!empty($msg)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($msg); ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <input type="text" name="usuario" placeholder="Usuário" required style="width:300px; font-family: Arial, sans-serif; font-size: 14px; height: 40px; padding: 8px;"><br><br>
<input type="password" name="senha" placeholder="Senha" required style="width:300px; font-family: Arial, sans-serif; font-size: 14px; height: 40px; padding: 8px;"><br>

           <input type="submit" name="login" value="Entrar" style="background-color: #007BFF; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
        </form>
    </div>
</body>
</html>
<?php
terminaPagina();
?>
