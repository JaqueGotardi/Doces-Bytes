<?php
session_start();
require_once 'catalogofuncoes.php';

// Verifica se o usuário está logado e se é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['nivel_acesso_id'] != 1) {
    header("Location: login.php");
    exit;
}

$pdo = getConnection();
$nivel_acesso = $_SESSION['user']['nivel_acesso_id'];

iniciaPagina("Painel do Administrador - Docesebytes");

// Menu personalizado
$menuItems = [
    '<a href="docesebytes.php?page=home">Home</a>',
    '<a href="docesebytes.php?page=comandas">Comandas</a>',
    '<a href="docesebytes.php?page=produtos">Produtos</a>',
    '<a href="docesebytes.php?page=clientes">Clientes</a>',
    '<a href="docesebytes.php?page=pagamentos">Pagamentos</a>',
    '<a href="docesebytes.php?page=relatorio_caixa">Relatórios</a>'
];
if ($nivel_acesso == 1) {
    $menuItems[] = '<a href="admin_usuarios.php">Gerenciar Usuários</a>';
}
$menuItems[] = '<a href="login.php?action=logout">Sair</a>';
$menuStr = implode("  |  ", $menuItems);
echo <<<HTML
<style>
nav a { color: #007BFF; text-decoration: none; font-weight: bold; }
nav a:hover { text-decoration: underline; }
nav div { font-size: 20px; }
</style>
<nav style="width:100%;">
    <div style="width:100%; text-align:center;">$menuStr</div>
</nav>
<hr>
HTML;

$msg = "";

// Se o formulário foi enviado, processa o cadastro do novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nivelAcessoId = (int)$_POST['nivel_acesso'];
    $senha = trim($_POST['senha']);

    if (empty($username) || empty($email) || empty($senha)) {
        $msg = "<p class='message' style='color: red;'>Todos os campos são obrigatórios.</p>";
    } else {
        try {
            $verifica = $pdo->prepare("SELECT 1 FROM usuarios WHERE username = ? LIMIT 1");
            $verifica->execute([$username]);

            if ($verifica->fetch()) {
                $msg = "<p class='message' style='color: red;'>Já existe um usuário com esse nome. Escolha outro.</p>";
            } else {
                $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, email, nivel_acesso_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $email, $nivelAcessoId]);

                $msg = "<p class='message' style='color: green;'>Usuário cadastrado com sucesso!</p>";
            }
        } catch (PDOException $e) {
            $msg = "<p class='message' style='color: red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
echo "<style>
		.submenu-container { display: flex; width: 100%; margin-bottom: 20px; gap: 5px; }
		.submenu-container button { flex: 1; background-color: #007BFF; color: #fff; border: none; border-radius: 5px; padding: 8px 10px; font-weight: bold; cursor: pointer; font-size: 16px; }
		.submenu-container button:hover { background-color: #0056b3; }


	</style>";

echo "<div class='submenu-container'>";
echo "<p><a class='buttonteste' href='admin_usuarios.php'>Gerenciar Usuários</a></p>";
echo   " <input class='buttonteste' type='submit' value='Cadastrar'>";
echo "</div>";
echo "<h2>Painel do Administrador</h2>";
?>

<h3>Cadastrar Novo Usuário</h3>
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
        <option value="4">Cozinha</option>
    </select>
</form>


<br><br>
<p><a class="buttonteste" href="docesebytes.php">Voltar ao Menu</a></p>

<?php
if (!empty($msg)) echo "<div style='margin-top: 20px;'>$msg</div>";
echo "</div>";
terminaPagina();
?>
