<?php

class DiceGame {
    private $patterns;
    
    public function __construct() {
        $this->patterns = [
            'odd' => ['label' => 'Odd', 'points' => 10, 'multiplier' => 2],
            'even' => ['label' => 'Even', 'points' => 10, 'multiplier' => 2],
            'low' => ['label' => 'Low (3-10)', 'points' => 10, 'multiplier' => 2],
            'high' => ['label' => 'High (11-18)', 'points' => 10, 'multiplier' => 2],
        ];
    }
    
    public function getPatterns() {
        return $this->patterns;
    }
    
    public function matchesPattern($pattern, $value) {
        switch ($pattern) {
            case 'odd':
                return $value % 2 !== 0;
            case 'even':
                return $value % 2 === 0;
            case 'low':
                return $value >= 3 && $value <= 10;
            case 'high':
                return $value >= 11 && $value <= 18;
            default:
                return false;
        }
    }
    
    public function rollDice() {
        $die1 = random_int(1, 6);
        $die2 = random_int(1, 6);
        $die3 = random_int(1, 6);
        return [
            'die1' => $die1,
            'die2' => $die2,
            'die3' => $die3,
            'total' => $die1 + $die2 + $die3
        ];
    }
    
    public function calculateWin($type, $value, $bet, $rollTotal) {
        $win = false;
        $multiplier = 1;
        
        if ($type === 'number') {
            $win = $rollTotal === (int)$value;
            $multiplier = 10;
        } else {
            $win = $this->matchesPattern($value, $rollTotal);
            $multiplier = $this->patterns[$value]['multiplier'];
        }
        
        $points = 0;
        if ($win) {
            $points = $bet * $multiplier;
            $points = round($points, 2);
        }
        
        return [
            'win' => $win,
            'points' => $points,
            'multiplier' => $multiplier
        ];
    }
    
    public function normalizeHistory($history) {
        return array_values(array_slice($history, 0, 10));
    }
    
    public function createRound($type, $value, $bet, $dice, $generated, $win, $points) {
        $label = ($type === 'number') ? "Number $value" : $this->patterns[$value]['label'];
        return [
            'timestamp' => date('c'),
            'pattern' => $label,
            'bet' => number_format($bet, 2), 
            'dice' => [$dice['die1'], $dice['die2'], $dice['die3']],
            'generated' => $generated,
            'win' => $win,
            'points' => number_format($points, 2)
        ];
    }
}
