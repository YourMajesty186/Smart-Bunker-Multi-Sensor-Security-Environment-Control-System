<?php
// C:\xampp\htdocs\bunker\api.php
header('Content-Type: application/json');
$conn = mysqli_connect("localhost", "root", "", "db_bunker");

// --- BAGIAN 1: MENERIMA DATA SENSOR DARI ESP32 (POST) ---
if(isset($_POST['device_id'])) {
    $dev = $_POST['device_id'];
    
    if ($dev == "ESP1") {
        $pintu = $_POST['pintu']; $gempa = $_POST['gempa']; 
        $suara = $_POST['suara']; $pesan = $_POST['pesan'];
        mysqli_query($conn, "INSERT INTO sensor_logs (device_id, pintu, gempa, suara, pesan) VALUES ('ESP1', '$pintu', '$gempa', '$suara', '$pesan')");
    } 
    else if ($dev == "ESP2") {
        $suhu = $_POST['suhu']; $lembab = $_POST['kelembaban']; 
        $kipas = $_POST['kipas']; $cahaya = $_POST['cahaya'];
        mysqli_query($conn, "INSERT INTO sensor_logs (device_id, suhu, kelembaban, kipas, cahaya) VALUES ('ESP2', '$suhu', '$lembab', '$kipas', '$cahaya')");
    }
    echo json_encode(["status" => "Data Saved"]);
    exit();
}

// --- BAGIAN 2: UPDATE KONTROL DARI WEBSITE (POST Action) ---
if(isset($_POST['action'])) {
    $col = $_POST['column']; // Nama kolom di database (misal: esp1_mode)
    $val = $_POST['value'];  // Nilai baru (misal: MANUAL)
    mysqli_query($conn, "UPDATE controls SET $col='$val' WHERE id=1");
    echo json_encode(["status" => "Updated"]);
    exit();
}

// --- BAGIAN 3: ESP MEMBACA PERINTAH / WEB MEMBACA STATUS (GET) ---
if(isset($_GET['read_controls'])) {
    $res = mysqli_query($conn, "SELECT * FROM controls WHERE id=1");
    $row = mysqli_fetch_assoc($res);
    
    // Jika request dari ESP32 (format text simple biar gampang diparse di Arduino)
    if(isset($_GET['format']) && $_GET['format'] == 'esp') {
        // Format: ESP1_MODE|ESP1_SERVO|ESP2_MODE|ESP2_KIPAS|ESP2_LED
        echo $row['esp1_mode'] . "|" . $row['esp1_servo'] . "|" . $row['esp2_mode'] . "|" . $row['esp2_kipas'] . "|" . $row['esp2_led'];
    } else {
        // Format JSON untuk Website
        echo json_encode($row);
    }
    exit();
}

// --- BAGIAN 4: WEB MEMBACA DATA SENSOR TERAKHIR (GET) ---
if(isset($_GET['read_sensors'])) {
    $q1 = mysqli_query($conn, "SELECT * FROM sensor_logs WHERE device_id='ESP1' ORDER BY id DESC LIMIT 1");
    $esp1 = mysqli_fetch_assoc($q1);
    
    $q2 = mysqli_query($conn, "SELECT * FROM sensor_logs WHERE device_id='ESP2' ORDER BY id DESC LIMIT 1");
    $esp2 = mysqli_fetch_assoc($q2);
    
    echo json_encode(["esp1" => $esp1, "esp2" => $esp2]);
    exit();
}
?>