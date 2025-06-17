<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Models;

use MultipleChain\Sui\Utils;
use MultipleChain\Utils\Math;
use Sui\Type\TransactionBlock;
use Sui\Type\TransactionKind;
use MultipleChain\Utils\Number;
use MultipleChain\Sui\Provider;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Sui\Assets\Coin;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\TransactionInterface;

class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var TransactionBlock|null
     */
    private ?TransactionBlock $data = null;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param string $id
     * @param Provider|null $provider
     */
    public function __construct(string $id, ?ProviderInterface $provider = null)
    {
        $this->id = $id;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return TransactionBlock|null
     */
    public function getData(): ?TransactionBlock
    {
        try {
            if (null !== $this->data) {
                return $this->data;
            }

            $response = $this->provider->client->getTransactionBlock($this->id, [
                'showInput' => true,
                'showEffects' => true,
                'showEvents' => true,
                'showRawInput' => true,
                'showBalanceChanges' => true,
                'showObjectChanges' => true
            ]);

            if (!$response->transaction) {
                return null;
            }

            return $this->data = $response;
        } catch (\Throwable $th) {
            throw new \RuntimeException(ErrorType::RPC_REQUEST_ERROR->value);
        }
    }

    /**
     * @param int|null $ms
     * @return TransactionStatus
     */
    public function wait(?int $ms = 4000): TransactionStatus
    {
        try {
            $this->provider->client->waitForTransaction($this->id);

            if (TransactionStatus::PENDING != ($status = $this->getStatus())) {
                return $status;
            }

            sleep($ms / 1000);

            return $this->wait($ms);
        } catch (\Throwable $th) {
            return TransactionStatus::FAILED;
        }
    }

    /**
     * @return TransactionType
     */
    public function getType(): TransactionType
    {
        $data = $this->getData();
        if (!$data) {
            return TransactionType::GENERAL;
        }

        switch (count($data->objectChanges ?? [])) {
            case 2:
                return 1 === count($data->balanceChanges ?? [])
                    ? TransactionType::NFT
                    : TransactionType::COIN;
            case 3:
                return TransactionType::TOKEN;
            default:
                return TransactionType::CONTRACT;
        }
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->provider->node['explorerUrl'] . 'tx/' . $this->id;
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        return $this->getData()?->transaction?->data?->sender ?? '';
    }

    /**
     * @return Number
     */
    public function getFee(): Number
    {
        $data = $this->getData();
        $decimals = (new Coin())->getDecimals();
        $storageCost = Utils::fromMist((int) ($data?->effects?->gasUsed->storageCost ?? 0));
        $storageRebate = Utils::fromMist((int) ($data?->effects?->gasUsed->storageRebate ?? 0));
        $computationCost = Utils::fromMist((int) ($data?->effects?->gasUsed->computationCost ?? 0));
        $result = Math::abs(
            Math::sub(
                Math::add(
                    $storageCost,
                    $computationCost,
                    $decimals
                ),
                $storageRebate,
                $decimals
            )
        );
        return new Number($result, $decimals);
    }

    /**
     * @return int
     */
    public function getBlockNumber(): int
    {
        return (int) ($this->getData()?->checkpoint ?? 0);
    }

    /**
     * @return int
     */
    public function getBlockTimestamp(): int
    {
        return (int) ($this->getData()?->timestampMs ?? 0);
    }

    /**
     * @return int
     */
    public function getBlockConfirmationCount(): int
    {
        $blockNumber = $this->getBlockNumber();
        $blockCount = $this->provider->client->getLatestCheckpointSequenceNumber();
        $confirmationCount = (int) $blockCount - $blockNumber;
        return $confirmationCount < 0 ? 0 : $confirmationCount;
    }

    /**
     * @return TransactionStatus
     */
    public function getStatus(): TransactionStatus
    {
        $data = $this->getData();
        if ('success' === $data?->effects?->status->status) {
            return TransactionStatus::CONFIRMED;
        } elseif ('failure' === $data?->effects?->status->status) {
            return TransactionStatus::FAILED;
        } else {
            return TransactionStatus::PENDING;
        }
    }

    /**
     * @return TransactionKind|null
     */
    protected function getTransaction(): ?TransactionKind
    {
        return $this->getData()?->transaction->data->transaction ?? null;
    }

    /**
     * @param string $type
     * @param string|null $vType
     * @return array<mixed>|null
     */
    protected function getInputs(string $type, ?string $vType = null): ?array
    {
        $tx = $this->getTransaction();
        if ($tx) {
            return array_filter($tx->inputs ?? [], function ($input) use ($type, $vType) {
                if ($vType && 'pure' === $input->type) {
                    return $input->valueType === $vType;
                }
                return $input->type === $type;
            });
        }
        return null;
    }
}
