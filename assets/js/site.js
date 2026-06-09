/* SunPlex.live — small front-end enhancements */
(function () {
    'use strict';

    /* ---------- Custom confirm modal ---------- */
    window.spConfirm = function (message, onOk, opts) {
        opts = opts || {};
        var overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML =
            '<div class="modal" role="dialog" aria-modal="true">' +
            '<div class="modal-icon">⚠️</div>' +
            '<h3 class="modal-title">' + (opts.title || 'Are you sure?') + '</h3>' +
            '<p class="modal-msg"></p>' +
            '<div class="modal-actions">' +
            '<button type="button" class="btn btn-outline" data-act="cancel">' + (opts.cancel || 'Cancel') + '</button>' +
            '<button type="button" class="btn btn-danger" data-act="ok">' + (opts.ok || 'Delete') + '</button>' +
            '</div></div>';
        overlay.querySelector('.modal-msg').textContent = message;
        document.body.appendChild(overlay);
        requestAnimationFrame(function () { overlay.classList.add('show'); });

        function close() {
            overlay.classList.remove('show');
            setTimeout(function () { overlay.remove(); }, 200);
            document.removeEventListener('keydown', onKey);
        }
        function onKey(e) { if (e.key === 'Escape') { close(); } }
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay || e.target.getAttribute('data-act') === 'cancel') { close(); }
            if (e.target.getAttribute('data-act') === 'ok') { close(); onOk(); }
        });
        document.addEventListener('keydown', onKey);
        overlay.querySelector('[data-act=ok]').focus();
    };

    // Any form/button with data-confirm="..." uses the modal instead of native confirm().
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var msg = (e.submitter && e.submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
        if (!msg || form.dataset.confirmed === '1') { return; }
        e.preventDefault();
        var submitter = e.submitter;
        window.spConfirm(msg, function () {
            form.dataset.confirmed = '1';
            if (submitter && submitter.name) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = submitter.name;
                hidden.value = submitter.value;
                form.appendChild(hidden);
            }
            form.submit();
        });
    }, true);

    // Mobile menu toggle.
    var header = document.querySelector('.site-header');
    var toggle = document.querySelector('.nav-toggle');
    if (header && toggle) {
        toggle.addEventListener('click', function () {
            var open = header.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        // Close the menu after tapping a link.
        header.querySelectorAll('.header-menu a').forEach(function (a) {
            a.addEventListener('click', function () {
                header.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
        // Reset when returning to desktop widths.
        window.addEventListener('resize', function () {
            if (window.innerWidth > 860) {
                header.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Let horizontal card scrollers respond to vertical mouse wheel.
    document.querySelectorAll('.card-scroller').forEach(function (row) {
        row.addEventListener('wheel', function (e) {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                row.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        }, { passive: false });
    });
})();
