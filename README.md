# 🪖 Command Center: Smart Bunker Alpha Control System

Proyek IoT (Internet of Things) terintegrasi untuk mensimulasikan sistem monitoring dan kendali otomatis sebuah bunker pertahanan jarak jauh. Menggunakan arsitektur multi-node dengan dua buah mikrokontroler **ESP32** sebagai *node* sensor/aktuator, serta **Web App PHP (Native)** sebagai pusat kendali (*Command Center*) berbasis sinkronisasi data HTTP API secara real-time.

---

## 🚀 Fitur Utama Sistem

### 1. Web Command Center (`index.php` & `api.php`)
* **Military Aesthetics Dashboard:** Desain antarmuka bertema ruang kendali taktis menggunakan font *custom* (`Black Ops One` & `VT323`).
* **Real-time Data Visualization:** Integrasi dengan **Chart.js** untuk menampilkan grafik perkembangan log sensor.
* **Dual Control Mode:** Kendali penuh per perangkat yang mendukung mode otomatis (`AUTO` berdasarkan kalkulasi logika sensor) maupun mode kendali paksa (`MANUAL`).

### 2. Node 1: Security & Hazard Detection (`uap1.ino`)
* **Deteksi Gempa Bumi:** Memanfaatkan sensor *Gyroscope/Accelerometer* **MPU6050** untuk melacak getaran di atas ambang batas (threshold).
* **Deteksi Dentuman/Suara:** Menggunakan **Mikrofon I2S** untuk menangkap desibel suara bahaya di luar bunker.
* **Sistem Gerbang Otomatis:** Mengontrol **Servo Motor** sebagai pengunci pintu bunker otomatis jika terdeteksi bahaya gempa/dentuman.
* **Audio Warning:** Aktivasi **Buzzer** sebagai alarm peringatan evakuasi.

### 3. Node 2: Environment Management (`uap2.ino`)
* **Iklim Ruangan:** Memonitor kondisi suhu dan kelembaban udara internal melalui sensor **DHT11**.
* **Sirkulasi Udara Otomatis:** Mengaktifkan kipas sirkulasi via **Relay** apabila temperatur ruangan melebihi batas nyaman (32°C).
* **Sistem Pencahayaan Pintar:** Sensor **LDR** mengukur intensitas cahaya dalam bunker untuk menyalakan/mematikan lampu LED penunjang secara efisien.

---

## 🛠️ Topologi Arsitektur Hardware & Software

```
[ Sensor Node 1: MPU6050 + I2S Mic ] ───(HTTP POST/GET)───┐
                                                            │
                                                      [ API Server ] ─── [ MySQL Database ]
                                                            │
[ Sensor Node 2: DHT11 + LDR ]        ───(HTTP POST/GET)───┘
                                                            │
[ Web Command Center UI (Dashboard) ] ───(AJAX/JSON/POST)───┘

🔌 Pemetaan Pin ESP32 (Pin Mapping)

```
🚨 Node 1 (Security Node)

    Servo Pintu Bunker ➡️ Terhubung ke GPIO 18 (Berfungsi sebagai gerbang evakuasi utama)

    Buzzer Alarm ➡️ Terhubung ke GPIO 23 (Berfungsi sebagai sirine tanda bahaya)

    LED Status Aman ➡️ Terhubung ke GPIO 19 (Indikator visual saat kondisi kondusif)

    LED Status Bahaya ➡️ Terhubung ke GPIO 5 (Indikator visual saat terjadi gempa/dentuman)

    Sensor Getaran MPU6050 (I2C) ➡️ Jalur komunikasi data terhubung ke pin standard SDA & SCL

    Mikrofon I2S Suara (WS / SD / SCK) ➡️ Terhubung berturut-turut ke GPIO 15 / GPIO 2 / GPIO 4

🌿 Node 2 (Environment Node)

    Sensor DHT11 ➡️ Terhubung ke GPIO 26 (Membaca tingkat suhu & kelembaban interior)

    Sensor Cahaya LDR ➡️ Terhubung ke GPIO 4 (Mengukur intensitas lux cahaya sekitar)

    LED Penerangan Utama ➡️ Terhubung ke GPIO 21 (Lampu interior otomatis/manual)

    Relay Kipas Angin ➡️ Terhubung ke GPIO 27 (Sistem aktuator pendingin & sirkulasi udara)

📦 Panduan Instalasi & Penggunaan
1. Konfigurasi Lingkungan Server Lokal (XAMPP / Laragon)

   1. Aktifkan modul Apache dan MySQL melalui Control Panel server lokal Anda.

    2. Masuk ke phpMyAdmin, buat database baru bernama db_bunker.

    3. Buat folder baru bernama ControlUAP di dalam direktori root server (C:\xampp\htdocs\ControlUAP\ atau C:\laragon\www\ControlUAP\).

    4. Letakkan berkas api.php dan index.php ke dalam folder tersebut.

2. Konfigurasi & Unggah Firmware Mikrokontroler

    1. Buka berkas uap1.ino dan uap2.ino menggunakan software Arduino IDE.

    2.Pastikan Anda telah memasang Library pendukung yang dibutuhkan: MPU6050_tockn, ESP32Servo, dan DHT sensor library.

    3. Sesuaikan parameter kredensial jaringan Wi-Fi agar mengarah ke router/hotspot yang sama dengan komputer server Anda:
  
    const char* ssid = "Nama_WiFi_Anda";
    const char* password = "Password_WiFi_Anda";

    4. Ubah variabel pengalamatan baseUrl menggunakan alamat IP Lokal Laptop/PC Server Anda saat ini (Cek IP Anda via cmd -> ipconfig):
  
    String baseUrl = "http://IP_LOKAL_LAPTOP_ANDA/ControlUAP/api.php";

    5. Pilih board target ESP32 Dev Module, lalu lakukan proses Compile dan Upload kode ke masing-masing papan mikrokontroler.
