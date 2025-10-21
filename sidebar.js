(function () {
  var sidebar  = document.getElementById('mainSidebar');
  var toggle   = document.getElementById('sidebarToggle');

  // Optional backdrop (add <div class="sidebar-backdrop" id="sidebarBackdrop"></div> just after .sidebar)
  var backdrop = document.getElementById('sidebarBackdrop');

  if (!sidebar || !toggle) return;

  function expand() {
    sidebar.classList.add('expanded');
    toggle.setAttribute('aria-expanded', 'true');
    if (backdrop) backdrop.classList.add('is-open');
    // Lock scroll behind if you want:
    // document.documentElement.style.overflow = 'hidden';
  }
  function collapse() {
    sidebar.classList.remove('expanded');
    toggle.setAttribute('aria-expanded', 'false');
    if (backdrop) backdrop.classList.remove('is-open');
    // document.documentElement.style.overflow = '';
  }

  toggle.addEventListener('click', function () {
    sidebar.classList.contains('expanded') ? collapse() : expand();
  });

  if (backdrop) {
    backdrop.addEventListener('click', collapse);
  }

  // Close on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') collapse();
  });
})();