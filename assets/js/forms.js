/*
 * SKYDROP - input validation in the browser (teachers' email, point 3) and confirmations. Loaded in every office.
 * The server ALWAYS validates again with \Validator: this file only gives the message earlier, without a page load.
 *
 * A form opts in with <form data-validate>. Each field declares its rules with data- attributes:
 *   data-required                     the field must not be empty
 *   data-min-length="3"               at least 3 characters        data-max-length="120"   at most 120 characters
 *   data-email                        a valid email address
 *   data-number  (+ data-min, data-max)   a number, optionally between min and max
 *   data-integer (+ data-min, data-max)   a whole number
 *   data-pattern="^[A-Z0-9-]+$"       must match the regular expression; data-pattern-message="..." is the message
 *   data-match="password"             must equal the field with this id (password confirmation)
 *   data-after="starts_at"            a date on or after the date field with this id
 *   data-phone                        8 to 15 digits, optional + at the start
 * Messages are the same sentences as the server's Validator. The error is shown under the field, the field gets
 * aria-invalid="true", and the first wrong field receives the focus.
 *
 * Confirmation: <button data-confirm="Delete this coupon?"> (or on a <form>) asks before sending. No inline onclick,
 * so the Content-Security-Policy can forbid inline scripts.
 */
(function () {
    'use strict';

    var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    function value(field) {
        return field.type === 'checkbox' ? (field.checked ? field.value : '') : String(field.value || '').trim();
    }

    /** The first broken rule of a field, as a message, or '' when the field is valid. */
    function check(field, form) {
        var v = value(field);
        var d = field.dataset;
        if (d.required !== undefined && v === '') { return 'This field is required.'; }
        if (v === '') { return ''; }
        if (d.minLength && v.length < +d.minLength) { return 'At least ' + d.minLength + ' characters.'; }
        if (d.maxLength && v.length > +d.maxLength) { return 'At most ' + d.maxLength + ' characters.'; }
        if (d.email !== undefined && !EMAIL.test(v)) { return 'Invalid email address.'; }
        if (d.phone !== undefined && !/^\+?\d{8,15}$/.test(v.replace(/\s+/g, ''))) { return 'Write 8 to 15 digits, for example 20123456.'; }
        if (d.integer !== undefined && !/^-?\d+$/.test(v)) { return 'Must be a whole number.'; }
        if ((d.number !== undefined || d.integer !== undefined)) {
            if (d.number !== undefined && !/^-?\d+(\.\d+)?$/.test(v)) { return 'Must be a number.'; }
            var n = parseFloat(v);
            if ((d.min !== undefined && n < +d.min) || (d.max !== undefined && n > +d.max)) {
                return 'Number out of range' + (d.min !== undefined && d.max !== undefined ? ' (' + d.min + ' to ' + d.max + ').' : '.');
            }
        }
        if (d.pattern) {
            try {
                if (!new RegExp(d.pattern).test(v)) { return d.patternMessage || 'Invalid format.'; }
            } catch (e) { /* a wrong pattern never blocks the form */ }
        }
        if (d.match) {
            var other = form.querySelector('#' + d.match);
            if (other && value(other) !== v) { return d.matchMessage || 'The two passwords are different.'; }
        }
        if (d.after) {
            var start = form.querySelector('#' + d.after);
            if (start && value(start) !== '' && v < value(start)) { return 'The end must be on or after the start.'; }
        }
        return '';
    }

    /** Shows or clears the message under the field (shared look: .ds-field-error in design-system.css). */
    function show(field, message) {
        var id = (field.id || field.name) + '-client-error';
        var box = document.getElementById(id);
        if (!message) {
            field.removeAttribute('aria-invalid');
            field.classList.remove('is-invalid');
            if (box) { box.remove(); }
            return;
        }
        field.setAttribute('aria-invalid', 'true');
        field.classList.add('is-invalid');
        if (!box) {
            box = document.createElement('p');
            box.className = 'ds-field-error';
            box.id = id;
            box.setAttribute('role', 'alert');
            var anchor = field.closest('.form-floating, .ds-stepper') || field;
            anchor.parentNode.insertBefore(box, anchor.nextSibling);
            field.setAttribute('aria-describedby', ((field.getAttribute('aria-describedby') || '') + ' ' + id).trim());
        }
        box.textContent = message;
        // A server message for the same field is replaced by the live one.
        var server = field.parentNode.querySelector('.invalid-feedback, .text-red-500');
        if (server) { server.hidden = true; }
    }

    function fieldsOf(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            return el.dataset && Object.keys(el.dataset).some(function (k) {
                return ['required', 'minLength', 'maxLength', 'email', 'number', 'integer', 'pattern', 'match', 'after', 'phone'].indexOf(k) !== -1;
            });
        });
    }

    function enhance(form) {
        var tried = false;
        form.addEventListener('submit', function (event) {
            tried = true;
            var first = null;
            fieldsOf(form).forEach(function (field) {
                var message = check(field, form);
                show(field, message);
                if (message && !first) { first = field; }
            });
            if (first) {
                event.preventDefault();
                event.stopImmediatePropagation();
                first.focus();
            }
        });
        // After the first try, each field is checked again as the user corrects it.
        form.addEventListener('input', function (event) {
            if (tried && event.target.dataset) { show(event.target, check(event.target, form)); }
        });
        form.addEventListener('change', function (event) {
            if (tried && event.target.dataset) { show(event.target, check(event.target, form)); }
        });
    }

    function confirmations() {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-confirm]');
            if (button && button.tagName !== 'FORM' && !window.confirm(button.dataset.confirm)) {
                event.preventDefault();
            }
        });
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (form.dataset.confirm !== undefined && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    }

    function ready(fn) {
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
    }

    ready(function () {
        document.querySelectorAll('form[data-validate]').forEach(enhance);
        confirmations();
    });

    // For tests and other scripts: SkydropForms.check(field) returns the message of the first broken rule.
    window.SkydropForms = { check: function (field) { return check(field, field.form || document); } };
})();
