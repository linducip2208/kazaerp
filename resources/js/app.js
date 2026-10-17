import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '@tabler/core/dist/js/tabler.min.js';
import Chart from 'chart.js/auto';

window.KazaChart = Chart;

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.getAttribute('data-confirm') || 'Yakin?')) e.preventDefault();
    });
  });
  const toast = document.querySelector('.toast.autoshow');
  if (toast && window.bootstrap) new window.bootstrap.Toast(toast, { delay: 4000 }).show();

  // Sidebar admin shell: collapse di desktop, drawer di mobile. Tanpa dependensi baru.
  const shell = document.querySelector('.admin-shell');
  const toggle = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('sidebarBackdrop');
  const closeBtn = document.getElementById('sidebarClose');
  if (shell && toggle) {
    const mq = window.matchMedia('(max-width: 991.98px)');
    const setExpanded = (open) => toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    try {
      if (!mq.matches && localStorage.getItem('kaza-sidebar') === 'collapsed') shell.classList.add('sidebar-collapsed');
    } catch (e) { /* abaikan: storage tidak tersedia */ }
    const toggleSidebar = () => {
      if (mq.matches) {
        const open = shell.classList.toggle('sidebar-open');
        setExpanded(open);
      } else {
        const collapsed = shell.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('kaza-sidebar', collapsed ? 'collapsed' : 'open'); } catch (e) { /* abaikan */ }
        setExpanded(!collapsed);
      }
    };
    const closeDrawer = () => { shell.classList.remove('sidebar-open'); setExpanded(false); };
    toggle.addEventListener('click', toggleSidebar);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
    setExpanded(shell.classList.contains('sidebar-open'));
  }
});
