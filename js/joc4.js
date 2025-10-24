const canvas = document.getElementById('gameCanvas');
const ctx = canvas ? canvas.getContext('2d') : null;

let gameState = {
    score: 0,
    lives: 3,
    difficulty: 'easy',
    fruits: [],
    particles: [],
    gameLoop: null,
    isPlaying: false
};

const difficulties = {
    easy: { spawnRate: 1500, fruitSpeed: 2, fruitCount: 1 },
    medium: { spawnRate: 1000, fruitSpeed: 3, fruitCount: 2 },
    hard: { spawnRate: 700, fruitSpeed: 4, fruitCount: 3 }
};

const fruitEmojis = ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🍑', '🥝', '🍍'];
const fruitColors = ['#ff6b6b', '#ffa500', '#ffd700', '#ffff00', '#90ee90', '#9370db', '#ff69b4', '#ffb6c1', '#98fb98', '#ffd700'];

function startGame(difficulty) {
    gameState.difficulty = difficulty;
    gameState.score = 0;
    gameState.lives = 3;
    gameState.fruits = [];
    gameState.particles = [];
    gameState.isPlaying = true;
    mouseTrail = [];
    lastMousePos = { x: 0, y: 0 };

    document.getElementById('menu').classList.add('hidden');
    document.getElementById('game').classList.remove('hidden');
    document.getElementById('gameOver').classList.add('hidden');
    document.getElementById('difficulty-label').textContent = difficulty.toUpperCase();

    setupCanvas();
    updateUI();
    startSpawning();
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

function startSpawning() {
    const config = difficulties[gameState.difficulty];
    
    const spawnInterval = setInterval(() => {
        if (!gameState.isPlaying) {
            clearInterval(spawnInterval);
            return;
        }
        
        for (let i = 0; i < config.fruitCount; i++) {
            spawnFruit();
        }
    }, config.spawnRate);
}

function spawnFruit() {
    const fruitIndex = Math.floor(Math.random() * fruitEmojis.length);
    const config = difficulties[gameState.difficulty];
    
    // Asegurar que la fruta aparece dentro del canvas con margen
    const margin = 60;
    const startX = Math.random() * (canvas.width - margin * 2) + margin;
    
    const fruit = {
        x: startX,
        y: canvas.height + 50,
        vx: (Math.random() - 0.5) * 2.5, // Velocidad horizontal reducida
        vy: -(Math.random() * 6 + 10+ config.fruitSpeed * 2), // Mucha más velocidad vertical (14-26)
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
            updateUI();
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
    document.getElementById('game').classList.add('hidden');
    document.getElementById('gameOver').classList.remove('hidden');
    document.getElementById('finalScore').textContent = gameState.score;
}

function updateUI() {
    document.getElementById('score').textContent = gameState.score;
    document.getElementById('lives').textContent = gameState.lives;
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