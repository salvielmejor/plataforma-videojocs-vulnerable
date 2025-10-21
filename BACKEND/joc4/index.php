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
        <div class="difficulty-buttons">
            <button class="btn btn-easy" onclick="startGame('easy')">FÁCIL</button>
            <button class="btn btn-medium" onclick="startGame('medium')">MEDIO</button>
            <button class="btn btn-hard" onclick="startGame('hard')">DIFÍCIL</button>
        </div>
        <div class="instructions">
            <p>Corta las frutas con el cursor</p>
            <p>¡No dejes que caigan!</p>
        </div>
    </div>

    <div id="game" class="game hidden">
        <div class="game-header">
            <div class="score">Puntos: <span id="score">0</span></div>
            <div class="lives">Vidas: <span id="lives">3</span></div>
            <div class="difficulty-label">Dificultad: <span id="difficulty-label"></span></div>
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
        <button class="btn btn-restart" onclick="backToMenu()">Volver al Menú</button>
    </div>

    <script src="../../js/joc4.js"></script>
</body>
</html>