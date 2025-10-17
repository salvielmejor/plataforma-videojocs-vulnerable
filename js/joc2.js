const playArea = document.getElementById('play-area');
const scoreDisplay = document.getElementById('score');
const levelDisplay = document.getElementById('level');
const livesDisplay = document.getElementById('lives');
const startBtn = document.getElementById('start-btn');
const winMessage = document.getElementById('win-message');
const throwSound = document.getElementById('throw-sound');
const hitSound = document.getElementById('hit-sound');

let score = 0;
let level = 1;
let lives = 3;
let monkeys = [];
let monkeySpeed = 2000;
let maxLevels = 5;
let gameActive = false;

function createMonkey() {
  const monkey = document.createElement('div');
  monkey.classList.add('monkey');

  if (Math.random() < 0.2) {
    monkey.classList.add('golden');
    monkey.style.backgroundImage = "url('../img/monkey.png')";
  }

  monkey.style.top = Math.random() * (playArea.clientHeight - 60) + 'px';
  monkey.style.left = Math.random() * (playArea.clientWidth - 60) + 'px';
  playArea.appendChild(monkey);
  monkeys.push(monkey);

  setInterval(() => {
    monkey.style.top = Math.random() * (playArea.clientHeight - 60) + 'px';
    monkey.style.left = Math.random() * (playArea.clientWidth - 60) + 'px';
  }, monkeySpeed);
}

function throwBanana(x, y) {
  if (!gameActive) return;

  const banana = document.createElement('div');
  banana.classList.add('banana');
  banana.style.left = '385px';
  banana.style.top = '470px';
  playArea.appendChild(banana);

  if (throwSound) throwSound.play();

  const dx = x - 400;
  const dy = y - 470;
  const distance = Math.sqrt(dx * dx + dy * dy);
  const speed = 50;
  const steps = distance / speed;
  let step = 0;

  const interval = setInterval(() => {
    step++;
    banana.style.left = 385 + (dx / steps) * step + 'px';
    banana.style.top = 470 + (dy / steps) * step + 'px';

    monkeys.forEach(monkey => {
      const mRect = monkey.getBoundingClientRect();
      const bRect = banana.getBoundingClientRect();
      if (
        bRect.left < mRect.right &&
        bRect.right > mRect.left &&
        bRect.top < mRect.bottom &&
        bRect.bottom > mRect.top
      ) {
        monkey.classList.add('hit');
        if (hitSound) hitSound.play();
        setTimeout(() => monkey.remove(), 300);
        banana.remove();
        monkeys = monkeys.filter(m => m !== monkey);
        score += monkey.classList.contains('golden') ? 30 : 10;
        scoreDisplay.textContent = 'Puntos: ' + score;
        checkLevelProgress();
        clearInterval(interval);
      }
    });

    if (step > steps) {
      banana.remove();
      if (gameActive) {
        lives--;
        if (lives <= 0) {
          lives = 0;
          livesDisplay.textContent = 'Vidas: 0';
          alert('¡Game Over!');
          gameActive = false;
          startBtn.disabled = false;
        } else {
          livesDisplay.textContent = 'Vidas: ' + lives;
        }
      }
      clearInterval(interval);
    }
  }, 20);
}

function checkLevelProgress() {
  if (monkeys.length === 0) {
    level++;
    if (level > maxLevels) {
      winMessage.classList.remove('hidden');
      gameActive = false;
      startBtn.disabled = false;
    } else {
      levelDisplay.textContent = 'Nivel: ' + level;
      monkeySpeed -= 300;
      startLevel();
    }
  }
}

function startLevel() {
  for (let i = 0; i < level + 2; i++) {
    createMonkey();
  }
}

startBtn.addEventListener('click', () => {
  score = 0;
  level = 1;
  lives = 3;
  monkeySpeed = 2000;
  gameActive = true;
  scoreDisplay.textContent = 'Puntos: 0';
  levelDisplay.textContent = 'Nivel: 1';
  livesDisplay.textContent = 'Vidas: 3';
  winMessage.classList.add('hidden');
  monkeys.forEach(m => m.remove());
  monkeys = [];
  startBtn.disabled = true;
  startLevel();
});

playArea.addEventListener('click', (e) => {
  if (!gameActive) return;
  const rect = playArea.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  throwBanana(x, y);
});
