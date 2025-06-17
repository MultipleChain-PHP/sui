<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Assets;

use Sui\Type\CoinStruct;
use Sui\Type\CoinMetadata;
use Sui\Transactions\Transaction;
use Sui\Transactions\BuildTransactionOptions;
use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\Assets\TokenInterface;
use MultipleChain\Sui\Services\TransactionSigner;

class Token extends Contract implements TokenInterface
{
    /**
     * @var null|CoinMetadata
     */
    private ?CoinMetadata $metadata = null;

    /**
     * @return CoinMetadata $metadata
     */
    public function getMetadata(): CoinMetadata
    {
        if ($this->metadata) {
            return $this->metadata;
        }
        return $this->metadata = $this->provider->client->getCoinMetadata($this->getAddress());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getMetadata()->name;
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->getMetadata()->symbol;
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return $this->getMetadata()->decimals;
    }

    /**
     * @param float $amount
     * @return int
     */
    private function toMist(float $amount): int
    {
        return (int) ($amount * (10 ** $this->getDecimals()));
    }

    /**
     * @param int $amount
     * @return float
     */
    private function fromMist(int $amount): float
    {
        return $amount / (10 ** $this->getDecimals());
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $balance = $this->provider->client->getBalance($owner, $this->getAddress());
        return new Number($this->fromMist((int) $balance->totalBalance), $this->getDecimals());
    }

    /**
     * @return Number
     */
    public function getTotalSupply(): Number
    {
        $supply = $this->provider->client->getTotalSupply($this->getAddress());
        return new Number($this->fromMist((int) $supply), $this->getDecimals());
    }

    /**
     * @param string $owner
     * @param string $spender
     * @return Number
     */
    public function getAllowance(string $owner, string $spender): Number
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($sender)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $amount = $this->toMist($amount);

        $coins = $this->provider->client->getCoins($sender, $this->getAddress());
        $transaction = new Transaction(new BuildTransactionOptions($this->provider->client));
        $enoughBalance = array_filter($coins->data, fn(CoinStruct $coin) => $coin->balance >= $amount);

        if (count($enoughBalance) > 0) {
            $transaction->transferObjects(
                [$transaction->splitCoins($transaction->object($enoughBalance[0]->coinObjectId), [$amount])],
                $receiver
            );
        } else {
            $coinObjectIds = array_map(fn(CoinStruct $coin) => $coin->coinObjectId, $coins->data);
            $primaryCoin = $transaction->object($coinObjectIds[0]);

            if (count($coinObjectIds) > 1) {
                $transaction->mergeCoins(
                    $primaryCoin,
                    array_map(fn(string $id) => $transaction->object($id), array_slice($coinObjectIds, 1))
                );
            }

            $transaction->transferObjects([$transaction->splitCoins($primaryCoin, [$amount])], $receiver);
        }

        return new TransactionSigner($transaction);
    }

    /**
     * @param string $spender
     * @param string $owner
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        float $amount
    ): TransactionSigner {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param float $amount
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, float $amount): TransactionSigner
    {
        throw new \Exception('Method not implemented.');
    }
}
