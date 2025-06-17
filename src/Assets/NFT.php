<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use Sui\Transactions\Transaction;
use Sui\Transactions\BuildTransactionOptions;
use MultipleChain\Interfaces\Assets\NftInterface;
use MultipleChain\Sui\Services\TransactionSigner;

class NFT extends Contract implements NftInterface
{
    /**
     * @var array<mixed>|null
     */
    private ?array $metadata = null;

    /**
     * Get metadata for the NFT.
     * @param string|null $address
     * @return array<mixed>|null
     */
    public function getMetadata(?string $address = null): ?array
    {
        $res = $this->provider->client->getObject($address ?? $this->getAddress(), [
            'showContent' => true,
            'showOwner' => true
        ]);

        if ('moveObject' == $res->content?->dataType) {
            $fields = $res->content?->fields ?? [];
            $this->metadata = [
                'name' => $fields['name'] ?? '',
                'owner' => $res->owner?->value ?? '',
                'symbol' => $fields['symbol'] ?? $fields['name'] ?? '',
                'description' => $fields['description'] ?? $fields['name'] ?? '',
                'image' => $fields['image'] ?? $fields['url'] ?? $fields['image_url'] ?? null,
            ];
        }

        return $this->metadata;
    }

    /**
     * @param string|null $address
     * @return string
     */
    public function getName(?string $address = null): string
    {
        $this->getMetadata($address);
        return $this->metadata['name'] ?? '';
    }

    /**
     * @param string|null $address
     * @return string
     */
    public function getSymbol(?string $address = null): string
    {
        $this->getMetadata($address);
        return $this->metadata['symbol'] ?? '';
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $res = $this->provider->client->getOwnedObjects(
            $owner,
            [
                'StructType' => $this->getAddress()
            ],
            [],
            null,
            50
        );
        return new Number(count($res->data), 0);
    }

    /**
     * @param int|string $tokenId
     * @return string
     */
    public function getOwner(int|string $tokenId): string
    {
        $this->getMetadata((string) $tokenId);
        return $this->metadata['owner'] ?? '';
    }

    /**
     * @param int|string $tokenId
     * @return string
     */
    public function getTokenURI(int|string $tokenId): string
    {
        $this->getMetadata((string) $tokenId);
        return $this->metadata['image'] ?? '';
    }

    /**
     * @param int|string $tokenId
     * @return string|null
     */
    public function getApproved(int|string $tokenId): ?string
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, int|string $tokenId): TransactionSigner
    {
        if ($this->getBalance($sender)->toFloat() <= 0) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $originalOwner = $this->getOwner($tokenId);

        if (strtolower($originalOwner) !== strtolower($sender)) {
            throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
        }

        $transaction = new Transaction(new BuildTransactionOptions($this->provider->client));

        $transaction->transferObjects([$transaction->object($tokenId)], $receiver);

        return new TransactionSigner($transaction);
    }

    /**
     * @param string $spender
     * @param string $owner
     * @param string $receiver
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        int|string $tokenId
    ): TransactionSigner {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, int|string $tokenId): TransactionSigner
    {
        throw new \Exception('Method not implemented.');
    }
}
