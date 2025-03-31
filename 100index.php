<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Data Charts</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h2 style="text-align: center;">Grow Room Conditions</h2>
<p style="text-align: center;">
    <span id="temperatureValue" style="color: red; font-size: 20px;">Temperature: -- °F</span>
</p>
<p style="text-align: center;">
    <span id="humidityValue" style="color: blue; font-size: 20px;">Humidity: -- %</span>
</p>

<canvas id="tempHumidityChart"></canvas>

<h2 style="text-align: center;">VPD Levels</h2>
<canvas id="vpdChart"></canvas>

<?php $manualOverride = 0; ?>
<!-- Navigation Buttons -->
<div style="text-align: center; margin-top: 20px;">
    <button onclick="changeTimePrev()">Previous 24 Hours</button>
    <button onclick="changeTimeNext()">Next 24 Hours</button>
    <button id="toggleOverride">
        <?php echo $manualOverride ? "Disable Email" : "Enable Email"; ?>
    </button>
</div>

<script>
    $(document).ready(function() {
        $("#toggleOverride").click(function() {
            $.post("100fetch_data.php", { toggle_override: 1 }, function(response) {
                if (response.success) {
                    $("#toggleOverride").text(response.newStatus ? "Disable Email" : "Enable Email");
                } else {
                    alert("Failed to update override status.");
                }
            }, "json");
        });
    });
</script>

<script>
    let tempHumidityChart;
    let vpdChart;
    let baseTimestamp = Math.floor(Date.now() / 1000); // Base timestamp that won't change on auto-refresh
    let currentTimestamp = baseTimestamp; // Auto-refresh timestamp, initialized to the base

    async function fetchData(timestamp = currentTimestamp) {
        try {
            const response = await fetch(`100fetch_data.php?timestamp=${timestamp}`);
            const result = await response.json();

            if (result.error) {
                console.error("Error fetching data:", result.error);
                return;
            }
//            console.log("Fetched Data:", result); // Debugging line
            currentTimestamp = result.timestamp; // Update to the latest timestamp
            baseTimestamp = timestamp;
            const labels = result.data.map(entry => entry.timestamp);
            const temperatures = result.data.map(entry => parseFloat(entry.temperature));
            const humidities = result.data.map(entry => parseFloat(entry.humidity));
            const vpds = result.data.map(entry => parseFloat(entry.vpd));

            updateCharts(labels, temperatures, humidities, vpds);
        } catch (error) {
            console.error("Fetch error:", error);
        } finally {
//            console.log("Fetch attempt completed, waiting for next interval...");
        }
    }

    function updateCharts(labels, temperatures, humidities, vpds) {
    	lastLabels = labels;
    	lastTemperatures = temperatures;
    	lastHumidities = humidities;
    	lastVpds = vpds;
    
        if (temperatures.length > 0) {
            document.getElementById("temperatureValue").innerText = `Temperature: ${temperatures[temperatures.length - 1]} °F`;
        }
        if (humidities.length > 0) {
            document.getElementById("humidityValue").innerText = `Humidity: ${humidities[humidities.length - 1]} %`;
        }

        // Temperature & Humidity Chart
        if (!tempHumidityChart) {
            const ctx1 = document.getElementById('tempHumidityChart').getContext('2d');
            tempHumidityChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Temperature (°F)',
                            data: temperatures,
                            borderColor: 'red',
                            backgroundColor: 'rgba(255, 0, 0, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Humidity (%)',
                            data: humidities,
                            borderColor: 'blue',
                            backgroundColor: 'rgba(0, 0, 255, 0.1)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
			x: {
				title: { display: true, text: '' },
				ticks: {
					autoSkip: true,
					maxRotation: 90,
					minRotation: 90,
				}
			},
                        y: { title: { display: true, text: 'Values' } }
                    },
                    plugins: {
                        zoom: {
                            pan: {
                                enabled: true,
                                mode: 'xy',  // Allow panning only on X-axis (time)
                                modifierKey: 'ctrl' // Hold Ctrl key to pan
                            },
                            zoom: {
                                wheel: {
                                    enabled: true,  // Enable zooming with mouse wheel
                                    modifierKey: 'ctrl' // Hold Ctrl key to zoom
                                },
                                pinch: {
                                    enabled: true  // Enable zooming on touch devices
                                },
                                drag: {
                                    enabled: true, // Enable zooming by dragging
                                    backgroundColor: 'rgba(0,0,0,0.2)'
                                },
                                mode: 'xy'  // Zoom only on X-axis
                            }
                        }
                    }
                }
            });
        } else {
            tempHumidityChart.data.labels = labels;
            tempHumidityChart.data.datasets[0].data = temperatures;
            tempHumidityChart.data.datasets[1].data = humidities;
            tempHumidityChart.update();
        }

        // VPD Chart
        if (!vpdChart) {
            const ctx2 = document.getElementById('vpdChart').getContext('2d');
            vpdChart = new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '',
                            data: vpds,
                            borderColor: 'black',
                            backgroundColor: 'rgba(0, 0, 0, 0.1)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                                title: { display: true, text: '' },
                                ticks: {
                                        autoSkip: true,
                                        maxRotation: 90,
                                        minRotation: 90,
                                }
                        },
                        y: { title: { display: true, text: 'VPD Value' } }
                    }
                }
            });
        } else {
            vpdChart.data.labels = labels;
            vpdChart.data.datasets[0].data = vpds;
            vpdChart.update();
        }
    }

    function changeTimePrev(seconds) {
        baseTimestamp = baseTimestamp - 86400;  // Add 24 hours (86400 seconds)
        //console.log("Previous 24 hours timestamp:", baseTimestamp);
        fetchData(baseTimestamp);
    }

    function changeTimeNext(seconds) {
        baseTimestamp = baseTimestamp + 86400;  // Add 24 hours (86400 seconds)
        //console.log("Next 24 hours timestamp:", baseTimestamp);
        fetchData(baseTimestamp);
    }

    setInterval(() => {
        //console.log("Auto-refreshing data...");
        fetchData(currentTimestamp);
    }, 60000); // wait 1min and refresh

    // Initial fetch
    fetchData();

</script>

</body>
</html>
