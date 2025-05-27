// chart.js – Estadísticas Bitácora · versión 19‑may‑2025
// ▶ Métricas: "count" (nº de registros)  ·  "time" (horas totales)
// ▶ Sin modo live – soporta selects múltiples – agrupa: day | month | usuario | proyecto | tarea | etiqueta

/* eslint-env browser */

document.addEventListener('DOMContentLoaded', () => {
  /* ───────────────────────── CONSTANTES ───────────────────────── */
  const coloresPastel = [
    '#F28B82', '#FBBC04', '#FFF475', '#CCFF90', '#A7FFEB',
    '#CBF0F8', '#AECBFA', '#D7AEFB', '#FDCFE8', '#E6C9A8', '#E8EAED'
  ];

  /* ───────────────────────── ELEMENTOS DOM ───────────────────────── */
  const ctx          = document.getElementById('grafico').getContext('2d');
  const fIni         = document.getElementById('fInicio');
  const fFin         = document.getElementById('fFin');
  const selGroup     = document.getElementById('selGroup');
  const btnFilter    = document.getElementById('btnFilter');
  const tableBox     = document.getElementById('tablaBox');
  const metricRadios = document.querySelectorAll('[name="metric"]');

  /* ───────────────────────── HELPERS ───────────────────────── */
  const getMetric = () => document.querySelector('[name="metric"]:checked').value;

  const secondsToHHMM = s => {
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return `${h}h ${m.toString().padStart(2, '0')}m`;
  };

  /* ───────────────────────── CHART INIT ───────────────────────── */
  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [{
        label: 'Registros',
        data: [],
        backgroundColor: [],
        borderWidth: 0,
        tension: 0.1,
        fill: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });

  /* ───────────────────────── LISTAS DE FILTRO ───────────────────────── */
  async function cargarListas () {
    try {
      const r = await fetch('../backend/tareas_api.php', { credentials: 'include' });
      const js = await r.json();
      poblar('#fltProyecto',  js.proyectos);
      poblar('#fltTarea',     js.tareas);
      poblar('#fltEtiqueta',  js.etiquetas);
      if (js.usuarios) poblar('#fltUsuario', js.usuarios);
    } catch (e) {
      console.error('Error cargando listas', e);
    }
  }

  function poblar (sel, arr) {
    const s = document.querySelector(sel);
    if (!s) return;
    s.innerHTML = '<option value=""></option>';
    arr.forEach(o => {
      const idKey = Object.keys(o).find(k => k.startsWith('id_'));
      if (!idKey) return;
      s.insertAdjacentHTML('beforeend', `<option value="${o[idKey]}">${o.nombre}</option>`);
    });
  }

  /* ───────────────────────── URL BUILDER ───────────────────────── */
  function buildURL () {
    const url = new URL('../backend/chart_api.php', location.href);

    // Filtros múltiples
    ['fltUsuario', 'fltProyecto', 'fltTarea', 'fltEtiqueta'].forEach(id => {
      const sel = document.getElementById(id);
      if (!sel) return;
      Array.from(sel.selectedOptions)
        .filter(o => o.value)
        .forEach(o => url.searchParams.append(id.slice(3).toLowerCase() + '[]', o.value));
    });

    url.searchParams.set('group', selGroup.value || 'tarea');
    url.searchParams.set('metric', getMetric());
    if (fIni.value) url.searchParams.set('start', fIni.value);
    if (fFin.value) url.searchParams.set('end',   fFin.value);

    return url.toString();
  }

  /* ───────────────────────── TABLA RESUMEN ───────────────────────── */
  function renderTable (rows, metric) {
    if (!rows.length) { tableBox.innerHTML = ''; return; }

    const head = metric === 'time'
      ? '<thead><tr><th>Item</th><th>Horas</th></tr></thead>'
      : '<thead><tr><th>Item</th><th>Registros</th></tr></thead>';

    const body = rows.map(r => {
      const val = metric === 'time' ? secondsToHHMM(r.tiempo_seg) : r.n;
      return `<tr><td>${r.label}</td><td>${val}</td></tr>`;
    }).join('');

    tableBox.innerHTML = `<table role="table">${head}<tbody>${body}</tbody></table>`;
  }

  /* ───────────────────────── ACTUALIZAR GRÁFICA ───────────────────────── */
  async function updateChart () {
    btnFilter.disabled = true;
    try {
      const resp = await fetch(buildURL(), { credentials: 'include' });
      if (!resp.ok) throw new Error(resp.status);
      const data = await resp.json();

      const metric = getMetric();
      const valores = metric === 'time'
        ? data.map(r => (r.tiempo_seg / 3600).toFixed(2))
        : data.map(r => r.n);

      /* —— Configurar tipo de gráfica según agrupación —— */
      if (selGroup.value === 'day' || selGroup.value === 'month') {
        chart.config.type = 'bar';
      } else {
        chart.config.type = 'doughnut';
      }

      chart.data.labels = data.map(r => r.label);
      chart.data.datasets[0].data = valores;
      chart.data.datasets[0].backgroundColor = coloresPastel.slice(0, data.length);
      chart.data.datasets[0].label = metric === 'time' ? 'Horas' : 'Registros';
      chart.update();

      renderTable(data, metric);
    } catch (err) {
      console.error(err);
      tableBox.innerHTML = '<p style="color:red">Error cargando datos</p>';
    } finally {
      btnFilter.disabled = false;
    }
  }

  /* ───────────────────────── CONFIG INICIAL ───────────────────────── */
  const hoy       = new Date().toISOString().slice(0, 10);
  const inicioMes = `${hoy.slice(0, 8)}01`;
  fIni.value = inicioMes;
  fFin.value = hoy;

  /* ───────────────────────── EVENTOS ───────────────────────── */
  btnFilter.addEventListener('click',       updateChart);
  selGroup.addEventListener('change',      updateChart);
  metricRadios.forEach(r => r.addEventListener('change', updateChart));

  /* ───────────────────────── ARRANQUE ───────────────────────── */
  cargarListas().then(updateChart);
});
