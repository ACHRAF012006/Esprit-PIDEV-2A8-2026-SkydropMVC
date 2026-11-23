/*
 * S1 - Coupon form (views/admin/coupon-form.php): the value field follows the chosen coupon type.
 *   percentage     -> value required, 1 to 100, unit "%"
 *   fixed amount   -> value required, more than 0, unit "TND"
 *   free delivery  -> no value: the field is hidden and emptied (the server ignores it too)
 * The rules are data- attributes read by forms.js, so the messages are the same as the server's.
 */
(function () {
    'use strict';

    function update(select) {
        var box = document.querySelector('[data-coupon-value]');
        if (!box) { return; }
        var input = box.querySelector('input');
        var unit = box.querySelector('[data-coupon-unit]');
        var option = select.options[select.selectedIndex];
        var kind = option ? option.getAttribute('data-kind') : '';

        delete input.dataset.max;
        input.dataset.min = '0.001';
        if (kind === 'free_delivery') {
            box.hidden = true;
            input.value = '';
            input.disabled = true;
            delete input.dataset.required;
            return;
        }
        box.hidden = false;
        input.disabled = false;
        if (kind === 'percent') {
            input.dataset.required = '';
            input.dataset.min = '1';
            input.dataset.max = '100';
            unit.textContent = '(%)';
        } else if (kind === 'fixed') {
            input.dataset.required = '';
            unit.textContent = '(TND)';
        } else {
            delete input.dataset.required;
            unit.textContent = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var select = document.querySelector('[data-coupon-type]');
        if (!select) { return; }
        update(select);
        select.addEventListener('change', function () { update(select); });
    });
}());
