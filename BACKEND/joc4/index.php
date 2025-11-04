<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    echo "<script>alert('⚠️ No estás logueado. Redirigiendo al login...'); window.location.href=' ../../index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fruit Ninja</title>
    <link rel="stylesheet" href="../../css/joc4.css">
</head>
<body>
    <div id="menu" class="menu">
        <h1>🍉 FRUIT NINJA 🍊</h1>
        <div class="menu-buttons">
            <button class="btn btn-play" onclick="startGame()">▶️ Play</button>
        </div>
        <div class="instructions">
            <p>Corta las frutas con el cursor</p>
            <p>¡No dejes que caigan!</p>
        </div>
        <button class="btn btn-menu-selector" onclick="goToMainMenu()">🎮 Menú de Juegos</button>
    </div>

    <div id="game" class="game hidden">
        <div class="game-header">
            <div class="score">Puntos: <span id="score">0</span></div>
            <div class="lives">Vidas: <span id="lives">3</span></div>
            <div class="level-label">Nivel: <span id="level-label">1</span></div>
        </div>
        <canvas id="gameCanvas"></canvas>
        <div class="game-buttons">
            <button class="btn btn-back" onclick="backToMenu()">Volver al Menú</button>
            <button class="btn btn-menu-selector" onclick="goToGameSelector()">🎮 Selector de Juegos</button>
        </div>
    </div>

    <div id="gameOver" class="game-over hidden">
        <h1>¡GAME OVER!</h1>
        <p class="final-score">Puntuación Final: <span id="finalScore">0</span></p>
        <div class="game-over-buttons">
            <button class="btn btn-restart" onclick="backToMenu()">Volver al Menú</button>
            <button class="btn btn-menu-selector" onclick="goToGameSelector()">🎮 Selector de Juegos</button>
        </div>
    </div>

    <script src="../../js/joc4.js"></script>
</body>
</html>