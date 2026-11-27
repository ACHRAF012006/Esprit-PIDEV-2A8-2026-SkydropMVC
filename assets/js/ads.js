/*
 * S5 Karim - Ad form (views/owner/ad-form.php), loaded after forms.js.
 * 1. Price preview: number of days (both dates included) x the price per day of the chosen placement (data-price).
 *    Only a preview: OwnerAdController computes the price again from the database.
 * 2. The two date rules that forms.js has no attribute for, with the same messages as the server:
 *    the first day is today or later (data-today = the server's date), and an ad runs at most data-max-days days.
 */
(function () {
    'use strict';

    var DAY = 86400000;

    /** "2026-12-01" -> milliseconds (UTC, so that summer time never changes the number of days), or NaN. */
    function parse(text) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text || '');
        return m ? Date.UTC(+m[1], +m[2] - 1, +m[3]) : NaN;
    }

    function days(form) {
        var start = parse(form.querySelector('#starts_at').value);
        var end = parse(form.querySelector('#ends_at').value);
        return isNaN(start) || isNaN(end) || end < start ? 0 : Math.round((end - start) / DAY) + 1;
    }

    function preview(form) {
        var select = form.querySelector('#placement_id');
        var option = select.options[select.selectedIndex];
        var price = option ? parseFloat(option.getAttribute('data-price')) : NaN;
        var n = days(form);
        var out = form.querySelector('#adPricePreview');
        if (isNaN(price) || n === 0) {
            out.textContent = 'Choose a placement and the two dates to see the price.';
            return;
        }
        out.textContent = n + ' day' + (n === 1 ? '' : 's') + ' x ' + price.toFixed(3) + ' TND = ' + (n * price).toFixed(3) + ' TND';
    }

    /** The message of the date rule broken by a field, or ''. */
    function dateRule(field, form) {
        var value = field.value.trim();
        if (field.id === 'starts_at' && value !== '' && value < form.dataset.today) {
            return 'The start must be today or later.';
        }
        if (field.id === 'ends_at' && days(form) > +form.dataset.maxDays) {
            return 'An ad runs at most ' + form.dataset.maxDays + ' days.';
        }
        return '';
    }

    /** Same look and place as the messages of forms.js. */
    function show(field, message) {
        var id = field.id + '-ads-error';
        var box = document.getElementById(id);
        if (!message) {
            if (box) {
                box.remove();
                field.removeAttribute('aria-invalid');
            }
            return;
        }
        if (!box) {
            box = document.createElement('p');
            box.className = 'ds-field-error';
            box.id = id;
            box.setAttribute('role', 'alert');
            field.parentNode.insertBefore(box, field.nextSibling);
        }
        box.textContent = message;
        field.setAttribute('aria-invalid', 'true');
        var server = field.parentNode.querySelector('.text-red-500');
        if (server) { server.hidden = true; }
    }

    /** Checks both date fields; returns the first wrong one. A field that forms.js already marks keeps its message. */
    function checkDates(form) {
        var first = null;
        ['starts_at', 'ends_at'].forEach(function (id) {
            var field = form.querySelector('#' + id);
            if (document.getElementById(id + '-client-error')) {
                show(field, '');
                field.setAttribute('aria-invalid', 'true'); // still wrong: forms.js shows its own message
                return;
            }
            var message = dateRule(field, form);
            show(field, message);
            if (message && !first) { first = field; }
        });
        return first;
    }

    function enhance(form) {
        var tried = false;
        preview(form);
        form.addEventListener('input', function () { preview(form); if (tried) { checkDates(form); } });
        form.addEventListener('change', function () { preview(form); if (tried) { checkDates(form); } });
        // forms.js runs first: when it finds an error it stops the event, so this only runs on an otherwise valid form.
        form.addEventListener('submit', function (event) {
            tried = true;
            var first = checkDates(form);
            if (first) {
                event.preventDefault();
                first.focus();
            }
        });
    }

    function ready(fn) {
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
    }

    ready(function () {
        document.querySelectorAll('form[data-ad-form]').forEach(enhance);
    });
})();
