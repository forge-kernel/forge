document.addEventListener('DOMContentLoaded', function() {
  // Toggle sidebar on mobile
  const menuToggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  
  if (menuToggle && sidebar) {
	menuToggle.addEventListener('click', function() {
	  sidebar.classList.toggle('open');
	});
  }
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
	if (window.innerWidth <= 768 && 
		!sidebar.contains(event.target) && 
		!menuToggle.contains(event.target) && 
		sidebar.classList.contains('open')) {
	  sidebar.classList.remove('open');
	}
  });
  
  // User profile dropdown toggle
  const userMenuToggle = document.querySelector('.user-menu-toggle');
  
  if (userMenuToggle) {
	userMenuToggle.addEventListener('click', function() {

	});
  }
  
  // Handle window resize
  window.addEventListener('resize', function() {
	if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
	  sidebar.classList.remove('open');
	}
  });
  
  function initializeDashboard() {
	//console.log('Dashboard initialized');
  }
  
  initializeDashboard();
});