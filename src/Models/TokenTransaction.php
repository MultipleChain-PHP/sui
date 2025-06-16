<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Sui\Assets\Token;
use MultipleChain\Interfaces\Models\TokenTransactionInterface;

class TokenTransaction extends ContractTransaction implements TokenTransactionInterface
{
    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return '0x';
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return '0x';
    }

    /**
     * @return Number
     */
    public function getAmount(): Number
    {
        return new Number('0', (new Token($this->getAddress()))->getDecimals());
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param float $amount
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, float $amount): TransactionStatus
    {
        return TransactionStatus::PENDING;
    }
}
