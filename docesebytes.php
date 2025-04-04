<?php
ob_start();
session_start();
require_once 'catalogofuncoes.php';

function logUserAction($pdo, $userId, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $dataHora = date("Y-m-d H:i:s");
    $stmt = $pdo->prepare("INSERT INTO log_acessos (user_id, action, details, ip, data_hora) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $ip, $dataHora]);
}

function logAction($pdo, $usuario, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $dataHora = date("Y-m-d H:i:s");
    $stmt = $pdo->prepare("INSERT INTO log_acoes (usuario, acao, detalhes, ip, data_hora) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario, $action, $details, $ip, $dataHora]);
}

function getDailyCounter($pdo) {
    $currentDate = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT contador FROM contador_diario WHERE data = ?");
    $stmt->execute([$currentDate]);
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $contador = $row['contador'] + 1;
        $stmtUpdate = $pdo->prepare("UPDATE contador_diario SET contador = ? WHERE data = ?");
        $stmtUpdate->execute([$contador, $currentDate]);
    } else {
        $contador = 1;
        $stmtInsert = $pdo->prepare("INSERT INTO contador_diario (data, contador) VALUES (?, ?)");
        $stmtInsert->execute([$currentDate, $contador]);
    }
    return $contador;
}

function isPasswordStrong($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W).{6,}$/', $password);
}

function sendRecoveryEmail($email, $recoveryLink) {
    return true;
}

function notifyKitchen($comandaId, $message) {
    return true;
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['user']['must_change_password']) && $_SESSION['user']['must_change_password'] === true) {
    header("Location: change_password.php");
    exit;
}

$nivel_acesso = $_SESSION['user']['nivel_acesso_id'];
$userId = $_SESSION['user']['id'];
$usuario = $_SESSION['user']['username'];
$pdo = getConnection();
logUserAction($pdo, $userId, "acesso_sistema", "Usuário acessou o sistema.");
iniciaPagina("Doces & Bytes - Sistema");

$menuItems = [
    '<a href="docesebytes.php?page=home">Home</a>',
    '<a href="docesebytes.php?page=comandas">Comandas</a>',
    '<a href="docesebytes.php?page=produtos">Produtos</a>',
    '<a href="docesebytes.php?page=clientes">Clientes</a>',
    '<a href="docesebytes.php?page=pagamentos">Pagamentos</a>',
    '<a href="docesebytes.php?page=relatorio">Relatórios</a>'
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
echo $menu;
?>
<script>
function createItemManager(options) {
    var manager = {};
    manager.produtos = options.produtos;
    manager.array = [];
    manager.hiddenField = document.getElementById(options.hiddenFieldId);
    manager.displayContainer = document.getElementById(options.displayContainerId);
    manager.dropdown = document.getElementById(options.dropdownId);
    manager.listContainer = document.getElementById(options.listContainerId);
    manager.entryContainer = document.getElementById(options.entryContainerId);
    manager.qtyField = document.getElementById(options.qtyFieldId);
    manager.valorField = document.getElementById(options.valorFieldId);
    manager.obsField = document.getElementById(options.obsFieldId);
    manager.mostrarItens = function() {
        var tipo = document.getElementById(options.tipoItemId).value;
        manager.dropdown.innerHTML = '';
        manager.entryContainer.style.display = 'none';
        if (tipo !== '') {
            manager.listContainer.style.display = 'block';
            var itens = manager.produtos[tipo] || [];
            var defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.text = 'Selecione um produto...';
            manager.dropdown.appendChild(defaultOption);
            for (var i = 0; i < itens.length; i++) {
                var opt = document.createElement('option');
                opt.value = itens[i].nome;
                opt.text = itens[i].nome;
                opt.setAttribute('data-preco', itens[i].preco);
                manager.dropdown.appendChild(opt);
            }
        } else {
            manager.listContainer.style.display = 'none';
        }
    };
    manager.mostrarItemEntry = function() {
        var produtoSelecionado = manager.dropdown.value;
        if (produtoSelecionado !== '') {
            manager.entryContainer.style.display = 'block';
            var preco = manager.dropdown.options[manager.dropdown.selectedIndex].getAttribute('data-preco');
            manager.valorField.value = preco;
        } else {
            manager.entryContainer.style.display = 'none';
        }
    };
    manager.adicionarItem = function() {
        var produto = manager.dropdown.value;
        if (produto === '') {
            alert('Selecione um produto.');
            return;
        }
        var qtd = manager.qtyField.value;
        var valor = manager.valorField.value;
        var obs = manager.obsField.value;
        if (!qtd || qtd <= 0 || !valor || valor <= 0) {
            alert('Preencha a quantidade (>0) e o valor (>0).');
            return;
        }
        var itemObj = { produto: produto, quantidade: parseFloat(qtd), valor: parseFloat(valor), observacao: obs };
        manager.array.push(itemObj);
        manager.atualizarItensAdicionados();
        manager.qtyField.value = '1';
        manager.valorField.value = '';
        manager.obsField.value = '';
        manager.dropdown.selectedIndex = 0;
        manager.entryContainer.style.display = 'none';
        manager.hiddenField.value = JSON.stringify(manager.array);
    };
    manager.atualizarItensAdicionados = function() {
        manager.displayContainer.innerHTML = '';
        if (manager.array.length > 0) {
            var table = document.createElement('table');
            table.setAttribute('border', '1');
            table.setAttribute('cellpadding', '5');
            table.setAttribute('cellspacing', '0');
            table.style.width = "100%";
            var headers = ['Produto', 'Quantidade', 'Observações', 'Valor Unitário', 'Total Produto'];
            var thead = document.createElement('thead');
            var headerRow = document.createElement('tr');
            headers.forEach(function(text) {
                var th = document.createElement('th');
                th.textContent = text;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            for (var i = 0; i < manager.array.length; i++) {
                var item = manager.array[i];
                var totalProduto = item.quantidade * item.valor;
                var row = document.createElement('tr');
                var tdProduto = document.createElement('td');
                tdProduto.textContent = item.produto;
                row.appendChild(tdProduto);
                var tdQuantidade = document.createElement('td');
                tdQuantidade.textContent = item.quantidade;
                row.appendChild(tdQuantidade);
                var tdObs = document.createElement('td');
                tdObs.textContent = item.observacao;
                row.appendChild(tdObs);
                var tdValor = document.createElement('td');
                tdValor.textContent = "R$ " + parseFloat(item.valor).toFixed(2);
                row.appendChild(tdValor);
                var tdTotal = document.createElement('td');
                tdTotal.textContent = "R$ " + parseFloat(totalProduto).toFixed(2);
                row.appendChild(tdTotal);
                tbody.appendChild(row);
            }
            table.appendChild(tbody);
            manager.displayContainer.appendChild(table);
        }
    };
    manager.removerItem = function(index) {
        manager.array.splice(index, 1);
        manager.atualizarItensAdicionados();
        manager.hiddenField.value = JSON.stringify(manager.array);
    };
    return manager;
}
</script>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $data_hora = date('Y-m-d H:i:s');

        if ($action === 'add_comanda') {
            $mesa = trim($_POST['mesa']);
            $itens_json = trim($_POST['itens']);
            $observacoes = trim($_POST['observacoes']);
            $cliente_nome = trim($_POST['cliente_nome']);
            $itens_array = json_decode($itens_json, true);

            if (empty($mesa) || empty($cliente_nome) || !is_array($itens_array) || count($itens_array) == 0) {
                $msg = "Número da mesa, nome do cliente e ao menos um item são obrigatórios.";
                echo "<p class='error'>$msg</p>";
            } else {
                $status = "aberto";
                $data_hora = date('Y-m-d H:i:s');
                $historico = json_encode([["acao" => "criado", "data" => $data_hora, "usuario" => $usuario]]);
                $numero_diario = getDailyCounter($pdo);
                $codigo_unico = $numero_diario;
                $stmt = $pdo->prepare("INSERT INTO comandas (mesa, itens, observacoes, atendente, status, data_hora, historico, cliente_nome, numero_diario, codigo_unico) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$mesa, $itens_json, $observacoes, $usuario, $status, $data_hora, $historico, $cliente_nome, $numero_diario, $codigo_unico]);
                $id_comanda = $pdo->lastInsertId();
                $_SESSION['msg'] = "Comanda cadastrada com sucesso. ID diário: $numero_diario";
                notifyKitchen($id_comanda, "Nova comanda criada e enviada para preparo.");
                logAction($pdo, $usuario, "criar_comanda", "ID: $id_comanda");
                header("Location: docesebytes.php?page=comandas&subpage=cadastrar");
                exit;
            }
        } elseif ($action === 'adicionar_itens_comanda') {
            $id_comanda = intval($_POST['id_comanda']);
            $novos_itens_json = trim($_POST['novos_itens']);
            $novos_itens_array = json_decode($novos_itens_json, true);
            if (!is_array($novos_itens_array) || count($novos_itens_array) == 0) {
                $msg = "Nenhum item foi adicionado.";
            } else {
                $stmt = $pdo->prepare("SELECT itens, status FROM comandas WHERE id = ?");
                $stmt->execute([$id_comanda]);
                $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$comanda) {
                    $msg = "Comanda não encontrada.";
                } elseif ($comanda['status'] != 'aberto') {
                    $msg = "Apenas comandas abertas podem receber novos itens.";
                } else {
                    $itens_atual = json_decode($comanda['itens'], true);
                    if (!is_array($itens_atual)) {
                        $itens_atual = [];
                    }
                    
                    foreach ($novos_itens_array as $novo) {
                        $found = false;
                        foreach ($itens_atual as &$existente) {
                            if (
                                isset($existente['produto'], $novo['produto']) &&
                                $existente['produto'] === $novo['produto'] &&
                                (float)$existente['valor'] === (float)$novo['valor'] &&
                                ((string)($existente['observacao'] ?? '') === (string)($novo['observacao'] ?? ''))
                            ) {
                                $existente['quantidade'] += $novo['quantidade'];
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $itens_atual[] = $novo;
                        }
                    }
                    $stmtUpdate = $pdo->prepare("UPDATE comandas SET itens = ? WHERE id = ?");
                    $stmtUpdate->execute([json_encode($itens_atual), $id_comanda]);
                    $msg = "Itens adicionados com sucesso.";
                    logAction($pdo, $usuario, "adicionar_itens_comanda", "Comanda ID: $id_comanda");
                }
            }
            echo "<p class='message'>$msg</p>";
            echo "<br><button onclick='history.go(-2)'>Voltar</button>";
            exit;
        } elseif ($action === 'cancel_comanda') {
            $id = intval($_POST['id']);
            $justificativa = trim($_POST['justificativa']);
            if (empty($justificativa)) {
                $msg = "Justificativa é obrigatória para cancelamento.";
            } else {
                $stmt = $pdo->prepare("SELECT status, historico, itens FROM comandas WHERE id = ?");
                $stmt->execute([$id]);
                $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$comanda) {
                    $msg = "Comanda não encontrada.";
                } else {
                    $historico = json_decode($comanda['historico'], true) ?: [];
                    $historico[] = [
                        "acao" => "cancelado",
                        "data" => $data_hora,
                        "usuario" => $usuario,
                        "justificativa" => $justificativa
                    ];
                    $historico_json = json_encode($historico);
                    $stmt = $pdo->prepare("UPDATE comandas SET status = 'cancelado', cancellation_justification = ?, historico = ? WHERE id = ?");
                    $stmt->execute([$justificativa, $historico_json, $id]);
                    $msg = "Comanda cancelada com sucesso.";
                    logAction($pdo, $usuario, "cancelar_comanda", "ID: $id, justificativa: $justificativa");
                    notifyKitchen($id, "Comanda cancelada. Interromper preparo.");
                }
            }

            echo "<p class='message' style='font-weight: bold; color: green; font-size: 18px;'>$msg</p>";
            echo "<br><button onclick='history.go(-2)' style='padding: 10px 20px; background-color: #007BFF; border: none; border-radius: 5px; color: white; font-weight: bold; cursor: pointer;'>Voltar</button>";
            exit;
        } elseif ($action === 'entregar_comanda') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE comandas SET status = 'entregue' WHERE id = ?");
            $stmt->execute([$id]);
            echo "<p style='color:green;'>Comanda entregue com sucesso! Pedido entregue para o cliente.</p>";
        } elseif ($action === 'processa_pagamento') {
            $id_comanda = intval($_POST['id_comanda']);
            $valor_recebido = floatval($_POST['valor']);
            $metodos = isset($_POST['metodos']) ? (array) $_POST['metodos'] : [];
            $stmt = $pdo->prepare("SELECT itens, status, pagamento FROM comandas WHERE id = ?");
            $stmt->execute([$id_comanda]);
            $comanda = $stmt->fetch();
            if (!$comanda) {
                $msg = "Comanda não encontrada.";
            } elseif (!in_array($comanda['status'], ['aberto','parcial'])) {
                $msg = "Comanda já processada ou fechada.";
            } else {
                $itens = json_decode($comanda['itens'], true);
                $valor_total = 0;
                if ($itens && is_array($itens)) {
                    foreach ($itens as $item) {
                        $quant = isset($item['quantidade']) ? $item['quantidade'] : 0;
                        $val = isset($item['valor']) ? $item['valor'] : 0;
                        $valor_total += $quant * $val;
                    }
                }
                $pagamento_atual = json_decode($comanda['pagamento'], true);
                if (!is_array($pagamento_atual)) {
                    $pagamento_atual = ["valor_recebido" => 0, "historico" => []];
                }
                $valor_acumulado = floatval($pagamento_atual['valor_recebido']);
                $novo_valor_acumulado = $valor_acumulado + $valor_recebido;
                $novo_pagamento = ["metodos" => $metodos, "valor" => $valor_recebido, "data_hora" => $data_hora, "usuario" => $usuario];
                $pagamento_atual['historico'][] = $novo_pagamento;
                $pagamento_atual['valor_recebido'] = $novo_valor_acumulado;
                if ($novo_valor_acumulado < $valor_total) {
                    $saldo = $valor_total - $novo_valor_acumulado;
                    $msg = "Pagamento parcial efetuado. Saldo restante: R$ " . number_format($saldo, 2, ',', '.');
                    $novo_status = "parcial";
                    $troco = 0;
                } else {
                    $troco = $novo_valor_acumulado - $valor_total;
                    if ($troco > 0) {
                        $msg = "Pagamento integral efetuado com troco. Troco: R$ " . number_format($troco, 2, ',', '.');
                    } else {
                        $msg = "Pagamento efetuado integralmente.";
                    }
                    $novo_status = "parcial";
                }
                $pagamento_json = json_encode($pagamento_atual);
                $stmtUpdate = $pdo->prepare("UPDATE comandas SET status = ?, pagamento = ? WHERE id = ?");
                $stmtUpdate->execute([$novo_status, $pagamento_json, $id_comanda]);
                logAction($pdo, $usuario, "processar_pagamento", "Comanda ID: $id_comanda, valor recebido: $valor_recebido");
                echo "<div id='recibo' style='max-width:600px; margin:20px auto; padding:10px; border:1px solid #000;'>
                        <h3>Recibo de Pagamento</h3>
                        <p><strong>Código da Comanda:</strong> " . htmlspecialchars($id_comanda) . "</p>
                        <h4>Itens do Pedido:</h4>";
                echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%;'>
                        <thead>
                          <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Total</th>
                          </tr>
                        </thead><tbody>";
                foreach ($itens as $item) {
                    $produto = isset($item['produto']) ? $item['produto'] : "";
                    $quant = isset($item['quantidade']) ? $item['quantidade'] : 0;
                    $val = isset($item['valor']) ? $item['valor'] : 0;
                    $totalItem = $quant * $val;
                    echo "<tr>
                            <td>" . htmlspecialchars($produto) . "</td>
                            <td>" . number_format($quant, 2, ',', '.') . "</td>
                            <td>R$ " . number_format($val, 2, ',', '.') . "</td>
                            <td>R$ " . number_format($totalItem, 2, ',', '.') . "</td>
                          </tr>";
                }
                echo "</tbody>
                      <tfoot>
                        <tr>
                          <td colspan='3' style='text-align:right;'><strong>Valor Total:</strong></td>
                          <td><strong>R$ " . number_format($valor_total, 2, ',', '.') . "</strong></td>
                        </tr>
                      </tfoot>
                     </table>";
                echo "<p><strong>Valor Recebido (acumulado):</strong> R$ " . number_format($novo_valor_acumulado, 2, ',', '.') . "</p>";
                if ($troco > 0) {
                    echo "<p><strong>Troco:</strong> R$ " . number_format($troco, 2, ',', '.') . "</p>";
                }
                echo "<p><strong>Métodos de Pagamento:</strong> " . implode(", ", $metodos) . "</p>";
                echo "<p><strong>Data/Hora do Pagamento:</strong> " . htmlspecialchars($data_hora) . "</p>";
                echo "<p><strong>Caixa Responsável:</strong> " . htmlspecialchars($usuario) . "</p>";
                echo "<p>" . $msg . "</p>";
                echo "</div>";
            }
            echo "<p class='message'>$msg</p>";
        } elseif ($action === 'add_produto') {
            if ($nivel_acesso != 1) {
                $msg = "Você não tem permissão para cadastrar produtos.";
            } else {
                $nome = trim($_POST['nome']);
                $descricao = trim($_POST['descricao']);
                $ingredientes = trim($_POST['ingredientes']);
                $preco = floatval($_POST['preco']);
                $categoria = trim($_POST['categoria']);
                $status = trim($_POST['status']);
                if (empty($nome) || empty($descricao) || empty($ingredientes) || $preco <= 0 || empty($categoria) || empty($status)) {
                    $msg = "Todos os campos obrigatórios devem ser preenchidos corretamente.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, ingredientes, preco, categoria, status, data_alteracao) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nome, $descricao, $ingredientes, $preco, $categoria, $status]);
                    $msg = "Produto cadastrado com sucesso.";
                    logAction($pdo, $usuario, "cadastrar_produto", "Produto: $nome");
                }
            }
            echo "<p class='message'>$msg</p>";
        } elseif ($action === 'edit_comanda') {
            $id = intval($_POST['id']);
            $mesa = trim($_POST['mesa']);
            $itens_json = trim($_POST['itens']);
            $observacoes = trim($_POST['observacoes']);
            $stmt = $pdo->prepare("UPDATE comandas SET mesa = ?, itens = ?, observacoes = ? WHERE id = ?");
            $stmt->execute([$mesa, $itens_json, $observacoes, $id]);
            $_SESSION['msg'] = "Comanda alterada com sucesso.";
            header("Location: docesebytes.php?page=comandas&subpage=edicao");
            exit;
        } elseif ($action === 'editar_produto') {
            if ($nivel_acesso != 1) {
                $msg = "Você não tem permissão para editar produtos.";
            } else {
                $id = intval($_POST['id']);
                $nome = trim($_POST['nome']);
                $descricao = trim($_POST['descricao']);
                $ingredientes = trim($_POST['ingredientes']);
                $preco = floatval($_POST['preco']);
                $categoria = trim($_POST['categoria']);
                $status = trim($_POST['status']);
                if (empty($nome) || empty($descricao) || empty($ingredientes) || $preco <= 0 || empty($categoria) || empty($status)) {
                    $msg = "Todos os campos obrigatórios devem ser preenchidos corretamente.";
                } else {
                    $stmtSelect = $pdo->prepare("SELECT nome, descricao, ingredientes, preco, categoria, status FROM produtos WHERE id = ?");
                    $stmtSelect->execute([$id]);
                    $produtoAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC);
                    $historico = json_encode($produtoAntigo);
                    $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, ingredientes = ?, preco = ?, categoria = ?, status = ?, data_alteracao = NOW(), historico = ? WHERE id = ?");
                    $stmt->execute([$nome, $descricao, $ingredientes, $preco, $categoria, $status, $historico, $id]);
                    $msg = "Produto atualizado com sucesso.";
                    logAction($pdo, $usuario, "editar_produto", "Produto ID: $id");
                }
            }
            echo "<p class='message'>$msg</p>";
        } elseif ($action === 'add_cliente') {
            if ($nivel_acesso != 1) {
                $msg = "Você não tem permissão para cadastrar clientes.";
            } else {
                $nome = trim($_POST['nome']);
                $cpf = trim($_POST['cpf']);
                $data_nascimento = trim($_POST['data_nascimento']);
                $endereco = trim($_POST['endereco']);
                $ddd_telefone = trim($_POST['ddd_telefone']);
                $telefone = trim($_POST['telefone']);
                $ddd_whatsapp = trim($_POST['ddd_whatsapp']);
                $whatsapp = trim($_POST['whatsapp']);
                $email = trim($_POST['email']);
                if (empty($nome) || empty($cpf) || empty($data_nascimento) || empty($endereco) || empty($ddd_telefone) || empty($telefone) || empty($ddd_whatsapp) || empty($whatsapp) || empty($email)) {
                    $msg = "Todos os campos obrigatórios devem ser preenchidos.";
                } else {
                    $telefoneCompleto = $ddd_telefone . $telefone;
                    $whatsappCompleto = $ddd_whatsapp . $whatsapp;
                    $stmt = $pdo->prepare("INSERT INTO cliente (nome, cpf, data_nascimento, endereco, telefone, whatsapp, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, $cpf, $data_nascimento, $endereco, $telefoneCompleto, $whatsappCompleto, $email]);
                    $msg = "Cliente cadastrado com sucesso.";
                    logAction($pdo, $usuario, "cadastrar_cliente", "Cliente: $nome");
                }
            }
            echo "<p class='message'>$msg</p>";
        } elseif ($action === 'edit_cliente') {
            $id = intval($_POST['id']);
            $nome = trim($_POST['nome']);
            $cpf = trim($_POST['cpf']);
            $data_nascimento = trim($_POST['data_nascimento']);
            $endereco = trim($_POST['endereco']);
            $ddd_telefone = trim($_POST['ddd_telefone']);
            $telefone = trim($_POST['telefone']);
            $ddd_whatsapp = trim($_POST['ddd_whatsapp']);
            $whatsapp = trim($_POST['whatsapp']);
            $email = trim($_POST['email']);
            if (empty($nome) || empty($cpf) || empty($data_nascimento) || empty($endereco) || empty($ddd_telefone) || empty($telefone) || empty($ddd_whatsapp) || empty($whatsapp) || empty($email)) {
                $msg = "Todos os campos obrigatórios devem ser preenchidos.";
            } else {
                $telefoneCompleto = $ddd_telefone . $telefone;
                $whatsappCompleto = $ddd_whatsapp . $whatsapp;
                $stmt = $pdo->prepare("UPDATE cliente SET nome = ?, cpf = ?, data_nascimento = ?, endereco = ?, telefone = ?, whatsapp = ?, email = ? WHERE id = ?");
                $stmt->execute([$nome, $cpf, $data_nascimento, $endereco, $telefoneCompleto, $whatsappCompleto, $email, $id]);
                $msg = "Cliente atualizado com sucesso.";
                logAction($pdo, $usuario, "editar_cliente", "Cliente ID: $id");
            }
            echo "<p class='message'>$msg</p>";
        }
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

switch ($page) {
    case 'home':
        echo "<h1>Bem-vindo ao Sistema Doces & Bytes</h1>
              <p>Selecione uma opção no menu para gerenciar comandas, produtos, clientes, pagamentos e relatórios.</p>";
        echo "<div style='text-align:center; margin-bottom:20px;'><br><br><br><br>
                <img src='logoteste.png' alt='Logo do Sistema' style='max-width:300px;'><br><br><br>
              </div>";
        break;

    case 'comandas':
        echo "<h2>Gerenciamento de Comandas</h2>";
        echo "<style>
        .submenu-container { display: flex; width: 100%; margin-bottom: 20px; gap: 5px; }
        .submenu-container button { flex: 1; background-color: #007BFF; color: #fff; border: none; border-radius: 5px; padding: 8px 10px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .submenu-container button:hover { background-color: #0056b3; }
        </style>";
        echo "<div class='submenu-container'>
            <button onclick=\"location.href='docesebytes.php?page=comandas&subpage=cadastrar'\">Cadastrar Comanda</button>
            <button onclick=\"location.href='docesebytes.php?page=comandas&subpage=ativas'\">Comandas Ativas</button>
            <button onclick=\"location.href='docesebytes.php?page=comandas&subpage=edicao'\">Edição de Comandas</button>";
        if ($nivel_acesso == 1) {
            echo "  <button onclick=\"location.href='docesebytes.php?page=comandas&subpage=cancelamentos'\">Cancelar Comandas</button>
                    <button onclick=\"location.href='docesebytes.php?page=comandas&subpage=relatorio'\">Relatório de Comandas Diários</button>";
        }
        echo "</div>";
        $subpage = isset($_GET['subpage']) ? $_GET['subpage'] : '';
        if ($subpage == '' || !in_array($subpage, ['cadastrar','ativas','adicionar','edicao','cancelamentos','relatorio'])) {
            echo "<p>Por favor, selecione uma das opções acima.</p>";
            break;
        }
        switch ($subpage) {
            case 'cadastrar':
                echo "<h3>Cadastrar Nova Comanda</h3>";
                if (isset($_SESSION['msg'])) {
                    echo "<p class='message'>" . htmlspecialchars($_SESSION['msg']) . "</p>";
                    echo "<button onclick=\"location.href='docesebytes.php?page=comandas'\" style='background-color: #007BFF; color: #fff; border: none; border-radius: 5px; padding: 8px 10px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px;'>Voltar</button>";
                    unset($_SESSION['msg']);
                } else {
                    $prodStmt = $pdo->query("SELECT nome, categoria, preco FROM produtos WHERE status = 'ativo' ORDER BY nome ASC");
                    $produtos = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
                    $produtosPorCategoria = [];
                    foreach ($produtos as $prod) {
                        $produtosPorCategoria[$prod['categoria']][] = ['nome' => $prod['nome'], 'preco' => $prod['preco']];
                    }
                    echo "<form method='post' action='docesebytes.php' onsubmit='return validateItems()'>
                            <input type='hidden' name='action' value='add_comanda'>
                            <label>Número da Mesa:</label>
                            <input type='text' name='mesa' required><br><br>
                            <label>Nome do Cliente:</label>
                            <input type='text' name='cliente_nome' required><br><br>
                            <div id='itens_adicionados' style='margin-bottom:10px;'></div>
                            <h4>Adicionar Itens</h4>
                            <label>Tipo de Itens:</label>
                            <select id='tipo_item' onchange='itemManager_comanda.mostrarItens()'>
                                <option value=''>Selecione...</option>";
                    foreach ($produtosPorCategoria as $cat => $lista) {
                        echo "<option value='" . htmlspecialchars($cat) . "'>" . ucfirst($cat) . "</option>";
                    }
                    echo "      </select><br><br>
                            <div id='lista_itens' style='display:none; margin-top:5px;'>
                                <label>Produtos Disponíveis:</label>
                                <select id='itens_disponiveis' onchange='itemManager_comanda.mostrarItemEntry()'>
                                    <option value=''>Selecione um produto...</option>
                                </select>
                            </div><br>
                            <div id='item_entry' style='display:none; margin-top:5px;'>
                                <label>Quantidade:</label>
                                <input type='number' id='qtd_item' name='qtd_item' min='1' value='1'>
                                <label>Valor Unitário:</label>
                                <input type='number' id='valor_item' name='valor_item' step='0.01' readonly>
                                <label>Observações (opcional):</label>
                                <input type='text' id='obs_item' name='obs_item'>
                                <button type='button' onclick='itemManager_comanda.adicionarItem()'>Adicionar Item</button>
                            </div><br>
                            <label>Observações Gerais (opcional):</label><br>
                            <textarea name='observacoes'></textarea><br><br>
                            <input type='hidden' name='itens' id='itens' value='[]' required>
                            <input type='submit' value='Registrar Comanda'>
                          </form>
                          <br><button onclick='history.go(-2)'>Voltar</button>";

                    echo "<script>
                            var produtosComanda = " . json_encode($produtosPorCategoria) . ";
                            var itemManager_comanda = createItemManager({
                                managerId: 'itemManager_comanda',
                                tipoItemId: 'tipo_item',
                                listContainerId: 'lista_itens',
                                dropdownId: 'itens_disponiveis',
                                entryContainerId: 'item_entry',
                                qtyFieldId: 'qtd_item',
                                valorFieldId: 'valor_item',
                                obsFieldId: 'obs_item',
                                hiddenFieldId: 'itens',
                                displayContainerId: 'itens_adicionados',
                                produtos: produtosComanda
                            });
                            function validateItems() {
                                var hiddenField = document.getElementById('itens');
                                var itensArray = JSON.parse(hiddenField.value);
                                if (!Array.isArray(itensArray) || itensArray.length === 0) {
                                    alert('É obrigatório adicionar pelo menos 1 item.');
                                    return false;
                                }
                                return true;
                            }
                          </script>";
                }
                break;

            case 'ativas':
                echo "<h3>Comandas Ativas</h3>";
                echo "<form method='get' action='docesebytes.php'>
                        <input type='hidden' name='page' value='comandas'>
                        <input type='hidden' name='subpage' value='ativas'>
                        <label>Filtro (Mesa ou ID):</label>
                        <input type='text' name='filtro' placeholder='Digite a mesa ou ID'>
                        <input type='submit' value='Filtrar'>
                      </form>";
                $query = "SELECT * FROM comandas WHERE status = 'aberto'";
                $params = [];
                if (isset($_GET['filtro']) && trim($_GET['filtro']) !== "") {
                    $filtro = trim($_GET['filtro']);
                    $query .= " AND (mesa LIKE ? OR id LIKE ?)";
                    $params[] = "%" . $filtro . "%";
                    $params[] = "%" . $filtro . "%";
                }
                $query .= " ORDER BY data_hora DESC";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $comandas = $stmt->fetchAll();
                if ($comandas) {
                    echo "<table border='1' cellpadding='5' cellspacing='0'>
                            <thead>
                              <tr>
                                <th>ID</th>
                                <th>Mesa</th>
                                <th>Itens</th>
                                <th>Observações</th>
                                <th>Status</th>
                                <th>Data/Hora</th>
                                <th>Atendente</th>
                                <th>Ações</th>
                              </tr>
                            </thead>
                            <tbody>";
                    foreach ($comandas as $c) {
                        $itensDisplay = "";
                        $itensDecodificados = json_decode($c['itens'], true);
                        if ($itensDecodificados && is_array($itensDecodificados)) {
                            foreach ($itensDecodificados as $item) {
                                $produto = isset($item['produto']) ? $item['produto'] : '';
                                $observacao = isset($item['observacao']) ? $item['observacao'] : '';
                                $totalProduto = $item['quantidade'] * $item['valor'];
                                $itensDisplay .= htmlspecialchars($produto) . " - Quant.: " . htmlspecialchars($item['quantidade']) .
                                                 " - Valor Unit.: R$ " . number_format($item['valor'], 2, ',', '.') .
                                                 " - Total: R$ " . number_format($totalProduto, 2, ',', '.') .
                                                 " - Obs.: " . htmlspecialchars($observacao) . "<br>";
                            }
                        } else {
                            $itensDisplay = $c['itens'];
                        }
                        echo "<tr>
                                <td>" . htmlspecialchars($c['id']) . "</td>
                                <td>" . htmlspecialchars($c['mesa']) . "</td>
                                <td>$itensDisplay</td>
                                <td>" . htmlspecialchars($c['observacoes']) . "</td>
                                <td>" . htmlspecialchars($c['status']) . "</td>
                                <td>" . htmlspecialchars($c['data_hora']) . "</td>
                                <td>" . htmlspecialchars($c['atendente']) . "</td>
                                <td>
                                    <a href='docesebytes.php?page=comandas&subpage=adicionar&id=" . htmlspecialchars($c['id']) . "'>Acrescentar Itens</a>
                                </td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>Nenhuma comanda ativa encontrada.</p>";
                }
                echo "<br><button onclick='history.go(-1)'>Voltar</button>";
                break;

            case 'adicionar':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_itens_comanda') {
                    $id_comanda = intval($_POST['id_comanda']);
                    $novos_itens_json = trim($_POST['novos_itens']);
                    $stmt = $pdo->prepare("SELECT itens FROM comandas WHERE id = ?");
                    $stmt->execute([$id_comanda]);
                    $comandaTemp = $stmt->fetch(PDO::FETCH_ASSOC);
                    $itens_atuais = [];
                    if ($comandaTemp) {
                        $itens_atuais = json_decode($comandaTemp['itens'], true);
                        if (!is_array($itens_atuais)) {
                            $itens_atuais = [];
                        }
                    }
                    $novos_itens_array = json_decode($novos_itens_json, true);
                    if (!is_array($novos_itens_array)) {
                        $novos_itens_array = [];
                    }
                    foreach ($novos_itens_array as $novo) {
                        $found = false;
                        foreach ($itens_atuais as &$existente) {
                            if (
                                isset($existente['produto'], $novo['produto']) &&
                                $existente['produto'] === $novo['produto'] &&
                                (float)$existente['valor'] === (float)$novo['valor'] &&
                                ((string)($existente['observacao'] ?? '') === (string)($novo['observacao'] ?? ''))
                            ) {
                                $existente['quantidade'] += $novo['quantidade'];
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $itens_atuais[] = $novo;
                        }
                    }
                    $stmtUpdate = $pdo->prepare("UPDATE comandas SET itens = ? WHERE id = ?");
                    $stmtUpdate->execute([json_encode($itens_atuais), $id_comanda]);
                    echo "<p class='message'>Itens adicionados com sucesso.</p>";
                    echo "<button onclick='history.go(-2)'>Voltar</button>";
                    exit;
                } else {
                    if (!isset($_GET['id'])) {
                        echo "<p class='error'>ID da comanda não informado.</p>";
                        break;
                    }
                    $id = intval($_GET['id']);
                    $stmt = $pdo->prepare("SELECT * FROM comandas WHERE id = ?");
                    $stmt->execute([$id]);
                    $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$comanda) {
                        echo "<p class='error'>Comanda não encontrada.</p>";
                        break;
                    }
                    echo "<h2>Acrescentar Itens à Comanda</h2>";
                    echo "<p><strong>Comanda ID:</strong> " . htmlspecialchars($comanda['id']) . "</p>";
                    echo "<p><strong>Mesa:</strong> " . htmlspecialchars($comanda['mesa']) . "</p>";
                    echo "<p><strong>Cliente:</strong> " . htmlspecialchars($comanda['cliente_nome']) . "</p>";
                    $itens = json_decode($comanda['itens'], true);
                    if (is_array($itens) && count($itens) > 0) {
                        echo "<h3>Itens Adicionados:</h3>";
                        echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse:collapse;'>
                                <thead>
                                  <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Valor Unitário</th>
                                    <th>Total</th>
                                    <th>Observações</th>
                                  </tr>
                                </thead>
                                <tbody>";
                        $valor_total = 0;
                        foreach ($itens as $item) {
                            $produto = $item['produto'] ?? '';
                            $qtd = $item['quantidade'] ?? 0;
                            $valor = $item['valor'] ?? 0;
                            $obs = $item['observacao'] ?? '';
                            $subtotal = $qtd * $valor;
                            $valor_total += $subtotal;
                            echo "<tr>
                                    <td>" . htmlspecialchars($produto) . "</td>
                                    <td>" . htmlspecialchars($qtd) . "</td>
                                    <td>R$ " . number_format($valor, 2, ',', '.') . "</td>
                                    <td>R$ " . number_format($subtotal, 2, ',', '.') . "</td>
                                    <td>" . htmlspecialchars($obs) . "</td>
                                  </tr>";
                        }
                        echo "</tbody>
                              <tfoot>
                                <tr>
                                  <td colspan='3' style='text-align:right;'><strong>Valor Total da Comanda:</strong></td>
                                  <td colspan='2'><strong>R$ " . number_format($valor_total, 2, ',', '.') . "</strong></td>
                                </tr>
                              </tfoot>
                             </table>";
                    } else {
                        echo "<h3>Itens Adicionados:</h3>";
                        echo "<p>Nenhum item adicionado ainda.</p>";
                    }
                    $prodStmt = $pdo->query("SELECT nome, categoria, preco FROM produtos WHERE status = 'ativo' ORDER BY nome ASC");
                    $produtos = $prodStmt->fetchAll(PDO::FETCH_ASSOC);
                    $produtosPorCategoria = [];
                    foreach ($produtos as $prod) {
                        $produtosPorCategoria[$prod['categoria']][] = ['nome' => $prod['nome'], 'preco' => $prod['preco']];
                    }
                    echo "<h3>Adicionar Novos Itens</h3>
                          <form method='post' action='docesebytes.php?page=comandas&subpage=adicionar&id=" . $id . "'>
                            <input type='hidden' name='action' value='adicionar_itens_comanda'>
                            <input type='hidden' name='id_comanda' value='" . $id . "'>
                            <label>Tipo de Itens:</label>
                            <select id='tipo_item' onchange='itemManager_adicionar.mostrarItens()'>
                                <option value=''>Selecione...</option>";
                    foreach ($produtosPorCategoria as $cat => $lista) {
                        echo "<option value='" . htmlspecialchars($cat) . "'>" . ucfirst($cat) . "</option>";
                    }
                    echo "</select><br><br>
                          <div id='lista_itens' style='display:none; margin-top:5px;'>
                              <label>Produtos Disponíveis:</label>
                              <select id='itens_disponiveis' onchange='itemManager_adicionar.mostrarItemEntry()'>
                                  <option value=''>Selecione um produto...</option>
                              </select>
                          </div><br>
                          <div id='item_entry' style='display:none; margin-top:5px;'>
                              <label>Quantidade:</label>
                              <input type='number' id='qtd_item' min='1' value='1'>
                              <label>Valor Unitário:</label>
                              <input type='number' id='valor_item' step='0.01' readonly>
                              <label>Observações (opcional):</label>
                              <input type='text' id='obs_item'>
                              <button type='button' onclick='itemManager_adicionar.adicionarItem()'>Adicionar Item</button>
                          </div><br>
                          <input type='hidden' name='novos_itens' id='novos_itens' value='[]'>
                          <div id='novos_itens_display'></div>
                          <input type='submit' value='Adicionar Itens'>
                        </form>
                        <br><button onclick='history.go(-2)'>Voltar</button>";
                    echo "<script>
                            var produtosAdicionar = " . json_encode($produtosPorCategoria) . ";
                            var itemManager_adicionar = createItemManager({
                                managerId: 'itemManager_adicionar',
                                tipoItemId: 'tipo_item',
                                listContainerId: 'lista_itens',
                                dropdownId: 'itens_disponiveis',
                                entryContainerId: 'item_entry',
                                qtyFieldId: 'qtd_item',
                                valorFieldId: 'valor_item',
                                obsFieldId: 'obs_item',
                                hiddenFieldId: 'novos_itens',
                                displayContainerId: 'novos_itens_display',
                                produtos: produtosAdicionar
                            });
                          </script>";
                }
                break;

            case 'edicao':
                if (isset($_GET['id'])) {
                    // Edição individual da comanda
                    $id = intval($_GET['id']);
                    $stmt = $pdo->prepare("SELECT * FROM comandas WHERE id = ?");
                    $stmt->execute([$id]);
                    $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($comanda && $comanda['status'] == 'aberto') {
                        echo "<h2>Editar Comanda <br><br> ID da Comanda: " . htmlspecialchars($comanda['id']) . " <br> Cliente: " . htmlspecialchars($comanda['cliente_nome']) . "</h2>";
                        echo "<form method='post' action='docesebytes.php'>
                                <input type='hidden' name='action' value='edit_comanda'>
                                <input type='hidden' name='id' value='" . htmlspecialchars($comanda['id']) . "'>
                                <label>Número da Mesa:</label><br>
                                <input type='text' name='mesa' value='" . htmlspecialchars($comanda['mesa']) . "' required><br><br>
                                <h3>Itens do Pedido</h3>";
                        $itens = json_decode($comanda['itens'], true);
                        echo "<table border='1' cellpadding='5' cellspacing='0'>
                                <thead>
                                  <tr>
                                    <th>Item</th>
                                    <th>Quantidade Atual</th>
                                    <th>Nova Quantidade</th>
                                    <th>Valor</th>
                                    <th>Excluir</th>
                                  </tr>
                                </thead>
                                <tbody id='itens_tbody'>";
                        if ($itens && is_array($itens) && count($itens) > 0) {
                            foreach ($itens as $index => $item) {
                                $produto = isset($item['produto']) ? $item['produto'] : "";
                                $quantidade = isset($item['quantidade']) ? $item['quantidade'] : 0;
                                $valor = isset($item['valor']) ? $item['valor'] : 0;
                                echo "<tr id='item_$index'>
                                        <td>" . htmlspecialchars($produto) . "</td>
                                        <td>" . htmlspecialchars($quantidade) . "</td>
                                        <td><input type='number' value='" . htmlspecialchars($quantidade) . "' min='0' onchange='atualizaQuantidade(this.value, $index)' /></td>
                                        <td>R$ " . number_format($valor, 2, ',', '.') . "</td>
                                        <td><button type='button' onclick='removerItem($index)'>Excluir</button></td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>Nenhum item adicionado.</td></tr>";
                        }
                        echo "</tbody></table>
                              <input type='hidden' name='itens' id='itens' value='" . $comanda['itens'] . "'>
                              <br><label>Observações:</label><br>
                              <textarea name='observacoes' style='width:100%; height:80px;'>" . htmlspecialchars($comanda['observacoes']) . "</textarea><br><br>
                              <label>Justificativa para edição:</label><br>
                              <textarea name='justificativa' style='width:100%; height:80px;' required></textarea><br><br>
                              <input type='submit' value='Atualizar Comanda'>
                              </form>
                              <br><button onclick='history.go(-1)'>Voltar</button>";
                        ?>
                        <script>
                            var itensArray = JSON.parse(document.getElementById('itens').value);
                            function atualizaQuantidade(newValue, index) {
                                newValue = parseFloat(newValue);
                                if (isNaN(newValue) || newValue < 0) {
                                    alert("Quantidade inválida");
                                    return;
                                }
                                itensArray[index].quantidade = newValue;
                                document.querySelector("#item_" + index + " td:nth-child(2)").innerText = newValue;
                                document.getElementById('itens').value = JSON.stringify(itensArray);
                            }
                            function removerItem(index) {
                                itensArray.splice(index, 1);
                                atualizarTabelaItens();
                                document.getElementById('itens').value = JSON.stringify(itensArray);
                            }
                            function atualizarTabelaItens() {
                                var tbody = document.getElementById('itens_tbody');
                                tbody.innerHTML = "";
                                for (var i = 0; i < itensArray.length; i++) {
                                    var item = itensArray[i];
                                    var tr = document.createElement('tr');
                                    tr.id = "item_" + i;
                                    tr.innerHTML = "<td>" + item.produto + "</td>" +
                                                   "<td>" + item.quantidade + "</td>" +
                                                   "<td><input type='number' value='" + item.quantidade + "' min='0' onchange='atualizaQuantidade(this.value, " + i + ")' /></td>" +
                                                   "<td>R$ " + parseFloat(item.valor).toFixed(2) + "</td>" +
                                                   "<td><button type='button' onclick='removerItem(" + i + ")'>Excluir</button></td>";
                                    tbody.appendChild(tr);
                                }
                            }
                        </script>
                        <?php
                    } else {
                        echo "<p class='error'>Comanda não encontrada ou não pode ser editada.</p>";
                        echo "<br><button onclick='history.go(-1)'>Voltar</button>";
                    }
                } else {
                    // Listagem para seleção com filtros
                    echo "<h3>Edição de Comandas</h3>";
                    echo "<form method='get' action='docesebytes.php'>
                            <input type='hidden' name='page' value='comandas'>
                            <input type='hidden' name='subpage' value='edicao'>
                            <label>ID:</label>
                            <input type='text' name='filtro_id' placeholder='Digite o ID'>
                            <label>Mesa:</label>
                            <input type='text' name='filtro_mesa' placeholder='Digite a mesa'>
                            <label>Cliente:</label>
                            <input type='text' name='filtro_cliente' placeholder='Digite o nome do cliente'>
                            <input type='submit' value='Filtrar'>
                          </form><br>";
                    $query = "SELECT * FROM comandas WHERE status IN ('aberto','parcial')";
                    $conditions = [];
                    $params = [];
                    if (!empty($_GET['filtro_id'])) {
                        $conditions[] = "id LIKE ?";
                        $params[] = "%" . $_GET['filtro_id'] . "%";
                    }
                    if (!empty($_GET['filtro_mesa'])) {
                        $conditions[] = "mesa LIKE ?";
                        $params[] = "%" . $_GET['filtro_mesa'] . "%";
                    }
                    if (!empty($_GET['filtro_cliente'])) {
                        $conditions[] = "cliente_nome LIKE ?";
                        $params[] = "%" . $_GET['filtro_cliente'] . "%";
                    }
                    if (count($conditions) > 0) {
                        $query .= " AND " . implode(" AND ", $conditions);
                    }
                    $query .= " ORDER BY data_hora DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    $comandas = $stmt->fetchAll();
                    if ($comandas) {
                        echo "<table border='1' cellpadding='5' cellspacing='0'>
                                <thead>
                                  <tr>
                                    <th>ID</th>
                                    <th>Mesa</th>
                                    <th>Cliente</th>
                                    <th>Status</th>
                                    <th>Data/Hora</th>
                                    <th>Ações</th>
                                  </tr>
                                </thead>
                                <tbody>";
                        foreach ($comandas as $c) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($c['id']) . "</td>
                                    <td>" . htmlspecialchars($c['mesa']) . "</td>
                                    <td>" . htmlspecialchars($c['cliente_nome']) . "</td>
                                    <td>" . htmlspecialchars($c['status']) . "</td>
                                    <td>" . htmlspecialchars($c['data_hora']) . "</td>
                                    <td><a href='docesebytes.php?page=comandas&subpage=edicao&id=" . htmlspecialchars($c['id']) . "'>Editar Comanda</a></td>
                                  </tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<p>Nenhuma comanda encontrada para edição.</p>";
                    }
                }
                echo "<br><button onclick='history.go(-1)'>Voltar</button>";
                break;

            case 'cancelamentos':
                echo "<h2>Cancelamento de Comandas</h2>";

                // Formulário de filtros
                echo "<h3>Filtrar Comandas Ativas</h3>
                      <form method='get' action='docesebytes.php'>
                        <input type='hidden' name='page' value='comandas'>
                        <input type='hidden' name='subpage' value='cancelamentos'>
                        <input type='hidden' name='view' value='active'>
                        <label>Status:</label>
                        <select name='status'>
                            <option value=''>-- Todos --</option>
                            <option value='aberto'>Aberto</option>
                            <option value='parcial'>Parcial</option>
                        </select>
                        <label>ID:</label>
                        <input type='text' name='id_comanda' placeholder='Digite o ID'>
                        <input type='submit' value='Filtrar'>
                      </form><br>";

                // Consulta comandas com status "aberto" ou "parcial"
                $query = "SELECT * FROM comandas WHERE status IN ('aberto','parcial')";
                $params = [];
                if (!empty($_GET['status'])) {
                    $query .= " AND status = ?";
                    $params[] = trim($_GET['status']);
                }
                if (!empty($_GET['id_comanda'])) {
                    $query .= " AND id LIKE ?";
                    $params[] = "%" . trim($_GET['id_comanda']) . "%";
                }
                $query .= " ORDER BY data_hora DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $comandas = $stmt->fetchAll();

                if ($comandas) {
                    echo "<table border='1' cellpadding='5' cellspacing='0'>
                            <thead>
                              <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Mesa</th>
                                <th>Cliente</th>
                                <th>Status</th>
                                <th>Data/Hora</th>
                                <th>Atendente</th>
                                <th>Total</th>
                                <th>Ações</th>
                              </tr>
                            </thead>
                            <tbody>";
                    foreach ($comandas as $c) {
                        $itens = json_decode($c['itens'], true);
                        $total = 0;
                        if (is_array($itens)) {
                            foreach ($itens as $item) {
                                $qtd = $item['quantidade'] ?? 0;
                                $valor = $item['valor'] ?? 0;
                                $total += $qtd * $valor;
                            }
                        }
                        echo "<tr>
                                <td>" . htmlspecialchars($c['id']) . "</td>
                                <td>" . htmlspecialchars($c['codigo_unico']) . "</td>
                                <td>" . htmlspecialchars($c['mesa']) . "</td>
                                <td>" . htmlspecialchars($c['cliente_nome']) . "</td>
                                <td>" . htmlspecialchars($c['status']) . "</td>
                                <td>" . htmlspecialchars($c['data_hora']) . "</td>
                                <td>" . htmlspecialchars($c['atendente']) . "</td>
                                <td>R$ " . number_format($total, 2, ',', '.') . "</td>
                                <td>";
                        if ($total == 0 && in_array($c['status'], ['aberto','parcial'])) {
                            echo "<a href='docesebytes.php?page=comandas&subpage=cancelamentos&view=active&cancel=1&id=" . htmlspecialchars($c['id']) . "'>Cancelar</a>";
                        } else {
                            echo "<span style='color:gray;'>Não pode cancelar</span>";
                        }
                        echo "</td></tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>Nenhuma comanda ativa encontrada para cancelamento.</p>";
                }

                // Exibe formulário de justificativa
                if (isset($_GET['cancel'], $_GET['id']) && $_GET['cancel'] == '1') {
                    $id_cancel = intval($_GET['id']);
                    echo "<h3>Cancelar Comanda #$id_cancel</h3>
                          <form method='post' action='docesebytes.php' onsubmit='return validaJustificativa()'>
                            <input type='hidden' name='action' value='cancel_comanda'>
                            <input type='hidden' name='id' value='$id_cancel'>
                            <label>Justificativa:</label><br>
                            <textarea name='justificativa' id='justificativa' required></textarea><br>
                            <input type='submit' value='Confirmar Cancelamento'>
                          </form>
                          <br><button onclick='history.go(-1)'>Voltar</button>";
                }

                echo "<br><button onclick='history.go(-1)'>Voltar</button>";
                echo "<script>
                        function validaJustificativa() {
                            var just = document.getElementById('justificativa').value;
                            if(just.trim() === '') {
                                alert('Justificativa é obrigatória para cancelamento.');
                                return false;
                            }
                            return true;
                        }
                      </script>";
                break;

            case 'relatorio':
                echo "<h3>Relatório de Comandas</h3>";

                // Formulário de filtros
                echo "<form method='get' action='docesebytes.php'>
                        <input type='hidden' name='page' value='comandas'>
                        <input type='hidden' name='subpage' value='relatorio'>
                        <label>Status:</label>
                        <select name='status'>
                            <option value=''>-- Todos --</option>
                            <option value='aberto'" . (isset($_GET['status']) && $_GET['status'] === 'aberto' ? ' selected' : '') . ">Aberto</option>
                            <option value='parcial'" . (isset($_GET['status']) && $_GET['status'] === 'parcial' ? ' selected' : '') . ">Parcial</option>
                            <option value='fechado'" . (isset($_GET['status']) && $_GET['status'] === 'fechado' ? ' selected' : '') . ">Fechado</option>
                            <option value='cancelado'" . (isset($_GET['status']) && $_GET['status'] === 'cancelado' ? ' selected' : '') . ">Cancelado</option>
                        </select>
                        <label>ID:</label>
                        <input type='text' name='id_comanda' placeholder='Digite o ID' value='" . htmlspecialchars($_GET['id_comanda'] ?? '') . "'>
                        <input type='submit' value='Filtrar'>
                      </form><br>";

                $dadosFiltrados = isset($_GET['status']) || isset($_GET['id_comanda']);

                if ($dadosFiltrados) {
                    $query = "SELECT * FROM comandas WHERE 1";
                    $conditions = [];
                    $params = [];

                    if (isset($_GET['status']) && trim($_GET['status']) !== "") {
                        $conditions[] = "status = ?";
                        $params[] = trim($_GET['status']);
                    }
                    if (isset($_GET['id_comanda']) && trim($_GET['id_comanda']) !== "") {
                        $conditions[] = "id LIKE ?";
                        $params[] = "%" . trim($_GET['id_comanda']) . "%";
                    }

                    if (count($conditions) > 0) {
                        $query .= " AND " . implode(" AND ", $conditions);
                    }

                    $query .= " ORDER BY data_hora DESC";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    $comandas = $stmt->fetchAll();

                    if ($comandas) {
                        echo "<table border='1' cellpadding='5' cellspacing='0'>
                                <thead>
                                  <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Mesa</th>
                                    <th>Cliente</th>
                                    <th>Status</th>
                                    <th>Data/Hora</th>
                                    <th>Atendente</th>
                                  </tr>
                                </thead>
                                <tbody>";
                        foreach ($comandas as $c) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($c['id']) . "</td>
                                    <td>" . htmlspecialchars($c['codigo_unico']) . "</td>
                                    <td>" . htmlspecialchars($c['mesa']) . "</td>
                                    <td>" . htmlspecialchars($c['cliente_nome']) . "</td>
                                    <td>" . htmlspecialchars($c['status']) . "</td>
                                    <td>" . htmlspecialchars($c['data_hora']) . "</td>
                                    <td>" . htmlspecialchars($c['atendente']) . "</td>
                                  </tr>";
                        }
                        echo "</tbody></table>";

                        // Botão para imprimir o relatório
                        echo "<br><button onclick='window.print()'>Imprimir Relatório</button>";
                    } else {
                        echo "<p>Nenhuma comanda encontrada com os filtros informados.</p>";
                    }
                } else {
                    echo "<p>Use os filtros acima para gerar o relatório de comandas.</p>";
                }

                echo "<br><button onclick='history.go(-1)'>Voltar</button>";

                // Estilo para impressão
                echo "<style media='print'>
                        body * {
                            visibility: hidden;
                        }
                        table, table * {
                            visibility: visible;
                        }
                        table {
                            position: absolute;
                            top: 0;
                            left: 0;
                        }
                      </style>";
                break;
        } // Fim do switch($subpage) para comandas
        break; // Fim do case 'comandas'

case 'produtos':
    echo "<h2>Gerenciamento de Produtos</h2>";
    $subpage = $_GET['subpage'] ?? null;
    $action = $_POST['action'] ?? $_GET['action'] ?? null;

    // Submenu com estilo
    echo "<style>
    .submenu-container { display: flex; width: 100%; margin-bottom: 20px; gap: 5px; }
    .submenu-container button { flex: 1; background-color: #007BFF; color: #fff; border: none; border-radius: 5px; padding: 8px 10px; font-weight: bold; cursor: pointer; font-size: 16px; }
    .submenu-container button:hover { background-color: #0056b3; }
    </style>";

    echo "<div class='submenu-container'>
            <a href='docesebytes.php?page=produtos&subpage=cadastrar'><button>Cadastrar Produtos</button></a>
            <a href='docesebytes.php?page=produtos&subpage=listar'><button>Listar Produtos</button></a>
            <a href='docesebytes.php?page=produtos&subpage=relatorio'><button>Relatório de Produtos</button></a>
          </div>";

    // --- Ações de POST ou GET (update e delete) ---
    if ($action === 'update_produto') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE produtos SET nome=?, descricao=?, ingredientes=?, preco=?, categoria=?, status=?, data_alteracao=NOW() WHERE id=?");
        $stmt->execute([
            $_POST['nome'], $_POST['descricao'], $_POST['ingredientes'], $_POST['preco'],
            $_POST['categoria'], $_POST['status'], $id
        ]);
        echo "<p>Produto atualizado com sucesso!</p>";
        echo "<br><button onclick='history.go(-2)'>Voltar</button>";
        break;
    }

    if ($action === 'excluir_produto') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            echo "<p>Produto excluído com sucesso!</p>";
        } else {
            echo "<p>ID inválido para exclusão.</p>";
        }
        echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        break;
    }

    // --- Subpáginas ---
    if (!$subpage) {
        echo "<p>Por favor, selecione uma das opções acima.</p>";
    } else {
        switch ($subpage) {
            case 'cadastrar':
                echo "<h3>Cadastrar Produto</h3>
                      <form method='post' action=''>
                        <input type='hidden' name='action' value='add_produto'>
                        <label>Nome do Produto:</label>
                        <input type='text' name='nome' required>
                        <label>Descrição:</label>
                        <textarea name='descricao' required></textarea>
                        <label>Ingredientes:</label>
                        <textarea name='ingredientes' required></textarea>
                        <label>Preço de Venda:</label>
                        <input type='number' step='0.01' name='preco' required>
                        <label>Categoria:</label>
                        <select name='categoria' required>
                            <option value='doces'>Doces</option>
                            <option value='bebidas'>Bebidas</option>
                            <option value='bolos'>Bolos</option>
                            <option value='paes'>Pães</option>
                        </select>
                        <label>Status:</label>
                        <select name='status' required>
                            <option value='ativo'>Ativo</option>
                            <option value='inativo'>Inativo</option>
                        </select>
                        <input type='submit' value='Cadastrar Produto'>
                      </form>
                      <br><button onclick='history.go(-1)'>Voltar</button>";
                break;

            case 'listar':
                echo "<h3>Listar Produtos</h3>
                      <form method='get' action='docesebytes.php'>
                        <input type='hidden' name='page' value='produtos'>
                        <input type='hidden' name='subpage' value='listar'>
                        <label>Buscar por nome:</label>
                        <input type='text' name='buscar_nome' value='" . htmlspecialchars($_GET['buscar_nome'] ?? '') . "'>
                        <input type='submit' value='Buscar'>
                      </form>";

                $buscar_nome = trim($_GET['buscar_nome'] ?? '');
                $stmt = $buscar_nome
                    ? $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ? ORDER BY id ASC")
                    : $pdo->query("SELECT * FROM produtos ORDER BY id ASC");

                if ($buscar_nome) $stmt->execute(["%$buscar_nome%"]);
                $produtos = $stmt->fetchAll();

                if ($produtos) {
                    echo "<table border='1' cellpadding='5' cellspacing='0'>
                            <thead><tr>
                                <th>Nº</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Status</th><th>Última Alteração</th><th>Ações</th>
                            </tr></thead><tbody>";
                    foreach ($produtos as $i => $p) {
                        echo "<tr>
                                <td>" . ($i+1) . "</td>
                                <td>" . htmlspecialchars($p['nome']) . "</td>
                                <td>" . htmlspecialchars($p['categoria']) . "</td>
                                <td>R$ " . number_format($p['preco'], 2, ',', '.') . "</td>
                                <td>" . htmlspecialchars($p['status']) . "</td>
                                <td>" . htmlspecialchars($p['data_alteracao']) . "</td>
                                <td>
                                    <a href='docesebytes.php?page=produtos&subpage=editar&id=" . $p['id'] . "'>Editar</a> |
                                    <a href='docesebytes.php?page=produtos&action=excluir_produto&id=" . $p['id'] . "' onclick=\"return confirm('Tem certeza que deseja excluir este produto?');\">Excluir</a>
                                </td>
                              </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>Nenhum produto encontrado.</p>";
                }
                echo "<br><button onclick='history.go(-1)'>Voltar</button>";
                break;

            case 'editar':
                $id = $_GET['id'] ?? null;
                if ($id) {
                    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
                    $stmt->execute([$id]);
                    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($produto) {
                        echo "<h2>Editar Produto</h2>
                              <form method='post' action=''>
                                <input type='hidden' name='action' value='update_produto'>
                                <input type='hidden' name='id' value='" . $produto['id'] . "'>
                                <label>Nome do Produto:</label>
                                <input type='text' name='nome' value='" . htmlspecialchars($produto['nome']) . "' required>
                                <label>Descrição:</label>
                                <textarea name='descricao' required>" . htmlspecialchars($produto['descricao']) . "</textarea>
                                <label>Ingredientes:</label>
                                <textarea name='ingredientes' required>" . htmlspecialchars($produto['ingredientes']) . "</textarea>
                                <label>Preço de Venda:</label>
                                <input type='number' step='0.01' name='preco' value='" . $produto['preco'] . "' required>
                                <label>Categoria:</label>
                                <select name='categoria' required>
                                    <option value='doces'" . ($produto['categoria'] == 'doces' ? ' selected' : '') . ">Doces</option>
                                    <option value='bebidas'" . ($produto['categoria'] == 'bebidas' ? ' selected' : '') . ">Bebidas</option>
                                    <option value='bolos'" . ($produto['categoria'] == 'bolos' ? ' selected' : '') . ">Bolos</option>
                                    <option value='paes'" . ($produto['categoria'] == 'paes' ? ' selected' : '') . ">Pães</option>
                                </select>
                                <label>Status:</label>
                                <select name='status' required>
                                    <option value='ativo'" . ($produto['status'] == 'ativo' ? ' selected' : '') . ">Ativo</option>
                                    <option value='inativo'" . ($produto['status'] == 'inativo' ? ' selected' : '') . ">Inativo</option>
                                </select>
                                <input type='submit' value='Salvar Alterações'>
                              </form>
                              <br><button onclick='history.go(-1)'>Voltar</button>";
                    } else {
                        echo "<p>Produto não encontrado.</p><br><button onclick='history.go(-1)'>Voltar</button>";
                    }
                } else {
                    echo "<p>ID inválido.</p><br><button onclick='history.go(-1)'>Voltar</button>";
                }
                break;

            case 'relatorio':
                echo "<h3>Relatório de Produtos</h3>
                      <form method='get' action='docesebytes.php'>
                        <input type='hidden' name='page' value='produtos'>
                        <input type='hidden' name='subpage' value='relatorio'>
                        <label>Categoria:</label>
                        <select name='categoria'>
                            <option value=''>Todas</option>
                            <option value='doces'>Doces</option>
                            <option value='bolos'>Bolos</option>
                            <option value='bebidas'>Bebidas</option>
                            <option value='paes'>Pães</option>
                        </select>
                        <label>Status:</label>
                        <select name='status'>
                            <option value=''>Todos</option>
                            <option value='ativo'>Ativo</option>
                            <option value='inativo'>Inativo</option>
                        </select>
                        <input type='submit' value='Gerar Relatório'>
                      </form><br>";

                $categoria = trim($_GET['categoria'] ?? '');
                $status = trim($_GET['status'] ?? '');
                if ($categoria || $status) {
                    $sql = "SELECT * FROM produtos WHERE 1";
                    $params = [];
                    if ($categoria) {
                        $sql .= " AND categoria = ?";
                        $params[] = $categoria;
                    }
                    if ($status) {
                        $sql .= " AND status = ?";
                        $params[] = $status;
                    }
                    $sql .= " ORDER BY id ASC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($produtos) {
                        echo "<table border='1' cellpadding='5' cellspacing='0'>
                                <thead><tr>
                                    <th>Nº</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Status</th><th>Última Alteração</th>
                                </tr></thead><tbody>";
                        foreach ($produtos as $i => $p) {
                            echo "<tr>
                                    <td>" . ($i+1) . "</td>
                                    <td>" . htmlspecialchars($p['nome']) . "</td>
                                    <td>" . htmlspecialchars($p['categoria']) . "</td>
                                    <td>R$ " . number_format($p['preco'], 2, ',', '.') . "</td>
                                    <td>" . htmlspecialchars($p['status']) . "</td>
                                    <td>" . htmlspecialchars($p['data_alteracao']) . "</td>
                                  </tr>";
                        }
                        echo "</tbody></table><br><button onclick='window.print()'>Imprimir Relatório</button>";
                    } else {
                        echo "<p>Nenhum produto encontrado com os filtros selecionados.</p>";
                    }
                } else {
                    echo "<p>Use os filtros acima para gerar o relatório.</p>";
                }
                echo "<br><button onclick='history.go(-1)'>Voltar</button>";
                break;

            default:
                echo "<p>Subpágina inválida.</p><br><button onclick='history.go(-1)'>Voltar</button>";
        } // fim do switch subpage
    }
    break; // fim do case 'produtos'


    case 'clientes':
        echo "<h2>Gerenciamento de Clientes</h2>";
        $subpage = isset($_GET['subpage']) ? $_GET['subpage'] : "cadastrar";
        echo "<div class='submenu-container' style='margin-bottom:20px; padding:10px; border:1px solid #ccc; background-color:#f9f9f9;'>
                <a href='docesebytes.php?page=clientes&subpage=cadastrar'><button style='margin-right:10px;'>Cadastrar Cliente</button></a>
                <a href='docesebytes.php?page=clientes&subpage=listar'><button style='margin-right:10px;'>Listar Clientes</button></a>
                <a href='docesebytes.php?page=clientes&subpage=relatorio'><button>Relatório de Clientes</button></a>
              </div>";
        if ($subpage == "cadastrar") {
            if ($_SESSION['user']['nivel_acesso_id'] != 1) {
                echo "<p class='message'>Você não tem permissão para cadastrar clientes.</p>";
            } else {
                echo "<h3>Cadastrar Cliente</h3>
                      <form method='post' action=''>
                        <input type='hidden' name='action' value='add_cliente'>
                        <label>Nome Completo:</label>
                        <input type='text' name='nome' required><br>
                        <label>CPF:</label>
                        <input type='text' name='cpf' required><br>
                        <label>Data de Nascimento:</label>
                        <input type='date' name='data_nascimento' required><br>
                        <label>Endereço:</label>
                        <input type='text' name='endereco' required><br>
                        <div style='display: flex; gap: 10px; align-items: center;'>
                            <div>
                                <label>Telefone - DDD:</label>
                                <input type='text' name='ddd_telefone' pattern='\d{2}' title='Digite exatamente 2 dígitos' style='width:50px;' required>
                            </div>
                            <div>
                                <label>Telefone:</label>
                                <input type='text' name='telefone' pattern='\d{8}' title='Digite exatamente 8 dígitos' style='width:100px;' required>
                            </div>
                        </div>
                        <div style='display: flex; gap: 10px; align-items: center; margin-top:5px;'>
                            <div>
                                <label>WhatsApp - DDD:</label>
                                <input type='text' name='ddd_whatsapp' pattern='\d{2}' title='Digite exatamente 2 dígitos' style='width:50px;' required>
                            </div>
                            <div>
                                <label>WhatsApp:</label>
                                <input type='text' name='whatsapp' pattern='\d{9}' title='Digite exatamente 9 dígitos' style='width:110px;' required>
                            </div>
                        </div>
                        <br>
                        <label>E-mail:</label>
                        <input type='email' name='email' pattern='.+@.+\..+' title='Digite um email válido com @' required><br>
                        <input type='submit' value='Cadastrar Cliente'>
                      </form>";
            }
            echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        } elseif ($subpage == "listar") {
            echo "<h3>Lista de Clientes</h3>
                  <form method='get' action='docesebytes.php'>
                    <input type='hidden' name='page' value='clientes'>
                    <input type='hidden' name='subpage' value='listar'>
                    <label>Nome:</label>
                    <input type='text' name='filtro_nome' value='" . (isset($_GET['filtro_nome']) ? htmlspecialchars($_GET['filtro_nome']) : "") . "'>
                    <label>CPF:</label>
                    <input type='text' name='filtro_cpf' value='" . (isset($_GET['filtro_cpf']) ? htmlspecialchars($_GET['filtro_cpf']) : "") . "'>
                    <input type='submit' value='Filtrar'>
                  </form>";
            $sql = "SELECT * FROM cliente WHERE 1";
            $params = [];
            if (!empty($_GET['filtro_nome'])) {
                $sql .= " AND nome LIKE ?";
                $params[] = "%" . trim($_GET['filtro_nome']) . "%";
            }
            if (!empty($_GET['filtro_cpf'])) {
                $sql .= " AND cpf LIKE ?";
                $params[] = "%" . trim($_GET['filtro_cpf']) . "%";
            }
            $sql .= " ORDER BY id ASC";
            if (count($params) > 0) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($sql);
            }
            $clientes = $stmt->fetchAll();
            if ($clientes) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>
                        <thead>
                          <tr>
                            <th>Nº</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Data de Nascimento</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>WhatsApp</th>
                            <th>E-mail</th>
                            <th>Ações</th>
                          </tr>
                        </thead>
                        <tbody>";
                $i = 1;
                foreach ($clientes as $c) {
                    echo "<tr>
                            <td>" . $i++ . "</td>
                            <td>" . htmlspecialchars($c['nome']) . "</td>
                            <td>" . htmlspecialchars($c['cpf']) . "</td>
                            <td>" . htmlspecialchars($c['data_nascimento']) . "</td>
                            <td>" . htmlspecialchars($c['endereco']) . "</td>
                            <td>" . htmlspecialchars($c['telefone']) . "</td>
                            <td>" . htmlspecialchars($c['whatsapp']) . "</td>
                            <td>" . htmlspecialchars($c['email']) . "</td>
                            <td>
                              <a href='docesebytes.php?page=clientes_consultar&id=" . htmlspecialchars($c['id']) . "'>Consultar</a> | 
                              <a href='docesebytes.php?page=clientes_edit&id=" . htmlspecialchars($c['id']) . "'>Editar</a>";
                    if ($_SESSION['user']['nivel_acesso_id'] == 1) {
                        echo " | <a href='docesebytes.php?action=excluir_cliente&id=" . htmlspecialchars($c['id']) . "' onclick=\"return confirm('Tem certeza que deseja excluir este cliente?');\">Excluir</a>";
                    }
                    echo "   </td>
                          </tr>";
                }
                echo "</tbody></table>";
                echo "<br><form method='get' action='docesebytes.php'>
                        <input type='hidden' name='page' value='clientes'>
                        <input type='hidden' name='subpage' value='relatorio'>
                        <input type='hidden' name='filtro_nome' value='" . (isset($_GET['filtro_nome']) ? htmlspecialchars($_GET['filtro_nome']) : "") . "'>
                        <input type='hidden' name='filtro_cpf' value='" . (isset($_GET['filtro_cpf']) ? htmlspecialchars($_GET['filtro_cpf']) : "") . "'>
                        <input type='submit' value='Gerar Relatório e Imprimir'>
                      </form>";
            } else {
                echo "<p>Nenhum cliente encontrado.</p>";
            }
            echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        } elseif ($subpage == "relatorio") {
            echo "<h3>Relatório de Clientes</h3>";
            $sql = "SELECT * FROM cliente WHERE 1";
            $params = [];
            if (!empty($_GET['filtro_nome'])) {
                $sql .= " AND nome LIKE ?";
                $params[] = "%" . trim($_GET['filtro_nome']) . "%";
            }
            if (!empty($_GET['filtro_cpf'])) {
                $sql .= " AND cpf LIKE ?";
                $params[] = "%" . trim($_GET['filtro_cpf']) . "%";
            }
            $sql .= " ORDER BY id ASC";
            if (count($params) > 0) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $pdo->query($sql);
            }
            $clientes = $stmt->fetchAll();
            if ($clientes) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>
                        <thead>
                          <tr>
                            <th>Nº</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Data de Nascimento</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>WhatsApp</th>
                            <th>E-mail</th>
                          </tr>
                        </thead>
                        <tbody>";
                $i = 1;
                foreach ($clientes as $c) {
                    echo "<tr>
                            <td>" . $i++ . "</td>
                            <td>" . htmlspecialchars($c['nome']) . "</td>
                            <td>" . htmlspecialchars($c['cpf']) . "</td>
                            <td>" . htmlspecialchars($c['data_nascimento']) . "</td>
                            <td>" . htmlspecialchars($c['endereco']) . "</td>
                            <td>" . htmlspecialchars($c['telefone']) . "</td>
                            <td>" . htmlspecialchars($c['whatsapp']) . "</td>
                            <td>" . htmlspecialchars($c['email']) . "</td>
                          </tr>";
                }
                echo "</tbody></table>";
                echo "<br><button onclick='window.print();'>Imprimir Relatório</button>";
            } else {
                echo "<p>Nenhum cliente encontrado.</p>";
            }
            echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        }
        break;

    case 'clientes_edit':
        if (!isset($_GET['id'])) {
            echo "<p class='message'>ID do cliente não informado.</p><br><button onclick='history.go(-1)'>Voltar</button>";
            break;
        }
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            echo "<p class='message'>Cliente não encontrado.</p><br><button onclick='history.go(-1)'>Voltar</button>";
            break;
        }
        echo "<h2>Editar Cliente</h2>
              <form method='post' action=''>
                <input type='hidden' name='action' value='edit_cliente'>
                <input type='hidden' name='id' value='" . htmlspecialchars($cliente['id']) . "'>
                <label>Nome Completo:</label>
                <input type='text' name='nome' value='" . htmlspecialchars($cliente['nome']) . "' required><br>
                <label>CPF:</label>
                <input type='text' name='cpf' value='" . htmlspecialchars($cliente['cpf']) . "' required><br>
                <label>Data de Nascimento:</label>
                <input type='date' name='data_nascimento' value='" . htmlspecialchars($cliente['data_nascimento']) . "' required><br>
                <label>Endereço:</label>
                <input type='text' name='endereco' value='" . htmlspecialchars($cliente['endereco']) . "' required><br>
                <div style='display: flex; gap: 10px; align-items: center;'>
                  <div>
                    <label>Telefone - DDD:</label>
                    <input type='text' name='ddd_telefone' pattern='\d{2}' title='Digite exatamente 2 dígitos' style='width:50px;' value='" . substr($cliente['telefone'], 0, 2) . "' required>
                  </div>
                  <div>
                    <label>Telefone:</label>
                    <input type='text' name='telefone' pattern='\d{8}' title='Digite exatamente 8 dígitos' style='width:100px;' value='" . substr($cliente['telefone'], 2) . "' required>
                  </div>
                </div>
                <div style='display: flex; gap: 10px; align-items: center; margin-top:5px;'>
                  <div>
                    <label>WhatsApp - DDD:</label>
                    <input type='text' name='ddd_whatsapp' pattern='\d{2}' title='Digite exatamente 2 dígitos' style='width:50px;' value='" . substr($cliente['whatsapp'], 0, 2) . "' required>
                  </div>
                  <div>
                    <label>WhatsApp:</label>
                    <input type='text' name='whatsapp' pattern='\d{9}' title='Digite exatamente 9 dígitos' style='width:110px;' value='" . substr($cliente['whatsapp'], 2) . "' required>
                  </div>
                </div>
                <br>
                <label>E-mail:</label>
                <input type='email' name='email' pattern='.+@.+\..+' title='Digite um email válido com @' value='" . htmlspecialchars($cliente['email']) . "' required><br>
                <input type='submit' value='Atualizar Cliente'>
              </form>
              <br><button onclick='history.go(-1)'>Voltar</button>";
        break;

    case 'clientes_consultar':
        if (!isset($_GET['id'])) {
            echo "<p class='message'>ID do cliente não informado.</p><br><button onclick='history.go(-1)'>Voltar</button>";
            break;
        }
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            echo "<p class='message'>Cliente não encontrado.</p><br><button onclick='history.go(-1)'>Voltar</button>";
            break;
        }
        echo "<h2>Consultar Cliente</h2>
              <p><strong>Nome:</strong> " . htmlspecialchars($cliente['nome']) . "</p>
              <p><strong>CPF:</strong> " . htmlspecialchars($cliente['cpf']) . "</p>
              <p><strong>Data de Nascimento:</strong> " . htmlspecialchars($cliente['data_nascimento']) . "</p>
              <p><strong>Endereço:</strong> " . htmlspecialchars($cliente['endereco']) . "</p>
              <p><strong>Telefone:</strong> " . htmlspecialchars($cliente['telefone']) . "</p>
              <p><strong>WhatsApp:</strong> " . htmlspecialchars($cliente['whatsapp']) . "</p>
              <p><strong>E-mail:</strong> " . htmlspecialchars($cliente['email']) . "</p>
              <br><button onclick='history.go(-1)'>Voltar</button>";
        break;

    case 'pagamentos':
        echo "<h2>Localizar Comanda para Pagamento</h2>";
        echo "<form method='get' action='docesebytes.php'>
            <input type='hidden' name='page' value='pagamentos'>
            <label>ID da Comanda:</label>
            <input type='text' name='filtro_id' placeholder='Digite o ID'>
            <label>Nome do Cliente:</label>
            <input type='text' name='filtro_nome' placeholder='Digite o nome'>
            <label>Mesa:</label>
            <input type='text' name='filtro_mesa' placeholder='Digite a mesa'>
            <input type='submit' value='Localizar'>
          </form>";
        $filtro_id = isset($_GET['filtro_id']) ? trim($_GET['filtro_id']) : "";
        $filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : "";
        $filtro_mesa = isset($_GET['filtro_mesa']) ? trim($_GET['filtro_mesa']) : "";
        if ($filtro_id != "" || $filtro_nome != "" || $filtro_mesa != "") {
            $query = "SELECT * FROM comandas WHERE status IN ('aberto','parcial')";
            $conditions = [];
            $params = [];
            if ($filtro_id != "") {
                $conditions[] = "id LIKE ?";
                $params[] = "%" . $filtro_id . "%";
            }
            if ($filtro_nome != "") {
                $conditions[] = "cliente_nome LIKE ?";
                $params[] = "%" . $filtro_nome . "%";
            }
            if ($filtro_mesa != "") {
                $conditions[] = "mesa LIKE ?";
                $params[] = "%" . $filtro_mesa . "%";
            }
            if (count($conditions) > 0) {
                $query .= " AND " . implode(" AND ", $conditions);
            }
            $query .= " ORDER BY data_hora DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $comandas = $stmt->fetchAll();
            if ($comandas) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>
                    <thead>
                      <tr>
                        <th>ID da Comanda</th>
                        <th>Mesa</th>
                        <th>Cliente</th>
                        <th>Data/Hora</th>
                        <th>Ações</th>
                      </tr>
                    </thead>
                    <tbody>";
                foreach ($comandas as $c) {
                    echo "<tr>
                        <td>" . htmlspecialchars($c['id']) . "</td>
                        <td>" . htmlspecialchars($c['mesa']) . "</td>
                        <td>" . htmlspecialchars($c['cliente_nome']) . "</td>
                        <td>" . htmlspecialchars($c['data_hora']) . "</td>
                        <td>
                          <a href='docesebytes.php?page=pagamento_detalhes&id=" . htmlspecialchars($c['id']) . "'>Visualizar Comanda</a>
                        </td>
                      </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p>Nenhuma comanda encontrada.</p>";
            }
        }
        echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        break;

    case 'pagamento_detalhes':
        if (!isset($_GET['id'])) {
            echo "<p class='error'>ID da comanda não informado.</p>";
            break;
        }
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM comandas WHERE id = ?");
        $stmt->execute([$id]);
        $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comanda) {
            echo "<p class='error'>Comanda não encontrada.</p>";
            break;
        }
        echo "<div id='comanda_detalhes' style='max-width:600px; margin:0 auto;'>
            <h2>Detalhes da Comanda #" . htmlspecialchars($comanda['id']) . "</h2>
            <p><strong>Mesa:</strong> " . htmlspecialchars($comanda['mesa']) . "</p>
            <p><strong>Cliente:</strong> " . htmlspecialchars($comanda['cliente_nome']) . "</p>
            <p><strong>Data/Hora:</strong> " . htmlspecialchars($comanda['data_hora']) . "</p>
            <h3>Itens do Pedido</h3>";
        $itens = json_decode($comanda['itens'], true);
        $valor_total = 0;
        if ($itens && is_array($itens) && count($itens) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%;'>
                <thead>
                  <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>";
            foreach ($itens as $item) {
                $qtd = isset($item['quantidade']) ? $item['quantidade'] : 0;
                $valor = isset($item['valor']) ? $item['valor'] : 0;
                $subtotal = $qtd * $valor;
                $valor_total += $subtotal;
                echo "<tr>
                    <td>" . htmlspecialchars($item['produto']) . "</td>
                    <td>" . number_format($qtd, 2, ',', '.') . "</td>
                    <td>R$ " . number_format($valor, 2, ',', '.') . "</td>
                    <td>R$ " . number_format($subtotal, 2, ',', '.') . "</td>
                  </tr>";
            }
            echo "</tbody>
              <tfoot>
                <tr>
                  <td colspan='3' style='text-align:right;'><strong>Total:</strong></td>
                  <td><strong>R$ " . number_format($valor_total, 2, ',', '.') . "</strong></td>
                </tr>
              </tfoot>
             </table>";
        } else {
            echo "<p>Nenhum item encontrado nesta comanda.</p>";
        }
        echo "</div>";
        echo "<br><button onclick='window.print()'>Imprimir Comanda</button>";
        echo "<br><br>
          <form method='get' action='docesebytes.php'>
            <input type='hidden' name='page' value='pagamento_encerrar'>
            <input type='hidden' name='id' value='" . $id . "'>
            <button type='submit'>Encerrar Comanda</button>
          </form>";
        echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        break;

    case 'pagamento_encerrar':
        if (!isset($_GET['id'])) {
            echo "<p class='error'>ID da comanda não informado.</p>";
            break;
        }
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM comandas WHERE id = ?");
        $stmt->execute([$id]);
        $comanda = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comanda) {
            echo "<p class='error'>Comanda não encontrada.</p>";
            break;
        }
        echo "<h2>Encerrar Comanda #" . htmlspecialchars($comanda['id']) . "</h2>";
        echo "<p><strong>Mesa:</strong> " . htmlspecialchars($comanda['mesa']) . "</p>";
        echo "<p><strong>Cliente:</strong> " . htmlspecialchars($comanda['cliente_nome']) . "</p>";
        $itens = json_decode($comanda['itens'], true);
        $valor_total = 0;
        if ($itens && is_array($itens)) {
            foreach ($itens as $item) {
                $qtd = isset($item['quantidade']) ? $item['quantidade'] : 0;
                $valor = isset($item['valor']) ? $item['valor'] : 0;
                $valor_total += ($qtd * $valor);
            }
        }
        echo "<p><strong>Total da Comanda:</strong> R$ " . number_format($valor_total, 2, ',', '.') . "</p>";
        echo "<h3>Processar Pagamento</h3>";
        echo "<form method='post' action='docesebytes.php'>
            <input type='hidden' name='action' value='processa_pagamento'>
            <input type='hidden' name='id_comanda' value='" . $id . "'>
            <label>Valor Recebido:</label><br>
            <input type='number' step='0.01' name='valor' required><br><br>
            <label>Métodos de Pagamento (selecione um ou mais):</label><br>
            <select name='metodos[]' multiple required>
                <option value='dinheiro'>Dinheiro</option>
                <option value='cartao_credito'>Cartão de Crédito</option>
                <option value='cartao_debito'>Cartão de Débito</option>
                <option value='pix'>PIX</option>
            </select><br><br>
            <input type='submit' value='Processar Pagamento'>
          </form>";
        echo "<br><button onclick='history.go(-1)'>Voltar</button>";
        break;

case 'relatorio_caixa':
    echo "<h2 style='margin-left: 20px;'>Relatório de Fechamento de Caixa</h2>";
    echo "<hr>";

    // Filtro
    echo "<form method='GET' style='margin: 20px;'>
        <input type='hidden' name='page' value='relatorios'>
        <input type='hidden' name='subpage' value='relatorio_caixa'>
        <label>Data Início:</label>
        <input type='date' name='data_inicio' value='" . ($_GET['data_inicio'] ?? '') . "' required>
        <label>Data Fim:</label>
        <input type='date' name='data_fim' value='" . ($_GET['data_fim'] ?? '') . "' required>
        <label>Tipo de Pagamento:</label>
        <select name='tipo_pagamento'>
            <option value=''>Todos</option>
            <option value='dinheiro'" . (($_GET['tipo_pagamento'] ?? '') == 'dinheiro' ? ' selected' : '') . ">Dinheiro</option>
            <option value='pix'" . (($_GET['tipo_pagamento'] ?? '') == 'pix' ? ' selected' : '') . ">Pix</option>
            <option value='cartao_credito'" . (($_GET['tipo_pagamento'] ?? '') == 'cartao_credito' ? ' selected' : '') . ">Cartão de Crédito</option>
            <option value='cartao_debito'" . (($_GET['tipo_pagamento'] ?? '') == 'cartao_debito' ? ' selected' : '') . ">Cartão de Débito</option>
        </select>
        <button type='submit'>Filtrar</button>
        <button onclick='window.print(); return false;'>Imprimir</button>
    </form>";

    // Processar dados
    if (isset($_GET['data_inicio'], $_GET['data_fim'])) {
        $data_inicio = $_GET['data_inicio'] . " 00:00:00";
        $data_fim = $_GET['data_fim'] . " 23:59:59";
        $tipo_pagamento = $_GET['tipo_pagamento'] ?? '';

        $stmt = $pdo->prepare("SELECT pagamento FROM comandas WHERE data_hora BETWEEN ? AND ? AND pagamento IS NOT NULL");
        $stmt->execute([$data_inicio, $data_fim]);
        $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totais = [
            'dinheiro' => 0,
            'pix' => 0,
            'cartao_credito' => 0,
            'cartao_debito' => 0
        ];

        foreach ($pagamentos as $linha) {
            $dados = json_decode($linha['pagamento'], true);
            if (!isset($dados['historico']) || !is_array($dados['historico'])) continue;

            foreach ($dados['historico'] as $pag) {
                foreach ($pag['metodos'] as $metodo) {
                    if (isset($totais[$metodo])) {
                        $totais[$metodo] += floatval($pag['valor']);
                    }
                }
            }
        }

        echo "<div style='margin: 20px;'>";
        echo "<h3>Totais de Vendas:</h3>";
        echo "<ul>";
        foreach ($totais as $metodo => $valor) {
            if ($tipo_pagamento == '' || $tipo_pagamento == $metodo) {
                $label = ucwords(str_replace("_", " ", $metodo));
                echo "<li><strong>$label:</strong> R$ " . number_format($valor, 2, ',', '.') . "</li>";
            }
        }
        echo "</ul>";
        echo "<p><strong>Período:</strong> " . htmlspecialchars($_GET['data_inicio']) . " a " . htmlspecialchars($_GET['data_fim']) . "</p>";
        echo "</div>";
    } else {
        echo "<p style='margin: 20px;'>Selecione um período para visualizar o relatório.</p>";
    }
    break;


    default:
        echo "<p>Página não encontrada.</p>";
        break;
}

terminaPagina();
?>
