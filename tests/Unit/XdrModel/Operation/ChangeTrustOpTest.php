<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\AccountMergeOp;
use Aledaas\StellarSdk\XdrModel\Operation\ChangeTrustOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;

class ChangeTrustOpTest extends TestCase
{
    public function testFromXdr()
    {
        $sourceOp = new ChangeTrustOp(Asset::newCustomAsset('TRUST', Keypair::newFromRandom()), 8888);

        /** @var ChangeTrustOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof ChangeTrustOp);

        $this->assertEquals('TRUST', $parsed->getAsset()->getAssetCode());
        $this->assertEquals(8888, $parsed->getLimit()->getScaledValue());
    }
}