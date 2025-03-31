<?php

header('Content-Type: application/json');

include('100credentials.php');

$connection = new mysqli($servername, $username, $password, $database_name);
if ($connection->connect_error) {
    echo json_encode(["success" => false, "error" => "Connection failed"]);
    exit();
}

// Get current timestamp or use user-provided timestamp
$result = $connection->query("SELECT MAX(timestamp) AS latest_timestamp FROM sensor_data");
$row = $result->fetch_assoc();
$currentTime = $row['latest_timestamp'] ? strtotime($row['latest_timestamp']) : time();
$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : time();
$endTime = date("Y-m-d H:i:s", $timestamp);
$startTime = date("Y-m-d H:i:s", $timestamp - 86400); // 24 hours earlier

$sql = "SELECT id, temperature, humidity, vpd, timestamp FROM sensor_data 
        WHERE timestamp BETWEEN '$startTime' AND '$endTime' 
        ORDER BY timestamp ASC";

$result = $connection->query($sql);
$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
	$temperature1 = $row["temperature"];
        $humidity1 = $row["humidity"];
        $id = $row["id"];

        $data[] = [
            "id" => $row["id"],
            "temperature" => $row["temperature"],
            "humidity" => $row["humidity"],
            "vpd" => $row["vpd"],
            "timestamp" => $row["timestamp"]
        ];
    }
}

$statusResult = $connection->query("SELECT manual_override FROM trigger_status WHERE id = 1");
if ($statusResult && $statusResult->num_rows > 0) {
    $row = $statusResult->fetch_assoc();
    $manualOverride = isset($row['manual_override']) ? intval($row['manual_override']) : 0;
}

$newStatus = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['toggle_override'])) {
    $newStatus = $manualOverride ? 0 : 1; // Toggle between 0 and 1
    $connection->query("UPDATE trigger_status SET manual_override = $newStatus WHERE id = 1");
    $manualOverride = $newStatus; // Update the variable for immediate UI feedback
}

echo json_encode([
    "success" => true,
    "newStatus" => $newStatus,
    "timestamp" => $currentTime,
    "data" => $data
]);

$connection->close();

?>
