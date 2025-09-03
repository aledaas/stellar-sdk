<?php

namespace Aledaas\StellarSdk\XdrModel;

class AlphaNum4
{
    protected string $assetCode;
    protected AccountId $issuer;

    public function __construct(string $assetCode, AccountId $issuer)
    {
        $this->assetCode = $assetCode;
        $this->issuer = $issuer;
    }

    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    public function getIssuer(): AccountId
    {
        return $this->issuer;
    }
}