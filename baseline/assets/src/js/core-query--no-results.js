document.addEventListener('DOMContentLoaded', () => {
    // Select all core query blocks on the page
    const coreQueryBlocks = document.querySelectorAll('.wp-block-query');

    coreQueryBlocks.forEach((queryBlock) => {
        // Check if the block contains a child with the class `.wp-block-post-template`
        const hasPostTemplate = queryBlock.querySelector('.wp-block-post-template');

        // If no `.wp-block-post-template` child is found, add the no-results class
        if (!hasPostTemplate) {
            queryBlock.classList.add('wp-block-query--no-results');
        }
    });
});
