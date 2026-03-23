<?php
session_start();

class SlotMachine {
    public const SYMBOLS = [
        'grapes' => ['name' => 'grapes.svg', 'multiplier' => 1.5, 'type' => 'fruit'],
        'orange' => ['name' => 'orange.svg', 'multiplier' => 1.5, 'type' => 'fruit'],
        'clover' => ['name' => 'clover.svg', 'multiplier' => 3, 'type' => 'special'],
        'diamond' => ['name' => 'cut-diamond.svg', 'multiplier' => 5, 'type' => 'jackpot'],
        'star' => ['name' => 'star.svg', 'multiplier' => 0, 'type' => 'wild']
    ];
    
    private array $reels = [];
    
    public function __construct() {
        $this->initializeReels();
    }
    
    private function initializeReels(): void {
        $symbolPool = [];
        foreach (self::SYMBOLS as $key => $symbol) {
            $weight = ($symbol['type'] === 'jackpot') ? 1 : (($symbol['type'] === 'wild') ? 2 : (($symbol['type'] === 'special') ? 3 : 5));
            for ($i = 0; $i < $weight; $i++) {
                $symbolPool[] = $key;
            }
        }
        $this->reels = [$symbolPool, $symbolPool, $symbolPool];
    }
    
    public function spin(): array {
        $result = [];
        foreach ($this->reels as $reel) {
            $randomIndex = array_rand($reel);
            $result[] = $reel[$randomIndex];
        }
        return $result;
    }
    
    public function calculateWin(array $result, float $bet): array {
        $win = false;
        $multiplier = 0;
        $winningSymbol = null;
        
        $wild = 'star';
        $target = null;
        foreach ($result as $sym) {
            if ($sym !== $wild) {
                $target = $sym;
                break;
            }
        }
        
        if ($target === null) {
            $multiplier = 10;
            $win = true;
            $winningSymbol = $wild;
        } else {
            $allMatch = true;
            foreach ($result as $sym) {
                if ($sym !== $target && $sym !== $wild) {
                    $allMatch = false;
                    break;
                }
            }
            
            if ($allMatch) {
                $win = true;
                $multiplier = self::SYMBOLS[$target]['multiplier'];
                $winningSymbol = $target;
            }
        }
        
        $payout = 0;
        if ($win) {
            $payout = $bet * $multiplier;
        }
        
        return [
            'win' => $win,
            'multiplier' => $multiplier,
            'payout' => $payout,
            'winningSymbol' => $winningSymbol,
            'result' => $result
        ];
    }
    
    public function getSymbolImage(string $symbolKey): string {
        return 'img/' . self::SYMBOLS[$symbolKey]['name'];
    }
}

// Initialize session variables
if (!isset($_SESSION['slot_score'])) {
    $_SESSION['slot_score'] = 100.00;
}
if (!isset($_SESSION['slot_history'])) {
    $_SESSION['slot_history'] = [];
}
if (!isset($_SESSION['slot_showWelcome'])) {
    $_SESSION['slot_showWelcome'] = true;
}

$score = $_SESSION['slot_score'];
$history = $_SESSION['slot_history'];
$result = null;
$winResult = null;
$error = null;
$message = null;
$currentBet = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'reset') {
            $_SESSION['slot_score'] = 100.00;
            $_SESSION['slot_history'] = [];
            $_SESSION['slot_showWelcome'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if ($_POST['action'] === 'clear_board') {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'spin') {
            $bet = (float)$_POST['bet'];
            $currentBet = $bet;
            
            if ($bet <= 0) {
                $error = 'Invalid bet amount. Please enter a stake amount greater than 0.';
            } elseif ($bet > $score) {
                $error = "Insufficient balance! You have " . number_format($score, 2) . " credits.";
            } else {
                $_SESSION['slot_showWelcome'] = false;
                $slot = new SlotMachine();
                $result = $slot->spin();
                $winResult = $slot->calculateWin($result, $bet);
                
                $newScore = $score - $bet;
                if ($winResult['win']) {
                    $newScore += $winResult['payout'];
                    $message = "🎉 WIN! You won " . number_format($winResult['payout'], 2) . " credits! (" . $winResult['multiplier'] . "x multiplier) 🎉";
                } else {
                    $message = "LOSE! Better luck next time!";
                }
                
                $_SESSION['slot_score'] = $newScore;
                $score = $newScore;
                
                $historyEntry = [
                    'bet' => $bet,
                    'win' => $winResult['win'],
                    'payout' => $winResult['payout'],
                    'multiplier' => $winResult['multiplier'],
                    'result' => $result,
                    'symbols' => $result,
                    'message' => $message
                ];
                array_unshift($history, $historyEntry);
                $history = array_slice($history, 0, 10);
                $_SESSION['slot_history'] = $history;
                
                $_SESSION['slot_animate_end'] = [
                    'reels' => [
                        ['symbol' => $result[0], 'image' => $slot->getSymbolImage($result[0])],
                        ['symbol' => $result[1], 'image' => $slot->getSymbolImage($result[1])],
                        ['symbol' => $result[2], 'image' => $slot->getSymbolImage($result[2])]
                    ],
                    'win' => $winResult['win'],
                    'winningSymbol' => $winResult['winningSymbol'],
                    'message' => $message,
                    'payout' => $winResult['payout']
                ];
            }
        }
    }
}

$score = $_SESSION['slot_score'];
$history = $_SESSION['slot_history'];
$slot = new SlotMachine();

$animateEnd = isset($_SESSION['slot_animate_end']) ? $_SESSION['slot_animate_end'] : null;
$displayScore = $score;

if ($animateEnd) {
    unset($_SESSION['slot_animate_end']);
    $winResult = null;
    $message = null; 
    
    // Reverse the latest history entry calculation for the rolling display state
    if (!empty($history)) {
        $displayScore = $score + $history[0]['bet'] - ($history[0]['win'] ? $history[0]['payout'] : 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Slot Machine - Casino Games</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes rollDown {
            0% { transform: translateY(-70%); opacity: 0; }
            50% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(70%); opacity: 0; }
        }
        .rolling-img {
            animation: rollDown 0.15s linear infinite;
        }

        /* Force horizontal display and proper sizing */
        .slot-reels-container {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 24px !important;
            flex-wrap: nowrap !important;
        }
        
        .slot-reel {
            width: 100px !important;
            height: 100px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(0, 0, 0, 0.35) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 16px !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4) !important;
            overflow: hidden !important;
        }
        
        .slot-symbol {
            width: 80px !important;
            height: 80px !important;
            object-fit: contain !important;
            display: block !important;
        }
        
        /* Remove any text that might appear */
        .slot-reel span, 
        .slot-reel:before,
        .slot-reel:after {
            display: none !important;
        }
        
        .slot-symbol-text {
            display: none !important;
        }
        
        /* Responsive for smaller screens */
        @media (max-width: 800px) {
            .slot-reels-container {
                gap: 16px !important;
            }
            .slot-reel {
                width: 70px !important;
                height: 70px !important;
            }
            .slot-symbol {
                width: 55px !important;
                height: 55px !important;
            }
        }
        
        @media (max-width: 480px) {
            .slot-reels-container {
                gap: 12px !important;
            }
            .slot-reel {
                width: 55px !important;
                height: 55px !important;
            }
            .slot-symbol {
                width: 45px !important;
                height: 45px !important;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/house.svg" alt="Home" />
    </a>
    
    <main class="container">
        <?php if (isset($_SESSION['slot_showWelcome']) && $_SESSION['slot_showWelcome'] && !$animateEnd && !$error): ?>
            <div class="message message-welcome">
                Welcome to the Slot Machine! Place a bet and spin to win.
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <div class="left-side">
                <!-- Game Display Section -->
                <div class="slot-display-section">
                    <div class="section-header">
                        <h2>Slot Reels</h2>
                        <div class="score">Credits: <strong id="scoreValue" data-final-score="<?php echo number_format($score, 2); ?>"><?php echo number_format($displayScore, 2); ?></strong></div>
                    </div>
                    <div class="slot-machine-display">
                        <div class="slot-reels-container">
                            <?php if ($winResult && isset($winResult['result'])): ?>
                                <?php foreach ($winResult['result'] as $index => $symbol): 
                                    $isWinning = $winResult['win'] && $winResult['winningSymbol'] == $symbol;
                                    $imagePath = $slot->getSymbolImage($symbol);
                                ?>
                                    <div class="slot-reel <?php echo $isWinning ? 'winning' : ''; ?>">
                                        <img src="<?php echo $imagePath; ?>" 
                                             class="slot-symbol" 
                                             alt="<?php echo $symbol; ?>" />
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="slot-reel">
                                    <img src="img/grapes.svg" class="slot-symbol" alt="grapes" />
                                </div>
                                <div class="slot-reel">
                                    <img src="img/orange.svg" class="slot-symbol" alt="orange" />
                                </div>
                                <div class="slot-reel">
                                    <img src="img/clover.svg" class="slot-symbol" alt="clover" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo $currentBet ? number_format($currentBet, 2) . ' credits' : (isset($_POST['bet']) ? number_format($_POST['bet'], 2) . ' credits' : '-'); ?></span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Result:</span>
                            <span class="result-value" id="resultText">
                                <?php if ($message): ?>
                                    <?php echo $message; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bottom-section">
                    <div class="betting-settings">
                        <div class="section-header">
                            <h2>Payout Rules</h2>
                        </div>
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">Winning Combinations</div>
                                <div class="payout-table">
                                    <div class="payout-row" style="margin-bottom: 8px;">
                                        <em>All wins require exactly 3 matching symbols.<br>The 🌟 Star symbol is WILD!</em>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍇 🍇 🍇 (3 Grapes)</span>
                                        </div>
                                        <span class="payout-multiplier">1.5x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍊 🍊 🍊 (3 Oranges)</span>
                                        </div>
                                        <span class="payout-multiplier">1.5x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🍀 🍀 🍀 (3 Clovers)</span>
                                        </div>
                                        <span class="payout-multiplier">3x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>💎 💎 💎 (3 Diamonds)</span>
                                        </div>
                                        <span class="payout-multiplier">5x bet</span>
                                    </div>
                                    <div class="payout-row">
                                        <div class="payout-symbols">
                                            <span>🌟 🌟 🌟 (3 Stars)</span>
                                        </div>
                                        <span class="payout-multiplier">10x bet</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & actions</h2>
                        </div>
                        <div class="actions-content">
                            <div class="stake-group">
                                <div class="group-title">Stake amount</div>
                                <div class="quick-stakes">
                                    <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                    <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                    <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                </div>
                                <form method="POST" id="spinForm">
                                    <input type="number" name="bet" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                    <input type="hidden" name="action" value="spin" />
                                    <button type="submit" class="btn-primary" style="margin-top: 16px;">🎰 SPIN 🎰</button>
                                </form>
                            </div>
                            
                            <form method="POST" id="resetForm" style="width: 100%;">
                                <input type="hidden" name="action" value="reset" />
                                <button type="button" id="resetBtn" class="btn-danger">Reset Game</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="history-section">
                    <div class="section-header">
                        <h2>Spin history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No spins yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($history as $index => $round): ?>
                                    <li class="history-item" <?php echo ($animateEnd && $index === 0) ? 'id="pendingHistoryItem" style="display: none;"' : ''; ?>>
                                        <div class="history-bet">
                                            Bet: <?php echo number_format($round['bet'], 2); ?> credits
                                        </div>
                                        <div class="history-result <?php echo $round['win'] ? 'win' : 'lose'; ?>">
                                            <?php echo $round['win'] ? 'WIN' : 'LOSE'; ?> <?php echo $round['win'] ? '+' . number_format($round['payout'], 2) : '-' . number_format($round['bet'], 2); ?>
                                        </div>
                                        <div class="history-dice">
                                            <?php 
                                            foreach ($round['symbols'] as $sym) {
                                                echo '<img src="' . $slot->getSymbolImage($sym) . '" style="width: 24px; height: 24px; margin: 0 2px; vertical-align: middle;" />';
                                            }
                                            ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickStakes = document.querySelectorAll('.quick-stake');
            const betAmount = document.getElementById('betAmount');
            const scoreValueEl = document.getElementById('scoreValue');
            const trueFinalScore = parseFloat(scoreValueEl.getAttribute('data-final-score')) || parseFloat(scoreValueEl.textContent);
            
            function showGameOverNotification() {
                Swal.fire({
                    title: 'Game Over',
                    text: 'You have 0 credits left. Reset the game to continue playing.',
                    icon: 'error',
                    confirmButtonText: 'Reset Game',
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('resetForm').submit();
                    }
                });
            }
            
            const resetBtn = document.getElementById('resetBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Reset Game',
                        text: 'Your score will be reset to 100 credits and all history will be cleared.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e64545',
                        cancelButtonColor: '#4ac47d',
                        confirmButtonText: 'Yes, reset it',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('resetForm').submit();
                        }
                    });
                });
            }
            
            const spinForm = document.getElementById('spinForm');
            if (spinForm) {
                spinForm.addEventListener('submit', function(e) {
                    if (trueFinalScore <= 0) {
                        e.preventDefault();
                        showGameOverNotification();
                        return;
                    }
                    const bet = parseFloat(betAmount.value);
                    if (bet > trueFinalScore) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Insufficient Balance',
                            html: `You have <strong>${trueFinalScore.toFixed(2)}</strong> credits, but you bet <strong>${bet.toFixed(2)}</strong> credits.`,
                            icon: 'error',
                            confirmButtonText: 'Adjust Bet',
                            showCancelButton: true,
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                betAmount.value = trueFinalScore.toFixed(2);
                            }
                        });
                        return;
                    }
                    
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.innerHTML = '<span style="opacity:0.7">🎰 Spinning...</span>';
                    }
                });
            }
            
            if (<?php echo json_encode(!$animateEnd); ?> && trueFinalScore <= 0) {
                showGameOverNotification();
            }
            
            // Handle Spinning Animation Logic
            const animateEndData = <?php echo json_encode($animateEnd); ?>;
            if (animateEndData) {
                const reelsElements = document.querySelectorAll('.slot-reel');
                const images = document.querySelectorAll('.slot-symbol');
                const resultTextEl = document.getElementById('resultText');
                
                resultTextEl.innerText = 'Rolling...';
                
                images.forEach(img => img.classList.add('rolling-img'));
                
                const symbolImages = ['img/grapes.svg', 'img/orange.svg', 'img/clover.svg', 'img/cut-diamond.svg', 'img/star.svg'];
                
                let rolls = 0;
                const rollMax = 15; 
                
                const rollInterval = setInterval(() => {
                    images.forEach(img => {
                        img.src = symbolImages[Math.floor(Math.random() * symbolImages.length)];
                    });
                    
                    rolls++;
                    if (rolls >= rollMax) {
                        clearInterval(rollInterval);
                        
                        images.forEach((img, idx) => {
                            img.classList.remove('rolling-img');
                            img.src = animateEndData.reels[idx].image;
                            
                            if (animateEndData.win && (animateEndData.winningSymbol === animateEndData.reels[idx].symbol || animateEndData.reels[idx].symbol === 'star')) {
                                reelsElements[idx].classList.add('winning');
                            } else {
                                reelsElements[idx].classList.remove('winning');
                            }
                        });
                        
                        if (animateEndData.win) {
                            resultTextEl.innerHTML = `<span class="win">${animateEndData.message}</span>`;
                        } else {
                            resultTextEl.innerHTML = `<span class="lose">${animateEndData.message}</span>`;
                        }
                        
                        // Reveal synced state!
                        const pItem = document.getElementById('pendingHistoryItem');
                        if (pItem) pItem.style.display = 'flex';
                        scoreValueEl.innerText = scoreValueEl.getAttribute('data-final-score');
                        
                        // Flash green/red briefly on score change
                        scoreValueEl.style.color = animateEndData.win ? '#4ac47d' : '#e64545';
                        setTimeout(() => { scoreValueEl.style.color = ''; }, 1000);
                        
                        setTimeout(() => {
                             const form = document.createElement('form');
                             form.method = 'POST';
                             const act = document.createElement('input'); act.type='hidden'; act.name='action'; act.value='clear_board';
                             form.appendChild(act); document.body.appendChild(form); form.submit();
                        }, 3000);
                    }
                }, 150);
            }
            
            if (quickStakes.length > 0 && betAmount) {
                quickStakes.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const multiplier = parseFloat(this.getAttribute('data-multiplier'));
                        let newValue;
                        
                        if (multiplier === 1) {
                            newValue = trueFinalScore;
                        } else {
                            newValue = trueFinalScore * multiplier;
                        }
                        
                        newValue = Math.floor(newValue * 100) / 100;
                        if (newValue < 0.01) newValue = 0.01;
                        betAmount.value = newValue.toFixed(2);
                    });
                });
            }
        });
    </script>
</body>
</html>