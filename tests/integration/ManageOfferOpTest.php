<?php


namespace Aledaas\StellarSdk\Test\Integration;


use Aledaas\StellarSdk\Test\Util\IntegrationTest;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\Operation\ManageOfferOp;
use Aledaas\StellarSdk\XdrModel\Price;

class ManageOfferOpTest extends IntegrationTest
{
    /**
     * @group requires-integrationnet
     * @throws \Aledaas\StellarSdk\Horizon\Exception\HorizonException
     * @throws \ErrorException
     */
    public function testSubmitOffer()
    {
        $usdBankKeypair = $this->fixtureAccounts['usdBankKeypair'];
        $usdAsset = $this->fixtureAssets['usd'];

        // Sell 100 USDTEST for 0.02 XLM
        $xlmPrice = new Price(2, 100);
        $offerOp = new ManageOfferOp($usdAsset, Asset::newNativeAsset(), 100, $xlmPrice);

        $response = $this->horizonServer->buildTransaction($usdBankKeypair)
            ->addOperation($offerOp)
            ->submit($usdBankKeypair);

        // todo: add support for offers and verify here
        // todo: verify canceling an offer
    }
}