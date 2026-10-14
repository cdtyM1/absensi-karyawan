(() => {
 for (const form of document.querySelectorAll('[data-attendance-form]')) {
  const panel=form.querySelector('[data-evidence]'); if(!panel)continue;
  const video=panel.querySelector('[data-video]'),preview=panel.querySelector('[data-preview]'),cameraMsg=panel.querySelector('[data-camera-message]'),locationMsg=panel.querySelector('[data-location-message]');
  let stream=null,locationAt=0,photoAt=0,previewUrl=null,locationPending=null;
  const stop=()=>{if(stream)stream.getTracks().forEach(t=>t.stop());stream=null;video.srcObject=null;video.hidden=true;panel.querySelector('[data-capture]').disabled=true;};
  const clearLocation=()=>{locationAt=0;for(const n of ['latitude','longitude','accuracy'])form.elements[n].value='';};
  const clearPhoto=()=>{photoAt=0;form.elements.selfie.value='';if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;preview.hidden=true;preview.removeAttribute('src');};
  const getLocation=()=>{
   if(locationPending)return locationPending;
   clearLocation();
   if(!window.isSecureContext||!navigator.geolocation){locationMsg.textContent='Lokasi membutuhkan HTTPS atau localhost dan browser yang mendukungnya.';return Promise.resolve(false);}
   const button=panel.querySelector('[data-location]');button.disabled=true;
   locationMsg.textContent='Mencari lokasi otomatis… izinkan akses lokasi di browser.';
   locationPending=new Promise(resolve=>navigator.geolocation.getCurrentPosition(p=>{
    if(Date.now()-p.timestamp>60000){locationMsg.textContent='Lokasi terlalu lama. Klik Coba lokasi lagi.';resolve(false);return;}
    for(const n of ['latitude','longitude','accuracy'])form.elements[n].value=p.coords[n];
    locationAt=Date.now();locationMsg.textContent=`Lokasi tercatat. Akurasi ±${Math.round(p.coords.accuracy)} m.`;resolve(true);
   },e=>{
    locationMsg.textContent=e.code===1?'Izin lokasi ditolak. Aktifkan izin lokasi di browser dan perangkat, lalu klik Coba lokasi lagi.':e.code===3?'Pencarian lokasi melewati batas waktu. Klik Coba lokasi lagi atau gunakan HP.':'Lokasi tidak tersedia. Periksa layanan lokasi perangkat, lalu klik Coba lokasi lagi.';
    resolve(false);
   },{enableHighAccuracy:true,timeout:20000,maximumAge:0})).finally(()=>{button.disabled=false;locationPending=null;});
   return locationPending;
  };
  panel.querySelector('[data-location]').onclick=()=>getLocation();
  getLocation();
  panel.querySelector('[data-camera]').onclick=async()=>{stop();clearPhoto();const b=panel.querySelector('[data-camera]');if(!window.isSecureContext||!navigator.mediaDevices?.getUserMedia){cameraMsg.textContent='Kamera membutuhkan HTTPS atau localhost.';return;}b.disabled=true;cameraMsg.textContent='Izinkan kamera di browser.';try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:640},height:{ideal:480}},audio:false});video.srcObject=stream;video.hidden=false;await video.play();panel.querySelector('[data-capture]').disabled=false;cameraMsg.textContent='Posisikan wajah, lalu klik Ambil selfie.';}catch(e){stop();cameraMsg.textContent=e.name==='NotAllowedError'?'Izin kamera ditolak. Periksa izin browser dan macOS.':'Kamera tidak tersedia atau sedang digunakan aplikasi lain.';}finally{b.disabled=false;}};
  panel.querySelector('[data-capture]').onclick=()=>{if(!stream||!video.videoWidth){cameraMsg.textContent='Tunggu kamera siap.';return;}const canvas=document.createElement('canvas'),scale=Math.min(1,1280/video.videoWidth,1280/video.videoHeight);canvas.width=Math.round(video.videoWidth*scale);canvas.height=Math.round(video.videoHeight*scale);canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);canvas.toBlob(blob=>{try{if(!blob)throw Error();const dt=new DataTransfer();dt.items.add(new File([blob],'selfie.jpg',{type:'image/jpeg'}));form.elements.selfie.files=dt.files;clearPreview();previewUrl=URL.createObjectURL(blob);preview.src=previewUrl;preview.hidden=false;photoAt=Date.now();stop();cameraMsg.textContent='Selfie siap dikirim. Klik Buka kamera untuk mengambil ulang.';}catch{clearPhoto();cameraMsg.textContent='Browser tidak mendukung pengiriman foto. Coba browser terbaru.';}},'image/jpeg',.85);};
  function clearPreview(){if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;}
  panel.querySelector('[data-stop]').onclick=stop;
  let submitting=false;
  form.addEventListener('submit',async e=>{
   e.preventDefault();if(submitting)return;
   const validPhoto=()=>photoAt&&Date.now()-photoAt<=120000&&form.elements.selfie.files.length;
   if(!validPhoto()){clearPhoto();cameraMsg.textContent='Ambil selfie baru sebelum mengirim (berlaku 2 menit).';return;}
   submitting=true;const button=form.querySelector('[data-submit]');button.disabled=true;
   try{
    if((!locationAt||Date.now()-locationAt>60000)&&!await getLocation())return;
    if(!validPhoto()){clearPhoto();cameraMsg.textContent='Ambil selfie ulang, lalu kirim absensi.';return;}
    stop();HTMLFormElement.prototype.submit.call(form);
   }finally{submitting=false;button.disabled=false;}
  });
  window.addEventListener('pagehide',()=>{stop();clearPreview();});
  document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();});
 }
})();
