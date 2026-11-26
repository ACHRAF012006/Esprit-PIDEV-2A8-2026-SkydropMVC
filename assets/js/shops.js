/*
 * S2 Mili - Shops and plan tiers.
 * 1. Front shop page: draws the small Leaflet map in #shopMap from its data-lat / data-lng / data-name.
 *    When Leaflet (window.L) is not on the page, the text and the OpenStreetMap link inside #shopMap stay.
 * 2. Owner shop form (form[data-shop-hours]): the closing time must be after the opening time, with the same
 *    message as OwnerShopController. forms.js checks the other rules; this file adds only this one.
 */
(function () {
    'use strict';

    var HOURS_MESSAGE = 'The closing time must be after the opening time.';

    function drawMap() {
        var box = document.getElementById('shopMap');
        if (!box || typeof window.L === 'undefined') { return; }
        var lat = parseFloat(box.dataset.lat);
        var lng = parseFloat(box.dataset.lng);
        if (isNaN(lat) || isNaN(lng)) { return; }
        box.innerHTML = '';
        var map = window.L.map(box, { scrollWheelZoom: false }).setView([lat, lng], 16);
        window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        window.L.marker([lat, lng]).addTo(map).bindPopup(box.dataset.name || '');
    }

    /** '' when the hours are fine (or one is still empty: forms.js asks for it), otherwise the message. */
    function hoursError(form) {
        var opens = form.querySelector('#opens_at');
        var closes = form.querySelector('#closes_at');
        if (!opens || !closes || opens.value === '' || closes.value === '') { return ''; }
        return closes.value.slice(0, 5) > opens.value.slice(0, 5) ? '' : HOURS_MESSAGE;
    }

    function showHours(form, message) {
        var closes = form.querySelector('#closes_at');
        var box = document.getElementById('closes_at-hours-error');
        if (!message) {
            if (box) { box.remove(); }
            return;
        }
        if (!box) {
            box = document.createElement('p');
            box.className = 'ds-field-error';
            box.id = 'closes_at-hours-error';
            box.setAttribute('role', 'alert');
            closes.parentNode.appendChild(box);
        }
        box.textContent = message;
        // The server message of the same rule is replaced by the live one.
        var server = document.getElementById('closes_at-error');
        if (server) { server.hidden = true; }
    }

    function hoursRule(form) {
        var tried = false;
        // Capture phase: runs before forms.js, which may stop the event when another field is wrong.
        document.addEventListener('submit', function (event) {
            if (event.target !== form) { return; }
            tried = true;
            var message = hoursError(form);
            showHours(form, message);
            if (message) { event.preventDefault(); }
        }, true);
        form.addEventListener('change', function (event) {
            if (tried && (event.target.id === 'opens_at' || event.target.id === 'closes_at')) {
                showHours(form, hoursError(form));
            }
        });
    }

    function ready(fn) {
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
    }

    ready(function () {
        drawMap();
        document.querySelectorAll('form[data-shop-hours]').forEach(hoursRule);
    });
})();
