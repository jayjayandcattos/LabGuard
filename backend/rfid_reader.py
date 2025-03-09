import serial
import requests

RFID_PORT = "COM3"  # Use "/dev/ttyUSB0" for Linux
BAUD_RATE = 9600

SERVER_URL = "http://localhost/rfid_attendance.php"

ser = serial.Serial(RFID_PORT, BAUD_RATE, timeout=1)

print("RFID Reader Connected. Waiting for scans...")

while True:
    try:
        rfid_tag = ser.readline().decode('utf-8').strip()  # Read tag
        if rfid_tag:
            print(f"Scanned RFID: {rfid_tag}")
            response = requests.post(SERVER_URL, data={'rfid': rfid_tag})
            print(f"Server Response: {response.text}")
    except Exception as e:
        print(f"Error: {e}") #Error handling for unauthorized student access... FIX
        break

