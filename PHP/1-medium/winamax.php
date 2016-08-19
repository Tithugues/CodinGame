<?php

class Card {
    protected $_value = null;
    protected $_color = null;

    protected static $_convert = array(
        'J' => 11,
        'Q' => 12,
        'K' => 13,
        'A' => 14
    );

    public function __construct($card) {
        $this->_value = substr($card, 0, -1);
        $this->_color = substr($card, -1);
    }

    /**
     * Get card value
     * @return int Card value
     */
    public function getValue() {
        if (isset(self::$_convert[$this->_value])) {
            return self::$_convert[$this->_value];
        }

        return (int)$this->_value;
    }
}

class Pack {
    /**
     * @var array Cards array
     */
    protected $_cards = array();

    /**
     * Add a card at the end of the pack
     * @param Card $oCard
     */
    public function addCard(Card $oCard) {
        array_push($this->_cards, $oCard);
    }

    /**
     * Get first card
     * @return bool|Card First card or false if none
     */
    public function getCard() {
        if ($this->isEmpty()) {
            return false;
        }

        return array_shift($this->_cards);
    }

    public function isEmpty() {
        return empty($this->_cards);
    }
}

class Plat {
    protected $_cards;

    public function __construct() {
        $this->init();
    }

    public function init() {
        $this->_cards = array();
    }

    public function getCards() {
        return $this->_cards;
    }

    public function addCard($player, Card $card) {
        $this->_cards[$player][] = $card;
    }
}

class Game {
    /**
     * @var Pack Pack
     */
    protected $_pack1 = null;
    /**
     * @var Pack Pack
     */
    protected $_pack2 = null;

    public function __construct(Pack $pack1, Pack $pack2) {
        $this->_pack1 = $pack1;
        $this->_pack2 = $pack2;
    }

    public function run() {
        $battle = false;
        $hidden = false;
        $card1 = null;
        $card2 = null;

        $plat = new Plat();
        $nbSets = 0;

        while (true) {
            $card1 = $this->_pack1->getCard();
            $card2 = $this->_pack2->getCard();

            //Check if ended
            if (false === $card1 || false === $card2) {
                break;
            }

            //Add cards to the plat
            $plat->addCard(1, $card1);
            $plat->addCard(2, $card2);

            //Check if hidden turn of battle
            if (true === $battle && false !== $hidden && 3 > $hidden) {
                $hidden++;
                continue;
            }

            //If new set
            if (false === $battle) {
                $nbSets++;
            }

            //Check if need to start a battle
            if ($card1->getValue() === $card2->getValue()) {
                $battle = true;
                $hidden = +0;
                continue;
            }

            //No battle and one player will win the set
            if ($card1->getValue() > $card2->getValue()) {
                $this->_winSet($this->_pack1, $plat);
            } elseif ($card1->getValue() < $card2->getValue()) {
                $this->_winSet($this->_pack2, $plat);
            }

            $battle = false;
            $hidden = false;
            $plat->init();
        }

        if (true === $battle && true === $hidden && (false === $card1 || false === $card2)) {
            return 'PAT';
        }

        if (false === $card2) {
            return '1 ' . $nbSets;
        } elseif (false === $card1) {
            return '2 ' . $nbSets;
        }

        return false;
    }

    protected function _winSet(Pack $pack, Plat $plat) {
        foreach ($plat->getCards() as $cards) {
            foreach ($cards as $card) {
                $pack->addCard($card);
            }
        }
    }
}
