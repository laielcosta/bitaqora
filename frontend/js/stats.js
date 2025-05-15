/* stats.js – 13 may 2025 (rev-D)
   ─ Multifiltros completos
   ─ Rellena selects por AJAX
   ─ Compatible con Lightweight-Charts v5 standalone
*/

// utilidades DOM
const $  = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

// refs
const chartBox = $('#grafico');
const tableBox = $('#tablaBox');
const fIni     = $('#fInicio');
const fFin     = $('#fFin');
const selGroup = $('#selGroup');
const chkLive  = $('#chkLive');
const btn      = $('#btnFilter');
const sUser    = $('#fUsuario');
const sProj    = $('#fProyecto');
const sTask    = $('#fTarea');
const sTag     = $('#fEtiqueta');

// gráfico
const { createChart } = LightweightCharts;
const chart = createChart(chartBox,{layout:{fontSize:12}});
let series=null, liveTimer=null;

// helpers
const toTimestamp = d => {const [y,m,dd]=d.split('-').map(Number);return{year:y,month:m,day:dd};};

function buildURL(){
  const u = new URL('../backend/stats_api.php',location.href);
  u.searchParams.set('group', selGroup.value);
  if(fIni.value) u.searchParams.set('start', fIni.value);
  if(fFin.value) u.searchParams.set('end',   fFin.value);
  if(chkLive.checked) u.searchParams.set('live','1');
  if(sUser.value) u.searchParams.set('id_usuario',  sUser.value);
  if(sProj.value) u.searchParams.set('id_proyecto', sProj.value);
  if(sTask.value) u.searchParams.set('id_tarea',    sTask.value);
  if(sTag.value)  u.searchParams.set('id_etiqueta', sTag.value);
  return u.href;
}

function renderTable(rows){
  if(!tableBox) return;
  if(!rows.length){tableBox.innerHTML='';return;}
  const head='<thead><tr><th>Item</th><th>Cantidad</th></tr></thead>';
  const body=rows.map(r=>`<tr><td>${r.label}</td><td>${r.n}</td></tr>`).join('');
  tableBox.innerHTML=`<table>${head}<tbody>${body}</tbody></table>`;
}

async function updateChart(){
  if(liveTimer){clearInterval(liveTimer);liveTimer=null;}
  const data = await fetch(buildURL(),{credentials:'include'})
                     .then(r=>{if(!r.ok)throw new Error(r.status);return r.json();});
  if(series) chart.removeSeries(series);

  // LIVE
  if(chkLive.checked){
    series = chart.addLineSeries({lineWidth:2});
    series.setData([{time:Date.now()/1000|0,value:data.total}]);
    liveTimer = setInterval(async()=>{
      const d=await fetch(buildURL()).then(r=>r.json());
      series.update({time:Date.now()/1000|0,value:d.total});
    },4000);
    renderTable([]);
    return;
  }

  // EJE TEMPORAL
  if(selGroup.value==='day'||selGroup.value==='month'){
    series = chart.addBarSeries({thinBars:true,upColor:'#0066ff',downColor:'#0066ff'});
    series.setData(data.map(r=>({
      time : selGroup.value==='day' ? toTimestamp(r.label) : `${r.label}-01`,
      value: r.n
    })));
    renderTable([]);
    return;
  }

  // EJE CATEGÓRICO
  renderTable(data);
}

// cargar opciones en selects
async function fillSelect(sel,url,idField,labelField){
  const res = await fetch(url,{credentials:'include'}).then(r=>r.json());
  res.forEach(o=>{
    const opt=document.createElement('option');
    opt.value = o[idField];
    opt.textContent = o[labelField];
    sel.appendChild(opt);
  });
}

document.addEventListener('DOMContentLoaded',()=>{
  fillSelect(sUser,'../backend/usuarios_api.php','id_usuario','nombre');
  fillSelect(sProj,'../backend/proyectos_api.php','id_proyecto','nombre');
  fillSelect(sTask,'../backend/tareas_api.php','id_tarea','nombre');
  fillSelect(sTag ,'../backend/etiquetas_api.php','id_etiqueta','nombre');
});

// eventos
[btn,selGroup,chkLive,sUser,sProj,sTask,sTag].forEach(el=>el.addEventListener('change',updateChart));

// fechas por defecto
const hoy = new Date().toISOString().slice(0,10);
fIni.value = `${hoy.slice(0,8)}01`;
fFin.value = hoy;
updateChart();
