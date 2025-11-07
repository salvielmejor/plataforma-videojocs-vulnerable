<?php
session_start();
header('Content-Type: application/json');

// Requiere usuario logueado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No hay sesión iniciada"]);
    exit;
}

// Configuración de base de datos
$host = "localhost";
$dbname = "plataforma_videojocs";
$user = "plataforma_user";
$pass = "123456789a";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de connexió: " . $e->getMessage()]);
    exit;
}

// Comprobar si se ha recibido JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['nivell_actual'])) {
    echo json_encode(["success" => false, "message" => "No s'han rebut dades vàlides"]);
    exit;
}

$nivell_actual = intval($data['nivell_actual']);

// Obtener el ID del usuario desde la sesión (nombre de usuario)
$nomUsuari = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT id FROM usuaris WHERE nom_usuari = ?");
$stmt->execute([$nomUsuari]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Usuario de sesión no válido"]);
    exit;
}
$usuari_id = (int)$row['id'];

// Obtener el ID del juego (Fruit Ninja)
$stmt = $pdo->prepare("SELECT id FROM jocs WHERE nom_joc = 'Fruit Ninja'");
$stmt->execute();
$joc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$joc) {
    echo json_encode(["success" => false, "message" => "Juego no encontrado"]);
    exit;
}
$joc_id = $joc["id"];

try {
    // Verificar si existe progreso
    $stmt = $pdo->prepare("SELECT nivell_actual FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?");
    $stmt->execute([$usuari_id, $joc_id]);
    $progres = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($progres) {
        // Solo actualizar si el nuevo nivel es mayor al actual
        if ($nivell_actual > $progres['nivell_actual']) {
            $stmt = $pdo->prepare("UPDATE progres_usuari SET nivell_actual = ? WHERE usuari_id = ? AND joc_id = ?");
            $stmt->execute([$nivell_actual, $usuari_id, $joc_id]);
            echo json_encode([
                "success" => true,
                "message" => "Nivel actualizado correctamente",
                "nivell_anterior" => (int)$progres['nivell_actual'],
                "nivell_actual" => $nivell_actual
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "message" => "El nivel no necesita actualización",
                "nivell_actual" => (int)$progres['nivell_actual']
            ]);
        }
    } else {
        // Crear nuevo progreso si no existe
        $stmt = $pdo->prepare("INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, ultima_partida) VALUES (?, ?, ?, 0, 0, NOW())");
        $stmt->execute([$usuari_id, $joc_id, $nivell_actual]);
        echo json_encode([
            "success" => true,
            "message" => "Progreso creado correctamente",
            "nivell_actual" => $nivell_actual
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $e->getMessage()]);
}
?>

