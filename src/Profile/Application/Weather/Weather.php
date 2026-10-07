<?php

declare(strict_types=1);

namespace App\Profile\Application\Weather;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class Weather
{
    private const WIND_DIRECTIONS = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSO', 'SO', 'OSO', 'O', 'ONO', 'NO', 'NNO'];
    private const FORECAST_CONDITIONS = [0 => 'partly', 1 => 'rainy', 2 => 'cloudy', 3 => 'sunny', 4 => 'storm', 5 => 'snow'];
    private const WIND_CLASSES = [0 => 'none', 1 => 'light', 2 => 'moderate', 3 => 'strong', 4 => 'storm'];
    private const CACHE_KEY = 'wmr500_weather';
    private const CACHE_TTL = 35; // 35 seconds (station polls every 30s)

    private static ?\ArrayObject $cache = null;

    public function __construct(
        #[Autowire('%env(MQTT_HOST)%')]
        private readonly string $mqttHost,
        #[Autowire('%env(int:MQTT_PORT)%')]
        private readonly int $mqttPort,
    ) {
    }

    /**
     * @return array<string, mixed> Parsed weather data from WMR500 (cached)
     *
     * @throws \Exception
     */
    public function getCurrentWeather(): array
    {
        // Check cache first (expires after 35 seconds)
        if (self::$cache === null) {
            self::$cache = new \ArrayObject();
        }

        $cached = self::$cache[self::CACHE_KEY] ?? null;
        if ($cached !== null && isset($cached['expires']) && $cached['expires'] > time()) {
            return $cached['data'];
        }

        // Try to fetch fresh data from MQTT (with 35 second timeout to match poll interval)
        $rawData = $this->fetchFromMqtt(35);

        if ($rawData === null) {
            // If MQTT fails but we have stale cache, return it
            if ($cached !== null) {
                return $cached['data'];
            }

            throw new \Exception('No weather data available from WMR500');
        }

        // Parse and cache
        $parsed = $this->parseWMR500Json($rawData);

        // Store in cache with expiration
        self::$cache[self::CACHE_KEY] = [
            'data' => $parsed,
            'expires' => time() + self::CACHE_TTL,
        ];

        return $parsed;
    }

    /**
     * @throws \Exception
     */
    private function fetchFromMqtt(int $timeoutSeconds): ?string
    {
        $rawData = null;
        $startTime = microtime(true);
        $mqtt = new MqttClient($this->mqttHost, $this->mqttPort, uniqid());

        try {
            $settings = (new ConnectionSettings())->setKeepAliveInterval(10);
            $mqtt->connect($settings);

            $mqtt->subscribe('enno/in/json', function (string $topic, string $message) use (&$rawData): void {
                $rawData = $message;
            }, 0);

            // Wait for data with timeout - process one iteration at a time
            while ($rawData === null && (microtime(true) - $startTime) < $timeoutSeconds) {
                try {
                    $loopStart = microtime(true);
                    $mqtt->loopOnce(floatval($loopStart), allowSleep: true, sleepMicroseconds: 100000);
                    if ($rawData !== null) {
                        break; // Exit loop as soon as data received
                    }
                } catch (\Exception) {
                    // Continue looping on error
                    usleep(100000); // Sleep 100ms to avoid busy loop
                }
            }

            $mqtt->unsubscribe('enno/in/json');
            $mqtt->disconnect();
        } catch (\Exception $e) {
            try {
                $mqtt->disconnect();
            } catch (\Exception) {
                // Ignore
            }

            // Return null instead of throwing - let caller decide what to do
            return null;
        }

        return $rawData;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseWMR500Json(string $json): array
    {
        $data = json_decode($json, associative: true);
        if (!$data || !isset($data['data']['6'])) {
            throw new \Exception('Invalid WMR500 JSON structure');
        }

        $station = $data['data']['6'];
        $result = [
            'timestamp' => $data['ts'] ?? null,
        ];

        // Parse only outdoor data (channel 1, in Celsius)
        if (isset($station['outdoor']['channel1'])) {
            $result['outdoor'] = $this->parseOutdoorData($station['outdoor']['channel1']);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $indoor
     *
     * @return array<string, mixed>
     */
    private function parseIndoorData(array $indoor): array
    {
        $result = [];

        // Temperature & humidity (w9) - convert F to C
        if (isset($indoor['w9'])) {
            $w9 = $indoor['w9'];
            $result['temperature'] = $this->fahrenheitToCelsius($w9['c91'] ?? null);
            $result['humidity'] = $this->sanitizeValue($w9['c96'] ?? null);
            $result['heat_index'] = $this->fahrenheitToCelsius($w9['c99'] ?? null);
            $result['dew_point'] = $this->fahrenheitToCelsius($w9['c913'] ?? null);
        }

        // Moon phase
        if (isset($indoor['moonphase'])) {
            $phases = ['first quarter', 'full moon', 'new moon', 'third quarter', 'waning crescent', 'waning gibbous', 'waxing crescent', 'waxing gibbous'];
            $result['moon_phase'] = $phases[$indoor['moonphase']] ?? null;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $channel
     *
     * @return array<string, mixed>
     */
    private function parseOutdoorData(array $channel): array
    {
        $result = [];

        // Temperature & humidity (w3) - convert F to C
        if (isset($channel['w3'])) {
            $w3 = $channel['w3'];
            $result['temperature'] = $this->fahrenheitToCelsius($w3['c31'] ?? null);
            $result['humidity'] = $this->sanitizeValue($w3['c35'] ?? null);
            $result['heat_index'] = $this->fahrenheitToCelsius($w3['c39'] ?? null);
            $result['dew_point'] = $this->fahrenheitToCelsius($w3['c313'] ?? null);
        }

        // Wind (w2)
        if (isset($channel['w2'])) {
            $w2 = $channel['w2'];
            $result['wind'] = [
                'gust_speed' => $this->sanitizeValue($w2['c21'] ?? null),
                'average_speed' => $this->sanitizeValue($w2['c22'] ?? null),
                'gust_direction' => self::WIND_DIRECTIONS[$w2['c23'] ?? -1] ?? null,
                'average_direction' => self::WIND_DIRECTIONS[$w2['c24'] ?? -1] ?? null,
                'dominant_direction' => self::WIND_DIRECTIONS[$w2['c25'] ?? -1] ?? null,
                'wind_chill' => $this->sanitizeValue($w2['c26'] ?? null),
                'class' => self::WIND_CLASSES[$w2['c28'] ?? -1] ?? null,
            ];
        }

        // Rainfall (w4)
        if (isset($channel['w4'])) {
            $w4 = $channel['w4'];
            $result['rain'] = [
                'today_total' => $this->sanitizeValue($w4['c41'] ?? null),
                'current_rate' => $this->sanitizeValue($w4['c42'] ?? null),
                'max_rate' => $this->sanitizeValue($w4['c43'] ?? null),
            ];
        }

        // Pressure (w5)
        if (isset($channel['w5'])) {
            $w5 = $channel['w5'];
            $forecast = $w5['c51'] ?? -1;
            $trend = $w5['c52'] ?? -1;
            $result['pressure'] = [
                'value' => $this->sanitizeValue($w5['c53'] ?? null),
                'forecast' => self::FORECAST_CONDITIONS[$forecast] ?? null,
                'trend' => [0 => 'stable', 1 => 'rising', 2 => 'falling'][$trend] ?? null,
            ];
        }

        return $result;
    }

    private function fahrenheitToCelsius(mixed $value): mixed
    {
        if ($value === null || $value === 210) {
            return null;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        $celsius = ($value - 32) * 5 / 9;

        return round($celsius, 1);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        // 210 = NaN/invalid in WMR500
        if ($value === 210 || $value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $parsed = (float) $value;

            return $parsed === (int) $parsed ? (int) $parsed : $parsed;
        }

        return $value;
    }
}
