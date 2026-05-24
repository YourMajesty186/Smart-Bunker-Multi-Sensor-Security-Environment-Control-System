#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <MPU6050_tockn.h>
#include <ESP32Servo.h>
#include <driver/i2s.h>

// --- KONFIGURASI NETWORK ---
const char* ssid = "Firman";
const char* password = "22442244";
String baseUrl = "http://10.75.82.74/ControlUAP/api.php"; 

// --- PIN MAPPING ---
#define PIN_SERVO       18
#define PIN_BUZZER      23
#define PIN_LED_AMAN    19
#define PIN_LED_BAHAYA  5
#define I2S_WS          15
#define I2S_SD          2
#define I2S_SCK         4
#define I2S_PORT        I2S_NUM_0

MPU6050 mpu6050(Wire);
Servo bunkerDoor;

// --- SETTING THRESHOLD (SENSITIVITAS) ---
float BATAS_GEMPA = 0.2; 
int32_t BATAS_SUARA = 20000;   

// --- VARIABEL KONTROL ---
String controlMode = "AUTO"; 
String manualCommand = "OPEN"; 
unsigned long lastTime = 0;
unsigned long timerDelay = 1000;

// Variabel untuk melacak posisi servo saat ini (0 = Buka, 180 = Tutup)
int currentServoPos = 0; 

// --- PROTOTYPES ---
void gerakServoPelan(int startPos, int targetPos, int speedDelay);
void gerakServo(int target);
void syncWithServer(String statusPintu, float gempa, int suara, String pesan);
void setupI2S();
int32_t getSoundVolume();
void bunyikanAlarm();

void setup() {
  Serial.begin(115200);
  
  // 1. Connect WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting");
  while(WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.println(" Connected!");

  // 2. Init Pin
  pinMode(PIN_BUZZER, OUTPUT);
  pinMode(PIN_LED_AMAN, OUTPUT);
  pinMode(PIN_LED_BAHAYA, OUTPUT);
  digitalWrite(PIN_BUZZER, LOW);
  digitalWrite(PIN_LED_AMAN, HIGH);
  digitalWrite(PIN_LED_BAHAYA, LOW);

  // 3. Init Sensor
  Wire.begin(); Wire.setClock(100000); 
  mpu6050.begin(); mpu6050.calcGyroOffsets(true);
  setupI2S();

  // 4. Init Servo (SLOW START)
  Serial.println("System Start: Opening Door Slowly...");
  // Panggil fungsi gerak pelan (fungsi ini sudah handle attach/detach sendiri)
  gerakServoPelan(180, 0, 25); 
  currentServoPos = 0; // Set posisi awal terbuka
}

void loop() {
  if ((millis() - lastTime) > timerDelay) {
    lastTime = millis();

    // A. BACA SENSOR
    mpu6050.update();
    float totalVector = sqrt(pow(mpu6050.getAccX(),2) + pow(mpu6050.getAccY(),2) + pow(mpu6050.getAccZ(),2));
    float guncangan = abs(totalVector - 1.0);
    int32_t suara = getSoundVolume();
    
    // Status Pintu berdasarkan perintah terakhir
    String statusPintu = (manualCommand == "CLOSE") ? "TERKUNCI" : "TERBUKA"; 
    String pesanSistem = "Aman";
    
    // B. LOGIKA KONTROL
    if (controlMode == "MANUAL") {
      // --- MODE MANUAL ---
      pesanSistem = "KONTROL MANUAL";
      
      if (manualCommand == "CLOSE") {
         // Tutup Cepat (Hanya jika belum tertutup)
         if (currentServoPos != 180) {
             gerakServo(180); 
             currentServoPos = 180;
         }
         digitalWrite(PIN_LED_BAHAYA, HIGH); digitalWrite(PIN_LED_AMAN, LOW);
         statusPintu = "TERKUNCI";
      } else {
         // Buka Pelan (Hanya jika belum terbuka)
         if (currentServoPos != 0) {
             // Gerak dari 180 (Tutup) ke 0 (Buka) dengan delay 25ms
             gerakServoPelan(180, 0, 25); 
             currentServoPos = 0;
         }
         digitalWrite(PIN_LED_BAHAYA, LOW); digitalWrite(PIN_LED_AMAN, HIGH);
         statusPintu = "TERBUKA";
      }
    } 
    else {
      // --- MODE AUTO ---
      bool bahaya = (guncangan > BATAS_GEMPA) || (suara > BATAS_SUARA);
      
      if (bahaya) {
        pesanSistem = "BAHAYA AUTO DETECT!";
        
        // Tutup Cepat saat bahaya (jika belum tertutup)
        if (currentServoPos != 180) {
            gerakServo(180); 
            currentServoPos = 180;
        }
        
        bunyikanAlarm();
        digitalWrite(PIN_LED_BAHAYA, HIGH); digitalWrite(PIN_LED_AMAN, LOW);
        statusPintu = "TERKUNCI";
        manualCommand = "CLOSE"; // Sinkronkan status manual command
      } else {
        pesanSistem = "AUTO - MONITORING";
      }
    }

    // C. KOMUNIKASI SERVER
    syncWithServer(statusPintu, guncangan, suara, pesanSistem);
  }
}

// --- FUNGSI PENDUKUNG ---

// Fungsi Gerak Pelan (Sekarang otomatis Attach/Detach)
void gerakServoPelan(int startPos, int targetPos, int speedDelay) {
  bunkerDoor.attach(PIN_SERVO); // Pasang Servo
  
  if (startPos < targetPos) {
    for (int pos = startPos; pos <= targetPos; pos++) {
      bunkerDoor.write(pos); delay(speedDelay);
    }
  } else {
    for (int pos = startPos; pos >= targetPos; pos--) {
      bunkerDoor.write(pos); delay(speedDelay);
    }
  }
  
  bunkerDoor.detach(); // Lepas Servo biar hemat daya & tidak getar
}

// Fungsi Gerak Cepat
void gerakServo(int target) {
  bunkerDoor.attach(PIN_SERVO);
  bunkerDoor.write(target);
  delay(500); 
  bunkerDoor.detach();
}

void bunyikanAlarm() {
  for(int i=0; i<3; i++) { digitalWrite(PIN_BUZZER, HIGH); delay(50); digitalWrite(PIN_BUZZER, LOW); delay(50); }
}

void syncWithServer(String statusPintu, float gempa, int suara, String pesan) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    
    // 1. KIRIM DATA
    http.begin(baseUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    String postData = "device_id=ESP1&pintu=" + statusPintu + "&gempa=" + String(gempa) + "&suara=" + String(suara) + "&pesan=" + pesan;
    http.POST(postData);
    http.end();

    // 2. AMBIL PERINTAH (ESP1_MODE|ESP1_SERVO|...)
    String getUrl = baseUrl + "?read_controls=1&format=esp";
    http.begin(getUrl);
    int httpCode = http.GET();
    if (httpCode == 200) {
      String payload = http.getString();
      int p1 = payload.indexOf('|');
      int p2 = payload.indexOf('|', p1 + 1);
      
      if(p1 > 0) {
        controlMode = payload.substring(0, p1); 
        if(controlMode == "MANUAL") {
            manualCommand = payload.substring(p1 + 1, p2);
        }
      }
    }
    http.end();
  }
}

void setupI2S() {
  const i2s_config_t i2s_config = { .mode = (i2s_mode_t)(I2S_MODE_MASTER | I2S_MODE_RX), .sample_rate = 44100, .bits_per_sample = I2S_BITS_PER_SAMPLE_32BIT, .channel_format = I2S_CHANNEL_FMT_ONLY_LEFT, .communication_format = i2s_comm_format_t(I2S_COMM_FORMAT_I2S | I2S_COMM_FORMAT_I2S_MSB), .intr_alloc_flags = ESP_INTR_FLAG_LEVEL1, .dma_buf_count = 8, .dma_buf_len = 64, .use_apll = false };
  const i2s_pin_config_t pin_config = { .bck_io_num = I2S_SCK, .ws_io_num = I2S_WS, .data_out_num = -1, .data_in_num = I2S_SD };
  i2s_driver_install(I2S_PORT, &i2s_config, 0, NULL); i2s_set_pin(I2S_PORT, &pin_config);
}

int32_t getSoundVolume() {
  int32_t sampleBuffer[64]; size_t bytesRead = 0;
  i2s_read(I2S_PORT, &sampleBuffer, sizeof(sampleBuffer), &bytesRead, 100); 
  int64_t sum = 0; if (bytesRead > 0) { for (int i = 0; i < bytesRead / 4; i++) { sum += abs(sampleBuffer[i] >> 14); } return (int32_t)(sum / (bytesRead / 4)); } return 0;
}