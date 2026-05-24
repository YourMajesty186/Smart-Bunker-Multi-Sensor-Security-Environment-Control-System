#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

// --- KONFIGURASI NETWORK ---
const char* ssid = "Firman";
const char* password = "22442244";
String baseUrl = "http://10.75.82.74/ControlUAP/api.php"; ; 

// --- PIN MAPPING ---
#define PIN_DHT     26
#define PIN_LDR     4
#define PIN_LED     21
#define PIN_RELAY   27
#define DHTTYPE     DHT11

float BATAS_SUHU = 32.0;
DHT dht(PIN_DHT, DHTTYPE);

// --- VARIABEL KONTROL ---
String controlMode = "AUTO";
String cmdKipas = "OFF";
String cmdLed = "OFF";

unsigned long lastTime = 0;
unsigned long timerDelay = 1000;

void syncWithServer(float suhu, float lembab, String kipas, String cahaya);

void setup() {
  Serial.begin(115200);
  
  WiFi.begin(ssid, password);
  while(WiFi.status() != WL_CONNECTED) { delay(500); }

  dht.begin();
  pinMode(PIN_LED, OUTPUT);
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_LDR, INPUT);

  // Default Mati (Relay Active Low -> HIGH = Mati)
  digitalWrite(PIN_LED, LOW);
  digitalWrite(PIN_RELAY, HIGH); 
}

void loop() {
  if ((millis() - lastTime) > timerDelay) {
    lastTime = millis();
    
    // 1. Baca Sensor
    float suhu = dht.readTemperature();
    float lembab = dht.readHumidity();
    int ldr = digitalRead(PIN_LDR); // 1 = Malam, 0 = Siang
    
    if(isnan(suhu)) suhu = 0;
    if(isnan(lembab)) lembab = 0;

    // 2. Tentukan Status Output
    bool fanState = false; // false = mati
    bool ledState = false;

    if (controlMode == "MANUAL") {
       // --- MODE MANUAL ---
       if (cmdKipas == "ON") fanState = true;
       if (cmdLed == "ON") ledState = true;
    } 
    else {
       // --- MODE AUTO ---
       if (suhu > BATAS_SUHU) fanState = true;
       if (ldr == HIGH) ledState = true; // Malam nyala
    }

    // 3. Eksekusi Hardware
    // Kipas (Relay Active Low: LOW = ON)
    if (fanState) digitalWrite(PIN_RELAY, LOW);
    else digitalWrite(PIN_RELAY, HIGH);

    // LED (Active High: HIGH = ON)
    if (ledState) digitalWrite(PIN_LED, HIGH);
    else digitalWrite(PIN_LED, LOW);

    // 4. Kirim Data & Baca Perintah
    String statusKipas = fanState ? "ON" : "OFF";
    String statusCahaya = ledState ? "ON" : "OFF";
    
    syncWithServer(suhu, lembab, statusKipas, statusCahaya);
  }
}

void syncWithServer(float suhu, float lembab, String kipas, String cahaya) {
  if(WiFi.status() == WL_CONNECTED){
    HTTPClient http;
    
    // A. KIRIM DATA (POST)
    http.begin(baseUrl);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    String postData = "device_id=ESP2&suhu=" + String(suhu) + "&kelembaban=" + String(lembab) + "&kipas=" + kipas + "&cahaya=" + cahaya;
    http.POST(postData);
    http.end();

    // B. BACA PERINTAH (GET)
    String getUrl = baseUrl + "?read_controls=1&format=esp";
    http.begin(getUrl);
    int httpCode = http.GET();
    
    if (httpCode == 200) {
      String payload = http.getString();
      
      int p1 = payload.indexOf('|');
      int p2 = payload.indexOf('|', p1 + 1);
      int p3 = payload.indexOf('|', p2 + 1); // Start ESP2 Mode
      int p4 = payload.indexOf('|', p3 + 1); // Start ESP2 Kipas
      
      if(p3 > 0 && p4 > 0) {
         controlMode = payload.substring(p2 + 1, p3); // "AUTO" / "MANUAL"
         cmdKipas = payload.substring(p3 + 1, p4);    // "ON" / "OFF"
         cmdLed = payload.substring(p4 + 1);          // "ON" / "OFF"
      }
    }
    http.end();
  }
}