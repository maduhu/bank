<?php
namespace groupcash\bank\app;

use groupcash\bank\app\sourced\domain\AggregateIdentifier;
use groupcash\bank\app\sourced\domain\AggregateRoot;
use groupcash\bank\app\sourced\Builder;
use groupcash\bank\app\sourced\messaging\Command;
use groupcash\bank\app\sourced\domain\DomainEvent;
use groupcash\bank\app\sourced\DomainEventListener;
use groupcash\bank\app\sourced\store\EventStore;
use groupcash\bank\app\sourced\MessageHandler;
use groupcash\bank\app\sourced\domain\Projection;
use groupcash\bank\app\sourced\messaging\Query;
use groupcash\bank\DeliverCoins;
use groupcash\bank\events\CoinsIssued;
use groupcash\bank\events\CoinsSent;
use groupcash\bank\events\TransferredCoin;
use groupcash\bank\ListBackers;
use groupcash\bank\ListCurrencies;
use groupcash\bank\ListTransactions;
use groupcash\bank\model\AccountIdentifier;
use groupcash\bank\model\Authenticator;
use groupcash\bank\model\Bank;
use groupcash\bank\model\BankIdentifier;
use groupcash\bank\model\Vault;
use groupcash\bank\projecting\AllCurrencies;
use groupcash\bank\projecting\AllBackers;
use groupcash\bank\projecting\TransactionHistory;
use groupcash\php\Groupcash;

class Application implements Builder, DomainEventListener {

    /** @var Groupcash */
    private $lib;

    /** @var Authenticator */
    private $auth;

    /** @var MessageHandler */
    private $handler;

    /**
     * @param EventStore $events
     * @param Cryptography $crypto
     * @param Groupcash $lib
     * @param Vault $vault
     */
    public function __construct(EventStore $events, Cryptography $crypto, Groupcash $lib, Vault $vault) {
        $this->lib = $lib;
        $this->auth = new Authenticator($crypto, $vault);
        $this->handler = new MessageHandler($events, $this);
        $this->handler->addListener($this);
    }

    public function handle($message) {
        return $this->handler->handle($message);
    }

    /**
     * @param Command $command
     * @return AggregateIdentifier
     */
    public function getAggregateIdentifier(Command $command) {
        return BankIdentifier::singleton();
    }

    /**
     * @param AggregateIdentifier $identifier
     * @return AggregateRoot
     * @throws \Exception
     */
    public function buildAggregateRoot(AggregateIdentifier $identifier) {
        if ($identifier instanceof BankIdentifier) {
            return new Bank($this->lib, $this->auth);
        }

        throw new \Exception('Unknown command.');
    }

    /**
     * @param Query $query
     * @return Projection
     * @throws \Exception
     */
    public function buildProjection(Query $query) {
        if ($query instanceof ListTransactions) {
            return new TransactionHistory(
                new AccountIdentifier($this->lib->getAddress($this->auth->getKey($query->getAccount())))
            );
        } else if ($query instanceof ListCurrencies) {
            return new AllCurrencies();
        } else if ($query instanceof ListBackers) {
            return new AllBackers($query->getCurrency());
        }

        throw new \Exception('Unknown query.');
    }

    /**
     * @param DomainEvent $event
     * @return bool
     */
    public function listensTo(DomainEvent $event) {
        return $event instanceof CoinsIssued || $event instanceof CoinsSent;
    }

    /**
     * @param DomainEvent $event
     * @return void
     */
    public function on(DomainEvent $event) {
        $method = 'on' . (new \ReflectionClass($event))->getShortName();
        call_user_func([$this, $method], $event);
    }

    protected function onCoinsIssued(CoinsIssued $e) {
        $this->handler->handle(new DeliverCoins(
            $e->getCurrency(),
            new AccountIdentifier((string)$e->getBacker()),
            $e->getCoins(),
            'Issued'
        ));
    }

    protected function onCoinsSent(CoinsSent $e) {
        $this->handler->handle(new DeliverCoins(
            $e->getCurrency(),
            $e->getTarget(),
            array_map(function (TransferredCoin $sentCoin) {
                return $sentCoin->getTransferred();
            }, $e->getSentCoins()),
            $e->getSubject()));
    }
}