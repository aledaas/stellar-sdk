<?php

namespace Aledaas\StellarSdk\XdrModel;

class AssetCode4 extends AlphaNum4
{
    public function __construct(string $code)
    {
        parent::__construct($code, null); // issuer is irrelevant for code-only representation
    }
}