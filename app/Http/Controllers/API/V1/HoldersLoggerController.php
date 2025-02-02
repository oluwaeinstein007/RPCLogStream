<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\LogStreamService;
use Illuminate\Http\Request;

class HoldersLoggerController extends Controller
{
    private $logStreamService;

    public function __construct(LogStreamService $logStreamService)
    {
        $this->logStreamService = $logStreamService;
    }

    public function processLogs(Request $request)
    {
        $tokenAddress = config('services.ethereum.token_address');
        return $this->logStreamService->processLogs($tokenAddress);

        return response()->json(['message' => 'Logs processed successfully.']);
    }
}
