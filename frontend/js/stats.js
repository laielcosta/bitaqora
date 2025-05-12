/* stats.js – 12 may 2025 (rev‑B)
   ➜ Diseñado para lightweight‑charts **v5.0.6** (standalone)
   ➜ Agrupa: usuario · proyecto · tarea · etiqueta · day · month · live
   ------------------------------------------------------------------ */

// -------------------------------------------------- utilidades DOM --
const $ = sel => document.querySelector(sel);

// elementos de interfaz
const chartBox = $('#grafico');
const tableBox = $('#tablaBox');
const fIni     = $('#fInicio');
const fFin     = $('#fFin');
const selGroup = $('#selGroup');
const chkLive  = $('#chkLive');
const btn      = $('#btnFilter');

// ------------------------------------------------ configuramos chart --
const { createChart } = LightweightCharts; // global expuesto por la build standalone v5
const chart = createChart(chartBox, {
  layout: { fontSize: 12 },
});
let series = null;
let liveTimer = null;

// ------------------------------------------------ helper funciones --
const toTimestamp = (d) => {
  const [y, m, dd] = d.split('-').map(Number);
  return { year: y, month: m, day: dd };
};

function buildURL() {
  const url = new URL('../backend/stats_api.php', location.href);
  url.searchParams.set('group', selGroup.value);
  if (fIni.value) url.searchParams.set('start', fIni.value);
  if (fFin.value) url.searchParams.set('end',   fFin.value);
  if (chkLive.checked) url.searchParams.set('live', '1');
  return url.href;
}

function renderTable(rows) {
  if (!tableBox) return;
  if (!rows.length) { tableBox.innerHTML = ''; return; }
  const head = '<thead><tr><th>Item</th><th>Cantidad</th></tr></thead>';
  const body = rows.map(r => `<tr><td>${r.label}</td><td>${r.n}</td></tr>`).join('');
  tableBox.innerHTML = `<table>${head}<tbody>${body}</tbody></table>`;
}

// ------------------------------------------------ función principal --
async function updateChart() {
  // detener live anterior
  if (liveTimer) { clearInterval(liveTimer); liveTimer = null; }

  // obtener datos
  const data = await fetch(buildURL(), { credentials: 'include' })
                    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

  // eliminar serie previa
  if (series) chart.removeSeries(series);

  // ---- L I V E -------------------------------------------------------
  if (chkLive.checked) {
    series = chart.addLineSeries({ lineWidth: 2 });
    series.setData([{ time: Math.floor(Date.now() / 1000), value: data.total }]);
    liveTimer = setInterval(async () => {
      const d = await fetch(buildURL()).then(r => r.json());
      series.update({ time: Math.floor(Date.now() / 1000), value: d.total });
    }, 4000);
    renderTable([]);
    return;
  }

  // ---- AGRUPACIÓN TEMPORAL (día/mes) --------------------------------
  if (selGroup.value === 'day' || selGroup.value === 'month') {
    series = chart.addBarSeries({ thinBars: true, upColor: '#0066ff', downColor: '#0066ff' });
    series.setData(
      data.map(r => ({
        time: selGroup.value === 'day' ? toTimestamp(r.label) : `${r.label}-01`,
        value: r.n,
      }))
    );
    renderTable([]);
    return;
  }

  // ---- AGRUPACIÓN CATEGÓRICA ----------------------------------------
  renderTable(data); // usuario / proyecto / tarea / etiqueta
}

// ------------------------------------------------ listeners ----------
btn.addEventListener('click', updateChart);
selGroup.addEventListener('change', updateChart);
chkLive.addEventListener('change', updateChart);

// fechas por defecto (mes actual)
const hoy = new Date().toISOString().slice(0, 10);
const inicioMes = `${hoy.slice(0, 8)}01`;
fIni.value = inicioMes;
fFin.value = hoy;

// arranque inicial
updateChart();
