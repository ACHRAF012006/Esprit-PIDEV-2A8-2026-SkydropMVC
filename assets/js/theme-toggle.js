/*
 * SKYDROP theme switch (light / dark), shared by the three offices.
 *
 * - A button with [data-theme-toggle] (front navbar) or the back-office moon button calls window.skyTheme.toggle().
 * - The choice is kept in localStorage "darkMode" (the same key TailAdmin uses), so every office opens in the same theme.
 * - The switch is the app's one signature moment: a small penguin walks up to a brass desk lamp with a green glass shade,
 *   pulls its chain, the lamp turns on or off, and the page changes theme at the moment of the pull.
 *   Only transform and opacity are animated.
 * - With "reduce motion" turned on in the system, the theme changes at once and nothing moves.
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var busy = false;

    function isDark() {
        return root.getAttribute('data-theme') === 'dark';
    }

    function reducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /** Applies a theme everywhere: attribute for the front office, class + Alpine state for TailAdmin. */
    function setTheme(dark) {
        root.classList.add('ds-theme-transition');
        root.setAttribute('data-theme', dark ? 'dark' : 'light');
        root.classList.toggle('dark', dark);
        try {
            localStorage.setItem('darkMode', JSON.stringify(dark));
        } catch (e) { /* private mode: the theme still changes for this page */ }
        if (window.Alpine && document.body && document.body._x_dataStack) {
            var data = window.Alpine.$data(document.body);
            if (data && 'darkMode' in data) {
                data.darkMode = dark;
            }
        }
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        });
        window.setTimeout(function () { root.classList.remove('ds-theme-transition'); }, 450);
    }

    /*
     * The scene, drawn in one SVG (240 x 230, ground at y = 222): a chubby penguin on the left (happy closed eyes,
     * white face mask, head tuft) and a banker's lamp on the right (brass base and column, green glass shade, pull chain).
     * Static markup, no user data.
     */
    var SCENE =
        '<svg viewBox="0 0 240 230" aria-hidden="true" focusable="false">' +
        '<defs>' +
        '  <radialGradient id="dsg-glow" cx="50%" cy="50%" r="50%">' +
        '    <stop offset="0" stop-color="#ffd27a" stop-opacity=".75"/><stop offset="1" stop-color="#ffd27a" stop-opacity="0"/>' +
        '  </radialGradient>' +
        '  <linearGradient id="dsg-brass" x1="0" x2="1" y1="0" y2="0">' +
        '    <stop offset="0" stop-color="#9a6a1c"/><stop offset=".45" stop-color="#f3c661"/><stop offset="1" stop-color="#8a5a12"/>' +
        '  </linearGradient>' +
        '  <linearGradient id="dsg-shade" x1="0" x2="0" y1="0" y2="1">' +
        '    <stop offset="0" stop-color="#9be22d"/><stop offset="1" stop-color="#3f9a12"/>' +
        '  </linearGradient>' +
        '</defs>' +
        '<ellipse class="ds-lamp-glow" cx="168" cy="100" rx="96" ry="96" fill="url(#dsg-glow)"/>' +
        '<ellipse cx="128" cy="223" rx="104" ry="6" class="ds-scene-shadow"/>' +

        /* lamp */
        '<g class="ds-lamp">' +
        '  <ellipse cx="178" cy="216" rx="40" ry="8" fill="url(#dsg-brass)"/>' +
        '  <path d="M140 216 Q142 204 160 202 L196 202 Q214 204 216 216 Z" fill="url(#dsg-brass)"/>' +
        '  <rect x="171" y="128" width="14" height="76" rx="3" fill="url(#dsg-brass)"/>' +
        '  <path d="M175 132 V200 M178 132 V200 M181 132 V200" stroke="#8a5a12" stroke-width=".8" opacity=".55"/>' +
        '  <ellipse cx="178" cy="128" rx="11" ry="5" fill="url(#dsg-brass)"/>' +
        '  <circle cx="178" cy="120" r="6" fill="url(#dsg-brass)"/>' +
        '  <path d="M178 116 C166 104 166 94 170 84 M178 116 C192 106 194 94 190 84" fill="none" stroke="#c99633" stroke-width="5" stroke-linecap="round"/>' +
        '  <ellipse class="ds-lamp-light" cx="176" cy="82" rx="52" ry="6"/>' +
        '  <path d="M122 82 Q124 50 176 44 Q228 48 230 82 Z" fill="url(#dsg-shade)" class="ds-lamp-shade"/>' +
        '  <path d="M138 66 Q150 52 176 50" fill="none" stroke="#e7ffb8" stroke-width="5" stroke-linecap="round" opacity=".55"/>' +
        '  <circle cx="230" cy="74" r="4" fill="url(#dsg-brass)"/>' +
        '  <g class="ds-lamp-cord">' +
        '    <line x1="150" y1="84" x2="150" y2="128" stroke="#d9a441" stroke-width="2.5" stroke-dasharray="2 2.5" stroke-linecap="round"/>' +
        '    <circle cx="150" cy="132" r="4" fill="#d9a441"/>' +
        '  </g>' +
        '</g>' +

        /* penguin */
        '<g class="ds-penguin">' +
        '  <ellipse cx="62" cy="217" rx="16" ry="7" class="ds-p-orange"/>' +
        '  <ellipse cx="96" cy="217" rx="16" ry="7" class="ds-p-orange"/>' +
        '  <ellipse cx="34" cy="160" rx="11" ry="28" transform="rotate(18 34 160)" class="ds-p-dark"/>' +
        '  <ellipse cx="79" cy="164" rx="47" ry="54" class="ds-p-dark ds-p-outline"/>' +
        '  <circle cx="79" cy="104" r="42" class="ds-p-dark ds-p-outline"/>' +
        '  <path d="M72 66 Q74 54 84 56 Q80 59 82 64 Q88 58 92 62 Q84 64 84 70 Z" class="ds-p-dark"/>' +
        '  <ellipse cx="79" cy="174" rx="32" ry="40" class="ds-p-white"/>' +
        '  <circle cx="64" cy="108" r="21" class="ds-p-white"/>' +
        '  <circle cx="94" cy="108" r="21" class="ds-p-white"/>' +
        '  <ellipse cx="79" cy="120" rx="22" ry="12" class="ds-p-white"/>' +
        '  <path d="M56 106 Q62 99 68 106 M90 106 Q96 99 102 106" fill="none" stroke="#2a2233" stroke-width="3.5" stroke-linecap="round"/>' +
        '  <ellipse cx="55" cy="117" rx="5" ry="3" class="ds-p-cheek"/><ellipse cx="103" cy="117" rx="5" ry="3" class="ds-p-cheek"/>' +
        '  <path d="M69 114 Q79 108 89 114 Q85 124 79 124 Q73 124 69 114 Z" class="ds-p-orange"/>' +
        '  <g class="ds-penguin-flipper"><ellipse cx="124" cy="164" rx="11" ry="28" transform="rotate(-18 124 164)" class="ds-p-dark"/></g>' +
        '</g>' +
        '</svg>';

    function buildScene() {
        var scene = document.createElement('div');
        scene.className = 'ds-penguin-scene';
        scene.setAttribute('aria-hidden', 'true');
        scene.innerHTML = SCENE;
        document.body.appendChild(scene);
        return scene;
    }

    function play(el, frames, options) {
        return el.animate(frames, Object.assign({ fill: 'forwards', easing: 'cubic-bezier(.2,.8,.2,1)' }, options)).finished;
    }

    function wait(ms) {
        return new Promise(function (resolve) { window.setTimeout(resolve, ms); });
    }

    /** The scene: rise in, waddle to the lamp, reach the chain, pull (the theme flips here), little hop, leave. */
    function animatedSwitch(dark) {
        var scene = buildScene();
        var penguin = scene.querySelector('.ds-penguin');
        var flipper = scene.querySelector('.ds-penguin-flipper');
        var cord = scene.querySelector('.ds-lamp-cord');
        scene.classList.toggle('is-on', !isDark()); // the lamp shows the current theme: on = light
        // The penguin waits at its start point (left of the lamp) while the scene rises, so it never jumps.
        penguin.style.transform = 'translateX(-60px)';

        return play(scene, [{ transform: 'translateY(105%)' }, { transform: 'translateY(0)' }], { duration: 360 })
            .then(function () {
                return play(penguin, [
                    { transform: 'translateX(-60px) rotate(0deg)' },
                    { transform: 'translateX(-45px) rotate(-5deg)', offset: 0.25 },
                    { transform: 'translateX(-30px) rotate(5deg)', offset: 0.5 },
                    { transform: 'translateX(-15px) rotate(-5deg)', offset: 0.75 },
                    { transform: 'translateX(0) rotate(0deg)' }
                ], { duration: 620, easing: 'ease-out' });
            })
            .then(function () {
                return play(flipper, [{ transform: 'rotate(0deg)' }, { transform: 'rotate(-96deg)' }], { duration: 240 });
            })
            .then(function () {
                var pull = { duration: 380, easing: 'cubic-bezier(.3,.7,.4,1)' };
                play(cord, [{ transform: 'translateY(0)' }, { transform: 'translateY(9px)', offset: 0.45 }, { transform: 'translateY(0)' }], pull);
                play(flipper, [{ transform: 'rotate(-96deg)' }, { transform: 'translateY(9px) rotate(-96deg)', offset: 0.45 }, { transform: 'rotate(-96deg)' }], pull);
                return wait(170);
            })
            .then(function () {
                scene.classList.toggle('is-on', !dark);
                setTheme(dark);
                return wait(240);
            })
            .then(function () {
                return play(flipper, [{ transform: 'rotate(-96deg)' }, { transform: 'rotate(0deg)' }], { duration: 220 });
            })
            .then(function () {
                return play(penguin, [
                    { transform: 'translateY(0)' }, { transform: 'translateY(-10px)', offset: 0.4 }, { transform: 'translateY(0)' }
                ], { duration: 340 });
            })
            .then(function () { return wait(300); })
            .then(function () {
                return play(scene, [{ transform: 'translateY(0)' }, { transform: 'translateY(105%)' }], { duration: 320, easing: 'ease-in' });
            })
            .then(function () { scene.remove(); }, function () { scene.remove(); setTheme(dark); });
    }

    function toggle() {
        if (busy) {
            return;
        }
        var dark = !isDark();
        if (reducedMotion() || !document.body || !Element.prototype.animate) {
            setTheme(dark);
            return;
        }
        busy = true;
        animatedSwitch(dark).then(function () { busy = false; }, function () { busy = false; });
    }

    window.skyTheme = { toggle: toggle, set: setTheme, isDark: isDark };

    function wireButtons() {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', isDark() ? 'true' : 'false');
            button.setAttribute('aria-label', isDark() ? 'Switch to light mode' : 'Switch to dark mode');
            button.addEventListener('click', function (event) {
                event.preventDefault();
                toggle();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wireButtons);
    } else {
        wireButtons();
    }
})();
