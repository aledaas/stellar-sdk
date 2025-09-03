<?php


namespace Aledaas\StellarSdk\Signing;

use Aledaas\StellarSdk\Transaction\TransactionBuilder;
use Aledaas\StellarSdk\XdrModel\DecoratedSignature;

interface SigningInterface
{
    /**
     * Returns a DecoratedSignature for the given TransactionBuilder
     *
     * @param TransactionBuilder $builder
     * @return DecoratedSignature
     */
    public function signTransaction(TransactionBuilder $builder);
}