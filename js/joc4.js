const canvas = document.getElementById('gameCanvas');
const ctx = canvas ? canvas.getContext('2d') : null;

// Constantes de API
const API_BASE = 'http://172.18.33.242/projecte_marcsalvi/BACKEND/api.php';
const GAME_NAME = 'Fruit Ninja';

let gameState = {
    score: 0,
    lives: 3,
    level: 1,
    fruits: [],
    particles: [],
    gameLoop: null,
    isPlaying: false,
    spawnTimer: null,
    maxLevel: 10,
    currentConfig: null
};

function getLevelConfig(level) {
    const cappedLevel = Math.max(1, Math.min(level, gameState.maxLevel));
    // Escalado: reduce spawnRate y aumenta velocidad y cantidad con el nivel
    const baseSpawn = 1500; // ms en nivel 1
    const minSpawn = 450;   // ms en nivel 10
    const spawnRate = Math.max(minSpawn, baseSpawn - (cappedLevel - 1) * 120);

    const baseSpeed = 2;    // velocidad extra vertical
    const fruitSpeed = baseSpeed + (cappedLevel - 1) * 0.5;

    const baseCount = 1;
    const fruitCount = Math.min(4, baseCount + Math.floor((cappedLevel - 1) / 3));

    return { spawnRate, fruitSpeed, fruitCount };
}

async function loadLevelConfig(level) {
    try {
        const url = `${API_BASE}?joc_nom=${encodeURIComponent(GAME_NAME)}&nivell=${encodeURIComponent(level)}`;
        const res = await fetch(url);
        if (res.ok) {
            const cfg = await res.json();
            return cfg;
        }
        // Si no existe en BD, usar configuración por defecto
        return getLevelConfig(level);
    } catch (e) {
        console.warn('Fallo al cargar configuración de nivel, usando por defecto', e);
        return getLevelConfig(level);
    }
}

async function saveLevelConfig(level, config, nomNivell) {
    try {
        const url = `${API_BASE}?joc_nom=${encodeURIComponent(GAME_NAME)}&nivell=${encodeURIComponent(level)}`;
        await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ configuracio: config, nom_nivell: nomNivell || `Nivell ${level}` })
        });
    } catch (e) {
        console.warn('No se pudo guardar la configuración del nivel', e);
    }
}

const fruitEmojis = ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍑', '🥝', '🍍'];
const fruitColors = ['#ff6b6b', '#ffa500', '#ffd700', '#ffff00', '#90ee90', '#9370db', '#ff69b4', '#ffb6c1', '#98fb98', '#ffd700'];

async function startGame() {
    // Cargar el progreso del usuario
    let userProgress = await loadUserProgress();
    const startingLevel = userProgress.nivell_actual || 1;
    
    gameState.score = 0;
    gameState.lives = 3;
    gameState.level = startingLevel;
    gameState.fruits = [];
    gameState.particles = [];
    gameState.isPlaying = true;
    gameState.initialLevel = startingLevel; // Guardar nivel inicial para detectar si se supera
    mouseTrail = [];
    lastMousePos = { x: 0, y: 0 };

    document.getElementById('menu').classList.add('hidden');
    document.getElementById('game').classList.remove('hidden');
    document.getElementById('gameOver').classList.add('hidden');
    const levelEl = document.getElementById('level-label');
    if (levelEl) levelEl.textContent = gameState.level;

    setupCanvas();
    updateUI();
    // Cargar config del nivel desde API (o usar por defecto)
    gameState.currentConfig = await loadLevelConfig(gameState.level);
    // Guardar la config calculada si no existía (no distinguimos 404, ahorraremos una llamada)
    saveLevelConfig(gameState.level, gameState.currentConfig);
    startSpawningLoop();
    gameLoop();
}

function setupCanvas() {
    canvas.width = Math.min(800, window.innerWidth - 40);
    canvas.height = Math.min(600, window.innerHeight - 200);
}

function backToMenu() {
    gameState.isPlaying = false;
    if (gameState.gameLoop) {
        cancelAnimationFrame(gameState.gameLoop);
    }
    if (gameState.spawnTimer) {
        clearTimeout(gameState.spawnTimer);
        gameState.spawnTimer = null;
    }
    document.getElementById('menu').classList.remove('hidden');
    document.getElementById('game').classList.add('hidden');
    document.getElementById('gameOver').classList.add('hidden');
}

function goToGameSelector() {
    window.location.href = 'http://172.18.33.242/projecte_marcsalvi/BACKEND/menu.php';
}

function goToMainMenu() {
    window.location.href = 'http://172.18.33.242/projecte_marcsalvi/BACKEND/menu.php';
}

function startSpawningLoop() {
    if (!gameState.isPlaying) return;
    const config = gameState.currentConfig || getLevelConfig(gameState.level);
    for (let i = 0; i < config.fruitCount; i++) {
        spawnFruit(config);
    }
    gameState.spawnTimer = setTimeout(startSpawningLoop, config.spawnRate);
}

function spawnFruit(configFromLevel) {
    const fruitIndex = Math.floor(Math.random() * fruitEmojis.length);
    const config = configFromLevel || gameState.currentConfig || getLevelConfig(gameState.level);
    
    // Asegurar que la fruta aparece dentro del canvas con margen
    const margin = 60;
    const startX = Math.random() * (canvas.width - margin * 2) + margin;
    
    const fruit = {
        x: startX,
        y: canvas.height + 50,
        vx: (Math.random() - 0.5) * 2.5, // Velocidad horizontal reducida
        vy: -(Math.random() * 6 + 10 + config.fruitSpeed * 2), // velocidad vertical en función del nivel
        size: 40,
        emoji: fruitEmojis[fruitIndex],
        color: fruitColors[fruitIndex],
        rotation: Math.random() * Math.PI * 2,
        rotationSpeed: (Math.random() - 0.5) * 0.1,
        sliced: false
    };
    
    gameState.fruits.push(fruit);
}

function gameLoop() {
    if (!gameState.isPlaying) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Dibujar efecto de rastro de corte (katana)
    for (let i = mouseTrail.length - 1; i >= 0; i--) {
        const trail = mouseTrail[i];
        trail.life--;
        
        const alpha = trail.life / 10;
        const gradient = ctx.createLinearGradient(trail.x, trail.y, trail.x2, trail.y2);
        gradient.addColorStop(0, `rgba(0, 255, 255, 0)`);
        gradient.addColorStop(0.5, `rgba(255, 255, 255, ${alpha})`);
        gradient.addColorStop(1, `rgba(0, 255, 255, 0)`);
        
        ctx.strokeStyle = gradient;
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.shadowBlur = 15;
        ctx.shadowColor = '#00ffff';
        
        ctx.beginPath();
        ctx.moveTo(trail.x, trail.y);
        ctx.lineTo(trail.x2, trail.y2);
        ctx.stroke();
        
        ctx.shadowBlur = 0;
        
        if (trail.life <= 0) {
            mouseTrail.splice(i, 1);
        }
    }
    
    // Actualizar y dibujar frutas
    for (let i = gameState.fruits.length - 1; i >= 0; i--) {
        const fruit = gameState.fruits[i];
        
        if (!fruit.sliced) {
            fruit.vy += 0.25; // Gravedad reducida para alcanzar más altura
            fruit.x += fruit.vx;
            fruit.y += fruit.vy;
            fruit.rotation += fruit.rotationSpeed;
            
            // Dibujar fruta
            ctx.save();
            ctx.translate(fruit.x, fruit.y);
            ctx.rotate(fruit.rotation);
            ctx.font = `${fruit.size}px Arial`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(fruit.emoji, 0, 0);
            ctx.restore();
            
            // Comprobar si cayó (más allá del borde inferior) o salió de los lados
            const outOfBoundsBottom = fruit.y > canvas.height + 100;
            const outOfBoundsLeft = fruit.x < -50;
            const outOfBoundsRight = fruit.x > canvas.width + 50;
            
            if (outOfBoundsBottom || outOfBoundsLeft || outOfBoundsRight) {
                gameState.fruits.splice(i, 1);
                // Solo perder vida si cayó por abajo, no por los lados
                if (outOfBoundsBottom) {
                    loseLife();
                }
            }
        } else {
            gameState.fruits.splice(i, 1);
        }
    }
    
    // Actualizar y dibujar partículas
    for (let i = gameState.particles.length - 1; i >= 0; i--) {
        const p = gameState.particles[i];
        p.x += p.vx;
        p.y += p.vy;
        p.vy += 0.3;
        p.life--;
        
        ctx.globalAlpha = p.life / 30;
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
        ctx.globalAlpha = 1;
        
        if (p.life <= 0) {
            gameState.particles.splice(i, 1);
        }
    }
    
    gameState.gameLoop = requestAnimationFrame(gameLoop);
}

function sliceFruit(x, y) {
    for (let i = gameState.fruits.length - 1; i >= 0; i--) {
        const fruit = gameState.fruits[i];
        const dx = x - fruit.x;
        const dy = y - fruit.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < fruit.size && !fruit.sliced) {
            fruit.sliced = true;
            gameState.score += 10;
            handleScoreChange();
            createParticles(fruit.x, fruit.y, fruit.color);
            break;
        }
    }
}

function createParticles(x, y, color) {
    for (let i = 0; i < 15; i++) {
        gameState.particles.push({
            x: x,
            y: y,
            vx: (Math.random() - 0.5) * 10,
            vy: (Math.random() - 0.5) * 10,
            size: Math.random() * 5 + 2,
            color: color,
            life: 30
        });
    }
}

function loseLife() {
    gameState.lives--;
    updateUI();
    
    if (gameState.lives <= 0) {
        endGame();
    }
}

function endGame() {
    gameState.isPlaying = false;
    if (gameState.spawnTimer) {
        clearTimeout(gameState.spawnTimer);
        gameState.spawnTimer = null;
    }
    document.getElementById('game').classList.add('hidden');
    document.getElementById('gameOver').classList.remove('hidden');
    document.getElementById('finalScore').textContent = gameState.score;
    saveGameResult(gameState.score, gameState.level);
}

function updateUI() {
    document.getElementById('score').textContent = gameState.score;
    document.getElementById('lives').textContent = gameState.lives;
    const levelEl = document.getElementById('level-label');
    if (levelEl) levelEl.textContent = gameState.level;
}

function handleScoreChange() {
    const previousLevel = gameState.level;
    const calculatedLevel = Math.min(1 + Math.floor(gameState.score / 100), gameState.maxLevel);
    // El nivel nunca debe ser menor que el nivel inicial del usuario
    const minLevel = gameState.initialLevel || 1;
    gameState.level = Math.max(calculatedLevel, minLevel);
    updateUI();
    if (gameState.level !== previousLevel) {
        // Cuando sube el nivel, cargar la configuración correspondiente y persistirla
        (async () => {
            gameState.currentConfig = await loadLevelConfig(gameState.level);
            saveLevelConfig(gameState.level, gameState.currentConfig);
            // Si el nivel supera el nivel inicial del usuario, actualizar inmediatamente
            if (gameState.level > minLevel) {
                await updateLevelProgress(gameState.level);
            }
        })();
    }
}

async function updateLevelProgress(newLevel) {
    try {
        const response = await fetch('actualizar_nivel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ nivell_actual: newLevel })
        });
        const data = await response.json();
        if (data.success) {
            console.log('Nivel actualizado a:', newLevel);
        }
    } catch (err) {
        console.error('Error al actualizar nivel:', err);
    }
}

async function loadUserProgress() {
    try {
        const response = await fetch('obtener_progreso.php');
        const data = await response.json();
        if (data.success) {
            return {
                nivell_actual: data.nivell_actual || 1,
                puntuacio_maxima: data.puntuacio_maxima || 0
            };
        }
        return { nivell_actual: 1, puntuacio_maxima: 0 };
    } catch (err) {
        console.error('Error al cargar progreso:', err);
        return { nivell_actual: 1, puntuacio_maxima: 0 };
    }
}

async function saveGameResult(score, level) {
    try {
        // Determinar el nivel máximo alcanzado durante la partida
        const maxLevelReached = Math.max(gameState.initialLevel || 1, level);
        
        const response = await fetch('guardar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                puntuacio: score, 
                nivell_id: level,
                nivell_maximo_alcanzado: maxLevelReached,
                actualizar_nivel: maxLevelReached > (gameState.initialLevel || 1)
            })
        });
        const data = await response.json();
        if (!data.success) {
            console.error('Error al guardar partida:', data.message);
        }
    } catch (err) {
        console.error('Error de red al guardar partida:', err);
    }
}

// Variables para el efecto de corte
let lastMousePos = { x: 0, y: 0 };
let mouseTrail = [];

// Event listeners
if (canvas) {
    canvas.addEventListener('mousemove', (e) => {
        if (!gameState.isPlaying) return;
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Crear efecto de rastro
        if (lastMousePos.x !== 0) {
            const dx = x - lastMousePos.x;
            const dy = y - lastMousePos.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance > 5) {
                mouseTrail.push({
                    x: lastMousePos.x,
                    y: lastMousePos.y,
                    x2: x,
                    y2: y,
                    life: 10
                });
            }
        }
        
        lastMousePos = { x, y };
        sliceFruit(x, y);
    });

    canvas.addEventListener('touchmove', (e) => {
        if (!gameState.isPlaying) return;
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;
        
        // Crear efecto de rastro para touch
        if (lastMousePos.x !== 0) {
            const dx = x - lastMousePos.x;
            const dy = y - lastMousePos.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance > 5) {
                mouseTrail.push({
                    x: lastMousePos.x,
                    y: lastMousePos.y,
                    x2: x,
                    y2: y,
                    life: 10
                });
            }
        }
        
        lastMousePos = { x, y };
        sliceFruit(x, y);
    });

    canvas.addEventListener('mouseleave', () => {
        lastMousePos = { x: 0, y: 0 };
    });
}

window.addEventListener('resize', () => {
    if (gameState.isPlaying) {
        setupCanvas();
    }
});