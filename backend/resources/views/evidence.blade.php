@if(config('attendance.require_evidence'))
<div class="evidence" data-evidence>
 <p>Lokasi diambil saat diminta. Kamera hanya digunakan untuk selfie, tanpa merekam suara.</p>
 <button type="button" data-location>Ambil lokasi</button><p data-location-message role="status">Lokasi belum diambil.</p>
 <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="accuracy">
 <button type="button" data-camera>Buka kamera</button> <button type="button" data-capture disabled>Ambil selfie</button> <button type="button" data-stop>Tutup kamera</button>
 <video data-video autoplay muted playsinline hidden></video><img data-preview alt="Selfie yang akan dikirim" hidden><p data-camera-message role="status">Selfie belum diambil.</p>
 <input type="file" name="selfie" accept="image/jpeg,image/png" hidden>
 <p><small>Lokasi dan foto dapat diperiksa oleh kamu dan Admin/HR. Selfie tidak memakai pengenalan wajah.</small></p>
</div>
@endif
