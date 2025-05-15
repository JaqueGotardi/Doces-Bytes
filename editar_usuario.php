<?php
session_start();
require_once 'catalogofuncoes.php';

// Verifica se o usuário é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['nivel_acesso_id'] != 1) {
    header("Location: login.php");
    exit;
}

$pdo = getConnection();
$nivel_acesso = $_SESSION['user']['nivel_acesso_id'];

iniciaPagina("Editar Usuário - Docesebytes");

// Menu
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

// Verifica ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_usuarios.php");
    exit;
}

$id = $_GET['id'];
$msg = "";

// Buscar usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}

// Atualiza os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nivelAcessoId = (int)$_POST['nivel_acesso'];

    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, email = ?, nivel_acesso_id = ? WHERE id = ?");
    $stmt->execute([$username, $email, $nivelAcessoId, $id]);

    $msg = "Usuário atualizado com sucesso!";
    // Atualiza para exibição
    $usuario['username'] = $username;
    $usuario['email'] = $email;
    $usuario['nivel_acesso_id'] = $nivelAcessoId;
}
?>

<h1>Editar Usuário</h1>

<?php if ($msg): ?>
    <p class="message"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<form method="post" action="">
    <label>Nome de Usuário:</label>
    <input type="text" name="username" value="<?= htmlspecialchars($usuario['username']) ?>" required>

    <label>E-mail:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

    <label>Nível de Acesso:</label>
    <select name="nivel_acesso" required>
        <option value="1" <?= ($usuario['nivel_acesso_id'] == 1) ? 'selected' : '' ?>>Admin</option>
        <option value="2" <?= ($usuario['nivel_acesso_id'] == 2) ? 'selected' : '' ?>>Atendente</option>
        <option value="3" <?= ($usuario['nivel_acesso_id'] == 3) ? 'selected' : '' ?>>Caixa</option>
        <option value="4" <?= ($usuario['nivel_acesso_id'] == 4) ? 'selected' : '' ?>>Cozinha</option>
    </select>

    <input type="submit" value="Atualizar" class="buttonteste">
</form>

<br>
<p><a class="buttonteste" href="admin_usuarios.php">Voltar para Gerenciar Usuários</a></p>

<?php terminaPagina(); ?>
