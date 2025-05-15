// chart.js – Integración de Chart.js (31 may 2025)


document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('grafico').getContext('2d');
  let chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: 'Cantidad',
        data: [],
        fill: false,
        tension: 0.1
      }]
    },
options: {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      beginAtZero: true
    },
    y: {
      beginAtZero: true
    }
  }
}

  });

  const fIni     = document.getElementById('fInicio');
  const fFin     = document.getElementById('fFin');
  const selGroup = document.getElementById('selGroup');
  const chkLive  = document.getElementById('chkLive');
  const btn      = document.getElementById('btnFilter');
  const tableBox = document.getElementById('tablaBox');
  let liveTimer  = null;

  function buildURL() {
    const url = new URL('../backend/chart_api.php', location.href);
    url.searchParams.set('group', selGroup.value);
    if (fIni.value)     url.searchParams.set('start', fIni.value);
    if (fFin.value)     url.searchParams.set('end',   fFin.value);
    if (chkLive.checked) url.searchParams.set('live',  '1');
    return url.toString();
  }

  function renderTable(rows) {
    if (!rows.length) {
      tableBox.innerHTML = '';
      return;
    }
    const head = '<thead><tr><th>Item</th><th>Cantidad</th></tr></thead>';
    const body = rows.map(r => `<tr><td>${r.label}</td><td>${r.n}</td></tr>`).join('');
    tableBox.innerHTML = `<table>${head}<tbody>${body}</tbody></table>`;
  }

  async function updateChart() {
    if (liveTimer) { clearInterval(liveTimer); liveTimer = null; }
    const resp = await fetch(buildURL(), { credentials: 'include' });
    if (!resp.ok) throw new Error(resp.status);
    const data = await resp.json();

    // Modo live
    if (chkLive.checked) {
      chart.config.type = 'line';
      chart.data.labels   = [ new Date() ];
      chart.data.datasets[0].data = [ data.total ];
      chart.update();
      liveTimer = setInterval(async () => {
        const r = await fetch(buildURL(), { credentials: 'include' });
        const d = await r.json();
        chart.data.labels.push(new Date());
        chart.data.datasets[0].data.push(d.total);
        chart.update();
      }, 4000);
      renderTable([]);
      return;
    }

    // Agrupación temporal: día / mes
if (selGroup.value === 'day' || selGroup.value === 'month') {
  chart.config.type = 'bar';
  chart.data.labels = data.map(r => r.label);
  chart.data.datasets[0].data = data.map(r => r.n);
  chart.update();
  renderTable([]);
  return;
}


    // Agrupación categórica: pie chart + tabla
    chart.config.type = 'pie';
    chart.data.labels   = data.map(r => r.label);
    chart.data.datasets[0].data = data.map(r => r.n);
    chart.update();
    renderTable(data);
  }

  // Fechas por defecto: inicio de mes – hoy
  const hoy       = new Date().toISOString().slice(0,10);
  const inicioMes = `${hoy.slice(0,8)}01`;
  fIni.value = inicioMes;
  fFin.value = hoy;

  btn.addEventListener('click',     updateChart);
  selGroup.addEventListener('change', updateChart);
  chkLive.addEventListener('change',  updateChart);

  updateChart();
});
