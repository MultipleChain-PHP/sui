<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Tests\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Sui\Tests\BaseTest;
use MultipleChain\Sui\Models\NftTransaction;

class NftTransactionTest extends BaseTest
{
    /**
     * @var NftTransaction
     */
    private NftTransaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new NftTransaction($this->data->nftTransferTx);
    }

    /**
     * @return void
     */
    public function testReceiver(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getReceiver()),
            strtolower($this->data->modelTestReceiver)
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getSender()),
            strtolower($this->data->modelTestSender)
        );
    }

    /**
     * @return void
     */
    public function testSigner(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getSigner()),
            strtolower($this->data->modelTestSender)
        );
    }

    /**
     * @return void
     */
    public function testNftId(): void
    {
        $this->assertEquals(
            $this->tx->getNftId(),
            $this->data->nftId
        );
    }

    /**
     * @return void
     */
    public function testVerifyTransfer(): void
    {
        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestReceiver,
                $this->data->nftId
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->modelTestSender,
                $this->data->nftId
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestSender,
                $this->data->nftId
            ),
            TransactionStatus::FAILED
        );
    }
}
