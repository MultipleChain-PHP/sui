<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Services;

use MultipleChain\Sui\Provider;
use Sui\Transactions\Transaction;
use Sui\Keypairs\Ed25519\Keypair;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Services\TransactionSignerInterface;

class TransactionSigner implements TransactionSignerInterface
{
    /**
     * @var Transaction
     */
    private Transaction $rawData;

    /**
     * @var array<mixed>
     */
    private array $signedData;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param mixed $rawData
     * @param Provider|null $provider
     * @return void
     */
    public function __construct(mixed $rawData, ?ProviderInterface $provider = null)
    {
        $this->rawData = $rawData;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @param string $privateKey
     * @return TransactionSignerInterface
     */
    public function sign(string $privateKey): TransactionSignerInterface
    {
        $keypair = Keypair::fromSecretKey($privateKey);
        $this->rawData->setSenderIfNotSet($keypair->toSuiAddress());
        $this->signedData = $keypair->signTransaction($this->rawData->build());
        return $this;
    }

    /**
     * @return string Transaction id
     */
    public function send(): string
    {
        $result = $this->provider->client->executeTransactionBlock(
            $this->signedData['bytes'],
            $this->signedData['signature']
        );

        return $result->digest;
    }

    /**
     * @return Transaction
     */
    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    /**
     * @return array<mixed>
     */
    public function getSignedData(): mixed
    {
        return $this->signedData;
    }
}
