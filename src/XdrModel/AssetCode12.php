<?php

namespace Aledaas\StellarSdk\XdrModel;

class AssetCode12 extends AlphaNum12
{
    public function __construct(string $code)
    {
        parent::__construct($code, null);
    }
}