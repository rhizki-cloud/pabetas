(function(){
  const units=['km','hm','dam','m','dm','cm','mm'];
  const names=['kilometer','hektometer','dekameter','meter','desimeter','sentimeter','milimeter'];
  const colors=['#bfdbfe','#ddd6fe','#fecdd3','#fde68a','#bbf7d0','#fed7aa','#e9d5ff'];

  const canvas=document.getElementById('ladderCanvas');
  if(!canvas) return;
  const ctx=canvas.getContext('2d');

  let pos=3;
  let anim=null;
  let pauseTimer=null;
  let runId=0;
  let path=[];
  let activeFrom=3;
  let activeTo=4;
  let currentStepText='Siap bergerak';
  let floatText='';
  let floatAlpha=0;

  function w(){ return canvas.clientWidth || 980; }
  function h(){ return canvas.clientHeight || 540; }

  function resize(){
    const ratio=window.devicePixelRatio || 1;
    const width=canvas.parentElement?.clientWidth || 980;
    const height = width < 576
  ? Math.max(560, width * 1.35)
  : Math.max(520, Math.min(640, width * 0.56));
    canvas.style.width='100%';
    canvas.style.height=height+'px';
    canvas.width=width*ratio;
    canvas.height=height*ratio;
    ctx.setTransform(ratio,0,0,ratio,0,0);
    draw();
  }

  function coords(i){
    const width = w();
    const height = h();
    const small = width < 760;
  
    const leftPad = small ? 18 : 52;
    const rightPad = small ? 22 : 72;
    const topPad = small ? 122 : 150;
    const bottomPad = small ? 96 : 118;
  
    const bw = small ? Math.min(52, Math.max(42, width / 8.4)) : 92;
    const bh = small ? 42 : 58;
  
    const startX = leftPad;
    const endX = Math.max(startX + (bw * 6.4), width - rightPad - bw);
    const step = (endX - startX) / 6;
  
    const startY = height - bottomPad;
    const endY = topPad;
    const rise = (startY - endY) / 6;
  
    return {
      x: startX + i * step,
      y: startY - i * rise,
      step,
      bw,
      bh,
      small
    };
  }

  function rr(x,y,w,h,r){
    ctx.beginPath();
    ctx.moveTo(x+r,y);
    ctx.arcTo(x+w,y,x+w,y+h,r);
    ctx.arcTo(x+w,y+h,x,y+h,r);
    ctx.arcTo(x,y+h,x,y,r);
    ctx.arcTo(x,y,x+w,y,r);
    ctx.closePath();
  }

  function drawCharacter(x,y,scale){
    ctx.save();
    ctx.translate(x,y);
    ctx.scale(scale,scale);
    ctx.fillStyle='#fff';
    ctx.strokeStyle='#10213f';
    ctx.lineWidth=3;
    ctx.beginPath();
    ctx.arc(0,-36,15,0,Math.PI*2);
    ctx.fill();
    ctx.stroke();
    ctx.fillStyle='#38bdf8';
    rr(-16,-18,32,40,13);
    ctx.fill();
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(-15,-4);ctx.lineTo(-30,10);
    ctx.moveTo(15,-4);ctx.lineTo(30,10);
    ctx.moveTo(-7,22);ctx.lineTo(-17,42);
    ctx.moveTo(7,22);ctx.lineTo(17,42);
    ctx.stroke();
    ctx.fillStyle='#10213f';
    ctx.beginPath();
    ctx.arc(-5,-39,2,0,Math.PI*2);
    ctx.arc(5,-39,2,0,Math.PI*2);
    ctx.fill();
    ctx.restore();
  }

  function drawBadge(text,x,y,type){
    const pad=12;
    ctx.font='900 12px system-ui';
    const tw=ctx.measureText(text).width;
    const bw=tw+(pad*2), bh=28;

    // Clamp badge agar tidak keluar canvas ketika posisi di km/mm.
    const safeX=Math.max(bw/2+8, Math.min(w()-bw/2-8, x));
    const safeY=Math.max(10, Math.min(h()-bh-10, y));

    ctx.fillStyle=type==='from'?'#dbeafe':'#dcfce7';
    ctx.strokeStyle=type==='from'?'#2563eb':'#16a34a';
    ctx.lineWidth=2;
    rr(safeX-bw/2,safeY,bw,bh,14);
    ctx.fill();
    ctx.stroke();
    ctx.fillStyle=type==='from'?'#1d4ed8':'#166534';
    ctx.textAlign='center';
    ctx.fillText(text,safeX,safeY+18);
  }

  function cardCenter(i){
    const c=coords(i);
    return {x:c.x+c.bw/2,y:c.y+c.bh/2,c};
  }

  function draw(){
    const width=w(), height=h();

    // Bersihkan seluruh canvas setiap frame. Ini mencegah gambar karakter
    // terlihat bertumpuk ketika animasi dipanggil berulang.
    ctx.clearRect(-2,-2,width+4,height+4);

    const g=ctx.createLinearGradient(0,0,width,height);
    g.addColorStop(0,'#e0f2fe');
    g.addColorStop(.55,'#fff7ed');
    g.addColorStop(1,'#ecfccb');
    ctx.fillStyle=g;
    rr(0,0,width,height,28);
    ctx.fill();

    ctx.fillStyle='#0f172a';
    ctx.textAlign='left';
    ctx.font=`900 ${width<760?22:30}px system-ui`;
    ctx.fillText('Tangga Satuan',24,42);
    ctx.font=`800 ${width<760?12:15}px system-ui`;
    ctx.fillStyle='#475569';
    ctx.fillText('Turun = ×10 tambah 0 • Naik = ÷10 kurangi 0',24,68);

    ctx.fillStyle='#1e3a8a';
    ctx.font=`900 ${width<760?12:15}px system-ui`;
    ctx.fillText(currentStepText,24,94);

    // Jalur dasar
    ctx.strokeStyle='#94a3b8';
    ctx.lineWidth=6;
    ctx.lineCap='round';
    ctx.beginPath();
    for(let i=0;i<units.length;i++){
      const p=cardCenter(i);
      if(i===0) ctx.moveTo(p.x,p.y);
      else ctx.lineTo(p.x,p.y);
    }
    ctx.stroke();

    // Jalur aktif berdasarkan pilihan dari kontrol, bukan posisi lama.
    if(path.length>1){
      ctx.strokeStyle='#f97316';
      ctx.lineWidth=7;
      ctx.setLineDash([10,8]);
      ctx.beginPath();
      path.forEach((i,k)=>{
        const p=cardCenter(i);
        if(k===0) ctx.moveTo(p.x,p.y);
        else ctx.lineTo(p.x,p.y);
      });
      ctx.stroke();
      ctx.setLineDash([]);
    }

    // Kartu satuan
    for(let i=0;i<units.length;i++){
      const c=coords(i);
      const isFrom=i===activeFrom;
      const isTo=i===activeTo;
      ctx.fillStyle=colors[i];
      rr(c.x,c.y,c.bw,c.bh,16);
      ctx.fill();
      ctx.strokeStyle=isFrom?'#2563eb':isTo?'#16a34a':'#1e293b';
      ctx.lineWidth=isFrom||isTo?5:2.5;
      ctx.stroke();

      ctx.textAlign='center';
      ctx.fillStyle='#0f172a';
      ctx.font=`900 ${c.small?20:28}px system-ui`;
      ctx.fillText(units[i],c.x+c.bw/2,c.y+(c.small?30:38));
      ctx.font=`800 ${c.small?9:12}px system-ui`;
      ctx.fillStyle='#475569';
      ctx.fillText(names[i],c.x+c.bw/2,c.y+c.bh+17);

      if(isFrom) drawBadge('DARI',c.x+c.bw/2,c.y-32,'from');
      if(isTo) drawBadge('KE',c.x+c.bw/2,c.y+c.bh+25,'to');

      if(i<units.length-1){
        const a=coords(i), b=coords(i+1);
        const bx=(a.x+b.x+a.bw)/2-8, by=(a.y+b.y+a.bh)/2-8;
        ctx.fillStyle='#fff';
        ctx.strokeStyle='#cbd5e1';
        ctx.lineWidth=2;
        rr(bx,by,c.small?38:48,24,12);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle='#0f172a';
        ctx.font=`900 ${c.small?10:12}px system-ui`;
        ctx.fillText('×10',bx+(c.small?19:24),by+17);
      }
    }

    // Posisi karakter interpolasi, bukan dibulatkan, agar geraknya benar-benar mengikuti tangga.
    const left=Math.floor(pos);
    const right=Math.ceil(pos);
    const t=pos-left;
    const p1=cardCenter(left);
    const p2=cardCenter(right);
    const rawX=p1.x+(p2.x-p1.x)*t;
    const rawY=p1.y+(p2.y-p1.y)*t-62;

    // Karakter diberi area aman supaya tidak menabrak kartu mm/dam
    // atau keluar panel pada layar besar maupun kecil.
    const x=Math.max(58, Math.min(width-92, rawX));
    const y=Math.max(105, Math.min(height-88, rawY));
    const scale=Math.max(.62, Math.min(.86, .72+(pos-3)*0.02));
    drawCharacter(x,y,scale);

    if(floatAlpha>0){
      ctx.save();
      ctx.globalAlpha=floatAlpha;
      ctx.fillStyle='#f97316';
      ctx.textAlign='center';
      ctx.font=`900 ${width<760?22:34}px system-ui`;
      ctx.fillText(floatText,x,y-54);
      ctx.restore();
    }

    ctx.textAlign='left';
  }

  function numberText(n){
    // Jangan pakai toFixed(4), karena 0.000003 akan dibulatkan menjadi 0.
    // Untuk konversi naik dari mm ke hm/km, angka desimal kecil harus tetap terlihat.
    const num=Number(n);
    if(!Number.isFinite(num)) return '0';
    const rounded=Math.round((num + Number.EPSILON) * 1e12) / 1e12;
    let text=rounded.toFixed(12).replace(/\.?0+$/,'');
    if(text==='-0' || text==='') text='0';
    // Format Indonesia: 0,000003 agar siswa melihat nol desimalnya dengan jelas.
    return text.replace('.', ',');
  }

  function buildSteps(value,a,b){
    const dir=b>a?1:-1;
    const steps=[];
    let current=value;
    if(a===b) return steps;
    for(let i=a;dir>0?i<b:i>b;i+=dir){
      const next=dir>0?current*10:current/10;
      steps.push({
        from:units[i],
        to:units[i+dir],
        before:numberText(current),
        after:numberText(next),
        op:dir>0?'×10':'÷10',
        zero:dir>0?'+0':'−0'
      });
      current=next;
    }
    return steps;
  }

  function setTrack(steps){
    const track=document.getElementById('zeroTrack');
    if(!track) return;
    track.innerHTML='';
    if(!steps.length){
      track.innerHTML='<span>Satuan asal dan tujuan sama. Tidak ada perpindahan.</span>';
      return;
    }
    steps.forEach((s,idx)=>{
      const pill=document.createElement('span');
      pill.className='zero-pill';
      pill.innerHTML=`<b>${s.before}</b> ${s.op} <b>${s.after}</b> <em>${s.from}→${s.to}</em>`;
      pill.style.animationDelay=(idx*.15)+'s';
      track.appendChild(pill);
    });
  }


  function stopCurrentAnimation(){
    runId++;
    if(anim) cancelAnimationFrame(anim);
    if(pauseTimer) clearTimeout(pauseTimer);
    anim=null;
    pauseTimer=null;
    floatAlpha=0;
  }

  function animateFromTo(from,to,steps){
    stopCurrentAnimation();
    const myRun=runId;
    activeFrom=from;
    activeTo=to;
    pos=from;
    path=[];

    const dir=to>from?1:to<from?-1:0;
    if(dir===0){
      path=[from];
      currentStepText='Tidak bergerak karena satuannya sama.';
      draw();
      return;
    }

    for(let i=from;dir>0?i<=to:i>=to;i+=dir) path.push(i);
    currentStepText=`Mulai dari ${units[from]} menuju ${units[to]}.`;
    draw();

    let idx=0;
    function moveOne(){
      if(myRun!==runId) return;
      if(idx>=path.length-1){
        pos=to;
        floatAlpha=0;
        currentStepText=`Selesai di ${units[to]}. Hasil akhir sudah sesuai kontrol.`;
        draw();
        return;
      }
      const start=path[idx];
      const end=path[idx+1];
      const s=steps[idx];
      let t=0;
      floatText=s?.zero || '';
      currentStepText=s ? `${s.before} ${s.from} → ${s.after} ${s.to} (${s.op})` : '';

      function frame(){
        if(myRun!==runId) return;
        t+=0.018;
        const eased=t<1 ? 1-Math.pow(1-t,3) : 1;
        pos=start+(end-start)*Math.min(1,eased);
        floatAlpha=Math.max(0,1-t);
        draw();
        if(t<1){
          anim=requestAnimationFrame(frame);
        }else{
          pos=end;
          idx++;
          pauseTimer=setTimeout(moveOne,260);
        }
      }
      frame();
    }

    pauseTimer=setTimeout(moveOne,320);
  }

  function convert(){
    const rawValue=String(document.getElementById('convertValue').value || '0').replace(',', '.');
    const value=parseFloat(rawValue || 0);
    const from=document.getElementById('fromUnit').value;
    const to=document.getElementById('toUnit').value;
    const a=units.indexOf(from), b=units.indexOf(to);
    if(a<0 || b<0) return;

    const diff=b-a;
    const result=value*Math.pow(10,diff);
    const stepsCount=Math.abs(diff);
    const factor=Math.pow(10,stepsCount);
    let text='Satuan asal dan tujuan sama.';
    if(diff>0) text=`Turun ${stepsCount} tingkat, dikali ${factor}.`;
    if(diff<0) text=`Naik ${stepsCount} tingkat, dibagi ${factor}.`;

    const resultBox=document.getElementById('convertResult');
    if(resultBox){
      resultBox.innerHTML=`<div class="fw-bold fs-4">${numberText(value)} ${from} = ${numberText(result)} ${to}</div><small>${text}</small>`;
    }

    const steps=buildSteps(value,a,b);
    setTrack(steps);
    window.pabetasSound?.('tangga');
    animateFromTo(a,b,steps);
  }

  document.getElementById('animateConvert')?.addEventListener('click',convert);
  document.getElementById('demoConvert')?.addEventListener('click',()=>{
    document.getElementById('convertValue').value=3;
    document.getElementById('fromUnit').value='km';
    document.getElementById('toUnit').value='m';
    convert();
  });
  document.getElementById('reverseDemo')?.addEventListener('click',()=>{
    document.getElementById('convertValue').value=3000;
    document.getElementById('fromUnit').value='mm';
    document.getElementById('toUnit').value='m';
    convert();
  });

  document.getElementById('fromUnit')?.addEventListener('change',()=>{
    stopCurrentAnimation();
    activeFrom=units.indexOf(document.getElementById('fromUnit').value);
    pos=activeFrom;
    path=[activeFrom, units.indexOf(document.getElementById('toUnit').value)].filter(i=>i>=0);
    currentStepText=`Karakter siap mulai dari ${units[activeFrom]}.`;
    draw();
  });
  document.getElementById('toUnit')?.addEventListener('change',()=>{
    stopCurrentAnimation();
    activeTo=units.indexOf(document.getElementById('toUnit').value);
    currentStepText=`Tujuan diatur ke ${units[activeTo]}. Klik Gerakkan Perlahan.`;
    draw();
  });

  window.addEventListener('resize',resize);
  resize();
})();
