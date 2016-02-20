<?php
namespace groupcash\bank;

use groupcash\bank\app\sourced\messaging\Command;
use groupcash\bank\app\sourced\messaging\Identifier;
use groupcash\bank\model\Authentication;
use groupcash\bank\model\BackerIdentifier;
use groupcash\bank\model\BankIdentifier;
use groupcash\bank\model\CurrencyIdentifier;

class IssueCoins implements Command {

    /** @var Authentication */
    private $issuer;

    /** @var int|null */
    private $number;

    /** @var CurrencyIdentifier */
    private $currency;

    /** @var BackerIdentifier */
    private $backer;

    /**
     * @param Authentication $issuer
     * @param int|null $number
     * @param CurrencyIdentifier $currency
     * @param BackerIdentifier $backer
     */
    public function __construct(Authentication $issuer, $number, CurrencyIdentifier $currency, BackerIdentifier $backer) {
        $this->issuer = $issuer;
        $this->number = $number;
        $this->currency = $currency;
        $this->backer = $backer;
    }

    /**
     * @return Authentication
     */
    public function getIssuer() {
        return $this->issuer;
    }

    /**
     * @return int|null
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * @return bool
     */
    public function isAll() {
        return is_null($this->number);
    }

    /**
     * @return CurrencyIdentifier
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @return BackerIdentifier
     */
    public function getBacker() {
        return $this->backer;
    }

    /**
     * @return Identifier
     */
    public function getAggregateIdentifier() {
        return BankIdentifier::singleton();
    }
}