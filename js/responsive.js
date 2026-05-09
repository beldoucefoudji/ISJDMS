/* ===================================================
   ISJ DOCS — RESPONSIVE SIDEBAR JS
   Add <script src="/js/responsive.js"></script>
   just before </body> on every dashboard page.
   =================================================== */

(function () {
    "use strict";

    function init() {

        /* -------------------------------------------
           1. Inject hamburger button + overlay
           ------------------------------------------- */
        const sidebar = document.querySelector(".sidebar");
        if (!sidebar) return;

        const btn = document.createElement("button");
        btn.className = "hamburger-btn";
        btn.setAttribute("aria-label", "Open navigation menu");
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(btn);

        const overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        document.body.appendChild(overlay);

        /* -------------------------------------------
           2. Open / close logic
           ------------------------------------------- */
        function openMenu() {
            sidebar.classList.add("open");
            overlay.classList.add("active");
            btn.innerHTML = '<i class="fas fa-times"></i>';
            btn.setAttribute("aria-label", "Close navigation menu");
            document.body.style.overflow = "hidden";
        }

        function closeMenu() {
            sidebar.classList.remove("open");
            overlay.classList.remove("active");
            btn.innerHTML = '<i class="fas fa-bars"></i>';
            btn.setAttribute("aria-label", "Open navigation menu");
            document.body.style.overflow = "";
        }

        btn.addEventListener("click", function () {
            sidebar.classList.contains("open") ? closeMenu() : openMenu();
        });

        overlay.addEventListener("click", closeMenu);

        /* Close on nav link tap (mobile UX) */
        sidebar.querySelectorAll("a, button").forEach(function (el) {
            el.addEventListener("click", function () {
                if (window.innerWidth <= 768) closeMenu();
            });
        });

        /* Close on resize to desktop */
        window.addEventListener("resize", function () {
            if (window.innerWidth > 768) closeMenu();
        });

        /* -------------------------------------------
           3. Escape key support
           ------------------------------------------- */
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && sidebar.classList.contains("open")) {
                closeMenu();
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();