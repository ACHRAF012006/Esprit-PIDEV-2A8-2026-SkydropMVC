/*
 * SKYDROP - bulk actions on the administrator's users list (S2 Youssef Mili). Loaded only by AdminUserController::index()
 * through 'pageScripts'. "Select all" checks or unchecks the boxes of this page, the Apply button stays disabled until
 * at least one box is checked, and "2 selected" is shown next to it.
 * This is only comfort: without JavaScript the button works and the server checks everything again (bulk()).
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
    }

    ready(function () {
        var form = document.getElementById('bulkForm');
        if (!form) { return; } // empty list: no bar
        var all = document.getElementById('bulkAll');
        var apply = document.getElementById('bulkApply');
        var count = document.getElementById('bulkCount');
        var boxes = Array.prototype.slice.call(document.querySelectorAll('input[data-bulk-row]'));

        function update() {
            var n = boxes.filter(function (box) { return box.checked; }).length;
            apply.disabled = n === 0;
            apply.classList.toggle('opacity-50', n === 0);
            apply.classList.toggle('cursor-not-allowed', n === 0);
            count.textContent = n > 0 ? n + ' selected' : '';
            // The header box is checked when every row is, half-checked when only some are.
            all.checked = n > 0 && n === boxes.length;
            all.indeterminate = n > 0 && n < boxes.length;
        }

        all.addEventListener('change', function () {
            boxes.forEach(function (box) { box.checked = all.checked; });
            update();
        });
        boxes.forEach(function (box) { box.addEventListener('change', update); });
        update();
    });
})();
