<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Tests\Models;

use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Sui\Tests\BaseTest;
use MultipleChain\Sui\Models\TokenTransaction;

class TokenTransactionTest extends BaseTest
{
    /**
     * @var TokenTransaction
     */
    private TokenTransaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new TokenTransaction($this->data->tokenTransferTx);
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
    public function testAmount(): void
    {
        $this->assertEquals(
            $this->tx->getAmount()->toFloat(),
            $this->data->tokenAmount
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
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->modelTestSender,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->modelTestSender,
                $this->data->tokenAmount
            ),
            TransactionStatus::FAILED
        );
    }
}
