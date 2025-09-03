<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Operation\AccountMergeOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;

class AccountMergeOpTest extends TestCase
{
    public function testFromXdr()
    {
        $source = new AccountMergeOp(Keypair::newFromRandom());

        /** @var AccountMergeOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($source->toXdr()));

        $this->assertTrue($parsed instanceof AccountMergeOp);
        $this->assertEquals($source->getDestination()->getAccountIdString(), $parsed->getDestination()->getAccountIdString());
    }
}