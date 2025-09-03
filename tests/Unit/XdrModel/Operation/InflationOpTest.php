<?php


namespace Aledaas\StellarSdk\Test\Unit\XdrModel\Operation;


use PHPUnit\Framework\TestCase;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\InflationOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;

class InflationOpTest extends TestCase
{
    public function testFromXdr()
    {
        $sourceOp = new InflationOp();

        /** @var InflationOpTest $parsed */
        $parsed = Operation::fromXdr(new XdrBuffer($sourceOp->toXdr()));

        $this->assertTrue($parsed instanceof InflationOp);
    }
}