<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Operation\BumpSequenceOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;

class BumpSequenceOpTest extends TestCase
{
    public function testFromXdr()
    {
        $source = new BumpSequenceOp(new BigInteger('1234567890'));

        /** @var BumpSequenceOp $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($source->toXdr()));

        $this->assertTrue($parsed instanceof BumpSequenceOp);
        $this->assertEquals($source->getBumpTo()->toString(), $parsed->getBumpTo()->toString());
    }
}