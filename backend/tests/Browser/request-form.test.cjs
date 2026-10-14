const {test}=require('node:test');const assert=require('node:assert/strict');const vm=require('node:vm');const fs=require('node:fs');
test('sickness hides duration and times; partial permission and overtime require times',()=>{
 const nodes={},events={};const field=(value='')=>({value,addEventListener:(name,callback)=>{events[value]=callback;}});
 const kind=field('Sakit'),scope=field('Sebagian hari');const form={elements:{kind,scope,start_time:{},end_time:{}},querySelector:s=>nodes[s]??={}};
 vm.runInNewContext(fs.readFileSync('public/requests.js','utf8'),{document:{querySelectorAll:()=>[form]}});
 assert.equal(scope.value,'Sehari penuh');assert.equal(nodes['[data-request-scope]'].hidden,true);assert.equal(nodes['[data-request-times]'].hidden,true);assert.equal(form.elements.start_time.disabled,true);
 kind.value='Izin';events.Sakit();assert.equal(nodes['[data-request-scope]'].hidden,false);
 scope.value='Sebagian hari';events['Sebagian hari']();assert.equal(nodes['[data-request-times]'].hidden,false);assert.equal(form.elements.start_time.required,true);
 kind.value='Lembur';events.Sakit();assert.equal(nodes['[data-request-scope]'].hidden,true);assert.equal(scope.value,'Sebagian hari');assert.equal(form.elements.end_time.required,true);
 kind.value='Cuti';events.Sakit();assert.equal(nodes['[data-request-times]'].hidden,true);assert.equal(form.elements.end_time.disabled,true);
});
