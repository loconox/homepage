<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Profile\Application\Weather\Weather as WeatherService;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class Weather
{
    public function __construct(
        private readonly WeatherService $weatherService,
    ) {
    }

    /**
     * Fetch current weather data from my weather station via MQTT. This may take a few seconds.
     */
    #[McpTool(name: 'get_weather')]
    public function getWeather(): string
    {
        try {
            $data = $this->weatherService->getCurrentWeather();

            return json_encode([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
