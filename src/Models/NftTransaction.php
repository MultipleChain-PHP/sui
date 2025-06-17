<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\NftTransactionInterface;

class NftTransaction extends ContractTransaction implements NftTransactionInterface
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
     * @return string
     */
    public function getNftId(): int|string
    {
        $data = $this->getData();
        if (!$data) {
            return '';
        }
        $ixs = $this->getInputs('object', 'immOrOwnedObject');
        return $ixs ? ($ixs[0]->objectId ?? '') : '';
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param int|string $nftId
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, int|string $nftId): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if ($this->getNftId() !== $nftId) {
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
