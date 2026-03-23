<?php

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
