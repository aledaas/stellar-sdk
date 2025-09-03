<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Operation\AccountMergeOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;
use Aledaas\StellarSdk\XdrModel\Operation\SetOptionsOp;
use Aledaas\StellarSdk\XdrModel\Signer;
use Aledaas\StellarSdk\XdrModel\SignerKey;

class SetOptionsOpTest extends TestCase
{
    public function testFlags()
    {
        $op = new SetOptionsOp();

        $op->setAuthRequired(true);
        $op->setAuthRevocable(false);
        $this->assertTrue($op->isAuthRequired());
        $this->assertFalse($op->isAuthRevocable());
    }


    public function testFromXdr()
    {
        $inflationDestinationKeypair = Keypair::newFromRandom();
        $masterWeight = 10;
        $highThreshold = 9;
        $mediumThreshold = 8;
        $lowThreshold = 7;
        $homeDomain = 'example.com';

        $signer = new Signer(SignerKey::fromHashX('hashx'), 6);

        $sourceOp = new SetOptionsOp();
        $sourceOp->setInflationDestination($inflationDestinationKeypair->getPublicKey());
        $sourceOp->setAuthRequired(true);
        $sourceOp->setAuthRevocable(false);
        $sourceOp->setMasterWeight($masterWeight);
        $sourceOp->setHighThreshold($highThreshold);
        $sourceOp->setMediumThreshold($mediumThreshold);
        $sourceOp->setLowThreshold($lowThreshold);
        $sourceOp->setHomeDomain($homeDomain);
        $sourceOp->updateSigner($signer);


        /** @var SetOptionsOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof SetOptionsOp);

        $this->assertEquals($inflationDestinationKeypair->getAccountId(), $parsed->getInflationDestinationAccount()->getAccountIdString());
        $this->assertEquals(true, $parsed->isAuthRequired());
        $this->assertEquals(false, $parsed->isAuthRevocable());
        $this->assertEquals($masterWeight, $parsed->getMasterWeight());
        $this->assertEquals($highThreshold, $parsed->getHighThreshold());
        $this->assertEquals($mediumThreshold, $parsed->getMediumThreshold());
        $this->assertEquals($lowThreshold, $parsed->getLowThreshold());
        $this->assertEquals($homeDomain, $parsed->getHomeDomain());
    }
}