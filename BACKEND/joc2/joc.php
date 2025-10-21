<?php
session_start();
// Suposem que ja tens una sessió oberta amb l'usuari autenticat.
// Exemple: $_SESSION['usuari_id'] i $_SESSION['nom_usuari']
if (!isset($_SESSION['usuari_id'])) {
    $_SESSION['usuari_id'] = 1; // valor per defecte per proves
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atrapa les Estrelles</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/estrella.css">
</head>
<body>
    <!-- Fons de vídeo -->
    <video id="video-fondo" autoplay loop muted>
        <source src="video/fondo.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <!-- Logo -->
    <div class="logo-wrapper">
        <h1 id="logo">⭐ Atrapa les Estrelles ⭐</h1>
    </div>

    <div id="gameContainer">
        <div id="menuScreen" class="panel-container">
            <h2>Selecciona la dificultat:</h2>
            <div class="jocs-container">
                <button class="difficulty-btn easy" onclick="startGame('facil')"><h3>Fàcil</h3><p>Estrelles grans i lentes</p></button>
                <button class="difficulty-btn medium" onclick="startGame('mitja')"><h3>Mitjana</h3><p>Velocitat moderada</p></button>
                <button class="difficulty-btn hard" onclick="startGame('dificil')"><h3>Difícil</h3><p>Ràpid i desafiant</p></button>
            </div>
        </div>

        <div id="gameScreen" class="hidden">
            <div id="hud">
                <span>Punts: <strong id="score">0</strong></span>
                <span style="margin-left: 50px;">Temps: <strong id="timer">30</strong>s</span>
            </div>
            <div id="play-area">
                <canvas id="gameCanvas" width="800" height="500"></canvas>
            </div>
            <button id="start-btn" onclick="backToMenu()">Tornar al Menú</button>
            <div id="win-message" class="hidden"></div>
        </div>
    </div>

    <script>
        const config = {
            "facil": { "velocitat": 1, "freq": 1500, "tamany": 80, "temps": 60, "punts": 5 },
            "mitja": { "velocitat": 3, "freq": 1000, "tamany": 60, "temps": 40, "punts": 15 },
            "dificil": { "velocitat": 6, "freq": 700, "tamany": 25, "temps": 30, "punts": 50 }
        };

        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        let stars = [], score = 0, timeLeft = 30, gameActive = false;
        let currentConfig = null, spawnInterval = null, timerInterval = null;
        let mouseX = 0, mouseY = 0;

        class Star {
            constructor(cfg) {
                this.x = Math.random() * (canvas.width - cfg.tamany);
                this.y = -cfg.tamany;
                this.size = cfg.tamany;
                this.speed = cfg.velocitat;
            }
            update() { this.y += this.speed; }
            draw() { ctx.font = `${this.size}px Arial`; ctx.fillText('⭐', this.x, this.y); }
            isClicked(mx, my) { return mx >= this.x && mx <= this.x + this.size && my >= this.y - this.size && my <= this.y; }
        }

        function startGame(dif) {
            document.getElementById('menuScreen').classList.add('hidden');
            document.getElementById('gameScreen').classList.remove('hidden');
            currentConfig = config[dif]; score = 0; timeLeft = currentConfig.temps; stars = []; gameActive = true;
            document.getElementById('score').textContent = score;
            document.getElementById('timer').textContent = timeLeft;
            document.getElementById('win-message').classList.add('hidden');
            spawnInterval = setInterval(() => { if (gameActive) stars.push(new Star(currentConfig)); }, currentConfig.freq);
            timerInterval = setInterval(() => {
                timeLeft--;
                document.getElementById('timer').textContent = timeLeft;
                if (timeLeft <= 0) endGame();
            }, 1000);
            gameLoop();
        }

        function gameLoop() {
            if (!gameActive) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(0,255,255,0.6)';
            ctx.beginPath(); ctx.arc(mouseX, mouseY, 15, 0, Math.PI*2); ctx.fill();
            ctx.strokeStyle = '#00ffff'; ctx.lineWidth = 2; ctx.stroke();
            for (let i = stars.length - 1; i >= 0; i--) {
                stars[i].update(); stars[i].draw();
                if (stars[i].y > canvas.height) stars.splice(i, 1);
            }
            requestAnimationFrame(gameLoop);
        }

        function endGame() {
            gameActive = false; clearInterval(spawnInterval); clearInterval(timerInterval);
            const msg = document.getElementById('win-message');
            msg.textContent = `Partida acabada! Puntuació: ${score}`;
            msg.classList.remove('hidden');

            // --- Enviar puntuació a la base de dades ---
            const usuari_id = <?php echo $_SESSION['usuari_id']; ?>;
            const joc_id = 1; // ID del joc Atrapa les Estrelles
            const durada = currentConfig.temps;

            fetch('guardar_partida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    usuari_id: usuari_id,
                    joc_id: joc_id,
                    puntuacio: score,
                    durada: durada
                })
            })
            .then(res => res.json())
            .then(data => console.log('Resultat guardat:', data))
            .catch(err => console.error('Error:', err));
        }

        function backToMenu() {
            gameActive = false; clearInterval(spawnInterval); clearInterval(timerInterval);
            document.getElementById('menuScreen').classList.remove('hidden');
            document.getElementById('gameScreen').classList.add('hidden');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        canvas.addEventListener('mousemove', e => {
            const rect = canvas.getBoundingClientRect();
            mouseX = e.clientX - rect.left; mouseY = e.clientY - rect.top;
        });
        canvas.addEventListener('click', e => {
            if (!gameActive || !currentConfig) return;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left, y = e.clientY - rect.top;
            for (let i = stars.length - 1; i >= 0; i--) {
                if (stars[i].isClicked(x, y)) {
                    score += currentConfig.punts;
                    document.getElementById('score').textContent = score;
                    stars.splice(i, 1); break;
                }
            }
        });
    </script>
</body>
</html>
