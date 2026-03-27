// ads.js
document.addEventListener('DOMContentLoaded', () => {
    // Dynamically create ad containers if they don't exist
    const setupContainers = () => {
        if (!document.getElementById('leftAdContainer')) {
            const left = document.createElement('div');
            left.id = 'leftAdContainer';
            left.className = 'fake-ad-container left';
            document.body.appendChild(left);
        }
        if (!document.getElementById('rightAdContainer')) {
            const right = document.createElement('div');
            right.id = 'rightAdContainer';
            right.className = 'fake-ad-container right';
            document.body.appendChild(right);
        }
    };
    setupContainers();

    const games = [
        {
            id: 'blackjack',
            name: 'Blackjack',
            icon: 'img/icons/card-random.svg',
            slogans: [
                'DEFEAT THE DEALER. BECOME A LEGEND. 🃏',
                'DRESS TO WIN. PLAY BLACKJACK NOW. 💰',
                'YOUR FORTUNE AWAITS AT THE TABLE. 🔥',
                '21 IS YOUR LUCKY NUMBER TODAY. 🍀'
            ],
            color: '#4ac47d'
        },
        {
            id: 'dice',
            name: 'Dice Betting',
            icon: 'img/icons/rolling-dices.svg',
            slogans: [
                '10x YOUR WIN. LUCK IS CALLING. 🎲',
                'ROLL THE DICE. CHANGE YOUR LIFE. 🚀',
                'BIG ROLLS, BIGGER WINS. 💰',
                'STRIKE IT RICH WITH EVERY ROLL. ✨'
            ],
            color: '#ffd700'
        },
        {
            id: 'slot',
            name: 'Slot Machine',
            icon: 'img/icons/slot-machine.svg',
            slogans: [
                'HIT THE JACKPOT. WIN INSTANT CASH. 🎰',
                'SPIN TO WIN. THE REELS ARE HOT. 🔥',
                'YOUR NEXT SPIN COULD BE THE ONE. 💎',
                'FEEL THE RUSH. PLAY SLOTS NOW. 🚀'
            ],
            color: '#ff41ff'
        }
    ];

    const currentPath = window.location.pathname.split('/').pop().toLowerCase().replace('.php', '') || 'index';

    const getRelevantGames = () => {
        // Handle home page or index
        if (currentPath === 'index' || currentPath === '' || currentPath === 'home') {
            const shuffled = [...games].sort(() => 0.5 - Math.random());
            return [shuffled[0], shuffled[1]];
        } else {
            // Respect current game = respective ads
            const currentGame = games.find(g => g.id === currentPath);
            return currentGame ? [currentGame, currentGame] : [games[0], games[1]];
        }
    };

    const getRandomSlogan = (game) => {
        return game.slogans[Math.floor(Math.random() * game.slogans.length)];
    };

    const createAdHtml = (game, side) => `
        <div class="fake-ad" id="${side}Ad" data-game="${game.id}">
            <img src="${game.icon}" class="fake-ad-icon" alt="${game.name}" />
            <div class="fake-ad-text">CLAIM YOUR ${game.name.toUpperCase()} BONUS 🎁</div>
            <div class="fake-ad-subtext">${getRandomSlogan(game)}</div>
            <div class="fake-ad-button" style="background: linear-gradient(135deg, ${game.color} 0%, #3a9d62 100%)">
                GET FREE CREDITS NOW
            </div>
        </div>
    `;

    const updateAds = () => {
        const selected = getRelevantGames();
        const leftContainer = document.getElementById('leftAdContainer');
        const rightContainer = document.getElementById('rightAdContainer');

        const refreshContainer = (container, game, side) => {
            if (!container || !game) return;
            const oldAd = container.querySelector('.fake-ad');
            if (oldAd) {
                oldAd.classList.add('fade-out');
                setTimeout(() => {
                    container.innerHTML = createAdHtml(game, side);
                    container.querySelector('.fake-ad').addEventListener('click', showCaptcha);
                }, 500);
            } else {
                container.innerHTML = createAdHtml(game, side);
                container.querySelector('.fake-ad').addEventListener('click', showCaptcha);
            }
        };

        refreshContainer(leftContainer, selected[0], 'left');
        refreshContainer(rightContainer, selected[1], 'right');
    };

    // ... (no changes to container setup)

    const showCaptcha = async (e) => {
        const adElement = e.currentTarget;
        const targetGame = adElement.getAttribute('data-game');

        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const sum = num1 + num2;

        const { value: answer } = await Swal.fire({
            title: 'UNLOCK YOUR BONUS',
            html: `
                <div style="margin-bottom: 20px;">
                    <p>Prove you're human to grab up to <strong>500 FREE CREDITS</strong> for ${targetGame.toUpperCase()}!</p>
                    <p style="font-size: 1.4rem; color: #4ac47d; font-weight: bold;">What is ${num1} + ${num2}?</p>
                </div>
            `,
            input: 'text',
            inputPlaceholder: 'Type your answer here...',
            showCancelButton: true,
            confirmButtonText: 'CLAIM NOW',
            confirmButtonColor: '#4ac47d',
            cancelButtonText: 'Not now',
            cancelButtonColor: '#e64545',
            inputValidator: (value) => {
                if (!value) return 'Enter the answer to claim credits!';
                if (parseInt(value) !== sum) return 'Incorrect. Try again!';
            }
        });

        if (answer) {
            try {
                const response = await fetch('api/add_credits.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ game: targetGame })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'BONUS CLAIMED',
                        text: `${data.creditsAdded} credits added to your ${targetGame.toUpperCase()} balance!`,
                        confirmButtonColor: '#4ac47d'
                    }).then(() => {
                        if (targetGame === currentPath || (currentPath === 'index' && targetGame === 'blackjack')) {
                            window.location.reload();
                        } else {
                            Swal.fire({
                                title: 'Direct Access?',
                                text: `Do you want to play ${targetGame.toUpperCase()} now?`,
                                showCancelButton: true,
                                confirmButtonText: 'PLAY NOW',
                                confirmButtonColor: '#4ac47d'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = `${targetGame}.php`;
                                } else {
                                    window.location.reload();
                                }
                            });
                        }
                    });
                } else {
                    Swal.fire('Error', data.error || 'Something went wrong', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Connection failed! Try again soon.', 'error');
            }
        }
    };

    updateAds();

    // Change ads every 15-20 seconds
    const startRotation = () => {
        const delay = Math.floor(Math.random() * 5000) + 15000;
        setTimeout(() => {
            updateAds();
            startRotation();
        }, delay);
    };

    startRotation();
});
