<?php
session_start();
require_once 'catalogofuncoes.php';

// Verifica se o usuário está logado e se é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['nivel_acesso_id'] != 1) {
    header("Location: login.php");
    exit;
}

$pdo = getConnection();
iniciaPagina("Painel do Administrador - Docesebytes");

// Se o formulário foi enviado, processa o cadastro do novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera os dados do formulário
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nivelAcessoId = (int)$_POST['nivel_acesso'];
    $senha = $_POST['senha'];
    
    // Gera o hash da senha usando password_hash
    $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);

    // Faz o INSERT no banco de dados
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, email, nivel_acesso_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $email, $nivelAcessoId]);

    echo "<p class='message'>Usuário cadastrado com sucesso!</p>";
}

// Conteúdo da página de administrador
echo "<h1>Painel do Administrador</h1>";
echo "<p>Bem-vindo, <strong>" . htmlspecialchars($_SESSION['user']['username']) . "</strong>. Utilize o formulário abaixo para cadastrar novos usuários.</p>";
?>

<h2>Cadastrar Novo Usuário</h2>
<form method="post" action="">
    <label>Nome de Usuário:</label>
    <input type="text" name="username" required>

    <label>E-mail:</label>
    <input type="email" name="email" required>

    <label>Senha:</label>
    <input type="password" name="senha" required>

    <label>Nível de Acesso:</label>
    <select name="nivel_acesso" required>
        <option value="1">Admin</option>
        <option value="2">Atendente</option>
        <option value="3">Caixa</option>
    </select>

    <input type="submit" value="Cadastrar">
</form>

<p><a href="admin_usuarios.php">Gerenciar Usuários</a></p>
<br><br>
<p><a href="docesebytes.php">Voltar</a></p>

<?php
terminaPagina();
?>
