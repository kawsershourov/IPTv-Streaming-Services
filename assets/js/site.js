/* SunPlex.live — small front-end enhancements */
(function () {
    'use strict';

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
