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
    
    if (!window.slotAnimateEnd && trueFinalScore <= 0) {
        showGameOverNotification();
    }
    
    // Handle Spinning Animation Logic
    const animateEndData = window.slotAnimateEnd;
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
