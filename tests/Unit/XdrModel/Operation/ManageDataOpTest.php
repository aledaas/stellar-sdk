<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\ManageDataOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;

class ManageDataOpTest extends TestCase
{
    public function testFromXdr()
    {
        $sourceOp = new ManageDataOp('testkey', 'testvalue');

        /** @var ManageDataOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof ManageDataOp);

        $this->assertEquals('testkey', $parsed->getKey());
        $this->assertEquals('testvalue', $parsed->getValue());
    }
}