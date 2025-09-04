<?php


namespace Aledaas\StellarSdk\Transaction;

use phpseclib3\Math\BigInteger;
use Aledaas\StellarSdk\Horizon\Api\PostTransactionResponse;
use Aledaas\StellarSdk\Horizon\ApiClient;
use Aledaas\StellarSdk\Horizon\Exception\HorizonException;
use Aledaas\StellarSdk\Horizon\Exception\PostTransactionException;
use Aledaas\StellarSdk\Keypair;
use Aledaas\StellarSdk\Model\StellarAmount;
use Aledaas\StellarSdk\Server;
use Aledaas\StellarSdk\Signing\PrivateKeySigner;
use Aledaas\StellarSdk\Signing\SigningInterface;
use Aledaas\StellarSdk\Util\MathSafety;
use Aledaas\StellarSdk\Xdr\Iface\XdrEncodableInterface;
use Aledaas\StellarSdk\Xdr\Type\VariableArray;
use Aledaas\StellarSdk\Xdr\XdrBuffer;
use Aledaas\StellarSdk\Xdr\XdrEncoder;
use Aledaas\StellarSdk\XdrModel\AccountId;
use Aledaas\StellarSdk\XdrModel\Asset;
use Aledaas\StellarSdk\XdrModel\DecoratedSignature;
use Aledaas\StellarSdk\XdrModel\Memo;
use Aledaas\StellarSdk\XdrModel\Operation\AccountMergeOp;
use Aledaas\StellarSdk\XdrModel\Operation\AllowTrustOp;
use Aledaas\StellarSdk\XdrModel\Operation\BumpSequenceOp;
use Aledaas\StellarSdk\XdrModel\Operation\ChangeTrustOp;
use Aledaas\StellarSdk\XdrModel\Operation\CreateAccountOp;
use Aledaas\StellarSdk\XdrModel\Operation\ManageDataOp;
use Aledaas\StellarSdk\XdrModel\Operation\Operation;
use Aledaas\StellarSdk\XdrModel\Operation\PaymentOp;
use Aledaas\StellarSdk\XdrModel\Operation\SetOptionsOp;
use Aledaas\StellarSdk\XdrModel\Operation\ManageOfferOp;
use Aledaas\StellarSdk\XdrModel\Signer;
use Aledaas\StellarSdk\XdrModel\SignerKey;
use Aledaas\StellarSdk\XdrModel\TimeBounds;
use Aledaas\StellarSdk\XdrModel\TransactionEnvelope;


/**
 * todo: rename to Transaction
 * Helper class to build a transaction on the Stellar network
 *
 * References:
 *  Debugging / testing:
 *      https://www.stellar.org/laboratory/
 *
 *  Retrieve fee information from:
 *      https://www.stellar.org/developers/horizon/reference/endpoints/ledgers-single.html
 *      https://www.stellar.org/developers/horizon/reference/resources/ledger.html
 *
 * Notes:
 *  - Per-operation fee is 100 stroops (0.00001 XLM)
 *  - Base reserve is 10 XLM
 *      - Minimum balance for an account is base reserve * 2
 *      - Each additional trustline, offer, signer, and data entry requires another 10 XLM
 *
 *
 * Format of a transaction:
 *  Source Address (AddressId)
 *      type
 *      address
 *  Fee (Uint32)
 *  Next sequence number (SequenceNumber - uint64)
 *      ...
 *  Time bounds (TimeBounds)
 *  Memo (Memo)
 *  Operations (Operation[])
 *  ext (TransactionExt) - extra? currently is a union with no arms
 */
class TransactionBuilder implements XdrEncodableInterface
{
    /**
     * Base-32 account ID
     *
     * @var AccountId
     */
    private $accountId;

    /**
     * @var TimeBounds
     */
    private $timeBounds;

    /**
     * @var Memo
     */
    private $memo;

    /**
     * @var VariableArray[]
     */
    private $operations;

    /**
     * Horizon API client, used for retrieving sequence numbers and validating
     * transaction
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var SigningInterface
     */
    protected $signingProvider;

    /**
     * @var DecoratedSignature[]
     */
    protected $signatures;

    /**
     * If null, this is retrieved from the network
     *
     * @var BigInteger
     */
    protected $sequenceNumber;

    protected $baseFee = 100;

    /**
     * TransactionBuilder constructor.
     *
     * @param $sourceAccountId
     * @return TransactionBuilder
     */
    public function __construct($sourceAccountId)
    {
        $this->accountId = new AccountId($sourceAccountId);

        $this->timeBounds = new TimeBounds();
        $this->memo = new Memo(Memo::MEMO_TYPE_NONE);
        $this->operations = new VariableArray();

        $this->signatures = [];

        return $this;
    }

    /**
     * Uses $signer to add a new DecoratedSignature to this TransactionBuilder
     *
     * @param SigningInterface $signer
     * @return DecoratedSignature
     */
    public function signWith(SigningInterface $signer)
    {
        $decoratedSignature = $signer->signTransaction($this);

        $this->signatures[] = $decoratedSignature;

        return $decoratedSignature;
    }

    /**
     * @param Keypair $keypair
     * @return DecoratedSignature
     */
    public function getSignatureForKeypair(Keypair $keypair)
    {
        $signer = new PrivateKeySigner($keypair);

        return $this->signWith($signer);
    }

    /**
     * @return TransactionEnvelope
     */
    public function getTransactionEnvelope()
    {
        $txEnvelope = new TransactionEnvelope($this);

        foreach ($this->signatures as $signature) {
            $txEnvelope->addDecoratedSignature($signature);
        }

        return $txEnvelope;
    }

    /**
     * @param $secretKeyString
     * @return TransactionEnvelope
     */
    public function sign($secretKeyString = null)
    {
        if ($secretKeyString instanceof Keypair) {
            $secretKeyString = $secretKeyString->getSecret();
        }

        // If $secretKeyString is null, check for a SigningProvider
        if (!$secretKeyString) {
            // No secret key and no signing provider: could be a pre-authorized
            // transaction. Return empty envelope with no signatures
            if (!$this->signingProvider) {
                return new TransactionEnvelope($this);
            }

            $this->signWith($this->signingProvider);

            return $this->getTransactionEnvelope();
        }
        else {
            return (new TransactionEnvelope($this))->sign($secretKeyString);
        }
    }

    public function hash()
    {
        return $this->apiClient->hash($this);
    }

    public function getHashAsString()
    {
        return $this->apiClient->getHashAsString($this);
    }

    /**
     * @param $secretKeyString string|Keypair
     * @return PostTransactionResponse
     * @throws PostTransactionException
     * @throws \ErrorException
     */
    public function submit($secretKeyString = null)
    {
        if ($secretKeyString instanceof Keypair) {
            $secretKeyString = $secretKeyString->getSecret();
        }

        return $this->apiClient->submitTransaction($this, $secretKeyString);
    }

    public function getFee()
    {
        // todo: load base fee from network
        return $this->baseFee * $this->operations->count();
    }

    public function setBaseFee(int $stroops)
    {
        $this->baseFee = $stroops;
        return $this;
    }

    /**
     * @param string|Keypair          $destination
     * @param number|BigInteger       $amount int representing lumens or BigInteger representing stroops
     * @param null                    $sourceAccountId
     * @return TransactionBuilder
     */
    public function addLumenPayment($destination, $amount, $sourceAccountId = null)
    {
        return $this->addOperation(PaymentOp::newNativePayment($destination, $amount, $sourceAccountId));
    }

    /**
     * @param string            $newAccountId
     * @param number|BigInteger $amount int representing lumens or BigInteger representing stroops
     * @param string            $sourceAccountId
     * @return TransactionBuilder
     */
    public function addCreateAccountOp($newAccountId, $amount, $sourceAccountId = null)
    {
        return $this->addOperation(new CreateAccountOp(new AccountId($newAccountId), $amount, $sourceAccountId));
    }

    /**
     * @param Asset               $asset
     * @param number|BigInteger   $amount number representing lumens or BigInteger representing stroops
     * @param string|Keypair      $destinationAccountId
     * $param null|string|Keypair $sourceAccountId
     * @return TransactionBuilder
     */
    public function addCustomAssetPaymentOp(Asset $asset, $amount, $destinationAccountId, $sourceAccountId = null)
    {
        return $this->addOperation(
            PaymentOp::newCustomPayment($destinationAccountId, $amount, $asset->getAssetCode(), $asset->getIssuer()->getAccountIdString(), $sourceAccountId)
        );
    }

    /**
     * @param Asset $asset
     * @param int   $amount defaults to maximum if null
     * @param null  $sourceAccountId
     * @return TransactionBuilder
     */
    public function addChangeTrustOp(Asset $asset, $amount = null, $sourceAccountId = null)
    {
        if ($amount === null) {
            $amount = StellarAmount::newMaximum();
        }

        return $this->addOperation(new ChangeTrustOp($asset, $amount, $sourceAccountId));
    }

    /**
     * This is called by asset issuers to authorize a trustline established by
     * a client account
     *
     * @param Asset $asset
     * @param       $trustorId
     * @param null  $sourceAccountId
     * @return TransactionBuilder
     */
    public function authorizeTrustline(Asset $asset, $trustorId, $sourceAccountId = null)
    {
        if ($trustorId instanceof Keypair) {
            $trustorId = $trustorId->getPublicKey();
        }

        $op = new AllowTrustOp($asset, new AccountId($trustorId), $sourceAccountId);
        $op->setIsAuthorized(true);

        return $this->addOperation($op);
    }

    /**
     * This is called by asset issuers to revoke a trustline established by
     * a client account
     *
     * @param Asset $asset
     * @param       $trustorId
     * @param null  $sourceAccountId
     * @return TransactionBuilder
     */
    public function revokeTrustline(Asset $asset, $trustorId, $sourceAccountId = null)
    {
        if ($trustorId instanceof Keypair) {
            $trustorId = $trustorId->getPublicKey();
        }

        $op = new AllowTrustOp($asset, new AccountId($trustorId), $sourceAccountId);
        $op->setIsAuthorized(false);

        return $this->addOperation($op);
    }

    /**
     * Adds an operation to merge the balance of the source account to $destinationAccountId
     * @param      $destinationAccountId
     * @param null $sourceAccountId
     * @return TransactionBuilder
     */
    public function addMergeOperation($destinationAccountId, $sourceAccountId = null)
    {
        if ($destinationAccountId instanceof Keypair) {
            $destinationAccountId = $destinationAccountId->getPublicKey();
        }

        return $this->addOperation(new AccountMergeOp($destinationAccountId, $sourceAccountId));
    }

    /**
     * @param      $key
     * @param      $value
     * @param null $sourceAccountId
     * @return TransactionBuilder
     */
    public function setAccountData($key, $value = null, $sourceAccountId = null)
    {
        return $this->addOperation(new ManageDataOp($key, $value, $sourceAccountId));
    }

    /**
     * @param      $key
     * @param null $sourceAccountId
     * @return TransactionBuilder
     */
    public function clearAccountData($key, $sourceAccountId = null)
    {
        return $this->addOperation(new ManageDataOp($key, null, $sourceAccountId));
    }

    /**
     * @param BigInteger $bumpTo
     * @param null       $sourceAccountId
     * @return TransactionBuilder
     */
    public function bumpSequenceTo(BigInteger $bumpTo, $sourceAccountId = null)
    {
        return $this->addOperation(new BumpSequenceOp($bumpTo, $sourceAccountId));
    }

    /**
     * @return string
     */
    public function toXdr()
    {
        $bytes = '';

        if (!$this->sequenceNumber) {
            $this->sequenceNumber = $this->generateSequenceNumber();
        }

        // Account ID (36 bytes)
        $bytes .= $this->accountId->toXdr();
        // Fee (4 bytes)
        $bytes .= XdrEncoder::unsignedInteger($this->getFee());
        // Sequence number (8 bytes)
        $bytes .= XdrEncoder::unsignedBigInteger64($this->sequenceNumber);

        // Time Bounds are optional
        if ($this->timeBounds->isEmpty()) {
            $bytes .= XdrEncoder::boolean(false);
        }
        else {
            $bytes .= XdrEncoder::boolean(true);
            $bytes .= $this->timeBounds->toXdr();
        }

        // Memo (4 bytes if empty, 36 bytes maximum)
        $bytes .= $this->memo->toXdr();

        // Operations
        $bytes .= $this->operations->toXdr();

        // TransactionExt (union reserved for future use)
        $bytes .= XdrEncoder::unsignedInteger(0);

        return $bytes;
    }

    /**
     * @param XdrBuffer $xdr
     * @param Server    $server
     * @return TransactionBuilder
     * @throws \ErrorException
     */
    public static function fromXdr(XdrBuffer $xdr, Server $server)
    {
        return Transaction::fromXdr($xdr)->toTransactionBuilder($server);
    }

    /**
     * @param $operation
     * @return TransactionBuilder
     */
    public function addOperation($operation)
    {
        $this->operations->append($operation);

        return $this;
    }

    /**
     * @param $memo
     * @return $this
     */
    public function setTextMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_TEXT, $memo);

        return $this;
    }

    /**
     * @param $memo
     * @return $this
     */
    public function setIdMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_ID, $memo);

        return $this;
    }

    /**
     * Note: this should be called with the raw sha256 hash
     *
     * For example:
     *  $builder->setHashMemo(hash('sha256', 'example thing being hashed', true));
     *
     * @param $memo 32-byte sha256 hash
     * @return $this
     */
    public function setHashMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_HASH, $memo);

        return $this;
    }

    /**
     * Note: this should be called with the raw sha256 hash
     *
     * For example:
     *  $builder->setReturnMemo(hash('sha256', 'example thing being hashed', true));
     *
     * @param $memo 32-byte sha256 hash
     * @return $this
     */
    public function setReturnMemo($memo)
    {
        $this->memo = new Memo(Memo::MEMO_TYPE_RETURN, $memo);

        return $this;
    }

    /**
     * @param \DateTime $lowerTimebound
     * @return $this
     */
    public function setLowerTimebound(\DateTime $lowerTimebound)
    {
        $this->timeBounds->setMinTime($lowerTimebound);

        return $this;
    }

    /**
     * @param \DateTime $upperTimebound
     * @return $this
     */
    public function setUpperTimebound(\DateTime $upperTimebound)
    {
        $this->timeBounds->setMaxTime($upperTimebound);

        return $this;
    }

    protected function generateSequenceNumber()
    {
        $this->ensureApiClient();

        $account = $this->apiClient
            ->getAccount($this->accountId->getAccountIdString());

        if (!$account) {
            throw new \ErrorException(sprintf('Account not found: %s', $this->accountId->getAccountIdString()));
        }

        return $account->getSequenceAsBigInteger()->add(new BigInteger(1));
    }

    protected function ensureApiClient()
    {
        if (!$this->apiClient) throw new \ErrorException("An API client is required, call setApiClient before using this method");
    }

    /**
     * @return ApiClient
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * @param ApiClient $apiClient
     * @return TransactionBuilder
     */
    public function setApiClient($apiClient)
    {
        $this->apiClient = $apiClient;

        return $this;
    }

    /**
     * @return SigningInterface
     */
    public function getSigningProvider()
    {
        return $this->signingProvider;
    }

    /**
     * @param SigningInterface $signingProvider
     */
    public function setSigningProvider($signingProvider)
    {
        $this->signingProvider = $signingProvider;

        return $this;
    }

    /**
     * @return BigInteger
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * @param BigInteger $sequenceNumber
     */
    public function setSequenceNumber($sequenceNumber)
    {
        if (!is_a($sequenceNumber, 'phpseclib\Math\BigInteger')) {
            $sequenceNumber = new BigInteger($sequenceNumber);
        }

        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }

    /**
     * @return Memo
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @param Memo $memo
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }
   public function setTimeout(int $seconds)
{
    $minTime = new \DateTime('@0'); // ahora
    $maxTime = new \DateTime();
    $maxTime->modify("+{$seconds} seconds");

    $this->setLowerTimebound($minTime);
    $this->setUpperTimebound($maxTime);

    return $this;
}

    public function appendChangeTrustOp(string $assetCode, string $issuerPublicKey, $amount = null, $sourceAccountId = null)
    {
        $asset = Asset::newCustomAsset($assetCode, $issuerPublicKey);
        return $this->addChangeTrustOp($asset, $amount, $sourceAccountId);
    }
    public function build()
    {
        return $this->getTransactionEnvelope();
    }
    public function appendPayment(string $destination, string $assetCode, string $issuer, string $amount, $sourceAccountId = null)
    {
        $paymentOp = PaymentOp::newCustomPayment(
            $destination,
            $this->toStroopAmount($amount),
            $assetCode,
            $issuer,
            $sourceAccountId
        );

        return $this->addOperation($paymentOp);
    }

    protected function toStroopAmount($amount): BigInteger
    {
        return (new StellarAmount($amount))->getUnscaledBigInteger();
    }
   
   public function appendManageSellOfferOp(
    string $sellingAssetCode,
    string $sellingAssetIssuer,
    string $buyingAssetCode,
    ?string $buyingAssetIssuer,
    string $amount,
    string $price,
    int $offerId = 0 // por defecto es 0, o sea, una nueva oferta
    ): self {
        $sellingAsset = Asset::newCustomAsset($sellingAssetCode, $sellingAssetIssuer);

        $buyingAsset = $buyingAssetCode === 'XLM'
            ? Asset::newNativeAsset()
            : Asset::newCustomAsset($buyingAssetCode, $buyingAssetIssuer);

        $opBody = new ManageOfferOp(
            $sellingAsset,
            $buyingAsset,
            $this->toStroopAmount($amount),
            $this->toPriceFraction($price),
            $offerId
        );

        $operation = new Operation(Operation::TYPE_MANAGE_OFFER, null, $opBody);

        return $this->appendOperation($operation);
    }

    public function appendManageBuyOfferOp(
    string $buyingAssetCode,
    ?string $buyingAssetIssuer,
    string $sellingAssetCode,
    string $sellingAssetIssuer,
    string $amount,
    string $price,
    int $offerId = 0 // por defecto es 0, o sea, una nueva oferta
    ): self {
        $buyingAsset = $buyingAssetCode === 'XLM'
            ? Asset::newNativeAsset()
            : Asset::newCustomAsset($buyingAssetCode, $buyingAssetIssuer);

        $sellingAsset = Asset::newCustomAsset($sellingAssetCode, $sellingAssetIssuer);

        $opBody = new ManageOfferOp(
            $sellingAsset,
            $buyingAsset,
            $this->toStroopAmount($amount),
            $this->toPriceFraction($price),
            $offerId
        );

        $operation = new Operation(Operation::TYPE_MANAGE_OFFER, null, $opBody);

        return $this->appendOperation($operation);
    }

    public function toPriceFraction(string $price): array
    {
        // Convertir el precio decimal en fracción
        $float = (float) $price;

        // Limitar la precisión a evitar floats largos
        $precision = 1e7;
        $numerator = (int) round($float * $precision);
        $denominator = (int) $precision;

        // Reducir la fracción al mínimo (opcional)
        $gcd = function($a, $b) use (&$gcd) {
            return ($b == 0) ? $a : $gcd($b, $a % $b);
        };

        $divisor = $gcd($numerator, $denominator);

        return [
            'n' => (int) ($numerator / $divisor),
            'd' => (int) ($denominator / $divisor)
        ];
    }
}