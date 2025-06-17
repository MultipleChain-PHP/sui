<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Tests\Models;

use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Sui\Tests\BaseTest;
use MultipleChain\Sui\Models\Transaction;

class TransactionTest extends BaseTest
{
    /**
     * @var Transaction
     */
    private Transaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new Transaction($this->data->coinTransferTx);
    }

    /**
     * @return void
     */
    public function testId(): void
    {
        $this->assertEquals($this->data->coinTransferTx, $this->tx->getId());
    }

    /**
     * @return void
     */
    public function testData(): void
    {
        $this->assertIsObject($this->tx->getData());
    }

    /**
     * @return void
     */
    public function testWait(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->wait());
    }

    /**
     * @return void
     */
    public function testUrl(): void
    {
        $this->assertEquals(
            'https://suiscan.xyz/testnet/tx/38rQ6ThScL69gSLaWez9i8kj3CEw6eyqjkCoNPbcxPKN',
            $this->tx->getUrl()
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(strtolower($this->data->modelTestSender), strtolower($this->tx->getSigner()));
    }

    /**
     * @return void
     */
    public function testFee(): void
    {
        $this->assertEquals(0.00199788, $this->tx->getFee()->toFloat());
    }

    /**
     * @return void
     */
    public function testBlockNumber(): void
    {
        $this->assertEquals(205822572, $this->tx->getBlockNumber());
    }

    /**
     * @return void
     */
    public function testBlockTimestamp(): void
    {
        $this->assertEquals(1749451910786, $this->tx->getBlockTimestamp());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCount(): void
    {
        $this->assertGreaterThan(397, $this->tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testStatus(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->getStatus());
    }
}
