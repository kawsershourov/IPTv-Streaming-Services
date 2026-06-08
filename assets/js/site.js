/* SunPlex.live — small front-end enhancements */
(function () {
    'use strict';

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
