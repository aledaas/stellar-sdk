<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\AccountId;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\AllowTrustOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;

class AllowTrustOpTest extends TestCase
{
    public function testFromXdr()
    {
        $sourceOp = new AllowTrustOp(Asset::newCustomAsset('TST', Keypair::newFromRandom()), new AccountId(Keypair::newFromRandom()));
        $sourceOp->setIsAuthorized(true);

        /** @var AllowTrustOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof AllowTrustOp);

        $this->assertEquals('TST', $parsed->getAsset()->getAssetCode());
        $this->assertEquals($sourceOp->getTrustor()->getAccountIdString(), $parsed->getTrustor()->getAccountIdString());
    }
}