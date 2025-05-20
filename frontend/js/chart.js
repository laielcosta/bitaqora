// chart.js – Integración de Chart.js (31 may 2025, revisado 19 may 2025)
// Versión sin modo “live”
document.addEventListener('DOMContentLoaded', () => {
  // Paleta genérica
  const coloresPastel = [
    '#F28B82', '#FBBC04', '#FFF475', '#CCFF90', '#A7FFEB',
    '#CBF0F8', '#AECBFA', '#D7AEFB', '#FDCFE8', '#E6C9A8', '#E8EAED'
  ];

  // ───────────────────────── gráfica vacía
  const ctx = document.getElementById('grafico').getContext('2d');
  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: [],
      datasets: [{ label: 'Cantidad', data: [], fill: false, tension: 0.1 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { beginAtZero: true },
        y: { beginAtZero: true }
      }
    }
  });

  // ───────────────────────── elementos de la UI
  const fIni     = document.getElementById('fInicio');
  const fFin     = document.getElementById('fFin');
  const selGroup = document.getElementById('selGroup');
  const btn      = document.getElementById('btnFilter');
  const tableBox = document.getElementById('tablaBox');

  // ───────────────────────── poblar selects (proyectos, tareas…)
  async function cargarListas () {
    const r = await fetch('../backend/tareas_api.php', { credentials: 'include' });
    const data = await r.json();

    poblar('#fltProyecto',  data.proyectos);
    poblar('#fltTarea',     data.tareas);
    poblar('#fltEtiqueta',  data.etiquetas);
    if (data.usuarios) poblar('#fltUsuario', data.usuarios);
  }

  function poblar (sel, arr) {
    const s = document.querySelector(sel);
    s.innerHTML = '<option value=""></option>'; // evita duplicados
    arr.forEach(o =>
      s.insertAdjacentHTML(
        'beforeend',
        `<option value="${o.id ?? o.id_usuario}">${o.nombre}</option>`
      )
    );
  }

 cargarListas();

  // ───────────────────────── construye la URL con filtros
  function buildURL () {
    const url = new URL('../backend/chart_api.php', location.href);

    // soporta selects con multiple
    ['fltUsuario', 'fltProyecto', 'fltTarea', 'fltEtiqueta'].forEach(id => {
      const sel = document.getElementById(id);
      Array.from(sel.selectedOptions)
        .filter(o => o.value)
        .forEach(o => url.searchParams.append(id.slice(3).toLowerCase() + '[]', o.value));
    });

    url.searchParams.set('group', selGroup.value || 'tarea');
    if (fIni.value) url.searchParams.set('start', fIni.value);
    if (fFin.value) url.searchParams.set('end',   fFin.value);

    return url.toString();
  }

  // ───────────────────────── tabla auxiliar
  function renderTable (rows) {
    if (!rows.length) { tableBox.innerHTML = ''; return; }
    const head = '<thead><tr><th>Item</th><th>Cantidad</th></tr></thead>';
    const body = rows.map(r => `<tr><td>${r.label}</td><td>${r.n}</td></tr>`).join('');
    tableBox.innerHTML = `<table>${head}<tbody>${body}</tbody></table>`;
  }

  // ───────────────────────── llamada principal
  async function updateChart () {
    const resp = await fetch(buildURL(), { credentials: 'include' });
    if (!resp.ok) throw new Error(resp.status);
    const data = await resp.json();

    // —— agrupación temporal
    if (selGroup.value === 'day' || selGroup.value === 'month') {
      chart.config.type = 'bar';
      chart.data.labels           = data.map(r => r.label);
      chart.data.datasets[0].data = data.map(r => r.n);
      chart.data.datasets[0].backgroundColor = coloresPastel.slice(0, data.length);
      chart.data.datasets[0].borderWidth = 0;
      chart.update();
      renderTable([]);
      return;
    }

    // —— agrupación categórica (usuario, proyecto, tarea, etiqueta)
    chart.config.type = 'doughnut';
    chart.data.labels           = data.map(r => r.label);
    chart.data.datasets[0].data = data.map(r => r.n);
    chart.data.datasets[0].backgroundColor = coloresPastel.slice(0, data.length);
    chart.data.datasets[0].borderWidth = 0;
    chart.update();
    renderTable(data);
  }

  // ───────────────────────── config inicial
  const hoy       = new Date().toISOString().slice(0, 10);
  const inicioMes = `${hoy.slice(0, 8)}01`;
  fIni.value = inicioMes;
  fFin.value = hoy;

  // listeners
  btn.addEventListener('click',       updateChart);
  selGroup.addEventListener('change', updateChart);

  updateChart();
});
