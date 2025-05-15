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
$menu = <<<HTML
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

echo $menu;
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

<p><br><a class="buttonteste" href="admin_dashboard.php">Criar novo usuário</a></p>
<br><br>
<p><a class="buttonteste" href="docesebytes.php">Voltar ao Menu</a></p>

<?php
terminaPagina();
?>
