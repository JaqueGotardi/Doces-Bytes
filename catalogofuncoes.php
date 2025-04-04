<?php
// catalogofuncoes.php



function iniciaPagina($title = "Docesebytes") {
    echo "<!DOCTYPE html>
<html lang='pt'>
<head>
    <meta charset='UTF-8'>
    <title>$title</title>
    <link rel='stylesheet' href='estilo.css'>
</head>
<body>
<div class='container'>";
}

function terminaPagina() {
    echo "</div>
</body>
</html>";
}

if (!function_exists('getDailyCounter')) {
    function getDailyCounter($pdo) {
        $currentDate = date("Y-m-d");
        $stmt = $pdo->prepare("SELECT contador FROM contador_diario WHERE data = ?");
        $stmt->execute([$currentDate]);
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
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
}
if (!function_exists('getConnection')) {
    function getConnection() {
        $host = 'localhost';
        $dbname = 'docesebytes';
        $user = 'root';
        $pass = '';
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }
}

?>
