(() => {
 for (const list of document.querySelectorAll('[data-account-list]')) {
  const input=list.querySelector('[data-account-search]');
  const rows=[...list.querySelectorAll('[data-account-row]')];
  const empty=list.querySelector('[data-account-empty]');
  const count=list.querySelector('[data-account-count]');
  const entries=rows.map(row=>({row,text:[...row.cells].slice(0,3).map(cell=>cell.textContent).join(' ').toLocaleLowerCase('id-ID')}));
  const filter=()=>{
   const words=input.value.trim().toLocaleLowerCase('id-ID').split(/\s+/).filter(Boolean);
   let visible=0;
   for(const entry of entries){entry.row.hidden=!words.every(word=>entry.text.includes(word));if(!entry.row.hidden)visible++;}
   empty.hidden=visible>0;
   count.textContent=words.length?`${visible} dari ${rows.length} akun`:`${rows.length} akun`;
  };
  input.addEventListener('input',filter);filter();
 }
})();
