<?php
session_start();
if (!isset($_SESSION['usuari_id'])) {
  header("Location: ../login.php");
  exit();
}

require_once "./../db_pdo.php";
$idUsuari = $_SESSION['usuari_id'];
$sql = "SELECT nom_usuari from usuaris WHERE id = $idUsuari";
//FER CONSULTA
//echo $resultat; //HA DE MOSTRAR SALVI

if (!isset($_SESSION['nivell'])) {
    $_SESSION['nivell'] = 1; 
}

?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8" />
  <title>Atrapa les Estrelles</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/estrella.css">
</head>
<body>
    <script>
        // API config
        const API_BASE = 'http://172.18.33.242/projecte_marcsalvi/BACKEND/api.php';
        const GAME_NAME = 'Atrapa les Estrelles';

</script>
  <video id="video-fondo" autoplay loop muted>
    <source src="video/fondo.mp4" type="video/mp4">
  </video>
  <div class="overlay"></div>
  <div class="logo-wrapper">
    <h1 id="logo">⭐ Atrapa les Estrelles ⭐</h1>
  </div>
  <div id="gameContainer">
        <div id="menuScreen" class="panel-container">
            <h2>Atrapa les Estrelles</h2>
            <div class="jocs-container">
                <button class="difficulty-btn easy" onclick="startGame()">
                    <h3>Començar Partida</h3>
                    <p>La dificultat augmentarà automàticament!</p>
                </button>
                    <button class="difficulty-btn" onclick="goToMainMenu()" style="margin-top: 12px;">
                        <h3>Tornar al menú principal</h3>
                    </button>
            </div>
            <div style="margin-top: 20px; color: #fff; text-align: center;">
                <p>🌟 0-50 punts: Fàcil</p>
                <p>🌟 50-150 punts: Mitjana</p>
                <p>🌟 150+ punts: Difícil</p>
                <p style="color: #ff6b6b; margin-top: 10px;">⚠️ No deixis escapar estrelles o perdràs vides!</p>
            </div>
        </div>

        <div id="gameScreen" class="hidden">
            <div id="hud">
                <span>Punts: <strong id="score">0</strong></span>
                <span style="margin-left: 30px;">Nivell: <strong id="level">Fàcil</strong></span>
                <span style="margin-left: 30px;">Vides: <strong id="lives">❤️❤️❤️</strong></span>
            </div>
            <div id="play-area">
                <canvas id="gameCanvas" width="800" height="500"></canvas>
            </div>
            <button id="start-btn" onclick="backToMenu()">Tornar al menú del joc</button>
            <button id="main-menu-btn" onclick="goToMainMenu()" style="margin-left: 10px;">Menú principal</button>
            <div id="win-message" class="hidden"></div>
            <div id="level-up-message" class="level-up-msg hidden">🎉 Nivell desbloquejat! 🎉</div>
        </div>
    </div>

    <script>
        const usuari_id = <?php echo $_SESSION['usuari_id']; ?>;
        const config = {
            "facil": { "velocitat": 1, "freq": 1500, "tamany": 80, "punts": 5, "umbral": 50 },
            "mitja": { "velocitat": 3, "freq": 1000, "tamany": 60, "punts": 10, "umbral": 150 },
            "dificil": { "velocitat": 6, "freq": 700, "tamany": 25, "punts": 20, "umbral": Infinity }
        };

        function levelFromDifficulty(diff) {
            return diff === 'facil' ? 1 : (diff === 'mitja' ? 2 : 3);
        }

        function fallbackConfig(diff) {
            return { ...config[diff] };
        }

        async function loadConfigByDifficulty(diff) {
            const lvl = levelFromDifficulty(diff);
            try {
                const url = `${API_BASE}?joc_nom=${encodeURIComponent(GAME_NAME)}&nivell=${lvl}`;
                const res = await fetch(url);
                if (res.ok) return await res.json();
                return fallbackConfig(diff);
            } catch (_) {
                return fallbackConfig(diff);
            }
        }

        async function saveConfigIfMissing(diff, cfg) {
            const lvl = levelFromDifficulty(diff);
            try {
                const url = `${API_BASE}?joc_nom=${encodeURIComponent(GAME_NAME)}&nivell=${lvl}&only_if_missing=1`;
                await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ configuracio: cfg, nom_nivell: diff.charAt(0).toUpperCase() + diff.slice(1) })
                });
            } catch (_) {}
        }

        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        let stars = [], score = 0, lives = 3, gameActive = false;
        let currentDifficulty = 'facil', currentConfig = null, spawnInterval = null;
        let mouseX = 0, mouseY = 0;
        let startTime = 0;

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

        async function startGame() {
            document.getElementById('menuScreen').classList.add('hidden');
            document.getElementById('gameScreen').classList.remove('hidden');
            currentDifficulty = 'facil';
            // Pre-crear en BD si falta y cargar
            await saveConfigIfMissing('facil', fallbackConfig('facil'));
            currentConfig = await loadConfigByDifficulty('facil');
            score = 0;
            lives = 3;
            stars = [];
            gameActive = true;
            startTime = Date.now();
            document.getElementById('score').textContent = score;
            document.getElementById('level').textContent = 'Fàcil';
            updateLivesDisplay();
            document.getElementById('win-message').classList.add('hidden');
            document.getElementById('level-up-message').classList.add('hidden');
            
            spawnInterval = setInterval(() => { 
                if (gameActive) stars.push(new Star(currentConfig)); 
            }, currentConfig.freq);
            
            gameLoop();
        }

        function updateLivesDisplay() {
            const livesText = '❤️'.repeat(lives);
            document.getElementById('lives').textContent = livesText || '💀';
            
            // Efecto visual cuando pierdes vida
            if (lives > 0 && lives < 3) {
                document.getElementById('lives').style.color = '#ff6b6b';
                setTimeout(() => {
                    document.getElementById('lives').style.color = '#fff';
                }, 500);
            }
        }

        function loseLife() {
            lives--;
            updateLivesDisplay();
            
            if (lives <= 0) {
                endGame();
            }
        }

        function checkLevelUp() {
            let newDifficulty = currentDifficulty;
            
            if (score >= 150 && currentDifficulty !== 'dificil') {
                newDifficulty = 'dificil';
            } else if (score >= 50 && currentDifficulty === 'facil') {
                newDifficulty = 'mitja';
            }
            
            if (newDifficulty !== currentDifficulty) {
                currentDifficulty = newDifficulty;
                // cargar config desde API y persistir si falta
                (async () => {
                    await saveConfigIfMissing(newDifficulty, fallbackConfig(newDifficulty));
                    currentConfig = await loadConfigByDifficulty(newDifficulty);
                    clearInterval(spawnInterval);
                    spawnInterval = setInterval(() => { 
                        if (gameActive) stars.push(new Star(currentConfig)); 
                    }, currentConfig.freq);
                })();
                
                // Actualizar nivel en HUD
                const levelNames = { 'facil': 'Fàcil', 'mitja': 'Mitjana', 'dificil': 'Difícil' };
                document.getElementById('level').textContent = levelNames[newDifficulty];
                
                // Mostrar mensaje de nivel
                const levelMsg = document.getElementById('level-up-message');
                levelMsg.textContent = `🎉 Nivell ${levelNames[newDifficulty]} desbloquejat! 🎉`;
                levelMsg.classList.remove('hidden');
                setTimeout(() => levelMsg.classList.add('hidden'), 2000);
                
                // el intervalo ya se actualiza tras cargar config
            }
        }

        function gameLoop() {
            if (!gameActive) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Dibujar cursor
            ctx.fillStyle = 'rgba(0,255,255,0.6)';
            ctx.beginPath(); 
            ctx.arc(mouseX, mouseY, 15, 0, Math.PI*2); 
            ctx.fill();
            ctx.strokeStyle = '#00ffff'; 
            ctx.lineWidth = 2; 
            ctx.stroke();
            
            // Actualizar y dibujar estrellas
            for (let i = stars.length - 1; i >= 0; i--) {
                stars[i].update(); 
                stars[i].draw();
                
                // Si la estrella sale de la pantalla, pierdes una vida
                if (stars[i].y > canvas.height) {
                    stars.splice(i, 1);
                    loseLife();
                }
            }
            requestAnimationFrame(gameLoop);
        }

        function endGame() {
            gameActive = false; 
            clearInterval(spawnInterval);
            
            const endTime = Date.now();
            const durada = Math.floor((endTime - startTime) / 1000);
            
            const msg = document.getElementById('win-message');
            const causeText = lives <= 0 ? 'Has perdut totes les vides!' : 'Partida acabada!';
            msg.textContent = `${causeText} Puntuació final: ${score}`;
            msg.classList.remove('hidden');

            // Guardar en la base de datos
            const joc_id = 1; // ID del juego "Atrapa les Estrelles"

            fetch('guardar.php', {
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
            .then(data => {
                if (data.status === 'ok') {
                    console.log('✅ Resultat guardat correctament');
                } else {
                    console.error('❌ Error guardant:', data.message);
                }
            })
            .catch(err => console.error('❌ Error de connexió:', err));
        }

        function backToMenu() {
            gameActive = false; 
            clearInterval(spawnInterval);
            document.getElementById('menuScreen').classList.remove('hidden');
            document.getElementById('gameScreen').classList.add('hidden');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function goToMainMenu() {
            window.location.href = '../menu.php';
        }

        canvas.addEventListener('mousemove', e => {
            const rect = canvas.getBoundingClientRect();
            mouseX = e.clientX - rect.left; 
            mouseY = e.clientY - rect.top;
        });
        
        canvas.addEventListener('click', e => {
            if (!gameActive || !currentConfig) return;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left, y = e.clientY - rect.top;
            for (let i = stars.length - 1; i >= 0; i--) {
                if (stars[i].isClicked(x, y)) {
                    score += currentConfig.punts;
                    document.getElementById('score').textContent = score;
                    stars.splice(i, 1);
                    checkLevelUp();
                    break;
                }
            }
        });
    </script>

    <style>
        .level-up-msg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 50px;
            border-radius: 20px;
            font-size: 28px;
            font-weight: bold;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            animation: popIn 0.5s ease;
            z-index: 1000;
        }

        @keyframes popIn {
            0% { transform: translate(-50%, -50%) scale(0); }
            50% { transform: translate(-50%, -50%) scale(1.1); }
            100% { transform: translate(-50%, -50%) scale(1); }
        }

        #lives {
            transition: color 0.3s ease;
        }
    </style>
</body>
</html>