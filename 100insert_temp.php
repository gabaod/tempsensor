<?php

if(isset($_GET["temperature"]) && isset($_GET["humidity"]) && isset($_GET["vpd"])) {
   $temperature = filter_input(INPUT_GET, "temperature", FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $humidity = filter_input(INPUT_GET, "humidity", FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $vpd = filter_input(INPUT_GET, "vpd", FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   include('100credentials.php');

   // Create MySQL connection fom PHP to MySQL server
   $connection = new mysqli($servername, $username, $password, $database_name);
   // Check connection
   if ($connection->connect_error) {
      die("MySQL connection failed: " . $connection->connect_error);
   }

   // Define the function to be triggered
   function processEmail($humidity, $temperature, $toEmailAddress, $fromEmailAddress) {
      //echo "Trigger function executed!<br>";
      // Place your custom logic here
      $to = $toEmailAddress;
      $subject = "Warning: Temp or Humidity is out of range";

      $message = "
      <html>
      <head>
      <title>Temp or Humidity is out of range</title>
      </head>
      <body>
      <p><br><br><br></p>
      <table>
      <tr>
      <td>Your current Temperature reading is " . $temperature . "</td>
      <td>Your current Humidity reading is " . $humidity . "</td>
      </tr>
      </table>
      </body>
      </html>
      ";

      // Always set content-type when sending HTML email
      $headers = "MIME-Version: 1.0" . "\r\n";
      $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
      $headers .= "From: <". $fromEmailAddress . ">\r\n";

      mail($to,$subject,$message,$headers);

   }

   $date = date("Y-m-d H:i:s");
   $stmt = $connection->prepare("INSERT INTO sensor_data (temperature, humidity, vpd, timestamp) VALUES (?, ?, ?, ?)");
   $stmt->bind_param("ddds", $temperature, $humidity, $vpd, $date);

   if ($stmt->execute()) {
      $lastInsertId = $connection->insert_id; // Correctly getting last insert ID
      echo "New record created successfully at $date<br>\n";

     // Fetch trigger status
     $statusResult = $connection->query("SELECT * FROM trigger_status WHERE id = 1");
     if ($statusResult && $statusResult->num_rows > 0) {
        $status = $statusResult->fetch_assoc();
     } else {
        $status = ['is_triggered' => 0, 'last_trigger_id' => 0, 'manual_override' => 0]; // Default values
     }

     // Ensure `last_trigger_id` is valid before subtraction
     $lastTriggerId = intval($status['last_trigger_id']);
     $isTriggered = intval($status['is_triggered']);
     $manOverride = intval($status['manual_override']);

     $outOfRange = ($temperature < $minTemp || $temperature > $maxTemp || $humidity < $minHumidity || $humidity > $maxHumidity);

     //process email section if enabaled
     if ($manOverride == 1) {
        if ($outOfRange && !$isTriggered) {
           // Trigger the function and update trigger status
           processEmail($humidity, $temperature, $toEmailAddress, $fromEmailAddress);
           $connection->query("UPDATE trigger_status SET last_trigger_id = '$lastInsertId', is_triggered = 1, updated_at = NOW() WHERE id = 1");
        }

        // Check if waitTime has passed in new db records that where inserted since the last trigger
        if ($isTriggered && ($lastInsertId - $lastTriggerId >= $waitTime)) {
           // Reset the trigger status
           $connection->query("UPDATE trigger_status SET last_trigger_id = '$lastInsertId', is_triggered = 0, updated_at = NOW() WHERE id = 1");
        }
     }
   } else {
     echo "Error: " . $stmt->error;
   }

   $stmt->close();
   $connection->close();
} else {
   echo "You must set temperature, humidity and vpd in the HTTP request";
}
?>
