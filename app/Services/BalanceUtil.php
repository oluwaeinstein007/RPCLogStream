<?php

namespace App\Services;

use App\Models\Holder;

class BalanceUtil
{
    public function saveBalancesToDatabase(array $balances): void
    {
        foreach ($balances as $address => $balance) {
            Holder::updateOrCreate(
                ['address' => $address],
                ['balance' => $balance]
            );
        }
    }
}
