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

    // Live visitor stats — poll the feed and update the numbers without a reload.
    var statsBar = document.querySelector('.site-stats[data-feed]');
    if (statsBar && window.fetch) {
        var feed = statsBar.getAttribute('data-feed');
        var refreshStats = function () {
            if (document.hidden) { return; }
            fetch(feed, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    statsBar.querySelectorAll('[data-stat]').forEach(function (el) {
                        var key = el.getAttribute('data-stat');
                        if (d[key] == null) { return; }
                        var val = Number(d[key]).toLocaleString();
                        if (el.textContent !== val) {
                            el.textContent = val;
                            el.classList.add('stat-bump');
                            setTimeout(function () { el.classList.remove('stat-bump'); }, 700);
                        }
                    });
                })
                .catch(function () {});
        };
        setInterval(refreshStats, 15000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { refreshStats(); } });
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

    /* ---------- Media picker ---------- */
    // Buttons with data-media-target="<selector>" + data-media-url open the picker;
    // choosing an item sets that input's value (or uploads a new file first).
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-media-target]');
        if (!trigger) { return; }
        e.preventDefault();
        var input = document.querySelector(trigger.getAttribute('data-media-target'));
        var endpoint = trigger.getAttribute('data-media-url');
        if (input && endpoint) { openMediaPicker(input, endpoint); }
    });

    function openMediaPicker(input, endpoint) {
        var overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML =
            '<div class="modal mp-modal" role="dialog" aria-modal="true">' +
            '<div class="mp-head"><strong>Media library</strong>' +
            '<button type="button" class="mp-close btn btn-ghost btn-sm" aria-label="Close">&times;</button></div>' +
            '<div class="mp-upload">' +
            '<input type="file" class="mp-file" multiple accept="image/*,video/mp4,video/webm,audio/mpeg,.pdf">' +
            '<button type="button" class="btn btn-primary btn-sm mp-upload-btn">Upload new</button>' +
            '<span class="muted" style="font-size:12px;">or pick an existing file below</span>' +
            '</div>' +
            '<div class="mp-body"><p class="empty" style="padding:24px;">Loading…</p></div>' +
            '</div>';
        document.body.appendChild(overlay);
        requestAnimationFrame(function () { overlay.classList.add('show'); });

        var body = overlay.querySelector('.mp-body');
        function close() { overlay.classList.remove('show'); setTimeout(function () { overlay.remove(); }, 200); document.removeEventListener('keydown', onKey); }
        function onKey(ev) { if (ev.key === 'Escape') { close(); } }
        document.addEventListener('keydown', onKey);

        function loadGrid(page) {
            body.innerHTML = '<p class="empty" style="padding:24px;">Loading…</p>';
            fetch(endpoint + '?picker=1&page=' + (page || 1), { credentials: 'same-origin' })
                .then(function (r) { return r.text(); })
                .then(function (html) { body.innerHTML = html; });
        }
        loadGrid(1);

        overlay.addEventListener('click', function (ev) {
            if (ev.target === overlay || ev.target.closest('.mp-close')) { close(); return; }
            var item = ev.target.closest('.mp-item');
            if (item) {
                input.value = item.getAttribute('data-url');
                input.dispatchEvent(new Event('change', { bubbles: true }));
                close();
                return;
            }
            var pg = ev.target.closest('.page-link');
            if (pg && !pg.classList.contains('disabled') && !pg.classList.contains('active')) {
                ev.preventDefault();
                loadGrid(parseInt(pg.getAttribute('data-page'), 10) || 1);
            }
        });

        overlay.querySelector('.mp-upload-btn').addEventListener('click', function () {
            var fileInput = overlay.querySelector('.mp-file');
            if (!fileInput.files.length) { alert('Choose file(s) to upload.'); return; }
            var fd = new FormData();
            fd.append('op', 'upload');
            fd.append('ajax', '1');
            var csrf = document.querySelector('input[name=_csrf]');
            if (csrf) { fd.append('_csrf', csrf.value); }
            for (var i = 0; i < fileInput.files.length; i++) { fd.append('files[]', fileInput.files[i]); }
            var self = this; self.textContent = 'Uploading…'; self.disabled = true;
            fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function () { self.textContent = 'Upload new'; self.disabled = false; fileInput.value = ''; loadGrid(1); })
                .catch(function () { self.textContent = 'Upload new'; self.disabled = false; });
        });
    }
})();
