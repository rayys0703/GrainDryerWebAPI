#include <DHT.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <math.h>

#define lampMerah 5   // GPIO5 untuk Lampu Merah
#define lampKuning 18 // GPIO18 untuk Lampu Kuning
#define lampHijau 19  // GPIO19 untuk Lampu Hijau
#define DHTPIN 2      // GPIO2 untuk DHT22
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x27, 16, 2);

// --- Konstanta untuk Termistor NTC 10k ---
const int ANALOG_PIN_GRAIN_TEMP = 36; // GPIO36 (VP) untuk Termistor
const float resistorValue = 10000.0; // Nilai resistor 10k Ohm yang terhubung seri dengan termistor

#define ANALOG_PIN_MOIS 39 // GPIO39 (VN) untuk Kadar Air Gabah
const int dryValue  = 2910;
const int wetValue = 1465; 

void setup() {
  Serial.begin(115200);
  dht.begin();
  lcd.init();
  lcd.backlight();
  pinMode(ANALOG_PIN_GRAIN_TEMP, INPUT); // GPIO36 (VP) untuk Termistor
  pinMode(ANALOG_PIN_MOIS, INPUT);       // GPIO39 (VN) untuk Moisture Meter
  pinMode(lampMerah, OUTPUT);            // GPIO5 untuk Relay Merah
  pinMode(lampKuning, OUTPUT);           // GPIO18 untuk Relay Kuning
  pinMode(lampHijau, OUTPUT);            // GPIO19 untuk Relay Hijau
  digitalWrite(lampMerah, HIGH);         // Awalnya mati (Relay aktif LOW, jadi set HIGH untuk mati)
  digitalWrite(lampKuning, HIGH);        // Awalnya mati (Relay aktif LOW, jadi set HIGH untuk mati)
  digitalWrite(lampHijau, HIGH);         // Awalnya mati (Relay aktif LOW, jadi set HIGH untuk mati)
}

float getMoisture(){ // Reff: https://www.youtube.com/watch?v=Wo5Rpk55Ft8&t=2s
  // return 10.0;
  // Ambil beberapa sampel untuk mengurangi noise
  const int numSamples = 10;
  long sensorValueSum = 0;
  for (int i = 0; i < numSamples; i++) {
    sensorValueSum += analogRead(ANALOG_PIN_MOIS);
    delay(10); // Jeda kecil antar pembacaan
  }
  float sensorValue = (float)sensorValueSum / numSamples; // Rata-rata pembacaan

  Serial.print("Moisture ADC: ");
  Serial.println(sensorValue, 2);

  // Cek apakah nilai ADC valid
  if (sensorValue == 0 || sensorValue > 4095) {
    return -999; // Nilai error untuk pembacaan tidak valid
  }

  // Kalibrasi kadar air gabah dengan interpolasi linier
  float moisture = (sensorValue - dryValue) * (100.0 - 0.0) / (wetValue - dryValue) + 0.0; // Konversi ke persen

  // Batasi rentang kelembapan antara 0% dan 100%
  if (moisture < 0) {
    return -999;
  } else if (moisture > 100) {
    return -999;
  }

  return moisture;
}

float getGrainTemp() { // Reff: https://www.e-tinkers.com/2019/10/using-a-thermistor-with-arduino-and-unexpected-esp32-adc-non-linearity/
  int sensorValue = analogRead(ANALOG_PIN_GRAIN_TEMP);
  if (sensorValue == 0) {return -999;}

  // Konstanta untuk termistor NTC 10k dan ESP32
  const float R1 = 10000.0;   // Nilai resistor seri (10k Ohm)
  const float Beta = 3950.0;  // Nilai Beta termistor
  const float To = 298.15;    // Suhu referensi dalam Kelvin (25°C)
  const float Ro = 10000.0;   // Resistansi termistor pada 25°C
  const float adcMax = 4095.0; // Resolusi ADC ESP32 (12-bit)
  const float Vs = 3.3;       // Tegangan referensi ESP32 (3.3V)

  // Hitung tegangan keluaran (Vout) dari pembacaan ADC
  float Vout = sensorValue * Vs / adcMax;
  
  // Hitung resistansi termistor (Rt)
  float Rt = R1 * Vout / (Vs - Vout);

  // Hitung suhu dalam Kelvin
  float T = 1.0 / (1.0 / To + log(Rt / Ro) / Beta);
  
  // Konversi ke Celsius
  float Tc = T - 273.15;

  if (Tc < 0 || isnan(Tc)) {return -999;}

  return Tc;
}

float getRoomTemp(){
  float tempDHT = dht.readTemperature();
  if (isnan(tempDHT)) {return -999;}
  return tempDHT;
}

void loop() {
  float moisture = getMoisture();           // Kadar Air Gabah dari Capacitive
  float graintemp = getGrainTemp();         // Suhu Gabah dari Termistor
  float tempDHT = getRoomTemp();            // Suhu Ruangan

  // Kontrol Relay berdasarkan kadar air
  if (moisture >= 0 && moisture <= 100) { // Pastikan nilai kadar air valid
    if (moisture < 12) {
      digitalWrite(lampMerah, LOW);   // Nyalakan lampu merah
      digitalWrite(lampKuning, HIGH); // Matikan lampu kuning
      digitalWrite(lampHijau, HIGH);  // Matikan lampu hijau
    } else if (moisture > 16) {
      digitalWrite(lampMerah, HIGH);  // Matikan lampu merah
      digitalWrite(lampKuning, LOW);  // Nyalakan lampu kuning
      digitalWrite(lampHijau, HIGH);  // Matikan lampu hijau
    } else { // 12 <= moisture <= 16
      digitalWrite(lampMerah, HIGH);  // Matikan lampu merah
      digitalWrite(lampKuning, HIGH); // Matikan lampu kuning
      digitalWrite(lampHijau, LOW);   // Nyalakan lampu hijau
    }
  } else {
    // Jika kadar air tidak valid, matikan semua lampu
    digitalWrite(lampMerah, HIGH);
    digitalWrite(lampKuning, HIGH);
    digitalWrite(lampHijau, HIGH);
  }

  // Tampilkan semua data di LCD
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("G:");
  lcd.print(graintemp, 1);
  lcd.print("C R:");
  lcd.print(tempDHT, 1);
  lcd.print("C");

  lcd.setCursor(0, 1);
  lcd.print("K.Air:");
  lcd.print(moisture, 1);
  lcd.print("%");

  // Tampilkan di Serial Monitor
  Serial.print("\nSuhu Gabah: ");
  Serial.print(graintemp, 2);
  Serial.println(" C");
  Serial.print("Suhu Ruangan: ");
  Serial.print(tempDHT, 2);
  Serial.println(" C");
  Serial.print("Kadar Air: ");
  Serial.print(moisture, 2);
  Serial.println(" %");
  Serial.println("-----------");
  
  delay(5000); // Delay 5 detik
}
