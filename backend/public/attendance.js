(() => {
 for (const form of document.querySelectorAll('[data-attendance-form]')) {
  const panel=form.querySelector('[data-evidence]'); if(!panel)continue;
  const video=panel.querySelector('[data-video]'),preview=panel.querySelector('[data-preview]'),cameraMsg=panel.querySelector('[data-camera-message]'),locationMsg=panel.querySelector('[data-location-message]');
  let stream=null,locationAt=0,photoAt=0,previewUrl=null,locationPending=null,cancelLocation=null;
  const button=form.querySelector('[data-submit]'),label=button.textContent;
  let busy=false,cameraGeneration=0;
  const stop=()=>{if(stream)stream.getTracks().forEach(t=>t.stop());stream=null;video.srcObject=null;video.hidden=true;button.textContent=label;};
  const clearLocation=()=>{locationAt=0;for(const n of ['latitude','longitude','accuracy'])form.elements[n].value='';};
  const clearPhoto=()=>{photoAt=0;form.elements.selfie.value='';if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;preview.hidden=true;preview.removeAttribute('src');};
  const getLocation=()=>{
   if(locationPending)return locationPending;
   clearLocation();
   if(!window.isSecureContext||!navigator.geolocation){locationMsg.textContent='Lokasi membutuhkan HTTPS atau localhost dan browser yang mendukungnya.';return Promise.resolve(false);}
   const button=panel.querySelector('[data-location]');button.disabled=true;
   locationMsg.textContent='Mencari lokasi otomatis… izinkan akses lokasi di browser.';
   locationPending=new Promise(resolve=>{
    let watchId=null,finished=false,staleAge=null;
    const finish=(ok,message)=>{if(finished)return;finished=true;clearTimeout(deadline);if(watchId!==null)navigator.geolocation.clearWatch(watchId);if(message)locationMsg.textContent=message;resolve(ok);};
    const deadline=setTimeout(()=>finish(false,staleAge!==null?`Perangkat masih memberikan lokasi lama (${staleAge} detik). Periksa tanggal/jam otomatis dan Layanan Lokasi perangkat, lalu coba lagi atau gunakan HP.`:'Lokasi belum tersedia setelah 30 detik. Periksa izin dan Layanan Lokasi perangkat, lalu coba lagi.'),30000);
    try{
     watchId=navigator.geolocation.watchPosition(p=>{
      if(finished)return;
      const age=Date.now()-p.timestamp;
      if(!Number.isFinite(p.timestamp)||p.timestamp<=0||age < -5000){locationMsg.textContent='Waktu lokasi tidak sesuai. Periksa tanggal dan jam otomatis perangkat.';return;}
      if(age>60000){staleAge=Math.round(age/1000);locationMsg.textContent='Perangkat memberikan lokasi lama. Menunggu lokasi terbaru…';return;}
      const c=p.coords;
      if(!Number.isFinite(c.latitude)||Math.abs(c.latitude)>90||!Number.isFinite(c.longitude)||Math.abs(c.longitude)>180||!Number.isFinite(c.accuracy)||c.accuracy<0){locationMsg.textContent='Data lokasi belum valid. Menunggu pembaruan…';return;}
      for(const n of ['latitude','longitude','accuracy'])form.elements[n].value=c[n];
      locationAt=p.timestamp;finish(true,`Lokasi tercatat. Akurasi ±${Math.round(c.accuracy)} m.`);
     },e=>{
      if(e.code===1)finish(false,'Izin lokasi ditolak. Aktifkan izin lokasi di browser dan perangkat, lalu klik Coba lokasi lagi.');
      else locationMsg.textContent='Lokasi belum tersedia. Masih mencoba sampai 30 detik…';
     },{enableHighAccuracy:true,timeout:10000,maximumAge:0});
     if(finished)navigator.geolocation.clearWatch(watchId);
    }catch{finish(false,'Layanan lokasi tidak dapat dimulai. Periksa izin browser, lalu coba lagi.');}
    cancelLocation=()=>finish(false,'Pencarian lokasi dihentikan. Klik Coba lokasi lagi.');
   }).finally(()=>{button.disabled=false;locationPending=null;cancelLocation=null;});
   return locationPending;
  };
  panel.querySelector('[data-location]').onclick=()=>getLocation();
  const locationRequired=panel.dataset?.locationRequired!=='0';
  if(locationRequired)getLocation();
  const cancel=panel.querySelector('[data-cancel]');
  const resetCamera=()=>{cameraGeneration++;stop();cancel.hidden=true;};
  const openCamera=async()=>{
   resetCamera();clearPhoto();
   if(!window.isSecureContext||!navigator.mediaDevices?.getUserMedia){cameraMsg.textContent='Kamera membutuhkan HTTPS atau localhost dan browser yang mendukungnya.';return;}
   const generation=cameraGeneration;
   cancel.hidden=false;cameraMsg.textContent='Izinkan kamera di browser.';
   try{
    const acquired=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:640},height:{ideal:480}},audio:false});
    if(generation!==cameraGeneration){acquired.getTracks().forEach(t=>t.stop());return;}
    stream=acquired;video.srcObject=stream;video.hidden=false;await video.play();
    if(generation!==cameraGeneration)return;
    button.textContent='Ambil foto & kirim absensi';cameraMsg.textContent='Posisikan wajah, lalu tekan Ambil foto & kirim absensi.';
   }catch(e){if(generation===cameraGeneration){resetCamera();cameraMsg.textContent=e.name==='NotAllowedError'?'Izin kamera ditolak. Aktifkan izin kamera, lalu tekan tombol absensi lagi.':'Kamera tidak tersedia atau sedang digunakan aplikasi lain.';}}
  };
  function clearPreview(){if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;}
  cancel.onclick=()=>{resetCamera();clearPhoto();cameraMsg.textContent='Dibatalkan. Tekan tombol absensi untuk membuka kamera lagi.';};
  form.addEventListener('submit',async e=>{
   e.preventDefault();if(busy)return;
   busy=true;button.disabled=true;
   let sent=false;
   try{
    if(!stream){await openCamera();return;}
    if(!video.videoWidth){cameraMsg.textContent='Tunggu kamera siap, lalu tekan tombol lagi.';return;}
    cancel.disabled=true;const captureGeneration=cameraGeneration;
    const canvas=document.createElement('canvas'),scale=Math.min(1,1280/video.videoWidth,1280/video.videoHeight);
    canvas.width=Math.round(video.videoWidth*scale);canvas.height=Math.round(video.videoHeight*scale);canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
    const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.85));
    if(captureGeneration!==cameraGeneration)return;
    if(!blob)throw Error('photo');
    const dt=new DataTransfer();dt.items.add(new File([blob],'selfie.jpg',{type:'image/jpeg'}));form.elements.selfie.files=dt.files;
    clearPreview();previewUrl=URL.createObjectURL(blob);preview.src=previewUrl;preview.hidden=false;photoAt=Date.now();resetCamera();
    cameraMsg.textContent='Foto diambil. Mengirim absensi…';button.textContent='Mengirim…';
    if(locationRequired&&(!locationAt||Date.now()-locationAt>60000)&&!await getLocation()){cameraMsg.textContent='Lokasi belum tersedia. Coba lagi setelah lokasi siap; foto baru akan diambil.';return;}
    if(Date.now()-photoAt>120000){clearPhoto();cameraMsg.textContent='Foto kedaluwarsa. Tekan tombol absensi untuk mengambil ulang.';return;}
    if(!locationRequired)clearLocation();
    HTMLFormElement.prototype.submit.call(form);sent=true;
   }catch{resetCamera();clearPhoto();cameraMsg.textContent='Foto belum dapat dikirim. Tekan tombol absensi untuk mencoba lagi atau gunakan browser terbaru.';}
   finally{busy=false;cancel.disabled=false;if(!sent){button.disabled=false;if(!stream)button.textContent=label;}}
  });
  window.addEventListener('pagehide',()=>{resetCamera();clearPreview();if(cancelLocation)cancelLocation();});
  document.addEventListener('visibilitychange',()=>{if(document.hidden)resetCamera();});
 }
})();
