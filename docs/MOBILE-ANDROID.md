# MoM Android (Capacitor)

## Pendekatan

APK memakai **Capacitor + remote URL** ke aplikasi Laravel yang sudah ada. Ini menjaga session, CSRF, route, dan authorization Laravel tanpa menulis ulang API.

- App ID: `id.mom.notulensi`
- App name: `MoM`
- Min SDK: 24
- Target SDK: 36
- Web dir: `mobile/www` (fallback lokal)
- Server URL: `CAPACITOR_SERVER_URL` saat `cap sync`
- Debug APK: `storage/app/builds/mom-debug-2026-11-26.apk`

## Build

```bash
# dari root proyek
export CAPACITOR_SERVER_URL=http://192.168.10.145:8100
npm run mobile:sync
cd android
./gradlew assembleDebug
```

APK debug:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

Untuk produksi:

```bash
export CAPACITOR_SERVER_URL=https://mom.perusahaan.com
npm run mobile:sync
cd android
./gradlew bundleRelease
```

## Release signing

Jangan menyimpan password/keystore di repository.

```bash
keytool -genkeypair -v \
  -keystore mom-release.keystore \
  -alias mom \
  -keyalg RSA -keysize 2048 -validity 10000
```

Buat `android/keystore.properties` (gitignored) dan konfigurasi signing di `android/app/build.gradle`. Simpan keystore di secret manager. Release AAB/APK harus dibuild ulang dengan URL HTTPS produksi.

## Fitur native

- Deep link: `mom://notulen/{id}` dan `https://mom.example.com/notulen/{id}` (ganti host sebelum rilis).
- Tombol back Android: menutup aplikasi dari dashboard; selain itu kembali ke riwayat sebelumnya.
- Share sheet: tombol **Bagikan** pada detail dokumen memakai share native di APK.
- Status bar mengikuti tema terang/gelap.
- Splash screen dari Capacitor.

## Push notification (belum aktif)

Belum ada project Firebase / `google-services.json` / service account di environment ini. Karena itu plugin FCM tidak dipasang dan push belum dapat diverifikasi.

Langkah saat kredensial tersedia:

1. Buat Firebase project dan Android app `id.mom.notulensi`.
2. Unduh `google-services.json` ke `android/app/`.
3. Tambahkan `@capacitor/push-notifications`.
4. Tambahkan endpoint device token (`/api/v1/devices`) dengan autentikasi dan rate limit.
5. Kirim FCM HTTP v1 dari backend untuk mention/tenggat; deep link ke `/notulen/{id}`.
6. Uji pada perangkat Android 13+ (izin `POST_NOTIFICATIONS`).

## Keamanan

- Produksi wajib HTTPS.
- Debug lokal memakai cleartext HTTP hanya untuk LAN; `android:usesCleartextTraffic` ada di debug manifest, bukan release.
- Tidak ada API key/secret Firebase di APK.
- Session Laravel tetap HttpOnly/Secure di WebView; tidak ada token di localStorage.
- CORS/API `/api/v1` belum diperlukan untuk remote URL.
- Ganti host deep link `mom.example.com` sebelum rilis.

## UAT perangkat nyata

- [ ] Install APK debug di Android 8/10/13/15.
- [ ] Login, refresh, dan logout.
- [ ] Buka dokumen via deep link.
- [ ] Tombol back Android.
- [ ] Share sheet.
- [ ] Tema terang/gelap.
- [ ] Offline (mode pesawat) menampilkan fallback.
- [ ] Rotasi portrait/landscape.
- [ ] Font besar / display size.
- [ ] Push setelah Firebase dikonfigurasi.

Status: APK debug berhasil dibuild, tetapi **belum diuji di perangkat fisik** karena tidak ada perangkat/emulator yang terhubung.
