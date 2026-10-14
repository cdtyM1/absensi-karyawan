const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
function setup(required=true){
 const nodes={};const node=key=>nodes[key]??=( {disabled:false,hidden:true,value:'',textContent:'',files:[],removeAttribute(){}} );
 const listeners={},events={},timers=new Map(),cleared=[];let success,error,watchCalls=0,id=0;
 const panel={querySelector:node,dataset:{locationRequired:required?'1':'0'}};const form={elements:{latitude:node('lat'),longitude:node('lng'),accuracy:node('accuracy'),selfie:node('file')},querySelector:key=>key==='[data-evidence]'?panel:node(key),addEventListener:(k,f)=>listeners[k]=f};
 const document={querySelectorAll:()=>[form],addEventListener(){}};
 const navigator={geolocation:{watchPosition(s,e,options){success=s;error=e;watchCalls++;assert.equal(options.maximumAge,0);return 7;},clearWatch:n=>cleared.push(n)}};
 vm.runInNewContext(fs.readFileSync('public/attendance.js','utf8'),{document,navigator,window:{isSecureContext:true,addEventListener:(k,f)=>events[k]=f},Date,Number,Math,Promise,setTimeout:f=>{timers.set(++id,f);return id;},clearTimeout:n=>timers.delete(n),URL:{revokeObjectURL(){}}});
 return {nodes,form,listeners,success:p=>success(p),error:e=>error(e),cleared,timers,events,calls:()=>watchCalls};
}
const position=(age=0)=>({timestamp:Date.now()-age,coords:{latitude:-6.3259438,longitude:106.9686316,accuracy:12}});
test('old first result waits for fresh position, then stops watching',async()=>{
 const x=setup();x.success(position(120000));assert.equal(x.form.elements.latitude.value,'');assert.equal(x.cleared.length,0);
 x.nodes['[data-location]'].onclick();assert.equal(x.calls(),1);
 x.success(position());await new Promise(setImmediate);assert.equal(x.form.elements.latitude.value,-6.3259438);assert.deepEqual(x.cleared,[7]);assert.equal(x.timers.size,0);assert.equal(x.nodes['[data-location]'].disabled,false);
});
test('persistent stale result times out and allows retry',async()=>{
 const x=setup();x.success(position(120000));[...x.timers.values()][0]();await new Promise(setImmediate);
 assert.equal(x.form.elements.latitude.value,'');assert.deepEqual(x.cleared,[7]);assert.match(x.nodes['[data-location-message]'].textContent,/lokasi lama/);
 x.nodes['[data-location]'].onclick();assert.equal(x.calls(),2);
});
test('denied permission stops and leaves coordinates empty',async()=>{
 const x=setup();x.error({code:1});await new Promise(setImmediate);assert.deepEqual(x.cleared,[7]);assert.equal(x.form.elements.latitude.value,'');assert.match(x.nodes['[data-location-message]'].textContent,/ditolak/);
});
test('page exit stops location acquisition',async()=>{
 const x=setup();x.events.pagehide();await new Promise(setImmediate);assert.deepEqual(x.cleared,[7]);assert.equal(x.timers.size,0);
});
test('future and invalid timestamps do not become usable locations',()=>{
 const x=setup();x.success(position(-120000));assert.equal(x.form.elements.latitude.value,'');x.success({...position(),timestamp:NaN});assert.equal(x.form.elements.latitude.value,'');
});

test('selfie-only mode never starts GPS and still blocks missing selfie',async()=>{
 const x=setup(false);assert.equal(x.calls(),0);let prevented=false;await x.listeners.submit({preventDefault(){prevented=true;}});assert.equal(prevented,true);assert.match(x.nodes['[data-camera-message]'].textContent,/Kamera membutuhkan/);assert.equal(x.calls(),0);
});
