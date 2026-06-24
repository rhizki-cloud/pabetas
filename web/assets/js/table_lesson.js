(function(){
const units=['km','hm','dam','m','dm','cm','mm'];
const names={km:'kilometer',hm:'hektometer',dam:'dekameter',m:'meter',dm:'desimeter',cm:'sentimeter',mm:'milimeter'};
const body=document.getElementById('verticalTableBody');
if(!body) return;
const valueInput=document.getElementById('tableValue');
const fromInput=document.getElementById('tableFrom');
const toInput=document.getElementById('tableTo');
const currentLabel=document.getElementById('currentValueLabel');
const directionLabel=document.getElementById('directionLabel');
const explanation=document.getElementById('tableExplanation');
let lesson={value:3,from:'km',to:'m',current:3,fromIndex:0,toIndex:3,activeIndex:0,dir:1,done:false};
function numberText(n){
  const num = Number(n);

  if (!Number.isFinite(num)) return '0';

  const rounded = Math.round((num + Number.EPSILON) * 1e12) / 1e12;

  let text = rounded.toFixed(12).replace(/\.?0+$/, '');

  if (text === '-0' || text === '') {
    text = '0';
  }

  return text.replace('.', ',');
}
function render(){
  body.innerHTML='';
  units.forEach((u,i)=>{
    const tr=document.createElement('tr');
    tr.className='';
    if(i===lesson.fromIndex) tr.classList.add('origin-row');
    if(i===lesson.toIndex) tr.classList.add('target-row');
    if(i===lesson.activeIndex) tr.classList.add('active-row');
    const passed = lesson.dir>0 ? (i>lesson.fromIndex && i<=lesson.activeIndex) : (i<lesson.fromIndex && i>=lesson.activeIndex);
    if(passed) tr.classList.add('passed-row');
    const arrow = i===lesson.fromIndex ? 'Mulai' : (i===lesson.toIndex ? 'Tujuan' : '');
    tr.innerHTML=`<td><b>${u}</b></td><td>${names[u]}</td><td class="number-cell">${i===lesson.activeIndex?numberText(lesson.current):''}</td><td>${arrow}</td>`;
    body.appendChild(tr);
  });
  currentLabel.textContent=numberText(lesson.current)+' '+units[lesson.activeIndex];
  const diff=lesson.toIndex-lesson.fromIndex;
  if(diff>0) directionLabel.textContent=`Turun ${diff} tingkat, tambah ${diff} nol`;
  else if(diff<0) directionLabel.textContent=`Naik ${Math.abs(diff)} tingkat, kurangi ${Math.abs(diff)} nol`;
  else directionLabel.textContent='Satuan sama';
}
function start(){
  const rawValue = String(valueInput.value || '0').replace(',', '.');
  const value = parseFloat(rawValue || 0);
  const from=fromInput.value, to=toInput.value;
  lesson={value,from,to,current:value,fromIndex:units.indexOf(from),toIndex:units.indexOf(to),activeIndex:units.indexOf(from),dir:units.indexOf(to)>units.indexOf(from)?1:-1,done:false};
  const diff=lesson.toIndex-lesson.fromIndex;
  if(diff===0){lesson.done=true; explanation.innerHTML=`<b>${numberText(value)} ${from} = ${numberText(value)} ${to}</b>. Satuannya sama, jadi angka tidak berubah.`;}
  else if(diff>0){explanation.innerHTML=`Dari <b>${from}</b> ke <b>${to}</b> turun ${diff} tingkat. Tekan tombol untuk menambahkan nol satu per satu.`;}
  else{explanation.innerHTML=`Dari <b>${from}</b> ke <b>${to}</b> naik ${Math.abs(diff)} tingkat. Tekan tombol untuk mengurangi nol atau membagi 10 satu per satu.`;}
  render();
}
function step(){
  if(lesson.done) return;
  if(lesson.activeIndex===lesson.toIndex){
    lesson.done=true;
    explanation.innerHTML=`✅ Hasil akhir: <b>${numberText(lesson.value)} ${lesson.from} = ${numberText(lesson.current)} ${lesson.to}</b>.`;
    return;
  }
  const before=lesson.current;
  lesson.activeIndex += lesson.dir;
  lesson.current = lesson.dir>0 ? lesson.current*10 : lesson.current/10;
  const op = lesson.dir>0 ? '×10, tambah 0' : '÷10, kurangi 0';
  explanation.innerHTML=`<span class="zero-pop">${lesson.dir>0?'+0':'−0'}</span> ${numberText(before)} menjadi <b>${numberText(lesson.current)}</b> karena ${op}.`;
  window.pabetasSound?.('materi');
  render();
  if(lesson.activeIndex===lesson.toIndex){
    setTimeout(()=>{explanation.innerHTML=`✅ Hasil akhir: <b>${numberText(lesson.value)} ${lesson.from} = ${numberText(lesson.current)} ${lesson.to}</b>.`;},450);
    lesson.done=true;
  }
}
function auto(){
  if(lesson.done || lesson.activeIndex===lesson.toIndex) start();
  const run=()=>{ if(lesson.done) return; step(); if(!lesson.done) setTimeout(run,750); };
  run();
}
document.getElementById('startTableLesson')?.addEventListener('click',start);
document.getElementById('applyZeroStep')?.addEventListener('click',step);
document.getElementById('autoZeroStep')?.addEventListener('click',auto);
document.querySelectorAll('[data-demo]').forEach(btn=>btn.addEventListener('click',()=>{
  const [v,f,t]=btn.dataset.demo.split('|'); valueInput.value=v; fromInput.value=f; toInput.value=t; start();
}));
start();
})();
