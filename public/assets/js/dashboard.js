async function loadRanking(){
  const table=document.querySelector('#rankingTable tbody'); if(!table) return;
  try{
    const res=await fetch('../api/live_ranking.php'); const data=await res.json();
    const rows=data.rows||[];
    table.innerHTML=rows.length?rows.map((r,i)=>`<tr><td><b>${i+1}</b> ${i===0?'👑':''}</td><td>${escapeHtml(r.name)}</td><td><span class="badge text-bg-warning">${r.score||0}</span></td><td>${r.total_correct||0}</td><td>${r.status==='finished'?'✅ Selesai':'🎮 Main'}</td></tr>`).join(''):'<tr><td colspan="5">Belum ada hasil.</td></tr>';
    const status=document.getElementById('liveStatus'); if(status) status.textContent='Update '+data.updated_at;
  }catch(e){ if(table) table.innerHTML='<tr><td colspan="5">Gagal memuat ranking.</td></tr>'; }
}
function escapeHtml(s){return String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
async function loadScoreChart(){
  const canvas=document.getElementById('scoreChart'); if(!canvas) return;
  const ctx=canvas.getContext('2d');
  const res=await fetch('../api/chart_data.php'); const data=await res.json(); const rows=data.rows||[];
  ctx.clearRect(0,0,canvas.width,canvas.height); ctx.font='14px sans-serif'; ctx.fillStyle='#1e293b'; ctx.fillText('Top Skor Murid',20,28);
  const max=Math.max(10,...rows.map(r=>Number(r.score||0))); const barH=24; let y=55;
  rows.forEach((r,i)=>{ const w=(Number(r.score||0)/max)*(canvas.width-180); ctx.fillStyle='#dbeafe'; ctx.fillRect(120,y,w,barH); ctx.fillStyle='#2563eb'; ctx.fillRect(120,y,Math.max(8,w),barH); ctx.fillStyle='#1e293b'; ctx.fillText((i+1)+'. '+r.name,20,y+17); ctx.fillText(r.score||0,130+w,y+17); y+=36; });
  if(!rows.length){ctx.fillText('Belum ada data hasil.',20,70)}
}
loadRanking(); loadScoreChart(); setInterval(loadRanking,3000); setInterval(loadScoreChart,7000);
