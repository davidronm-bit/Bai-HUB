document.addEventListener('DOMContentLoaded', function() {
    const quickStakes = document.querySelectorAll('.quick-stake');
    const betAmount = document.getElementById('betAmount');
    const scoreValueEl = document.getElementById('scoreValue');
    const trueFinalScore = parseFloat(scoreValueEl.getAttribute('data-final-score')) || parseFloat(scoreValueEl.textContent);
    const forms = document.querySelectorAll('form');
    
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
    
    if (!window.blackjackAnimateEnd && !window.blackjackGameDataObj && trueFinalScore <= 0) {
        showGameOverNotification();
    }
    
    const betForm = document.getElementById('betForm');
    if (betForm) {
        betForm.addEventListener('submit', function(e) {
            if (trueFinalScore <= 0) {
                e.preventDefault();
                showGameOverNotification();
                return;
            }
            if (betAmount) {
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
            }
        });
    }
    
    // Smooth out the conclusion interactions
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const actionInput = this.querySelector('input[name="action"]');
            if (actionInput) {
                const action = actionInput.value;
                if (action === 'stand' || action === 'double_down') {
                    e.preventDefault();
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.innerHTML = '<span style="opacity:0.7">Dealer playing...</span>';
                        document.getElementById('dealerCards').style.opacity = '0.5';
                        document.getElementById('dealerCards').style.transition = 'opacity 0.3s ease';
                        setTimeout(() => {
                            this.submit();
                        }, 1200); // 1.2 second suspense delay
                    }
                } else if (action === 'hit') {
                    e.preventDefault();
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.innerHTML = '<span style="opacity:0.7">Hitting...</span>';
                        setTimeout(() => {
                            this.submit();
                        }, 400); // Quick 400ms delay for a hit
                    }
                }
            }
        });
    });
    
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

    // --- BLACKJACK ANIMATION LOGIC ---
    if (window.blackjackAnimationData) {
        const animateEndData = window.blackjackAnimationData;
        const finalMessage = window.blackjackResultMessage || '';
        
        // Hide result text and dealer's real cards initially 
        const resultTextEl = document.getElementById('resultText');
        const dealerValueEl = document.getElementById('dealerValue');
        const dealerArea = document.getElementById('dealerCards');
        
        if (resultTextEl && dealerValueEl && dealerArea) {
            resultTextEl.innerText = 'Calculating...';
            dealerValueEl.innerText = '?';
            dealerArea.innerHTML = '';
            
            // Show first card
            setTimeout(() => {
                if (animateEndData.dealerHand.length > 0) {
                    dealerArea.innerHTML = `<div class="game-card">${animateEndData.dealerHand[0].value}${animateEndData.dealerHand[0].suit}</div>`;
                    
                    // Show second card 
                    setTimeout(() => {
                        if (animateEndData.dealerHand.length > 1) {
                            let cardsHTML = `<div class="game-card">${animateEndData.dealerHand[0].value}${animateEndData.dealerHand[0].suit}</div>`;
                            cardsHTML += `<div class="game-card">${animateEndData.dealerHand[1].value}${animateEndData.dealerHand[1].suit}</div>`;
                            dealerArea.innerHTML = cardsHTML;
                            
                            // Draw remainder if any
                            let i = 2;
                            const drawInterval = setInterval(() => {
                                if (i < animateEndData.dealerHand.length) {
                                    cardsHTML += `<div class="game-card">${animateEndData.dealerHand[i].value}${animateEndData.dealerHand[i].suit}</div>`;
                                    dealerArea.innerHTML = cardsHTML;
                                    i++;
                                } else {
                                    clearInterval(drawInterval);
                                    // End of dealing
                                    dealerValueEl.innerText = animateEndData.dealerValue;
                                    resultTextEl.innerText = finalMessage;
                                    
                                    // Sync UI with final state natively!
                                    const pItem = document.getElementById('pendingHistoryItem');
                                    if (pItem) pItem.style.display = 'flex';
                                    
                                    const sValEl = document.getElementById('scoreValue');
                                    if (sValEl && sValEl.hasAttribute('data-final-score')) {
                                        sValEl.innerText = sValEl.getAttribute('data-final-score');
                                        sValEl.style.color = (animateEndData.win) ? '#4ac47d' : (animateEndData.payout === animateEndData.bet ? '' : '#e64545');
                                        setTimeout(() => { sValEl.style.color = ''; }, 1000);
                                    }
                                    
                                    // Wait 3 seconds and clear board
                                    setTimeout(() => {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        const act = document.createElement('input');
                                        act.type = 'hidden'; 
                                        act.name = 'action'; 
                                        act.value = 'clear_board';
                                        form.appendChild(act);
                                        document.body.appendChild(form);
                                        form.submit();
                                    }, 3000); // 3 seconds delay as requested!
                                }
                            }, 800);
                        }
                    }, 800);
                }
            }, 400);
        }
    }
});
