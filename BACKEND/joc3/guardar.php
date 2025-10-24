<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'No hay usuario logueado']);
    exit;
}

$host = 'localhost';
$user = 'plataforma_user';
$pass = '123456789a';
$dbname = 'plataforma_videojocs';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['usuari_id'], $data['joc_id'], $data['puntuacio'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos', 'received' => $data]);
    exit;
}

$usuari_id = intval($data['usuari_id']);
$joc_id = intval($data['joc_id']);
$puntuacio = intval($data['puntuacio']);

// Verificar que los datos sean válidos
if ($usuari_id <= 0 || $joc_id <= 0 || $puntuacio < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Insertar la partida en la tabla partides
    $stmt = $conn->prepare("INSERT INTO partides (usuari_id, joc_id, nivell_jugat, puntuacio_obtinguda, durada_segons) VALUES (?, ?, 1, ?, 0)");
    if (!$stmt) {
        throw new Exception("Error preparando statement: " . $conn->error);
    }
    $stmt->bind_param("iii", $usuari_id, $joc_id, $puntuacio);
    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando insert: " . $stmt->error);
    }
    $stmt->close();

    // Verificar si ya existe progreso para este usuario y juego
    $sql_check = "SELECT id, puntuacio_maxima, partides_jugades FROM progres_usuari WHERE usuari_id=? AND joc_id=?";
    $stmt = $conn->prepare($sql_check);
    if (!$stmt) {
        throw new Exception("Error preparando check: " . $conn->error);
    }
    $stmt->bind_param("ii", $usuari_id, $joc_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Actualizar progreso existente
        $row = $res->fetch_assoc();
        $max = max($row['puntuacio_maxima'], $puntuacio);
        $total = $row['partides_jugades'] + 1;
        
        $upd = $conn->prepare("UPDATE progres_usuari SET puntuacio_maxima=?, partides_jugades=?, ultima_partida=NOW() WHERE usuari_id=? AND joc_id=?");
        if (!$upd) {
            throw new Exception("Error preparando update: " . $conn->error);
        }
        $upd->bind_param("iiii", $max, $total, $usuari_id, $joc_id);
        if (!$upd->execute()) {
            throw new Exception("Error ejecutando update: " . $upd->error);
        }
        $upd->close();
        
        $message = $max > $row['puntuacio_maxima'] ? 'Nueva puntuación máxima!' : 'Partida guardada';
    } else {
        // Crear nuevo progreso
        $ins = $conn->prepare("INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, ultima_partida) VALUES (?, ?, 1, ?, 1, NOW())");
        if (!$ins) {
            throw new Exception("Error preparando insert: " . $conn->error);
        }
        $ins->bind_param("iii", $usuari_id, $joc_id, $puntuacio);
        if (!$ins->execute()) {
            throw new Exception("Error ejecutando insert: " . $ins->error);
        }
        $ins->close();
        
        $message = 'Primera partida guardada';
    }

    echo json_encode(['status' => 'ok', 'message' => $message, 'puntuacion' => $puntuacio]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
