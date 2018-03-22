<?php
//
require_once __DIR__ . '/board.php';
require_once __DIR__ . '/ship.php';
/**
 * User: Tin NT
 * Date: 3/13/2018
 * Time: 4:24 PM
 */
class Generalship
{
    protected $_config = array();
    protected $_data = array();
    protected $_ships = array();
    private $_board = array();
    private $_shipsType = array();

    public function __construct(array $config)
    {
        $this->_config = $config;
        for ($row = 0; $row < Board::$rows; $row++) {
            for ($col = 0; $col < Board::$cols; $col++) {
                $this->_board[$row][$col] = 0;
            }
        }
    }

    /**
     * @return array
     */
    public function _initMatch()
    {
        if (empty($this->_data)) {
            $shipPresets = require_once __DIR__ . '/ship-presets.php';
            $this->_data = $shipPresets[rand(0, count($shipPresets) - 1)]; // Pick random
//            $this->_data = $shipPresets[0]; // Pick random
        }
        return $this->_data;
    }

    public function initMatchTest()
    {
        if (empty($this->_data)) {
            foreach ($this->_config as $shipData) {
                for ($count = 0; $count < $shipData['quantity']; $count++) {
                    $ship = $this->randomShip($shipData['type']);
                    $this->_data[] = $ship;
                    $this->drawShip($this->_ships[$ship['x'] . '_' . $ship['y']]);
                }
            }
        }
        return $this->_data;
    }

    public function initMatchTest2()
    {
        if (empty($this->_data)) {
            foreach ($this->_config as $shipData) {
                for ($count = 0; $count < $shipData['quantity']; $count++) {
                    $this->_shipsType[] = $shipData['type'];
                }
            }
            shuffle($this->_shipsType);

            while($type = array_pop($this->_shipsType)){
                $ship = $this->randomShip($type);
                $this->_data[] = $ship;
                $this->drawShip($this->_ships[$ship['x'] . '_' . $ship['y']]);

                $type = array_pop($this->_shipsType);
                if ($type) {
                    $ship = $this->randomShip($type, $ship);
                    $this->_data[] = $ship;
                    $this->drawShip($this->_ships[$ship['x'] . '_' . $ship['y']]);
                }
            }
        }
        return $this->_data;
    }

    private function randomShip($type, $shipNext = null)
    {
        $direcArr = Ship::returnDirecArr();
        $x = rand(0, Board::$cols - 1);
        $y = rand(0, Board::$rows - 1);

        // Get position for ship with next ship
        if (!empty($shipNext)) {
            $shipNextData = $this->_ships[$shipNext['x'] . '_' . $shipNext['y']];
            $distanceX = count($shipNextData['matrix'][0]);
            $distanceY = count($shipNextData['matrix']);
            if (($distanceX + $shipNext['x']) > (Board::$cols - 1)) {
                $x = rand($shipNext['x'] - $distanceX, $shipNext['x']);
            } else {
                $x = rand($shipNext['x'], $shipNext['x'] + $distanceX);
            }
            if (($distanceY + $shipNext['y']) > (Board::$rows - 1)) {
                $y = rand($shipNext['y'] - $distanceY, $shipNext['y']);
            } else {
                $y = rand($shipNext['y'], $shipNext['y'] + $distanceY);
            }
        }

        $ship = array(
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'direction' => $direcArr[rand(0, count($direcArr) - 1)]
        );
        $shipData = (new Ship($ship['type'], $ship))->toArr();

        if (!$this->checkAvailable($shipData)) {
            return $this->randomShip($type, $shipNext);
        }

        $this->_ships[$x . '_' . $y] = $shipData;

        return $ship;
    }

    private function checkAvailable($shipData)
    {
        $y = $shipData['y'];
        foreach ($shipData['matrix'] as $row) {
            $x = $shipData['x'];
            foreach ($row as $cell) {
                if (!isset($this->_board[$y][$x])) {
                    return false;
                }
                if ($this->_board[$y][$x]) {
                    return false;
                }
                $x++;
            }
            $y++;
        }
        return true;
    }

    private function drawShip($shipData)
    {
        $y = $shipData['y'];
        foreach ($shipData['matrix'] as $row) {
            $x = $shipData['x'];
            foreach ($row as $cell) {
                $this->_board[$y][$x] = $cell;
                $x++;
            }
            $y++;
        }
        return $this;
    }
}