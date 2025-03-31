#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <Adafruit_Sensor.h>

const char ssid[] = "mynetwork";  //ssid of your wifi
const char password[] = "mypassword";  // password of your wifi

String HOST_NAME = "http://10.0.10.2"; // REPLACE WITH YOUR WEBSERVERS's IP ADDRESS
String PHP_FILE_NAME   = "/100insert_temp.php";  //REPLACE WITH YOUR PHP FILE NAME

// DHT Sensor Configurations
#define DHTPIN 14
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

unsigned long lastSendTime = 0;  // Stores last time data was sent
const long interval = 60000;      // 60 seconds (30,000 milliseconds)

// Function to read temperature
float readTemperature() {
  float temp = dht.readTemperature(true);
  return isnan(temp) ? 0.0 : temp;
}

// Function to read humidity
float readHumidity() {
  float hum = dht.readHumidity();
  return isnan(hum) ? 0.0 : hum;
}

// Function to calculate VPD
float calculateVPD() {
  float temp = readTemperature();
  float hum = readHumidity();
  float C = (temp - 32) * 5/9; //convert my reading to celclius
  float SVP = 0.61078 * exp(17.27 * C / (C + 237.3));
  float AVP = SVP * (hum / 100);
  float VPD = SVP - AVP;
  return isnan(VPD) ? 0.0 : VPD;
}

void setup() {
  Serial.begin(115200); 
  WiFi.begin(ssid, password);
  Serial.println("Connecting");

  //wait for wifi connection
  while(WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("");
  Serial.print("Connected to WiFi network with IP Address: ");
  Serial.println(WiFi.localIP());
  
  dht.begin();

}

void loop() {
  if (millis() - lastSendTime >= interval) {
    lastSendTime = millis();  // Update last send time
    
    float temp = readTemperature();
    float hum = readHumidity();
    float newvpd = calculateVPD();

    char newQuery[100];  // Allocate a buffer for the query string
    snprintf(newQuery, sizeof(newQuery), "?temperature=%.2f&humidity=%.2f&vpd=%.2f", temp, hum, newvpd);

    HTTPClient http;
    String server = HOST_NAME + PHP_FILE_NAME + newQuery;
    Serial.println("Sending data to: " + server);
    
    http.begin(server);
    int httpCode = http.GET();

    if (httpCode > 0) {
      if (httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        Serial.println("Server Response: " + payload);
      } else {
        Serial.printf("HTTP GET... code: %d\n", httpCode);
      }
    } else {
      Serial.printf("HTTP GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
    }

    http.end();
  }
}
