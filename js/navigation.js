// ========== NAVIGATION MANAGEMENT ==========

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.querySelector('.menu-toggle');
  const navLinks = document.querySelector('.nav-links');

  // Toggle mobile menu
  menuToggle?.addEventListener('click', () => {
    navLinks?.classList.toggle('active');
  });

  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.navbar')) {
      navLinks?.classList.remove('active');
    }
  });

  // Smooth scrolling for navigation links
  document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const sectionId = link.dataset.section;
      const section = document.getElementById(sectionId);
      section?.scrollIntoView({ behavior: 'smooth' });
      navLinks?.classList.remove('active');
    });
  });
});