# Bai-HUB Casino Platform

Bai-HUB is a professional-grade casino gaming platform designed with a premium aesthetic and a robust backend. The platform provides a seamless gambling experience across multiple game types with integrated advertisement and credit management systems.

## Key Features

### Game Selection
- **Dice Betting**: A versatile dice game where players can bet on patterns such as Odd/Even and High/Low (2x payout) or predict the exact sum of three dice for a high-risk, high-reward 10x payout.
- **Blackjack**: A refined implementation of the classic 21 game against a dealer. It includes standard actions such as Hit, Stand, and Double Down, with a 2.5x payout for a natural Blackjack.
- **Slot Machine**: A fast-paced matching game with variable multipliers based on symbol rarity, ranging from standard fruit matches (0.8x - 1.2x) to Diamond Jackpots (5x).

### Advertisement System
- **Context-Aware Targeting**: The platform features a specialized ad system that displays game-specific advertisements based on the current page, ensuring high relevance.
- **Persuasive Marketing**: Ad banners utilize a carousel of rotating slogans designed for maximum user engagement and conversion.
- **Bonus Credit Generation**: Users can claim between 100 and 500 free credits via an interactive captcha system. To ensure platform stability, claims are restricted by a 10-minute global cooldown.

### Professional UI/UX
- **Emoji-Free Design**: The interface is strictly professional, utilizing high-quality icons and a consistent text-based design system to avoid visual clutter.
- **Cross-Game Persistence**: Credits and game history are maintained across the entire platform, allowing players to move between games without losing their balance.

## Technical Architecture

- **Frontend**: Built with semantic HTML5 and vanilla CSS3. Interactive elements and game state management are handled by optimized JavaScript.
- **Backend / API**: All game logic is processed server-side via PHP for security and consistency.
- **Storage System**: Utilizes a persistent JSON-based data layer. The system is designed for high portability and includes "self-healing" logic that automatically generates the required directory structure and data files on the first run.

### Directory Structure
- `/api/`: PHP API endpoints for game actions and data storage.
- `/data/`: Persistent storage for user balances, game history, and global metadata.
- `/scripts/`: Client-side logic for games and the advertisement engine.
- `/img/`: Visual assets, including icons and game-specific imagery.

## Deployment Instructions

1. Deploy the project folder to any web server with PHP support (e.g., Apache, Nginx).
2. No manual database configuration is required; the system will automatically initialize the `data/` directory and required JSON files upon the first page load.
3. Ensure the web server has standard write permissions for the project root to facilitate automatic folder creation.
