<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Assets;

use Sui\Transactions\Transaction;
use Sui\Transactions\BuildTransactionOptions;
use MultipleChain\Sui\Utils;
use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Sui\Provider;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\CoinInterface;
use MultipleChain\Sui\Services\TransactionSigner;

class Coin implements CoinInterface
{
    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param Provider|null $provider
     */
    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Sui';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'SUI';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 9;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $balance = $this->provider->client->getBalance($owner);
        return new Number(Utils::fromMist((int) $balance->totalBalance), $this->getDecimals());
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

        $transaction = new Transaction(new BuildTransactionOptions($this->provider->client));

        $coin = $transaction->splitCoins($transaction->gas(), [Utils::toMist($amount)]);

        $transaction->transferObjects([$coin], $receiver);

        return new TransactionSigner($transaction);
    }
}
