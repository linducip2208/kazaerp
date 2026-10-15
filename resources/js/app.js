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
});
