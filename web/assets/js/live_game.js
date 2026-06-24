if (typeof window.pabetasSound !== 'function') window.pabetasSound = function(){};
const liveApp = document.getElementById('liveGameApp');
let lastQuestionIndex = null;
let submitting = false;
let localAnswered = false;
let lastPayload = null;
let lastRankPosition = null;
let pollTimer = null;
let hiddenOptionIndexes = [];
let shortDrafts = {};
function csrfToken(){return document.querySelector('meta[name="csrf-token"]')?.content || ''}
function h(s){return String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
function myRank(rows){ if(!rows) return null; const name = window.CURRENT_DISPLAY_NAME || ''; return rows.findIndex(r => String(r.id) === String(window.LIVE_PLAYER_ID)); }
function avatarBubble(r){return `<span class="rank-avatar">${h(r.avatar_emoji || '🚀')}</span>`}
function playerTiles(rows){
  if(!rows || !rows.length) return '<div class="empty-ranking">Belum ada siswa yang masuk.</div>';
  return `<div class="player-lobby-grid">${rows.map(r=>`<div class="player-tile"><div class="player-avatar">${h(r.avatar_emoji||'🚀')}</div><b>${h(r.display_name)}</b><small>${r.last_seen?'online':'menunggu'}</small></div>`).join('')}</div>`;
}
function rankRows(rows){
  if(!rows || !rows.length) return '<div class="empty-ranking">Belum ada peserta yang masuk.</div>';
  return `<div class="ranking-list">${rows.map((r,i)=>`<div class="rank-item ${i===0?'rank-gold':''}"><div class="rank-no">${i===0?'👑':i+1}</div><div class="rank-name">${avatarBubble(r)} ${h(r.display_name)}</div><div class="rank-score">${Number(r.score||0)} poin</div><div class="rank-correct">✅ ${Number(r.total_correct||0)}</div></div>`).join('')}</div>`;
}
function timerBar(game){
  const max = game.phase === 'ranking' ? game.ranking_seconds : game.question_seconds;
  const pct = Math.max(0, Math.min(100, (game.remaining_seconds / Math.max(1,max))*100));
  return `<div class="live-timer"><div style="width:${pct}%"></div></div><div class="live-time-text">⏱ ${game.remaining_seconds} detik ${game.bonus_time_active?' + bonus waktu':''}</div>`;
}
function updateTimerOnly(game){
  const max = game.phase === 'ranking' ? game.ranking_seconds : game.question_seconds;
  const pct = Math.max(0, Math.min(100, (game.remaining_seconds / Math.max(1,max))*100));
  const bar = liveApp?.querySelector('.live-timer > div');
  const text = liveApp?.querySelector('.live-time-text');
  if(bar) bar.style.width = `${pct}%`;
  if(text) text.textContent = `⏱ ${game.remaining_seconds} detik ${game.bonus_time_active?' + bonus waktu':''}`;
}
function teacherControls(data){
  if(window.LIVE_GAME_ROLE !== 'guru') return '';
  const g=data.game;
  const manual = g.control_mode === 'manual';
  let buttons = '';
  if(g.status === 'waiting') buttons += `<button class="btn btn-success rounded-pill live-control" data-action="start">▶️ Start</button>`;
  if(g.status === 'running' && g.phase === 'question') buttons += `<button class="btn btn-warning rounded-pill live-control" data-action="show_ranking">🏆 Tampilkan Ranking</button>`;
  if(g.status === 'running' && g.phase === 'ranking') buttons += `<button class="btn btn-primary rounded-pill live-control" data-action="next_question">➡️ Lanjut Soal</button>`;
  if(g.status === 'running') buttons += `<button class="btn btn-danger rounded-pill live-control" data-action="finish">⛔ Selesai</button>`;
  return `<div class="teacher-control-panel"><div><b>${manual?'Mode manual guru':'Mode otomatis'}</b><br><small>${manual?'Guru menentukan kapan lanjut fase.':'Sistem lanjut sesuai timer.'}</small></div><div class="d-flex flex-wrap gap-2">${buttons}</div></div>`;
}
async function doControl(action){
  try{
    const res = await fetch(window.LIVE_CONTROL_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken()},body:JSON.stringify({game_id:window.LIVE_GAME_ID,action})});
    const data = await res.json();
    if(!data.ok) alert(data.message||'Kontrol gagal.');
    loadStatus();
  }catch(e){alert('Kontrol gagal. Periksa koneksi.');}
}
function bindControls(){document.querySelectorAll('.live-control').forEach(b=>b.addEventListener('click',()=>doControl(b.dataset.action)));}
function powerupPanel(data){
  if(window.LIVE_GAME_ROLE !== 'murid' || data.has_answered) return '';
  const used = data.my_powerups || {};
  const btn = (type, icon, label) => `<button class="powerup-btn" data-powerup="${type}" ${used[type]?'disabled':''}><span>${icon}</span><b>${label}</b><small>${used[type]?'Sudah dipakai':'Sekali pakai'}</small></button>`;
  return `<div class="powerup-panel"><h4>🧠 Power-up Edukatif</h4><div class="powerup-grid">${btn('ladder_help','🪜','Bantuan Tangga')}${btn('fifty','✨','Hapus 2 Opsi')}${btn('bonus_time','⏱️','Bonus Waktu')}</div><div id="powerupMessage" class="powerup-message"></div></div>`;
}
async function usePowerup(type){
  try{
    const res = await fetch(window.LIVE_POWERUP_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken()},body:JSON.stringify({game_id:window.LIVE_GAME_ID,type})});
    const data = await res.json();
    const msg = document.getElementById('powerupMessage');
    if(msg) msg.innerHTML = `<b>${h(data.message||'')}</b>`;
    if(data.ok && type==='fifty' && data.data?.removed_options){ hiddenOptionIndexes = data.data.removed_options.map(Number); document.querySelectorAll('.answer-choice').forEach((btn,i)=>{ if(hiddenOptionIndexes.includes(i)) btn.classList.add('option-hidden'); }); }
    if(data.ok) pabetasSound('powerup'); else pabetasSound('wrong');
  }catch(e){alert('Power-up gagal dipakai.');}
}
function bindPowerups(){document.querySelectorAll('[data-powerup]').forEach(b=>b.addEventListener('click',()=>usePowerup(b.dataset.powerup)));}
function renderWaiting(data){
  const g=data.game;
  liveApp.innerHTML = `${teacherControls(data)}<div class="live-card text-center lobby-card"><div class="live-pill">Lobby Game</div><h2>${h(g.name)}</h2><p>Game belum dimulai. Siswa yang masuk akan tampil dengan avatar.</p><div class="live-code-big">${h(g.code)}</div><h3 class="mt-4">👋 Peserta Masuk</h3>${playerTiles(data.players)}</div>`;
  bindControls();
}
function renderQuestion(data){
  const g=data.game, q=data.question;
  const existingShort = document.getElementById('shortAnswer');
  const sameQuestion = lastQuestionIndex === g.current_index;
  if(existingShort && document.activeElement === existingShort && sameQuestion && !data.has_answered && !localAnswered){
    shortDrafts[g.current_index] = existingShort.value;
    updateTimerOnly(g);
    return;
  }
  if(lastQuestionIndex !== g.current_index){ localAnswered=false; lastQuestionIndex=g.current_index; hiddenOptionIndexes=[]; pabetasSound('countdown'); }
  if(window.LIVE_GAME_ROLE === 'guru'){
    liveApp.innerHTML = `${teacherControls(data)}<div class="row g-4"><div class="col-lg-7"><div class="live-card"><div class="live-pill">Soal ${g.question_number}/${g.total_questions}</div><h2>${h(q?.prompt || 'Soal belum tersedia')}</h2>${timerBar(g)}<p class="text-muted mt-3">Guru dapat menunggu timer atau klik tampilkan ranking secara manual.</p></div></div><div class="col-lg-5"><div class="live-card"><h3>Peserta</h3>${rankRows(data.ranking)}</div></div></div>`;
    bindControls(); return;
  }
  if(data.has_answered || localAnswered){ renderAnswered(data); return; }
  const savedShort = shortDrafts[g.current_index] || '';
  const options = q.type === 'multiple' ? (q.options||[]).map((opt,i)=>`<button class="answer-choice ${hiddenOptionIndexes.includes(i)?'option-hidden':''}" data-answer="${h(opt)}">${h(opt)}</button>`).join('') : `<div class="short-answer-box"><input id="shortAnswer" class="form-control form-control-lg" placeholder="Tulis jawabanmu" value="${h(savedShort)}" autocomplete="off"><button id="sendShortAnswer" class="btn btn-primary btn-lg rounded-pill mt-3">Kirim Jawaban</button><small class="text-muted d-block mt-2">Jawaban tidak akan hilang saat timer berjalan.</small></div>`;
  liveApp.innerHTML = `<div class="live-card live-question-card"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><div class="live-pill">Soal ${g.question_number}/${g.total_questions}</div><div class="live-mini-rank">Ranking berubah setelah dijawab</div></div><h2>${h(q.prompt)}</h2>${timerBar(g)}${powerupPanel(data)}<div class="answer-grid mt-4">${options}</div></div>`;
  document.querySelectorAll('.answer-choice').forEach(btn=>btn.addEventListener('click',()=>submitAnswer(btn.dataset.answer)));
  const shortInput = document.getElementById('shortAnswer');
  if(shortInput){
    shortInput.addEventListener('input',()=>{ shortDrafts[g.current_index] = shortInput.value; });
    shortInput.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); submitAnswer(shortInput.value || ''); } });
  }
  document.getElementById('sendShortAnswer')?.addEventListener('click',()=>submitAnswer(document.getElementById('shortAnswer')?.value || ''));
  bindPowerups();
}
function renderAnswered(data){
  const mine = data.my_answer;
  const feedback = mine ? (Number(mine.is_correct)===1 ? '✅ Jawaban benar!' : '💪 Belum tepat, tetap semangat!') : 'Jawaban tersimpan.';
  liveApp.innerHTML = `<div class="row g-4"><div class="col-lg-5"><div class="live-card text-center score-animate"><div class="live-pill">Jawaban Terkirim</div><h2>${feedback}</h2><p>Ranking sementara muncul sambil menunggu fase berikutnya.</p><div class="live-score-pop">+${mine ? Number(mine.score||0) : 0}</div></div></div><div class="col-lg-7"><div class="live-card"><h3>🏆 Ranking Sementara</h3>${rankRows(data.ranking)}</div></div></div>`;
}
function renderRanking(data){
  const g=data.game;
  liveApp.innerHTML = `${teacherControls(data)}<div class="live-card"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><div class="live-pill">Ranking Soal ${g.question_number}</div><div>${timerBar(g)}</div></div><h2>🏆 Peringkat Sementara</h2>${data.correct_answer?`<p class="correct-answer-box">Kunci jawaban: <b>${h(data.correct_answer)}</b></p>`:''}<p>${g.control_mode==='manual'?'Guru akan menekan lanjut soal.':'Setelah beberapa detik, game otomatis lanjut ke soal berikutnya.'}</p>${rankRows(data.ranking)}</div>`;
  bindControls();
}
function renderFinal(data){
  liveApp.innerHTML = `${teacherControls(data)}<div class="live-card final-ranking"><div class="live-pill">Game Selesai</div><h1>🎉 Ranking Keseluruhan</h1><p>Inilah peringkat akhir pada game saat ini.</p>${rankRows(data.ranking)}<div class="d-flex flex-wrap gap-2 mt-4"><a class="btn btn-primary btn-lg rounded-pill" href="${window.LIVE_GAME_ROLE==='guru' ? '../teacher/live_games.php' : 'dashboard.php'}">Kembali</a>${window.LIVE_GAME_ROLE==='guru'?`<a class="btn btn-success btn-lg rounded-pill" href="../teacher/live_recap.php?game=${window.LIVE_GAME_ID}">Lihat Rekap</a>`:''}</div></div>`;
  bindControls();
}
function render(data){
  const oldRank = lastPayload ? myRank(lastPayload.ranking) : null;
  lastPayload = data;
  const newRank = myRank(data.ranking);
  if(window.LIVE_GAME_ROLE==='murid' && oldRank !== null && newRank !== null && newRank < oldRank) pabetasSound('rankup');
  const phase = data.game.phase;
  if(data.game.status === 'waiting' || phase === 'waiting') return renderWaiting(data);
  if(phase === 'question') return renderQuestion(data);
  if(phase === 'ranking') return renderRanking(data);
  if(phase === 'final' || data.game.status === 'finished') return renderFinal(data);
}
async function loadStatus(){
  try{
    const res = await fetch(`${window.LIVE_STATUS_URL}?game=${encodeURIComponent(window.LIVE_GAME_ID)}&t=${Date.now()}`);
    const data = await res.json();
    if(!data.ok){ liveApp.innerHTML = `<div class="alert alert-danger">${h(data.message||'Game gagal dimuat')}</div>`; return; }
    if(data.players){ const me = data.players.find(p=>String(p.user_id)===String(window.CURRENT_USER_ID)); if(me) window.LIVE_PLAYER_ID = me.id; }
    render(data);
  }catch(e){ liveApp.innerHTML = '<div class="alert alert-warning">Koneksi live game terputus. Coba muat ulang halaman.</div>'; }
}
async function submitAnswer(answer){
  if(submitting) return;
  if(lastQuestionIndex !== null){ const inp = document.getElementById('shortAnswer'); if(inp) shortDrafts[lastQuestionIndex] = inp.value; }
  answer = String(answer||'').trim();
  if(!answer){ alert('Isi jawaban dulu ya.'); return; }
  submitting = true; localAnswered = true;
  liveApp.innerHTML = '<div class="live-card text-center"><h2>⏳ Menyimpan jawaban...</h2></div>';
  try{
    const res = await fetch(window.LIVE_ANSWER_URL, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken()}, body:JSON.stringify({game_id:window.LIVE_GAME_ID, answer})});
    const data = await res.json();
    if(!data.ok){ liveApp.innerHTML = `<div class="alert alert-danger">${h(data.message||'Jawaban gagal disimpan')}</div>`; submitting=false; return; }
    if(lastQuestionIndex !== null) delete shortDrafts[lastQuestionIndex];
    pabetasSound(data.is_correct ? 'correct' : 'wrong');
    renderAnswered({game:(lastPayload?.game||{}), ranking:data.ranking, my_answer:{is_correct:data.is_correct?1:0, score:data.score}});
  }catch(e){ liveApp.innerHTML = '<div class="alert alert-danger">Jawaban gagal dikirim. Periksa koneksi.</div>'; }
  submitting = false;
}
loadStatus();
pollTimer = setInterval(loadStatus, 900);
