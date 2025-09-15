<?php

namespace Aledaas\StellarSdk\XdrModel\Operation;

use phpseclib3\Math\BigInteger;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Model\StellarAmount;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\Xdr\XdrEncoder;
use Aledaas\StellarSdk\XdrModel\AccountId;
use Aledaas\StellarSdk\XdrModel\Asset;

/**
 * https://github.com/stellar/stellar-core/blob/master/src/xdr/Stellar-transaction.x#L72
 */
class PathPaymentOp extends Operation
{
    protected $sendAsset;
    protected $sendMax;
    protected $destinationAccount;
    protected $destinationAsset;
    protected $destinationAmount;

    /** @var Asset[] */
    protected $paths;

    public function __construct(
        Asset $sendAsset,
              $sendMax,
              $destinationAccountId,
        Asset $destinationAsset,
              $destinationAmount,
              $sourceAccountId = null
    ) {
        parent::__construct(Operation::TYPE_PATH_PAYMENT, $sourceAccountId);

        if ($destinationAccountId instanceof Keypair) {
            $destinationAccountId = $destinationAccountId->getPublicKey();
        }

        $this->sendAsset = $sendAsset;
        $this->sendMax = new StellarAmount($sendMax);
        $this->destinationAccount = new AccountId($destinationAccountId);
        $this->destinationAsset = $destinationAsset;
        $this->destinationAmount = new StellarAmount($destinationAmount);
        $this->paths = [];
    }

    public function toXdr()
    {
        $bytes = parent::toXdr();
        $bytes .= $this->sendAsset->toXdr();
        $bytes .= XdrEncoder::signedBigInteger64($this->sendMax->getUnscaledBigInteger());
        $bytes .= $this->destinationAccount->toXdr();
        $bytes .= $this->destinationAsset->toXdr();
        $bytes .= XdrEncoder::signedBigInteger64($this->destinationAmount->getUnscaledBigInteger());

        // Encode paths array
        $bytes .= XdrEncoder::unsignedInteger(count($this->paths));
        foreach ($this->paths as $asset) {
            if (!$asset instanceof Asset) {
                throw new \InvalidArgumentException('Each path must be an instance of Asset');
            }
            $bytes .= $asset->toXdr();
        }

        return $bytes;
    }

    public static function fromXdr(XdrBuffer $xdr)
    {
        $sendingAsset = Asset::fromXdr($xdr);
        $sendMax = StellarAmount::fromXdr($xdr);
        $destinationAccount = AccountId::fromXdr($xdr);
        $destinationAsset = Asset::fromXdr($xdr);
        $destinationAmount = StellarAmount::fromXdr($xdr);

        $model = new PathPaymentOp(
            $sendingAsset,
            $sendMax->getUnscaledBigInteger(),
            $destinationAccount->getAccountIdString(),
            $destinationAsset,
            $destinationAmount->getUnscaledBigInteger()
        );

        $numPaths = $xdr->readUnsignedInteger();
        $paths = [];
        for ($i = 0; $i < $numPaths; $i++) {
            $paths[] = Asset::fromXdr($xdr);
        }
        $model->setPaths($paths);

        return $model;
    }

    public function addPath(Asset $path)
    {
        if (count($this->paths) >= 5) {
            throw new \InvalidArgumentException('Too many paths: PathPaymentOp can contain a maximum of 5 paths');
        }

        $this->paths[] = $path;
        return $this;
    }

    public function getSendAsset() { return $this->sendAsset; }
    public function setSendAsset($sendAsset) { $this->sendAsset = $sendAsset; }
    public function getSendMax() { return $this->sendMax; }
    public function setSendMax($sendMax) { $this->sendMax = new StellarAmount($sendMax); }
    public function getDestinationAccount() { return $this->destinationAccount; }
    public function setDestinationAccount($destinationAccount) { $this->destinationAccount = $destinationAccount; }
    public function getDestinationAsset() { return $this->destinationAsset; }
    public function setDestinationAsset($destinationAsset) { $this->destinationAsset = $destinationAsset; }
    public function getDestinationAmount() { return $this->destinationAmount; }
    public function setDestinationAmount($destinationAmount) { $this->destinationAmount = new StellarAmount($destinationAmount); }

    /** @return Asset[] */
    public function getPaths() { return $this->paths; }

    /** @param Asset[] $paths */
    public function setPaths(array $paths)
    {
        foreach ($paths as $path) {
            if (!$path instanceof Asset) {
                throw new \InvalidArgumentException('Each path must be an instance of Asset');
            }
        }
        $this->paths = $paths;
    }
}
