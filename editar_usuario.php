<?php
session_start();
require_once 'catalogofuncoes.php';

// Verifica se o usuário é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['nivel_acesso_id'] != 1) {
    header("Location: login.php");
    exit;
}

$pdo = getConnection();

// Verifica se foi passado um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_usuarios.php");
    exit;
}

$id = $_GET['id'];
$msg = "";

// Buscar usuário pelo ID
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: admin_usuarios.php");
    exit;
}

// Atualiza os dados do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nivelAcessoId = (int)$_POST['nivel_acesso'];

    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, email = ?, nivel_acesso_id = ? WHERE id = ?");
    $stmt->execute([$username, $email, $nivelAcessoId, $id]);

    $msg = "Usuário atualizado com sucesso!";
    // Atualiza os dados do usuário para exibição
    $usuario['username'] = $username;
    $usuario['email'] = $email;
    $usuario['nivel_acesso_id'] = $nivelAcessoId;
}

iniciaPagina("Editar Usuário");
?>

<h1>Editar Usuário</h1>

<?php if ($msg): ?>
    <p class="success"><?= htmlspecialchars($msg) ?></p>
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
    </select>

    <input type="submit" value="Atualizar">
</form>

<p><a href="admin_usuarios.php">Voltar</a></p>

<?php
terminaPagina();
?>
