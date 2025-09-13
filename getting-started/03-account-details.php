<?php

$secret = 'SCJKVBHYMXSASDIM5NPG6JKHZECUUZFZJVWPTVAI6WUDMLVRF4Q5ERGI';
$sellingAssetCode = 'FAvor';
$sellingAssetIssuer = 'GCU33F5DTSWATAJLVYDRFAQOQEU27QUNU7NFPNPZQW5RAN3JWVJQ26KT';
$buyingAssetCode = 'XLM';
$buyingAssetIssuer = 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF'; // Native asset
$amount = '100';
$price = '0.5';
$offerId = 0; // Puede ser 0 para crear nueva oferta



require __DIR__ . '/../vendor/autoload.php';

use Aledaas\StellarSdk\Server;

// See 01-create-account.php for where this was generated
$publicAccountId = 'GCU33F5DTSWATAJLVYDRFAQOQEU27QUNU7NFPNPZQW5RAN3JWVJQ26KT';

$server = Server::testNet();

$account = $server->getAccount($publicAccountId);

print 'Balances for account ' . $publicAccountId . PHP_EOL;

foreach ($account->getBalances() as $balance) {
    printf('  Type: %s, Code: %s, Balance: %s' . PHP_EOL,
        $balance->getAssetType(),
        $balance->getAssetCode(),
        $balance->getBalance()
    );
}

