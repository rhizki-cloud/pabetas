(function(){
  'use strict';

  let ctx = null;
  let music = null;
  let musicTimer = null;
  let currentContext = 'home';
  let manualEnabled = localStorage.getItem('pabetas_sound_enabled');
  let enabled = manualEnabled === null ? true : manualEnabled === '1';
  let unlocked = false;

  const melodyMap = {
    home:     {tempo: 820, wave:'sine',     bass:[196,196,247,220], melody:[392,440,494,392,330,392,440,392]},
    materi:   {tempo: 760, wave:'triangle', bass:[174,196,220,196], melody:[330,392,440,392,330,294,330,392]},
    tangga:   {tempo: 620, wave:'sine',     bass:[220,247,262,294], melody:[440,494,523,587,659,587,523,494]},
    individu: {tempo: 700, wave:'triangle', bass:[196,220,247,220], melody:[392,523,494,440,392,440,494,523]},
    tim:      {tempo: 690, wave:'square',   bass:[147,196,220,196], melody:[294,392,440,392,349,392,440,494]},
    live:     {tempo: 540, wave:'sawtooth', bass:[131,165,196,220], melody:[523,659,784,659,523,587,659,784]},
    refleksi: {tempo: 900, wave:'sine',     bass:[196,174,165,147], melody:[392,349,330,294,330,349,392,440]},
    teacher:  {tempo: 780, wave:'triangle', bass:[130,165,196,165], melody:[262,330,392,330,294,330,349,392]}
  };

  const sfx = {
    tap:       {freq:[520,650], wave:'triangle', dur:.16, vol:.045},
    nav:       {freq:[330,440,550], wave:'sine', dur:.22, vol:.045},
    select:    {freq:[420,630], wave:'triangle', dur:.18, vol:.05},
    type:      {freq:[360], wave:'sine', dur:.08, vol:.025},
    correct:   {freq:[660,880,990], wave:'sine', dur:.34, vol:.075},
    wrong:     {freq:[230,180,140], wave:'sawtooth', dur:.34, vol:.05},
    rankup:    {freq:[523,659,784,1046], wave:'triangle', dur:.45, vol:.07},
    countdown: {freq:[440], wave:'square', dur:.16, vol:.04},
    powerup:   {freq:[740,980,1180], wave:'triangle', dur:.32, vol:.07},
    finish:    {freq:[523,659,784,988,1175], wave:'sine', dur:.55, vol:.08}
  };

  function audioContext(){
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return null;
    if (!ctx) ctx = new AC();
    if (ctx.state === 'suspended') ctx.resume().catch(()=>{});
    unlocked = ctx.state === 'running';
    return ctx;
  }

  function playNote(freq, start, duration, wave, volume, destination){
    const c = audioContext();
    if (!c) return;
    const o = c.createOscillator();
    const g = c.createGain();
    o.type = wave || 'sine';
    o.frequency.setValueAtTime(freq, start);
    g.gain.setValueAtTime(0.0001, start);
    g.gain.exponentialRampToValueAtTime(Math.max(volume, 0.0002), start + 0.025);
    g.gain.exponentialRampToValueAtTime(0.0001, start + duration);
    o.connect(g);
    g.connect(destination || c.destination);
    o.start(start);
    o.stop(start + duration + 0.04);
  }

  function playSfx(type='tap'){
    if (!enabled) return;
    const c = audioContext();
    if (!c) return;
    const cfg = sfx[type] || sfx.tap;
    const now = c.currentTime;
    cfg.freq.forEach((f, i)=> playNote(f, now + (i * 0.055), cfg.dur, cfg.wave, cfg.vol, c.destination));
    animateClick(type);
  }

  function stopMusic(){
    if (musicTimer) clearInterval(musicTimer);
    musicTimer = null;
    if (music && music.gain) {
      try {
        const c = audioContext();
        const now = c ? c.currentTime : 0;
        music.gain.gain.cancelScheduledValues(now);
        music.gain.gain.setTargetAtTime(0.0001, now, 0.08);
      } catch(e) {}
    }
    music = null;
  }

  function startMusic(context){
    if (!enabled) return;
    const c = audioContext();
    if (!c) return;
    stopMusic();
    currentContext = context || document.body.dataset.soundContext || 'home';
    const cfg = melodyMap[currentContext] || melodyMap.home;
    const master = c.createGain();
    master.gain.setValueAtTime(0.0001, c.currentTime);
    master.gain.exponentialRampToValueAtTime(10, c.currentTime + 0.4);
    master.connect(c.destination);
    music = {gain: master, step: 0};

    const tick = () => {
      if (!music || !enabled || document.hidden) return;
      const now = c.currentTime;
      const i = music.step % cfg.melody.length;
      const bassIndex = Math.floor(music.step / 2) % cfg.bass.length;
      playNote(cfg.melody[i], now, 0.32, cfg.wave, 0.06, master);
      if (music.step % 2 === 0) playNote(cfg.bass[bassIndex], now, 0.55, 'sine', 0.04, master);
      music.step++;
    };
    tick();
    musicTimer = setInterval(tick, cfg.tempo);
  }

  function setButtonState(){
    const btn = document.getElementById('soundToggle');
    if (!btn) return;
    btn.textContent = enabled ? '🔊 Musik Aktif' : '🔇 Musik Mati';
    btn.classList.toggle('btn-warning', enabled);
    btn.classList.toggle('btn-outline-light', !enabled);
  }

  function toggleMusic(){
    enabled = !enabled;
    localStorage.setItem('pabetas_sound_enabled', enabled ? '1' : '0');
    if (enabled) {
      startMusic(currentContext);
      playSfx('nav');
    } else {
      stopMusic();
    }
    setButtonState();
  }

  function animateClick(type){
    const el = window.__lastSoundTarget;
    if (!el || !el.classList) return;
    el.classList.remove('sound-pop','sound-correct','sound-wrong');
    void el.offsetWidth;
    if (type === 'correct') el.classList.add('sound-correct');
    else if (type === 'wrong') el.classList.add('sound-wrong');
    else el.classList.add('sound-pop');
  }

  function bindGlobalSfx(){
    document.addEventListener('click', (e)=>{
      const target = e.target.closest('button, .btn, a, .option-box, label, .avatar-choice, .mood-option, [data-sound]');
      if (!target) return;
      window.__lastSoundTarget = target;
      const custom = target.getAttribute('data-sound');
      if (custom) playSfx(custom);
      else if (target.matches('a')) playSfx('nav');
      else if (target.matches('.option-box,label,.avatar-choice,.mood-option')) playSfx('select');
      else playSfx('tap');
      if (enabled && !music) startMusic(currentContext);
    }, true);

    document.addEventListener('change', (e)=>{
      if (e.target.matches('select, input[type="radio"], input[type="checkbox"]')) {
        window.__lastSoundTarget = e.target.closest('label,.form-check,.card,select') || e.target;
        playSfx('select');
      }
    }, true);

    let typeTimer = null;
    document.addEventListener('input', (e)=>{
      if (!e.target.matches('input[type="text"], input[type="password"], input[type="number"], textarea')) return;
      clearTimeout(typeTimer);
      typeTimer = setTimeout(()=>playSfx('type'), 40);
    }, true);
  }

  window.pabetasSound = playSfx;
  window.PabetasSound = {
    tap:()=>playSfx('tap'),
    nav:()=>playSfx('nav'),
    select:()=>playSfx('select'),
    correct:()=>playSfx('correct'),
    wrong:()=>playSfx('wrong'),
    rankup:()=>playSfx('rankup'),
    countdown:()=>playSfx('countdown'),
    powerup:()=>playSfx('powerup'),
    finish:()=>playSfx('finish'),
    startMusic:(ctx)=>startMusic(ctx || currentContext),
    stopMusic,
    toggleMusic
  };

  window.addEventListener('DOMContentLoaded', ()=>{
    currentContext = document.body.dataset.soundContext || 'home';
    bindGlobalSfx();
    setButtonState();
    const btn = document.getElementById('soundToggle');
    if (btn) btn.addEventListener('click', toggleMusic);

    // Mencoba autoplay. Jika browser memblokir, musik akan aktif setelah klik/tap pertama.
    if (enabled) {
      setTimeout(()=>startMusic(currentContext), 350);
      const unlock = ()=>{
        if (enabled && !music) startMusic(currentContext);
        playSfx('nav');
        document.removeEventListener('pointerdown', unlock);
        document.removeEventListener('keydown', unlock);
      };
      document.addEventListener('pointerdown', unlock, {once:true});
      document.addEventListener('keydown', unlock, {once:true});
    }
  });

  document.addEventListener('visibilitychange', ()=>{
    if (document.hidden) stopMusic();
    else if (enabled) setTimeout(()=>startMusic(currentContext), 250);
  });
})();
