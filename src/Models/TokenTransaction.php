<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Models;

use MultipleChain\Utils\Math;
use MultipleChain\Utils\Number;
use MultipleChain\Sui\Assets\Token;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\TokenTransactionInterface;

class TokenTransaction extends ContractTransaction implements TokenTransactionInterface
{
    /**
     * @return string
     */
    public function getReceiver(): string
    {
        $data = $this->getData();
        if (!$data) {
            return '';
        }
        $ixs = $this->getInputs('pure', 'address');
        return $ixs ? $ixs[0]->value : '';
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->getSigner();
    }

    /**
     * @return Number
     */
    public function getAmount(): Number
    {
        $address = $this->getAddress();
        $ixs = $this->getInputs('pure', 'u64');
        $value = (int) ($ixs ? $ixs[0]->value : 0);
        $decimals = (new Token($address))->getDecimals();
        $amount = Math::div($value, Math::pow(10, $decimals), $decimals);
        return new Number($amount, $decimals);
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param float $amount
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, float $amount): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if ($this->getAmount()->toFloat() !== $amount) {
            return TransactionStatus::FAILED;
        }

        if (AssetDirection::INCOMING === $direction) {
            if (strtolower($this->getReceiver()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
        } else {
            if (strtolower($this->getSender()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
        }

        return TransactionStatus::CONFIRMED;
    }
}
