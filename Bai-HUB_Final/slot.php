<?php
require_once 'includes/functions.php';
require_once 'classes/SlotMachine.php';
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
        window.slotAnimateEnd = <?php echo json_encode($animateEnd); ?>;
    </script>
    <script src="js/slot_script.js"></script>
</body>
</html>