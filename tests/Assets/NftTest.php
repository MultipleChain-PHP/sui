<?php

declare(strict_types=1);

namespace MultipleChain\Sui\Tests\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Sui\Assets\NFT;
use MultipleChain\Sui\Tests\BaseTest;
use MultipleChain\Sui\Models\Transaction;

class NftTest extends BaseTest
{
    /**
     * @var NFT
     */
    private NFT $nft;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->nft = new NFT($this->data->nftTestAddress);
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $this->assertEquals('Test NFT 1', $this->nft->getName($this->data->nftTransferId));
    }

    /**
     * @return void
     */
    public function testSymbol(): void
    {
        $this->assertEquals('Test NFT 1', $this->nft->getSymbol($this->data->nftTransferId));
    }

    /**
     * @return void
     */
    public function testBalance(): void
    {
        $this->assertEquals(
            $this->data->nftBalanceTestAmount,
            $this->nft->getBalance($this->data->balanceTestAddress)->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testOwner(): void
    {
        $this->assertEquals(
            strtolower($this->data->receiverTestAddress),
            strtolower($this->nft->getOwner($this->data->nftId))
        );
    }

    /**
     * @return void
     */
    public function testTokenURI(): void
    {
        $this->assertEquals(
            'https://i.pinimg.com/736x/b6/51/40/b651403a18268e29a362121ab58541ce.jpg',
            $this->nft->getTokenURI($this->data->nftTransferId)
        );
    }

    // /**
    //  * @return void
    //  */
    // public function testApproved(): void
    // {
    //     $this->assertEquals(
    //         null,
    //         $this->nft->getApproved(600)
    //     );
    // }

    /**
     * @return void
     */
    public function testTransfer(): void
    {
        $signer = $this->nft->transfer(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->nftTransferId
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->nftTransactionTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        (new Transaction($signer->send()))->wait();

        $this->assertEquals(
            strtolower($this->nft->getOwner($this->data->nftTransferId)),
            strtolower($this->data->receiverTestAddress)
        );

        $signer2 = $this->nft->transfer(
            $this->data->receiverTestAddress,
            $this->data->senderTestAddress,
            $this->data->nftTransferId
        );

        $signer2 = $signer2->sign($this->data->receiverPrivateKey);

        (new Transaction($signer2->send()))->wait();

        $this->assertEquals(
            strtolower($this->nft->getOwner($this->data->nftTransferId)),
            strtolower($this->data->senderTestAddress)
        );
    }

    // /**
    //  * @return void
    //  */
    // public function testApprove(): void
    // {
    //     $customOwner = $this->data->nftTransactionTestIsActive
    //         ? $this->data->receiverTestAddress
    //         : $this->data->senderTestAddress;
    //     $customSpender = $this->data->nftTransactionTestIsActive
    //         ? $this->data->senderTestAddress
    //         : $this->data->receiverTestAddress;
    //     $customPrivateKey = $this->data->nftTransactionTestIsActive
    //         ? $this->data->receiverPrivateKey
    //         : $this->data->senderPrivateKey;

    //     $signer = $this->nft->approve(
    //         $customOwner,
    //         $customSpender,
    //         $this->data->nftTransferId
    //     );

    //     $signer = $signer->sign($customPrivateKey);

    //     if (!$this->data->nftTransactionTestIsActive) {
    //         $this->assertTrue(true);
    //         return;
    //     }

    //     (new Transaction($signer->send()))->wait();

    //     $this->assertEquals(
    //         strtolower($this->nft->getApproved($this->data->nftTransferId)),
    //         strtolower($this->data->senderTestAddress)
    //     );
    // }

    // /**
    //  * @return void
    //  */
    // public function testTransferFrom(): void
    // {
    //     if (!$this->data->nftTransactionTestIsActive) {
    //         $this->assertTrue(true);
    //         return;
    //     }

    //     $signer = $this->nft->transferFrom(
    //         $this->data->senderTestAddress,
    //         $this->data->receiverTestAddress,
    //         $this->data->senderTestAddress,
    //         $this->data->nftTransferId
    //     );

    //     (new Transaction($signer->sign($this->data->senderPrivateKey)->send()))->wait();

    //     $this->assertEquals(
    //         strtolower($this->nft->getOwner($this->data->nftTransferId)),
    //         strtolower($this->data->senderTestAddress)
    //     );
    // }
}
