<?php
session_start();

$host = 'localhost';
$user = 'plataforma_user';
$pass = '123456789a';
$dbname = 'plataforma_videojocs';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de connexió']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['usuari_id'], $data['joc_id'], $data['puntuacio'], $data['durada'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dades incompletes']);
    exit;
}

if (!isset($_SESSION['usuari_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sessió no iniciada']);
    exit;
}

if ($data['usuari_id'] != $_SESSION['usuari_id']) {
    echo json_encode(['status' => 'error', 'message' => 'Usuari no autoritzat']);
    exit;
}

$usuari_id = intval($data['usuari_id']);
$joc_id = intval($data['joc_id']);
$puntuacio = intval($data['puntuacio']);
$durada = intval($data['durada']);

// --- 1. Inserir partida ---
$stmt = $conn->prepare("INSERT INTO partides (usuari_id, joc_id, nivell_jugat, puntuacio_obtinguda, durada_segons) VALUES (?, ?, 1, ?, ?)");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error prepare partides: ' . $conn->error]);
    exit;
}
$stmt->bind_param("iiii", $usuari_id, $joc_id, $puntuacio, $durada);
$stmt->execute();
$stmt->close();

// --- 2. Actualitzar o inserir al progrés ---
$sql_check = "SELECT id, puntuacio_maxima, partides_jugades FROM progres_usuari WHERE usuari_id=? AND joc_id=?";
$stmt = $conn->prepare($sql_check);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error prepare check: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $usuari_id, $joc_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error execució select: ' . $conn->error]);
    exit;
}

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $max = max($row['puntuacio_maxima'], $puntuacio);
    $total = $row['partides_jugades'] + 1;

    $upd = $conn->prepare("UPDATE progres_usuari SET puntuacio_maxima=?, partides_jugades=?, ultima_partida=NOW() WHERE usuari_id=? AND joc_id=?");
    if (!$upd) {
        echo json_encode(['status' => 'error', 'message' => 'Error prepare update: ' . $conn->error]);
        exit;
    }
    $upd->bind_param("iiii", $max, $total, $usuari_id, $joc_id);
    $upd->execute();
    if ($upd->error) {
        echo json_encode(['status' => 'error', 'message' => 'Error execució update: ' . $upd->error]);
        exit;
    }
    $upd->close();

} else {
    $ins = $conn->prepare("INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, ultima_partida) VALUES (?, ?, 1, ?, 1, NOW())");
    if (!$ins) {
        echo json_encode(['status' => 'error', 'message' => 'Error prepare insert progres: ' . $conn->error]);
        exit;
    }
    $ins->bind_param("iiii", $usuari_id, $joc_id, $puntuacio, $puntuacio);
    $ins->execute();
    if ($ins->error) {
        echo json_encode(['status' => 'error', 'message' => 'Error execució insert: ' . $ins->error]);
        exit;
    }
    $ins->close();
}

echo json_encode(['status' => 'ok', 'message' => 'Partida i progrés guardats correctament']);
$conn->close();
?>
