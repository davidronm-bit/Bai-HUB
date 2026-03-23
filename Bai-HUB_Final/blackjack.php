<?php
require_once 'includes/functions.php';
require_once 'classes/BlackjackGame.php';

$score = $_SESSION['blackjack_score'];
$history = $_SESSION['blackjack_history'];
$game = $_SESSION['blackjack_game'];
$currentBet = $_SESSION['blackjack_current_bet'];
$gameData = $_SESSION['blackjack_game_data'];
$error = null;
$message = $_SESSION['blackjack_message'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'reset') {
            $_SESSION['blackjack_score'] = 100.00;
            $_SESSION['blackjack_history'] = [];
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_current_bet'] = null;
            $_SESSION['blackjack_game_data'] = null;
            $_SESSION['blackjack_message'] = null;
            $_SESSION['blackjack_showWelcome'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($_POST['action'] === 'clear_board') {
            $_SESSION['blackjack_game'] = null;
            $_SESSION['blackjack_current_bet'] = null;
            $_SESSION['blackjack_game_data'] = null;
            $_SESSION['blackjack_message'] = null;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'place_bet') {
            $bet = (float)$_POST['bet'];
            
            if ($bet <= 0) {
                $error = 'Invalid bet amount. Please enter a stake amount greater than 0.';
            } elseif ($bet > $score) {
                $error = "Insufficient balance! You have " . number_format($score, 2) . " credits.";
            } else {
                $_SESSION['blackjack_showWelcome'] = false;
                $_SESSION['blackjack_current_bet'] = $bet;
                $game = new BlackjackGame();
                $_SESSION['blackjack_game'] = $game;
                $gameData = $game->startGame();
                $currentBet = $bet;
                
                if ($gameData['naturalBlackjack']) {
                    $dealerTotal = $game->calculateHandValue($game->dealerHand);
                    $winAmount = 0;
                    $outcomeStatus = 'lose';
                    if ($dealerTotal === 21) {
                        $winAmount = $currentBet;
                        $message = "Push! Both have Blackjack. Your bet is returned.";
                        $outcomeStatus = 'push';
                    } else {
                        $winAmount = $currentBet * 2.5; // Pays 3:2
                        $message = "BLACKJACK! You won " . number_format($winAmount, 2) . " credits!";
                        $outcomeStatus = 'win';
                    }
                    
                    $newScore = $score - $currentBet + $winAmount;
                    $_SESSION['blackjack_score'] = $newScore;
                    
                    $historyEntry = [
                        'bet' => $currentBet,
                        'status' => $outcomeStatus,
                        'payout' => $winAmount,
                        'playerValue' => 21,
                        'dealerValue' => $dealerTotal,
                        'message' => $message
                    ];
                    array_unshift($history, $historyEntry);
                    $history = array_slice($history, 0, 10);
                    $_SESSION['blackjack_history'] = $history;
                    $_SESSION['blackjack_message'] = $message;
                    
                    $gameData['dealerHand'] = $game->dealerHand;
                    $gameData['dealerValue'] = $dealerTotal;
                    
                    $_SESSION['blackjack_game'] = null;
                    $_SESSION['blackjack_animate_end'] = $gameData;
                    $_SESSION['blackjack_game_data'] = $gameData;
                    $score = $newScore;
                } else {
                    $_SESSION['blackjack_game_data'] = $gameData;
                    $_SESSION['blackjack_message'] = null;
                }
            }
        }
        
        if ($_POST['action'] === 'hit' && $game && $game->getGameState() === 'playing') {
            $result = $game->hit();
            $gameData = [
                'playerHand' => $result['playerHand'],
                'playerValue' => $result['playerValue'],
                'dealerHand' => [$game->dealerHand[0], ['value' => '?', 'suit' => '?']],
                'dealerValue' => $game->getCardValue($game->dealerHand[0])
            ];
            $_SESSION['blackjack_game_data'] = $gameData;
            
            if (!$result['gameActive']) {
                $standResult = $game->stand();
                $winAmount = 0;
                $outcomeStatus = 'lose';
                if ($standResult['win']) {
                    $winAmount = $currentBet * 2;
                    $message = $standResult['message'] . " You won " . number_format($winAmount, 2) . " credits!";
                    $outcomeStatus = 'win';
                } elseif (isset($standResult['message']) && strpos($standResult['message'], 'Push') !== false) {
                    $winAmount = $currentBet;
                    $message = $standResult['message'] . " Your bet of " . number_format($currentBet, 2) . " credits is returned.";
                    $outcomeStatus = 'push';
                } else {
                    $message = $standResult['message'];
                }
                
                $newScore = $score - $currentBet + $winAmount;
                $_SESSION['blackjack_score'] = $newScore;
                
                $historyEntry = [
                    'bet' => $currentBet,
                    'status' => $outcomeStatus,
                    'payout' => $winAmount,
                    'playerValue' => $gameData['playerValue'],
                    'dealerValue' => $standResult['dealerValue'],
                    'message' => $message
                ];
                array_unshift($history, $historyEntry);
                $history = array_slice($history, 0, 10);
                $_SESSION['blackjack_history'] = $history;
                $_SESSION['blackjack_message'] = $message;
                
                $_SESSION['blackjack_game'] = null;
                $gameData['dealerHand'] = $standResult['dealerHand'];
                $gameData['dealerValue'] = $standResult['dealerValue'];
                $_SESSION['blackjack_animate_end'] = $gameData;
                $_SESSION['blackjack_game_data'] = $gameData;
                $score = $newScore;
            }
        }
        
        if ($_POST['action'] === 'stand' && $game && $game->getGameState() === 'playing') {
            $result = $game->stand();
            $winAmount = 0;
            $outcomeStatus = 'lose';
            if ($result['win']) {
                $winAmount = $currentBet * 2;
                $message = $result['message'] . " You won " . number_format($winAmount, 2) . " credits!";
                $outcomeStatus = 'win';
            } elseif (isset($result['message']) && strpos($result['message'], 'Push') !== false) {
                $winAmount = $currentBet;
                $message = $result['message'] . " Your bet of " . number_format($currentBet, 2) . " credits is returned.";
                $outcomeStatus = 'push';
            } else {
                $message = $result['message'];
            }
            
            $newScore = $score - $currentBet + $winAmount;
            $_SESSION['blackjack_score'] = $newScore;
            
            $historyEntry = [
                'bet' => $currentBet,
                'status' => $outcomeStatus,
                'payout' => $winAmount,
                'playerValue' => $gameData['playerValue'],
                'dealerValue' => $result['dealerValue'],
                'message' => $message
            ];
            array_unshift($history, $historyEntry);
            $history = array_slice($history, 0, 10);
            $_SESSION['blackjack_history'] = $history;
            $_SESSION['blackjack_message'] = $message;
            
            $_SESSION['blackjack_game'] = null;
            $gameData['dealerHand'] = $result['dealerHand'];
            $gameData['dealerValue'] = $result['dealerValue'];
            $_SESSION['blackjack_animate_end'] = $gameData;
            $_SESSION['blackjack_game_data'] = $gameData;
            $score = $newScore;
        }

        if ($_POST['action'] === 'double_down' && $game && $game->getGameState() === 'playing') {
            if ($score >= $currentBet * 2) {
                $currentBet *= 2;
                $_SESSION['blackjack_current_bet'] = $currentBet;
                
                $game->hit();
                $result = $game->stand();
                
                $winAmount = 0;
                $outcomeStatus = 'lose';
                if ($result['win']) {
                    $winAmount = $currentBet * 2;
                    $message = $result['message'] . " You won " . number_format($winAmount, 2) . " credits!";
                    $outcomeStatus = 'win';
                } elseif (isset($result['message']) && strpos($result['message'], 'Push') !== false) {
                    $winAmount = $currentBet;
                    $message = $result['message'] . " Your bet of " . number_format($currentBet, 2) . " credits is returned.";
                    $outcomeStatus = 'push';
                } else {
                    $message = $result['message'];
                }
                
                $newScore = $score - $currentBet + $winAmount;
                $_SESSION['blackjack_score'] = $newScore;
                
                $historyEntry = [
                    'bet' => $currentBet,
                    'status' => $outcomeStatus,
                    'payout' => $winAmount,
                    'playerValue' => $result['playerValue'],
                    'dealerValue' => $result['dealerValue'],
                    'message' => 'Double Down: ' . $message
                ];
                array_unshift($history, $historyEntry);
                $history = array_slice($history, 0, 10);
                $_SESSION['blackjack_history'] = $history;
                $_SESSION['blackjack_message'] = 'Double Down: ' . $message;
                
                $_SESSION['blackjack_game'] = null;
                $gameData['dealerHand'] = $result['dealerHand'];
                $gameData['dealerValue'] = $result['dealerValue'];
                $_SESSION['blackjack_animate_end'] = $gameData;
                $_SESSION['blackjack_game_data'] = $gameData;
                $score = $newScore;
            } else {
                $_SESSION['blackjack_message'] = "Insufficient balance to Double Down.";
            }
        }
    }
}

$score = $_SESSION['blackjack_score'];
$history = $_SESSION['blackjack_history'];
$game = $_SESSION['blackjack_game'];
$currentBet = $_SESSION['blackjack_current_bet'];
$gameData = $_SESSION['blackjack_game_data'];

$animateEnd = isset($_SESSION['blackjack_animate_end']) ? $_SESSION['blackjack_animate_end'] : null;
$message = $_SESSION['blackjack_message'];
$displayScore = $score;

if ($animateEnd) {
    unset($_SESSION['blackjack_animate_end']);
    if (!empty($history)) {
        $displayScore = $score + $history[0]['bet'] - $history[0]['payout'];
    }
}

if ($message) {
    $_SESSION['blackjack_message'] = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blackjack - Casino Games</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/blackjack_script.js"></script>
</head>
<body>
    <a href="index.php" class="home-btn" title="Home">
        <img src="img/house.svg" alt="Home" />
    </a>
    <main class="container">
        <?php if (isset($_SESSION['blackjack_showWelcome']) && $_SESSION['blackjack_showWelcome'] && !$gameData && !$error): ?>
            <div class="message message-welcome">
                Welcome to Blackjack! Place a bet to start the game.
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <div class="left-side">
                <!-- Game Display Section -->
                <div class="game-display-section">
                    <div class="section-header">
                        <h2>Blackjack Table</h2>
                        <div class="score">Credits: <strong id="scoreValue" data-final-score="<?php echo number_format($score, 2); ?>"><?php echo number_format($displayScore, 2); ?></strong></div>
                    </div>
                    <div class="blackjack-table">
                        <div class="dealer-area">
                            <div class="area-label">Dealer</div>
                            <div class="cards-area" id="dealerCards">
                                <?php if ($gameData): ?>
                                    <?php foreach ($gameData['dealerHand'] as $card): ?>
                                        <div class="game-card">
                                            <?php echo $card['value'] . $card['suit']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card-placeholder">Waiting for bet...</div>
                                <?php endif; ?>
                            </div>
                            <div class="hand-total">Value: <span id="dealerValue"><?php echo $gameData ? $gameData['dealerValue'] : '-'; ?></span></div>
                        </div>
                        
                        <div class="player-area">
                            <div class="area-label">Player</div>
                            <div class="cards-area" id="playerCards">
                                <?php if ($gameData): ?>
                                    <?php foreach ($gameData['playerHand'] as $card): ?>
                                        <div class="game-card">
                                            <?php echo $card['value'] . $card['suit']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="card-placeholder">Place a bet to start</div>
                                <?php endif; ?>
                            </div>
                            <div class="hand-total">Value: <span id="playerValue"><?php echo $gameData ? $gameData['playerValue'] : '-'; ?></span></div>
                        </div>
                    </div>
                    <div class="result-panel">
                        <div class="result-row">
                            <span class="result-label">Your bet:</span>
                            <span class="result-value" id="displayBet"><?php echo ($gameData || $animateEnd) ? number_format($animateEnd ? $history[0]['bet'] : $currentBet, 2) . ' credits' : '-'; ?></span>
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
                            <h2>Game Rules</h2>
                        </div>
                        <div class="betting-content">
                            <div class="bet-group">
                                <div class="group-title">How to Play</div>
                                <div class="rules-list">
                                    <div class="rule-item">🎯 Beat the dealer to 21</div>
                                    <div class="rule-item">💎 Blackjack pays 2x your bet</div>
                                    <div class="rule-item">🃏 Dealer must stand on 17</div>
                                    <div class="rule-item">🔄 Push returns your bet</div>
                                    <div class="rule-item">Ace can be 1 or 11</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stakes-actions">
                        <div class="section-header">
                            <h2>Stakes & actions</h2>
                        </div>
                        <div class="actions-content">
                            <?php if (!$gameData || $animateEnd): ?>
                                <div class="stake-group">
                                    <div class="group-title">Stake amount</div>
                                    <div class="quick-stakes">
                                        <button type="button" class="quick-stake" data-multiplier="0.25">1/4</button>
                                        <button type="button" class="quick-stake" data-multiplier="0.5">1/2</button>
                                        <button type="button" class="quick-stake" data-multiplier="1">All in</button>
                                    </div>
                                    <form method="POST" id="betForm">
                                        <input type="number" name="bet" id="betAmount" class="stake-input" step="0.01" placeholder="Enter bet amount" required />
                                        <input type="hidden" name="action" value="place_bet" />
                                        <button type="submit" class="btn-primary" style="margin-top: 16px;">Place Bet</button>
                                    </form>
                                </div>
                            <?php elseif (!$animateEnd): ?>
                                <div class="action-group">
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="hit" />
                                        <button type="submit" class="btn-primary">Hit</button>
                                    </form>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="stand" />
                                        <button type="submit" class="btn-primary">Stand</button>
                                    </form>
                                    <?php if (count($gameData['playerHand']) === 2 && $score >= $currentBet * 2): ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="action" value="double_down" />
                                        <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #ffd700, #ff8c00);">Double Down</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
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
                        <h2>Game history</h2>
                        <span class="badge">last 10 rounds</span>
                    </div>
                    <div class="history-list-container">
                        <ul class="history-list">
                            <?php if (empty($history)): ?>
                                <li class="history-item empty">
                                    <span>No rounds yet</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($history as $index => $round): ?>
                                    <li class="history-item" <?php echo ($animateEnd && $index === 0) ? 'id="pendingHistoryItem" style="display: none;"' : ''; ?>>
                                        <div class="history-bet">
                                            Bet: <?php echo number_format($round['bet'], 2); ?> credits
                                        </div>
                                        <?php 
                                            // Handle old records gracefully
                                            $status = 'lose';
                                            if (isset($round['status'])) {
                                                $status = $round['status'];
                                            } else {
                                                if (isset($round['win']) && is_string($round['win'])) {
                                                    $status = $round['win'];
                                                } elseif (isset($round['win'])) {
                                                    $status = $round['win'] ? 'win' : (($round['payout'] > 0) ? 'push' : 'lose');
                                                }
                                            }
                                        ?>
                                        <div class="history-result <?php echo $status; ?>">
                                            <?php echo strtoupper($status); ?> 
                                            <?php if ($status !== 'lose'): ?>+<?php echo number_format($round['payout'], 2); ?><?php else: ?>-<?php echo number_format($round['bet'], 2); ?><?php endif; ?>
                                        </div>
                                        <div class="history-dice">
                                            Player: <?php echo $round['playerValue']; ?> | Dealer: <?php echo $round['dealerValue']; ?>
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
        window.blackjackAnimateEnd = <?php echo json_encode($animateEnd); ?>;
        window.blackjackGameDataObj = <?php echo json_encode($gameData); ?>;
        <?php if ($animateEnd): ?>
        window.blackjackAnimationData = <?php echo json_encode($animateEnd); ?>;
        window.blackjackResultMessage = <?php echo json_encode($message); ?>;
        <?php endif; ?>
    </script>
</body>
</html>