// Add body class on window scroll
window.addEventListener('scroll', function(event) {
	// Get the current scroll position
	const scrollPosition = window.scrollY;
	if (scrollPosition > 100) {
		document.body.classList.add('page-scrolled');
	} else {
		document.body.classList.remove('page-scrolled');
	}
});
