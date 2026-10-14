(() => {
 for(const form of document.querySelectorAll('[data-request-form]')){
  const kind=form.elements.kind,scope=form.elements.scope;
  const update=()=>{
   const full=kind.value==='Sakit'||kind.value==='Cuti';
   const overtime=kind.value==='Lembur';
   if(full)scope.value='Sehari penuh';
   if(overtime)scope.value='Sebagian hari';
   form.querySelector('[data-request-scope]').hidden=full||overtime;
   const timed=overtime||(!full&&scope.value==='Sebagian hari');
   form.querySelector('[data-request-times]').hidden=!timed;
   form.querySelector('[data-request-time-help]').hidden=!timed;
   for(const name of ['start_time','end_time']){form.elements[name].required=timed;form.elements[name].disabled=!timed;}
  };
  kind.addEventListener('change',update);scope.addEventListener('change',update);update();
 }
})();
