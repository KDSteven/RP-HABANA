(function () {
  var sidebar  = document.getElementById('mainSidebar');
  var toggle   = document.getElementById('sidebarToggle');
  var backdrop = document.getElementById('sidebarBackdrop');

  if (!sidebar || !toggle) return;

  // --- Hide sidebar by default ---
  sidebar.classList.remove('expanded'); // ensure it's collapsed initially
  toggle.setAttribute('aria-expanded', 'false');
  if (backdrop) backdrop.classList.remove('is-open');

  function expand() {
    sidebar.classList.add('expanded');
    toggle.setAttribute('aria-expanded', 'true');
    if (backdrop) backdrop.classList.add('is-open');
  }

  function collapse() {
    sidebar.classList.remove('expanded');
    toggle.setAttribute('aria-expanded', 'false');
    if (backdrop) backdrop.classList.remove('is-open');
  }

  toggle.addEventListener('click', function () {
    sidebar.classList.contains('expanded') ? collapse() : expand();
  });

  if (backdrop) {
    backdrop.addEventListener('click', collapse);
  }

  // Close on ESC key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') collapse();
  });
})();
