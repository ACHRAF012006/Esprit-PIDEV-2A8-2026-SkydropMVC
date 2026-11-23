/*
 * SKYDROP customer pages: small, progressive enhancements. Every page works without this file; it only adds comfort.
 * Each feature is switched on by a data- attribute in the view, so a page opts in by markup only:
 *
 *   data-stepper              number input gets - / + buttons (44 px, keyboard friendly)
 *   data-delivery-hint        live text: which delivery modes the chosen quantity allows (product page)
 *   (automatic)               cards and section heads below the fold fade up once when scrolled into view
 *
 * Text is always inserted with textContent, never innerHTML.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    /* ---------- Quantity steppers ----------------------------------------------------------------------------- */
    function steppers() {
        document.querySelectorAll('input[type="number"][data-stepper]').forEach(function (input) {
            if (input.closest('.ds-stepper')) { return; }
            var wrap = el('div', 'ds-stepper');
            var label = (input.labels && input.labels[0] ? input.labels[0].textContent.trim() : 'quantity').toLowerCase();
            var minus = el('button', 'ds-stepper-btn', '−');
            var plus = el('button', 'ds-stepper-btn', '+');
            minus.type = plus.type = 'button';
            minus.setAttribute('aria-label', 'Decrease ' + label);
            plus.setAttribute('aria-label', 'Increase ' + label);
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(minus);
            wrap.appendChild(input);
            wrap.appendChild(plus);

            function sync() {
                var v = parseInt(input.value, 10) || 0;
                var min = input.min === '' ? -Infinity : parseInt(input.min, 10);
                var max = input.max === '' ? Infinity : parseInt(input.max, 10);
                minus.disabled = input.disabled || v <= min;
                plus.disabled = input.disabled || v >= max;
            }
            function step(delta) {
                var v = (parseInt(input.value, 10) || 0) + delta;
                var min = input.min === '' ? -Infinity : parseInt(input.min, 10);
                var max = input.max === '' ? Infinity : parseInt(input.max, 10);
                input.value = String(Math.min(max, Math.max(min, v)));
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                sync();
            }
            minus.addEventListener('click', function () { step(-1); });
            plus.addEventListener('click', function () { step(1); });
            input.addEventListener('input', sync);
            sync();
        });
    }

    /* ---------- Delivery hint (product page) ------------------------------------------------------------------ */
    function deliveryHints() {
        document.querySelectorAll('[data-delivery-hint]').forEach(function (box) {
            var input = document.getElementById(box.dataset.quantityInput || '');
            var unitGrams = parseFloat(box.dataset.unitGrams) || 0;
            var droneMax = parseFloat(box.dataset.droneMax) || 0;
            var evMax = parseFloat(box.dataset.evMax) || 0;
            var text = box.querySelector('[data-delivery-text]');
            if (!input || !text || !unitGrams) { return; }

            function update() {
                var qty = Math.max(1, parseInt(input.value, 10) || 1);
                var kg = unitGrams * qty / 1000;
                var modes = ['pickup'];
                if (kg <= droneMax) { modes.unshift('drone'); }
                if (kg <= evMax) { modes.splice(modes.length - 1, 0, 'electric car'); }
                var list = modes.length > 1 ? modes.slice(0, -1).join(', ') + ' or ' + modes[modes.length - 1] : modes[0];
                text.textContent = 'Delivery for ' + qty + (qty > 1 ? ' units' : ' unit') + ' (' + kg.toFixed(2) + ' kg): ' + list + '.';
                box.classList.toggle('is-limited', kg > droneMax);
            }
            input.addEventListener('input', update);
            update();
        });
    }

    /* ---------- Password fields: show / hide, and a Caps Lock warning ------------------------------------------ */
    function passwords() {
        document.querySelectorAll('.office-front input[type="password"]').forEach(function (input) {
            if (input.dataset.enhanced) { return; }
            input.dataset.enhanced = '1';
            var holder = input.closest('.form-floating') || input.parentNode;
            holder.classList.add('ds-password');

            var toggle = el('button', 'ds-password-toggle', 'Show');
            toggle.type = 'button';
            toggle.setAttribute('aria-pressed', 'false');
            toggle.setAttribute('aria-controls', input.id);
            toggle.setAttribute('aria-label', 'Show password');
            holder.appendChild(toggle);

            var caps = el('p', 'ds-caps-warning', '');
            caps.setAttribute('aria-live', 'polite');
            holder.parentNode.insertBefore(caps, holder.nextSibling);

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.textContent = show ? 'Hide' : 'Show';
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                input.focus();
            });
            function capsCheck(event) {
                if (!event.getModifierState) { return; }
                caps.textContent = event.getModifierState('CapsLock') ? 'Caps Lock is on.' : '';
            }
            input.addEventListener('keydown', capsCheck);
            input.addEventListener('keyup', capsCheck);
            input.addEventListener('blur', function () { caps.textContent = ''; });
            // The form always sends the field as a password field.
            if (input.form) {
                input.form.addEventListener('submit', function () { input.type = 'password'; });
            }
        });
    }

    /* ---------- Sort a list of cards in the browser (search results) ------------------------------------------ */
    function sortLists() {
        document.querySelectorAll('select[data-sort-list]').forEach(function (select) {
            var list = document.getElementById(select.dataset.sortList);
            if (!list) { return; }
            select.addEventListener('change', function () {
                var items = Array.prototype.slice.call(list.querySelectorAll('[data-sort-item]'));
                var how = select.value;
                items.sort(function (a, b) {
                    if (how === 'price-asc') { return parseFloat(a.dataset.price) - parseFloat(b.dataset.price); }
                    if (how === 'price-desc') { return parseFloat(b.dataset.price) - parseFloat(a.dataset.price); }
                    if (how === 'name-asc') { return a.dataset.name.localeCompare(b.dataset.name); }
                    return parseInt(a.dataset.index, 10) - parseInt(b.dataset.index, 10);
                });
                items.forEach(function (item) { list.appendChild(item); });
            });
        });
    }

    /* ---------- Recent searches (kept in this browser only) --------------------------------------------------- */
    var RECENT_KEY = 'skydropRecentSearches';

    function readRecent() {
        try {
            var list = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
            return Array.isArray(list) ? list.filter(function (w) { return typeof w === 'string'; }) : [];
        } catch (e) { return []; }
    }

    function recentSearches() {
        var params = new URLSearchParams(window.location.search);
        var current = (params.get('page') === 'search' ? (params.get('q') || '') : '').trim().slice(0, 80);
        var recent = readRecent();
        if (current) {
            recent = [current].concat(recent.filter(function (w) { return w.toLowerCase() !== current.toLowerCase(); })).slice(0, 5);
            try { localStorage.setItem(RECENT_KEY, JSON.stringify(recent)); } catch (e) { /* not stored */ }
        }
        document.querySelectorAll('[data-recent-searches]').forEach(function (box) {
            var words = recent.filter(function (w) { return w.toLowerCase() !== current.toLowerCase(); });
            if (!words.length) { return; }
            words.forEach(function (word) {
                var link = el('a', 'ds-chip', word);
                link.href = box.dataset.searchUrl + '&q=' + encodeURIComponent(word);
                box.appendChild(link);
            });
            var clear = el('button', 'ds-chip ds-chip-clear', 'Clear');
            clear.type = 'button';
            clear.setAttribute('aria-label', 'Clear recent searches');
            clear.addEventListener('click', function () {
                try { localStorage.removeItem(RECENT_KEY); } catch (e) { /* nothing to clear */ }
                box.hidden = true;
            });
            box.appendChild(clear);
            box.hidden = false;
        });
    }

    /* ---------- Highlight a value when another script changes it (checkout fee and total) ------------------- */
    function flashChanges() {
        if (!window.MutationObserver) { return; }
        document.querySelectorAll('[data-flash-change]').forEach(function (node) {
            new MutationObserver(function () {
                node.classList.remove('ds-changed');
                void node.offsetWidth; // restart the animation
                node.classList.add('ds-changed');
            }).observe(node, { childList: true, characterData: true, subtree: true });
        });
    }

    /* ---------- Messages after an action: a close button, and focus for screen readers ------------------------ */
    function messages() {
        document.querySelectorAll('[data-dismissible]').forEach(function (box) {
            var close = el('button', 'ds-message-close', '');
            close.type = 'button';
            close.setAttribute('aria-label', 'Close this message');
            close.appendChild(el('i', 'bi bi-x-lg'));
            close.addEventListener('click', function () { box.remove(); });
            box.appendChild(close);
        });
    }

    /* ---------- Press "/" to jump to the search field --------------------------------------------------------- */
    function searchShortcut() {
        var field = document.querySelector('[data-search-shortcut]');
        if (!field) { return; }
        var hint = el('span', 'ds-shortcut-hint', 'Press ');
        hint.appendChild(el('kbd', 'ds-kbd', '/'));
        hint.appendChild(document.createTextNode(' to search'));
        // Under the search field: inside the home search row, or right after the search page input row.
        var home = field.closest('.ds-search');
        var row = field.closest('.d-flex');
        if (home) { home.appendChild(hint); } else if (row) { row.parentNode.insertBefore(hint, row.nextSibling); }
        document.addEventListener('keydown', function (event) {
            if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) { return; }
            var target = event.target;
            if (target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName))) { return; }
            event.preventDefault();
            field.focus();
            field.select();
        });
    }

    /* ---------- Scroll reveal: blocks below the fold fade up once when they enter the screen -------------------- */
    function scrollReveal() {
        var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (calm || !('IntersectionObserver' in window)) { return; }
        var selector = '#main .ds-card, #main .ds-empty, #main .ds-section-head, #main .ds-results-bar, #main .ds-steps';
        var fold = window.innerHeight;
        var targets = Array.prototype.filter.call(document.querySelectorAll(selector), function (node) {
            // Only leaves: never hide a card inside another revealed block, and never what is already on screen.
            return !node.parentElement.closest('.ds-card') && node.getBoundingClientRect().top > fold * 0.9;
        });
        if (!targets.length) { return; }
        var observer = new IntersectionObserver(function (entries) {
            var shown = entries.filter(function (entry) { return entry.isIntersecting; });
            shown.forEach(function (entry, i) {
                // Blocks that enter together cascade by 60 ms, at most 180 ms.
                entry.target.style.setProperty('--ds-delay', Math.min(i * 60, 180) + 'ms');
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        targets.forEach(function (node) {
            node.classList.add('ds-reveal');
            observer.observe(node);
        });
        // Safety net: anything still hidden after printing or a jump to the page end is shown.
        window.addEventListener('beforeprint', function () {
            targets.forEach(function (node) { node.classList.add('is-visible'); });
        });
    }

    ready(function () {
        scrollReveal();
        steppers();
        deliveryHints();
        passwords();
        sortLists();
        recentSearches();
        flashChanges();
        messages();
        searchShortcut();
    });
})();
