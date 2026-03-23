# Bai-HUB Casino Games Project

## Teammate Tasks: UI Enhancements & Layout

Hi! Start with these frontend tasks to improve the layout and smooth out the transitions. The backend and game logics have already been fully modularized (PHP game engines in `testing/classes/`, shared functions in `testing/includes/`, and pure frontend scripts in `testing/js/`).

### Task 1: Migrate Game Rules to a `(?)` Icon Modal/Tooltip
The goal is to free up screen real estate by hiding the large "Rules" panels by default, moving them into a pop-up, modal, or tooltip triggered by a `(?)` icon next to the actionable panels.

**1. Slot Machine (`testing/slot.php`)**
- **Where to cook:**
  - **HTML:** `testing/slot.php`
  - **CSS:** `testing/style.css`
  - **JS:** `testing/js/slot_script.js` (if you add modal toggle logic)
- **Instructions:**
  - Find the `<div class="section-header"><h2>Stakes & actions</h2></div>` block (around line 398).
  - Add a `(?)` icon/button next to the heading, e.g., `<h2>Stakes & actions <span class="info-icon">(?)</span></h2>`.
  - Find the current `<div class="betting-settings">...<h2>Payout Rules</h2>...</div>` block (around line 351).
  - Remove it from the main visual flow and implement it as a modal popup or a hover tooltip triggered by the new `(?)` icon.

**2. Blackjack (`testing/blackjack.php`)**
- **Where to cook:** 
  - **HTML:** `testing/blackjack.php`
  - **CSS:** `testing/style.css`
  - **JS:** `testing/js/blackjack_script.js`
- **Instructions:**
  - Find `<div class="section-header"><h2>Stakes & actions</h2></div>` (around line 316).
  - Add the `(?)` icon/button next to the heading.
  - Find the `<div class="betting-settings">...<h2>Game rules</h2>...</div>` block (around line 258).
  - Migrate this rules block into the new `(?)` modal/tooltip.

**3. Dice Game (`testing/dice.php`)**
- **Where to cook:** 
  - **HTML:** `testing/dice.php`
  - **CSS:** `testing/style.css`
  - **JS:** `testing/js/dice_script.js`
- **Instructions:**
  - Find the `<div class="section-header"><h2>Stakes & actions</h2></div>` block (around line 125).
  - Add the `(?)` icon/button next to the heading.
  - Find the `<div class="betting-settings">...<h2>Betting settings</h2>...</div>` block (table of odds) (around line 95).
  - Migrate this block into the new `(?)` modal/tooltip.

### Task 2: UI Issues and Smoothing Transitions
- **Where to cook:** `testing/style.css` and the individual JS files in `testing/js/`
- **Instructions:**
  - Polish the main layout once the heavy rules panels are removed from the main DOM flow. You may need to adjust Flexbox/Grid layouts so the `Stakes & actions` panel looks centered and balanced.
  - Add smooth CSS transitions (`transition: all 0.3s ease;`) to interactive elements (action buttons, bet chips, hovers, card hover states).
  - Ensure the new `(?)` modal/tooltip animations feel seamless (e.g., quick fade-in, slight slide-up).

---
*Note: Make sure not to accidentally delete the `window` variable declarations inside the `<script>` tags at the very bottom of the `.php` files (e.g., `window.slotAnimateEnd`). The external javascript blocks depend on these to fetch the outcome logic passed down from PHP!*
