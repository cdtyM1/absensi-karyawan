@if(config('attendance.require_evidence'))
<div class="evidence" data-evidence data-location-required="{{ config('attendance.require_location', true) ? '1' : '0' }}">
 @if(config('attendance.require_location', true))
 <p>Lokasi diambil otomatis saat halaman dibuka dan diperbarui saat mengirim jika sudah kedaluwarsa. Kamera hanya digunakan untuk selfie, tanpa merekam suara.</p>
 <button type="button" data-location>Coba lokasi lagi</button><p data-location-message role="status">Menyiapkan lokasi otomatis…</p>
 @else
 <p>Selfie wajib. Lokasi sementara tidak diwajibkan; absensi akan ditandai untuk pemeriksaan HR.</p>
 <button type="button" data-location hidden>Coba lokasi lagi</button><p data-location-message hidden></p>
 @endif
 <input type="hidden" name="latitude"><input type="hidden" name="longitude"><input type="hidden" name="accuracy">
 <video data-video autoplay muted playsinline hidden></video><img data-preview alt="Foto absensi yang dikirim" hidden>
 <button type="button" data-cancel hidden>Batal</button><p data-camera-message role="status">Tekan tombol absensi untuk membuka kamera, lalu ambil foto dan kirim.</p>
 <input type="file" name="selfie" accept="image/jpeg,image/png" hidden>
 <p><small>Lokasi dan foto dapat diperiksa oleh kamu dan Admin/HR. Selfie tidak memakai pengenalan wajah.</small></p>
</div>
@endif
