
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Horizon\ApiClient;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\AssetCode4;
use Aledaas\StellarSdk\XdrModel\AssetType;
use Aledaas\StellarSdk\XdrModel\AccountId;
use Aledaas\StellarSdk\XdrModel\Price;
use Aledaas\StellarSdk\XdrModel\Operation\ManageOfferOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;
use Aledaas\StellarSdk\Transaction\TransactionBuilder;

// Paso 1: Setup de red y claves
$horizon = ApiClient::testNet();
$sourceKeypair = Keypair::fromSecret('SB...'); // Reemplaza con tu secret real
$sourceAccountId = $sourceKeypair->getPublicKey();
$account = $horizon->getAccount($sourceAccountId);

// Paso 2: Crear assets
$selling = new Asset();
$selling->setType(AssetType::ALPHANUM4);
$selling->setAlphaNum4(new AssetCode4('FAvo', new AccountId('GCU33F5DTSWATAJ...'))); // Reemplaza el issuer

$buying = new Asset(); // XLM es el asset nativo
$buying->setType(AssetType::NATIVE);

// Paso 3: Crear operación
$manageOffer = new ManageOfferOp();
$manageOffer->setSelling($selling);
$manageOffer->setBuying($buying);
$manageOffer->setAmount("1000000000"); // 100 tokens (XLM = 7 decimales)
$manageOffer->setPrice(Price::fromFloat(0.5)); // 0.5 XLM
$manageOffer->setOfferId(0);

$operation = new Operation();
$operation->setSourceAccount(new AccountId($sourceAccountId));
$operation->setBody($manageOffer); // El método correcto según tu SDK

// Paso 4: Crear transacción
$builder = TransactionBuilder::forAccount($account)
    ->addOperation($operation)
    ->setTimeout(30)
    ->build();

$builder->sign([$sourceKeypair]);

// Paso 5: Enviar transacción
try {
    $response = $horizon->submitTransaction($builder);
    echo "✅ Transacción enviada:\n";
    echo json_encode($response->toArray(), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
