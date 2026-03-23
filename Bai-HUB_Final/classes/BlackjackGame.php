<?php

class BlackjackGame {
    private $deck;
    public $playerHand;
    public $dealerHand;
    private $gameState;
    
    public function __construct() {
        $this->initializeDeck();
        $this->gameState = 'betting';
    }
    
    private function initializeDeck() {
        $suits = ['♥', '♠', '♦', '♣'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $this->deck = [];
        
        for ($i = 0; $i < 6; $i++) {
            foreach ($suits as $suit) {
                foreach ($values as $value) {
                    $this->deck[] = ['value' => $value, 'suit' => $suit];
                }
            }
        }
        
        shuffle($this->deck);
    }
    
    public function getCardValue($card) {
        $value = $card['value'];
        if (in_array($value, ['J', 'Q', 'K'])) {
            return 10;
        }
        if ($value == 'A') {
            return 11;
        }
        return (int)$value;
    }
    
    public function calculateHandValue($hand) {
        $total = 0;
        $aces = 0;
        
        foreach ($hand as $card) {
            $value = $this->getCardValue($card);
            if ($value == 11) {
                $aces++;
            }
            $total += $value;
        }
        
        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }
        
        return $total;
    }
    
    public function drawCard() {
        if (empty($this->deck)) {
            $this->initializeDeck();
        }
        return array_pop($this->deck);
    }
    
    public function startGame() {
        $this->playerHand = [$this->drawCard(), $this->drawCard()];
        $this->dealerHand = [$this->drawCard(), $this->drawCard()];
        $this->gameState = 'playing';
        
        $playerValue = $this->calculateHandValue($this->playerHand);
        $dealerValue = $this->calculateHandValue($this->dealerHand);

        return [
            'playerHand' => $this->playerHand,
            'dealerHand' => [$this->dealerHand[0], ['value' => '?', 'suit' => '?']],
            'playerValue' => $playerValue,
            'dealerValue' => $this->getCardValue($this->dealerHand[0]),
            'naturalBlackjack' => ($playerValue === 21)
        ];
    }
    
    public function hit() {
        $this->playerHand[] = $this->drawCard();
        $playerValue = $this->calculateHandValue($this->playerHand);
        
        if ($playerValue > 21) {
            $this->gameState = 'gameover';
        }
        
        return [
            'playerHand' => $this->playerHand,
            'playerValue' => $playerValue,
            'gameActive' => $playerValue <= 21
        ];
    }
    
    public function stand() {
        $playerValue = $this->calculateHandValue($this->playerHand);
        
        if ($playerValue > 21) {
            return [
                'win' => false,
                'message' => 'Bust! You went over 21.',
                'dealerHand' => $this->dealerHand,
                'dealerValue' => $this->calculateHandValue($this->dealerHand)
            ];
        }
        
        $dealerValue = $this->calculateHandValue($this->dealerHand);
        while ($dealerValue < 17) {
            $this->dealerHand[] = $this->drawCard();
            $dealerValue = $this->calculateHandValue($this->dealerHand);
        }
        
        $win = false;
        $message = '';
        
        if ($dealerValue > 21) {
            $win = true;
            $message = 'Dealer busts! You win!';
        } elseif ($playerValue > $dealerValue) {
            $win = true;
            $message = 'You beat the dealer!';
        } elseif ($playerValue == $dealerValue) {
            $message = 'Push! Your bet is returned.';
        } else {
            $message = 'Dealer wins. Better luck next time!';
        }
        
        $this->gameState = 'gameover';
        
        return [
            'win' => $win,
            'message' => $message,
            'dealerHand' => $this->dealerHand,
            'dealerValue' => $dealerValue,
            'playerValue' => $playerValue
        ];
    }
    
    public function getGameState() {
        return $this->gameState;
    }
}
