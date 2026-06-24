const list=document.getElementById('materialList'); let dragged=null;
if(list){
  list.addEventListener('dragstart',e=>{dragged=e.target.closest('.drag-row'); if(dragged) dragged.classList.add('dragging')});
  list.addEventListener('dragend',()=>{if(dragged) dragged.classList.remove('dragging'); saveOrder(); dragged=null});
  list.addEventListener('dragover',e=>{e.preventDefault(); const after=getDragAfterElement(list,e.clientY); if(!dragged) return; if(after==null) list.appendChild(dragged); else list.insertBefore(dragged,after)});
}
function getDragAfterElement(container,y){return [...container.querySelectorAll('.drag-row:not(.dragging)')].reduce((closest,child)=>{const box=child.getBoundingClientRect(); const offset=y-box.top-box.height/2; if(offset<0&&offset>closest.offset){return{offset,element:child}} return closest},{offset:Number.NEGATIVE_INFINITY}).element}
async function saveOrder(){const order=[...document.querySelectorAll('#materialList .drag-row')].map(el=>el.dataset.id); const res=await fetch('../api/reorder_materials.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken()},body:JSON.stringify({order})}); const data=await res.json(); const info=document.getElementById('reorderInfo'); if(info) info.textContent=data.message||'Tersimpan';}
