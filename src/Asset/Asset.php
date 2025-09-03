<?php

namespace Aledaas\StellarSdk\Asset;

use Aledaas\StellarSdk\XdrModel\Asset as XdrAsset;
use Aledaas\StellarSdk\XdrModel\AssetType;
use Aledaas\StellarSdk\XdrModel\AlphaNum4;
use Aledaas\StellarSdk\XdrModel\AlphaNum12;
use Aledaas\StellarSdk\XdrModel\AssetCode4;
use Aledaas\StellarSdk\XdrModel\AssetCode12;
use Aledaas\StellarSdk\XdrModel\AccountId;

class Asset
{
    public static function newCustomAsset(string $code, string $issuer): XdrAsset
    {
        $asset = new XdrAsset();

        if (strlen($code) <= 4) {
            $asset->setType(AssetType::ASSET_TYPE_CREDIT_ALPHANUM4);
            $assetCode = new AssetCode4($code);
            $assetBody = new AlphaNum4($assetCode, AccountId::fromAddress($issuer));
            $asset->setAlphaNum4($assetBody);
        } else {
            $asset->setType(AssetType::ASSET_TYPE_CREDIT_ALPHANUM12);
            $assetCode = new AssetCode12($code);
            $assetBody = new AlphaNum12($assetCode, AccountId::fromAddress($issuer));
            $asset->setAlphaNum12($assetBody);
        }

        return $asset;
    }
}