<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Initializes common game session variables across all casino tables
 */
function initGameSessions() {
    // Dice
    if (!isset($_SESSION['score'])) {
        $_SESSION['score'] = 100.00;
    }
    if (!isset($_SESSION['history'])) {
        $_SESSION['history'] = [];
    }
    if (!isset($_SESSION['showWelcome'])) {
        $_SESSION['showWelcome'] = true;
    }

    // Blackjack
    if (!isset($_SESSION['blackjack_score'])) {
        $_SESSION['blackjack_score'] = 100.00;
    }
    if (!isset($_SESSION['blackjack_history'])) {
        $_SESSION['blackjack_history'] = [];
    }
    if (!isset($_SESSION['blackjack_game'])) {
        $_SESSION['blackjack_game'] = null;
    }
    if (!isset($_SESSION['blackjack_current_bet'])) {
        $_SESSION['blackjack_current_bet'] = null;
    }
    if (!isset($_SESSION['blackjack_game_data'])) {
        $_SESSION['blackjack_game_data'] = null;
    }
    if (!isset($_SESSION['blackjack_message'])) {
        $_SESSION['blackjack_message'] = null;
    }
    if (!isset($_SESSION['blackjack_showWelcome'])) {
        $_SESSION['blackjack_showWelcome'] = true;
    }

    // Slot
    if (!isset($_SESSION['slot_score'])) {
        $_SESSION['slot_score'] = 100.00;
    }
    if (!isset($_SESSION['slot_history'])) {
        $_SESSION['slot_history'] = [];
    }
    if (!isset($_SESSION['slot_showWelcome'])) {
        $_SESSION['slot_showWelcome'] = true;
    }
}

initGameSessions();
