<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BlockByTimestamp
{
    private $fraxApiBaseUrl;
    private $fraxApiKey;
    private $requestsPerSecond = 5;
    private $interval;
    private $lastRequestTime = 0;

    public function __construct()
    {
        $this->fraxApiBaseUrl = config('services.frax.base_url');
        $this->fraxApiKey = config('services.frax.api_key');
        $this->interval = 1000 / $this->requestsPerSecond;

        if (!$this->fraxApiBaseUrl || !$this->fraxApiKey) {
            throw new \Exception('FraxBaseUrl or FraxApiKey is not defined in environment variables.');
        }
    }

    public function getBlockByTimestamp(int $timestamp): int
    {
        $now = microtime(true) * 1000;
        $timeSinceLastRequest = $now - $this->lastRequestTime;

        if ($timeSinceLastRequest < $this->interval) {
            usleep(($this->interval - $timeSinceLastRequest) * 1000);
        }

        try {
            $client = new Client();
            $response = $client->get($this->fraxApiBaseUrl, [
                'query' => [
                    'module' => 'block',
                    'action' => 'getblocknobytime',
                    'timestamp' => $timestamp,
                    'closest' => 'before',
                    'apikey' => $this->fraxApiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === '1') {
                $this->lastRequestTime = microtime(true) * 1000;
                return (int) $data['result'];
            } else {
                throw new \Exception($data['message'] ?? 'Error fetching block by timestamp.');
            }
        } catch (\Exception $e) {
            Log::error('Error fetching block by timestamp: ' . $e->getMessage());
            throw $e;
        }
    }
}
