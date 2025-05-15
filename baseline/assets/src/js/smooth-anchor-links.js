document.addEventListener("DOMContentLoaded", function () {
    const siteHeader = document.querySelector(".site-header");

    // Smooth scrolling for all anchor links outside the anchor nav
    function handleGlobalAnchorLinks() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]:not(.anchor-nav__menu a)');

        anchorLinks.forEach(function (link) {
            link.addEventListener("click", function (e) {
                const targetId = link.getAttribute("href").substring(1);
                const targetElement = document.getElementById(targetId);

                if (targetElement) {
                    e.preventDefault();

                    const headerHeight = siteHeader ? siteHeader.offsetHeight : 0;
                    const totalOffset = headerHeight; // Adjust for site header only

                    window.scrollTo({
                        top: targetElement.offsetTop - totalOffset,
                        behavior: "smooth",
                    });
                }
            });
        });
    }

    // Initialize global anchor link handler
    handleGlobalAnchorLinks();
});
