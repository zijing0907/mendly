// Mendly — About page
// Wires up the shared site header's burger menu (#burger-menu) to the
// right-side drawer nav (#nav-menu) included via nav.php.

document.addEventListener('DOMContentLoaded', function () {
  var burger = document.getElementById('burger-menu');
  var drawer = document.getElementById('nav-menu');

  if (!burger || !drawer) return;

  function closeDrawer() {
    burger.classList.remove('active');
    drawer.classList.remove('active');
  }

  burger.addEventListener('click', function () {
    burger.classList.toggle('active');
    drawer.classList.toggle('active');
  });

  // Close when a drawer link is clicked
  drawer.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeDrawer);
  });

  // Close when clicking outside the drawer
  document.addEventListener('click', function (e) {
    if (!drawer.classList.contains('active')) return;
    if (drawer.contains(e.target) || burger.contains(e.target)) return;
    closeDrawer();
  });

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDrawer();
  });
});