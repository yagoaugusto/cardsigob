// IGOB Filter Memory
(function(){
  const GLOBAL_KEYS = ['filial','data_ini','data_fim','processo','atividade','supervisor'];
  function storageKey(userId, scope){ return `igobFilters:${userId}:${scope}`; }
  function load(userId, scope){ try { return JSON.parse(localStorage.getItem(storageKey(userId, scope)) || '{}'); } catch(e){ return {}; } }
  function save(userId, scope, data){ try { localStorage.setItem(storageKey(userId, scope), JSON.stringify(data || {})); } catch(e){} }
  function collectForm(form){
    const data = {};
    const els = form.querySelectorAll('input,select,textarea');
    els.forEach(el => {
      if(!el.name) return;
      // Normalizar nome base se for array ([])
      if(el.name.endsWith('[]')){
        const base = el.name.slice(0,-2);
        if(el.type === 'checkbox') {
          if(!data[base]) data[base] = [];
          if(el.checked) data[base].push(el.value);
        } else if(el.tagName === 'SELECT' && el.multiple) {
          data[base] = Array.from(el.selectedOptions).map(o=>o.value);
        }
      } else if(el.type === 'checkbox') {
        data[el.name] = el.checked ? (el.value || '1') : '';
      } else if(el.type === 'radio') {
        if(el.checked) data[el.name] = el.value;
      } else {
        data[el.name] = el.value;
      }
    });
    return data;
  }
  function applyValues(form, values){
    Object.entries(values || {}).forEach(([name,val]) => {
      if(val === undefined || val === null) return;
      if(Array.isArray(val)){
        const boxes = form.querySelectorAll(`[name='${name}[]']`);
        if(boxes.length){ boxes.forEach(b => { b.checked = val.map(String).includes(String(b.value)); }); return; }
        const select = form.querySelector(`select[name='${name}[]']`);
        if(select && select.multiple){ Array.from(select.options).forEach(o => { o.selected = val.map(String).includes(String(o.value)); }); }
      } else {
        // tenta campo simples
        const field = form.querySelector(`[name='${name}']`);
        if(field){ if(field.type === 'checkbox'){ field.checked = !!val && val !== '0'; } else { field.value = val; } }
      }
    });
  }
  function ensureResetButton(form, userId, page){
    if(form.querySelector('.btn-reset-filters-memory')) return;
  const btn = document.createElement('button');
  btn.type='button';
  btn.className='btn btn-sm btn-outline-warning btn-reset-filters-memory d-inline-flex align-items-center gap-1';
  btn.style.minHeight = '38px';
  btn.innerHTML = '<i class="bi bi-x-circle"></i><span>Limpar Memória</span>';
    btn.addEventListener('click', () => {
      if(!confirm('Remover filtros salvos desta página e globais?')) return;
      localStorage.removeItem(storageKey(userId,'global'));
      localStorage.removeItem(storageKey(userId,page));
      alert('Memória de filtros limpa. Recarregue ou ajuste novos filtros.');
    });
    // Tenta colocar próximo ao botão submit
    const submit = form.querySelector("button[type='submit'], input[type='submit']");
    if(submit && submit.parentElement){ submit.parentElement.appendChild(btn); }
    else { form.appendChild(btn); }
  }
  window.IGOBFilterMemory = {
    init(opts){
      const { userId, page, formSelector='#filtrosForm', autoApply=true, autoSubmit=true } = opts || {};
      if(!userId || !page) return;
      const form = document.querySelector(formSelector);
      if(!form) return;
      ensureResetButton(form, userId, page);
      const globalSaved = load(userId,'global');
      const pageSaved = load(userId,page);
      const hasQuery = !!window.location.search && window.location.search.length > 1;
      if(autoApply){
        if(!hasQuery){
          // merge com prioridade para dados específicos da página
            const merged = Object.assign({}, globalSaved, pageSaved);
            if(Object.keys(merged).length){
              applyValues(form, merged);
              if(autoSubmit){
                setTimeout(()=>{ try { form.submit(); } catch(e){} }, 60);
              }
            }
        } else {
          // Se a URL já tem query, atualiza storage após coleta
          const now = collectForm(form);
          const globalSubset = {};
          GLOBAL_KEYS.forEach(k => { if(now[k] !== undefined) globalSubset[k] = now[k]; });
          save(userId,'global', globalSubset);
          save(userId,page, now);
        }
      }
      function persist(){
        const all = collectForm(form);
        const globalSubset = {};
        GLOBAL_KEYS.forEach(k => { if(all[k] !== undefined) globalSubset[k] = all[k]; });
        save(userId,'global', globalSubset);
        save(userId,page, all);
      }
      form.addEventListener('submit', persist);
      form.addEventListener('change', persist);
    }
  };
})();