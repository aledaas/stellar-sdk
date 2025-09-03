<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\ManageOfferOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;
use Aledaas\StellarSdk\XdrModel\Price;

class ManageOfferOpTest extends TestCase
{
    public function testFromXdr()
    {
        $sellingAsset = Asset::newCustomAsset('SELLING', Keypair::newFromRandom());
        $buyingAsset = Asset::newCustomAsset('BUYING', Keypair::newFromRandom());
        $amount = 9000.00001;
        $price = new Price(1, 4000);
        $offerId = 128;

        $sourceOp = new ManageOfferOp(
            $sellingAsset,
            $buyingAsset,
            $amount,
            $price,
            $offerId
        );

        /** @var ManageOfferOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof ManageOfferOp);

        $this->assertEquals($sellingAsset->getAssetCode(), $parsed->getSellingAsset()->getAssetCode());
        $this->assertEquals($buyingAsset->getAssetCode(), $parsed->getBuyingAsset()->getAssetCode());
        $this->assertEquals($amount, $parsed->getAmount()->getScaledValue());
        $this->assertEquals(.00025, $parsed->getPrice()->toFloat());
        $this->assertEquals($offerId, $parsed->getOfferId());
    }
}