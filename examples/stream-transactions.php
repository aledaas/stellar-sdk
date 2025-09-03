<?php

require '../vendor/autoload.php';

use \Aledaas\StellarSdk\Horizon\ApiClient;
use \Aledaas\StellarSdk\Model\Transaction;

$client = ApiClient::newPublicClient();

$client->streamTransactions('now', function(Transaction $transaction) {
    printf('[%s] Transaction #%s with memo %s' . PHP_EOL,
        (new \DateTime())->format('Y-m-d h:i:sa'),
        $transaction->getId(),
        $transaction->getMemo()
    );
});

