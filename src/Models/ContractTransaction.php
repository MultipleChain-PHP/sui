<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Models;

use Sui\Constants;
use MultipleChain\Interfaces\Models\ContractTransactionInterface;

class ContractTransaction extends Transaction implements ContractTransactionInterface
{
    /**
     * @return string
     */
    public function getAddress(): string
    {
        $data = $this->getData();
        if (!$data) {
            return '';
        }
        foreach (($data->objectChanges ?? []) as $change) {
            if ('published' === $change->type || str_contains($change->objectType, Constants::SUI_TYPE_ARG)) {
                continue;
            }
            preg_match('/0x2::coin::Coin<(.+)>/', $change->objectType, $matches);
            return isset($matches[1]) ? $matches[1] : $change->objectType;
        }

        return '';
    }
}
