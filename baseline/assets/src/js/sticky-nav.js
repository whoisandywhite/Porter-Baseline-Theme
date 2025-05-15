// Add body class on window scroll
window.addEventListener('scroll', function () {
    // Get the current scroll position
    const scrollPosition = window.scrollY;
    if (scrollPosition > 100) {
        document.body.classList.add('page-scrolled');
    } else {
        document.body.classList.remove('page-scrolled');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // Create a new div element
    const newDiv = document.createElement('div');

    // Get the header content wrapper element
    const headerContentWrapper = document.querySelector('.site-header');

    // If the headerContentWrapper does not have class is-fixed, return
    // if (!headerContentWrapper || !headerContentWrapper.classList.contains('is-fixed')) {
    //     return;
    // }


    // Store the initial screen width and header height
    let initialScreenWidth = window.innerWidth;

    // Function to set and save the initial height
    function setInitialHeaderHeight() {
        if (!headerContentWrapper.dataset.initialHeight) {
            const initialHeight = headerContentWrapper.offsetHeight;
            headerContentWrapper.dataset.initialHeight = initialHeight; // Save initial height as a data attribute
            newDiv.style.height = `${initialHeight}px`; // Set spacer height
            updateHeaderHeightVariable(initialHeight); // Update CSS variable
        }
    }

    // Function to update the spacer height
    function updateSpacerHeight() {
        const height = headerContentWrapper.dataset.initialHeight || headerContentWrapper.offsetHeight;
        newDiv.style.height = `${height}px`; // Use saved height if available
        updateHeaderHeightVariable(height); // Update CSS variable
    }

    // Function to update 'top' value for sticky elements based on the current header height
    function updateStickyTop() {
        const height = headerContentWrapper.offsetHeight;

        // Get all elements with the 'is-position-sticky' class and update their 'top' value
        const stickyElements = document.querySelectorAll('.is-position-sticky');
        stickyElements.forEach(function (stickyElement) {
            stickyElement.style.top = `${height + 16}px`; // Add 16px to the header height
        });

        // Update the 'top' value for the anchor-nav
        const anchorNav = document.querySelectorAll('.anchor-nav');
        anchorNav.forEach(function (anchorNavDiv) {
            anchorNavDiv.style.top = `${height}px`;
        });
    }

    // Function to update the --header-height CSS variable
    function updateHeaderHeightVariable(height) {
        const mainMenu = document.querySelector('.mobile-menu-container');
        if (mainMenu) {
            mainMenu.style.setProperty('--header-height', `${height}px`);
        }
    }

    // Handle screen resizing
    function handleResize() {
        const currentScreenWidth = window.innerWidth;

        // Recalculate only if the screen width changes and the page is not scrolled
        if (currentScreenWidth !== initialScreenWidth && !document.body.classList.contains('page-scrolled')) {
            initialScreenWidth = currentScreenWidth; // Update stored screen width
            headerContentWrapper.dataset.initialHeight = ''; // Clear the saved height
            setInitialHeaderHeight(); // Recalculate the height
        }
    }

    if (headerContentWrapper) {
        // Insert the spacer div above the .site-header
        headerContentWrapper.parentNode.insertBefore(newDiv, headerContentWrapper);

        // Add the 'is-fixed' class to the site header
        headerContentWrapper.classList.add('is-fixed');

        // Set the initial height for the spacer
        setInitialHeaderHeight();
    }

    // Update the 'top' value for sticky elements on scroll
    window.addEventListener('scroll', function () {
        updateStickyTop();
        updateSpacerHeight(); // Ensure spacer height remains consistent
    });

    // Handle resize logic
    window.addEventListener('resize', handleResize);
});
