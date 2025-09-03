<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\AccountId;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;
use Aledaas\StellarSdk\XdrModel\Operation\PathPaymentOp;

class PathPaymentOpTest extends TestCase
{
    public function testFromXdr()
    {
        $sendingAsset = Asset::newCustomAsset('SEND', Keypair::newFromRandom());
        $sendMax = 100.99;
        $destinationKeypair = Keypair::newFromRandom();
        $destinationAsset = Asset::newCustomAsset('DEST', Keypair::newFromRandom());
        $desinationAmount = 500;

        $pathA = Asset::newCustomAsset('PATHA', Keypair::newFromRandom());
        $pathB = Asset::newCustomAsset('PATHB', Keypair::newFromRandom());

        $sourceOp = new PathPaymentOp($sendingAsset, $sendMax, $destinationKeypair, $destinationAsset, $desinationAmount);
        $sourceOp->addPath($pathA);
        $sourceOp->addPath($pathB);

        /** @var PathPaymentOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof PathPaymentOp);

        $this->assertEquals($sourceOp->getDestinationAccount()->getAccountIdString(), $parsed->getDestinationAccount()->getAccountIdString());
        $this->assertEquals($sendMax, $parsed->getSendMax()->getScaledValue());
        $this->assertEquals($destinationKeypair->getAccountId(), $parsed->getDestinationAccount()->getAccountIdString());
        $this->assertEquals($destinationAsset->getAssetCode(), $parsed->getDestinationAsset()->getAssetCode());
        $this->assertEquals($desinationAmount, $parsed->getDestinationAmount()->getScaledValue());
    }
}