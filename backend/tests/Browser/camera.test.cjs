const {test}=require('node:test');const assert=require('node:assert/strict');const vm=require('node:vm');const fs=require('node:fs');
function setup({denied=false,deferred=false}={}){
 const nodes={},listeners={},events={};let requests=0,sent=0,stopped=0,release;
 const node=k=>nodes[k]??={textContent:'Absen masuk',hidden:true,disabled:false,value:'',files:[],videoWidth:640,videoHeight:480,play:async()=>{},removeAttribute(){}};
 const stream={getTracks:()=>[{stop(){stopped++;}}]};
 const panel={dataset:{locationRequired:'0'},querySelector:node};const form={elements:{latitude:node('lat'),longitude:node('lng'),accuracy:node('acc'),selfie:node('file')},querySelector:k=>k==='[data-evidence]'?panel:node(k),addEventListener:(k,f)=>listeners[k]=f};
 const document={querySelectorAll:()=>[form],addEventListener:(k,f)=>events[k]=f,createElement:()=>({getContext:()=>({drawImage(){}}),toBlob:cb=>cb({})})};
 class Transfer{constructor(){this.files=[];this.items={add:f=>this.files.push(f)};}}
 const navigator={mediaDevices:{getUserMedia:async()=>{requests++;if(deferred)await new Promise(r=>release=r);if(denied){const e=Error();e.name='NotAllowedError';throw e;}return stream;}}};
 vm.runInNewContext(fs.readFileSync('public/attendance.js','utf8'),{document,navigator,window:{isSecureContext:true,addEventListener:(k,f)=>events[k]=f},Date,Number,Math,Promise,setTimeout,clearTimeout,URL:{createObjectURL:()=> 'blob:test',revokeObjectURL(){}},DataTransfer:Transfer,File:class{},HTMLFormElement:{prototype:{submit(){sent++;}}}});
 return {nodes,form,events,submit:()=>listeners.submit({preventDefault(){}}),counts:()=>({requests,sent,stopped}),release:()=>release()};
}
test('first attendance click opens camera; next takes and sends one photo',async()=>{const x=setup();await x.submit();assert.equal(x.counts().sent,0);assert.match(x.nodes['[data-submit]'].textContent,/Ambil foto/);await x.submit();assert.equal(x.counts().sent,1);assert.equal(x.form.elements.selfie.files.length,1);assert.equal(x.counts().stopped,1);assert.equal(x.nodes['[data-submit]'].disabled,true);});
test('cancel closes camera without submitting',async()=>{const x=setup();await x.submit();x.nodes['[data-cancel]'].onclick();assert.equal(x.counts().sent,0);assert.equal(x.counts().stopped,1);assert.equal(x.nodes['[data-submit]'].textContent,'Absen masuk');});
test('permission denied never submits and allows retry',async()=>{const x=setup({denied:true});await x.submit();assert.equal(x.counts().sent,0);assert.match(x.nodes['[data-camera-message]'].textContent,/ditolak/);assert.equal(x.nodes['[data-submit]'].disabled,false);});
test('cancel while permission is pending stops the late stream',async()=>{const x=setup({deferred:true});const pending=x.submit();x.nodes['[data-cancel]'].onclick();x.release();await pending;assert.equal(x.counts().stopped,1);assert.equal(x.counts().sent,0);assert.equal(x.nodes['[data-video]'].hidden,true);});
test('rapid repeated click does not open multiple cameras',async()=>{const x=setup({deferred:true});const pending=x.submit();await x.submit();assert.equal(x.counts().requests,1);x.release();await pending;});
