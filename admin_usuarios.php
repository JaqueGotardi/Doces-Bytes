<?php
session_start();
require_once 'catalogofuncoes.php';

// Verifica se o usuário é administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['nivel_acesso_id'] != 1) {
    header("Location: login.php");
    exit;
}

$pdo = getConnection();

// Excluir usuário, se solicitado
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_usuarios.php");
    exit;
}

// Buscar todos os usuários
$stmt = $pdo->query("SELECT id, username, email, nivel_acesso_id FROM usuarios");
$usuarios = $stmt->fetchAll();

iniciaPagina("Gerenciar Usuários");
?>

<h1>Gerenciar Usuários</h1>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Usuário</th>
        <th>Email</th>
        <th>Nível de Acesso</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($usuarios as $usuario): ?>
    <tr>
        <td><?= htmlspecialchars($usuario['id']) ?></td>
        <td><?= htmlspecialchars($usuario['username']) ?></td>
        <td><?= htmlspecialchars($usuario['email']) ?></td>
        <td><?= htmlspecialchars($usuario['nivel_acesso_id']) ?></td>
        <td>
            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>">Editar</a> |
            <a href="admin_usuarios.php?delete=<?= $usuario['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<<p><a href="admin_dashboard.php">Criar novo usuário</a></p>
<br><br>
<p><a href="docesebytes.php">Voltar</a></p>

<?php
terminaPagina();
?>
