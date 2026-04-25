<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function getWeatherData($city = 'Egypt') {
    // Using a free weather API that doesn't require authentication
    $url = "https://wttr.in/" . urlencode($city) . "?format=j1";

    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "timeout" => 10,
            "header" => "User-Agent: PHP Weather App\r\n"
        ]
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        return [
            'error' => 'Unable to fetch weather data',
            'city' => $city
        ];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Invalid JSON response',
            'city' => $city
        ];
    }

    // Extract current weather data
    $current = $data['current_condition'][0] ?? null;
    if (!$current) {
        return [
            'error' => 'No weather data available',
            'city' => $city
        ];
    }

    return [
        'name' => $data['nearest_area'][0]['areaName'][0]['value'] ?? $city,
        'temp' => $current['temp_C'] ?? 'N/A',
        'description' => $current['weatherDesc'][0]['value'] ?? 'N/A',
        'humidity' => $current['humidity'] ?? 'N/A',
        'wind_speed' => $current['windspeedKmph'] ?? 'N/A'
    ];
}

// Get city from query parameter or default to Egypt
$city = isset($_GET['city']) ? $_GET['city'] : 'Egypt';

$weatherData = getWeatherData($city);

echo json_encode($weatherData);
?>