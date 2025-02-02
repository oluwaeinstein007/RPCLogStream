<?php

namespace App\Services;

use App\Models\Holder;
use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use App\Models\Logger;
use Exception;
use Illuminate\Support\Facades\Log;

class LogStreamService
{
    private $web3;
    private $blockByTimestamp;
    private $balanceUtil;

    public function __construct(BlockByTimestamp $blockByTimestamp, BalanceUtil $balanceUtil)
    {
        $this->blockByTimestamp = $blockByTimestamp;
        $this->balanceUtil = $balanceUtil;

        // Initialize Web3 with the Ethereum provider URL
        $provider = new HttpProvider(new HttpRequestManager(config('services.ethereum.provider'), 10000));
        $this->web3 = new Web3($provider);
    }

    public function processLogs(string $tokenAddress)
    {
        $currentTimestamp = Logger::latest('timestamp')->value('timestamp') ?? 1709785187;

        while (true) {
            try {
                $fromBlock = $this->blockByTimestamp->getBlockByTimestamp($currentTimestamp);
                $nextTimestamp = $currentTimestamp + (25 * 24 * 60 * 60);
                $toBlock = ($nextTimestamp > time()) ? 'latest' : $this->blockByTimestamp->getBlockByTimestamp($nextTimestamp);

                // Fetch logs and process balances
                $logs = $this->fetchLogs($tokenAddress, $fromBlock, $toBlock);
                $balances = $this->processLogsData($logs);

                // Save balances once after processing all logs
                if (!empty($balances)) {
                    $this->balanceUtil->saveBalancesToDatabase($balances);
                }

                // Log timestamp and block number
                Logger::create(['timestamp' => $currentTimestamp, 'block_number' => $fromBlock]);

                $currentTimestamp = $nextTimestamp;
                if ($toBlock === 'latest') break;
            } catch (Exception $e) {
                Log::error('Error processing logs: ' . $e->getMessage());
                throw $e;
            }
        }
    }



    private function fetchLogs(string $tokenAddress, int $fromBlock, $toBlock): array
    {
        $logs = [];
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $this->web3->eth->getLogs([
                    'address' => $tokenAddress,
                    'fromBlock' => $fromBlock,
                    'toBlock' => $toBlock,
                ], function ($err, $response) use (&$logs) {
                    if ($err) throw new Exception($err->getMessage());
                    $logs = $response;
                });
                return $logs;
            } catch (Exception $e) {
                Log::warning("Attempt $attempt failed: " . $e->getMessage());
                sleep(1);
            }
        }
        throw new Exception('Failed to fetch logs after 3 attempts.');
    }



    private function processLogsData(array $logs): array
    {
        $balances = [];
        $contractABI = $this->loadContractABI();

        foreach ($logs as $log) {
            try {
                $decodedLog = $this->decodeLogManually((array) $log, $contractABI);
                if (!isset($decodedLog['event'], $decodedLog['data'])) continue;
                $this->updateBalance($balances, $decodedLog['event'], $decodedLog['data']);
            } catch (Exception $e) {
                Log::error('Error decoding log: ' . $e->getMessage());
            }
        }
        return $balances;
    }



    private function updateBalance(array &$balances, string $event, array $data): void
    {
        $amount = $this->formatAmount($data['value'] ?? '0');

        if ($event === 'Transfer') {
            $from = $data['from'] ?? null;
            $to = $data['to'] ?? null;

            if ($from && $from !== '0x0000000000000000000000000000000000000000') {
                $balances[$from] = ($balances[$from] ?? 0) - $amount;
            }

            if ($to) {
                $balances[$to] = ($balances[$to] ?? 0) + $amount;
            }
        } elseif ($event === 'Mint') {
            $to = $data['to'] ?? null;

            if ($to) {
                $balances[$to] = ($balances[$to] ?? 0) + $amount;
            }
        }
    }



    private function loadContractABI(): array
    {
        $contractABIPath = storage_path('app/abi/contractABI.json');
        if (!file_exists($contractABIPath)) throw new Exception('ABI file is missing.');
        return json_decode(file_get_contents($contractABIPath), true) ?: throw new Exception('Invalid ABI file format.');
    }



    private function formatAmount($amount): string
    {
        return is_string($amount) && strpos($amount, '0x') === 0 ? hexdec($amount) : (string) ($amount ?: '0');
    }



    private function decodeLogManually(array $log, array $contractABI): array
    {
        foreach ($contractABI as $item) {
            if ($item['type'] === 'event') {
                if ($item['name'] === 'Transfer') {
                    return [
                        'event' => 'Transfer',
                        'data' => [
                            'from' => '0x' . substr($log['topics'][1], 26),
                            'to' => '0x' . substr($log['topics'][2], 26),
                            'value' => hexdec(substr($log['data'], 2)),
                        ],
                    ];
                } elseif ($item['name'] === 'Mint') {
                    return [
                        'event' => 'Mint',
                        'data' => [
                            'to' => '0x' . substr($log['topics'][1], 26),
                            'value' => hexdec(substr($log['data'], 2)),
                        ],
                    ];
                }
            }
        }
        throw new Exception('Unable to decode log. Event not found in ABI.');
    }
}
