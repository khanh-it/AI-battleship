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
    protected $_config = [];
    protected $_data = [];
    protected $_ships = [];
    private $_board = [];
    private $_shipsType = [];
    private $_shipsTypeDestroyer = [];
    private $_totalShip = 0;
    private $_pointBlackList = [];

    public function __construct(array $config)
    {
        $this->_config = $config;
        for ($row = 0; $row < Board::$rows; $row++) {
            for ($col = 0; $col < Board::$cols; $col++) {
                $this->_board[$row][$col] = 0;
            }
        }

        foreach ($this->_config as $shipData) {
            if ($shipData['type'] == Ship::TYPE_DESROYER || $shipData['type'] == Ship::TYPE_CRUISER) {
                for ($count = 0; $count < $shipData['quantity']; $count++) {
                    $this->_shipsTypeDestroyer[] = $shipData['type'];
                }
            } else {
                for ($count = 0; $count < $shipData['quantity']; $count++) {
                    $this->_shipsType[] = $shipData['type'];
                }
            }

            $this->_totalShip += $shipData['quantity'];
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
            shuffle($this->_shipsType);
            shuffle($this->_shipsTypeDestroyer);

            while($type = array_pop($this->_shipsType)) {
                $ship = $this->randomShip($type);
                $this->_data[] = $ship;
                $this->drawShip($this->_ships[$ship['x'] . '_' . $ship['y']]);
            }

            $this->randomShipDestroyer();
        }

        return $this->_data;
    }

    public function initMatchTest3()
    {
        if ($this->_totalShip >= 10) {
            return $this->initMatchTest();
        }

        if (empty($this->_data)) {
            $this->_shipsType = array_merge($this->_shipsType, $this->_shipsTypeDestroyer);
            shuffle($this->_shipsType);

            $count = 1;
            $constantConfigRandom = [
                1 => [
                    'xMin' => 0,
                    'xMax' => (Board::$cols / 2) - 1,
                    'yMin' => 0,
                    'yMax' => (Board::$rows / 2) - 1
                ],
                2 => [
                    'xMin' => Board::$cols / 2,
                    'xMax' => Board::$cols - 1,
                    'yMin' => 0,
                    'yMax' => (Board::$rows / 2) - 1
                ],
                3 => [
                    'xMin' => 0,
                    'xMax' => (Board::$cols / 2) - 1,
                    'yMin' => Board::$rows / 2,
                    'yMax' => Board::$rows - 1
                ],
                4 => [
                    'xMin' => Board::$cols / 2,
                    'xMax' => Board::$cols - 1,
                    'yMin' => Board::$rows / 2,
                    'yMax' => Board::$rows - 1
                ]
            ];

            while($type1 = array_pop($this->_shipsType)) {
                // Init config random
                $configRandom = $constantConfigRandom[$count];
                $type2 = array_pop($this->_shipsType);

                GENERATE_SHIP :
                // Random ship 1
                try {
                    $ship1 = $this->randomShip($type1, null, $configRandom);
                } catch (\Exception $ex) {
                    goto GENERATE_SHIP;
                }
                $this->drawShip($this->_ships[$ship1['x'] . '_' . $ship1['y']]);

                if ($type2) {
                    // Random ship 2
                    try {
                        $ship2 = $this->randomShip($type2, $ship1, $configRandom);
                    } catch (\Exception $ex) {
                        $this->eraseShip($this->_ships[$ship1['x'] . '_' . $ship1['y']]);
                        unset($this->_ships[$ship1['x'] . '_' . $ship1['y']]);
                        goto GENERATE_SHIP;
                    }
                    $this->_data[] = $ship2;
                    $this->drawShip($this->_ships[$ship2['x'] . '_' . $ship2['y']]);
                }
                $this->_data[] = $ship1;

                // Reset count
                if ($count == 4) {
                    $count = 1;
                } else {
                    $count++;
                }
            }
        }

        return $this->_data;
    }

    public function initMatchTest2()
    {
        if ($this->_totalShip > 10) {
            return $this->initMatchTest();
        }

        if (empty($this->_data)) {
            shuffle($this->_shipsType);

            $count = 1;
            $constantConfigRandom = [
                1 => [
                    'xMin' => 2,
                    'xMax' => 5,
                    'yMin' => 0,
                    'yMax' => Board::$rows - 1
                ],
                2 => [
                    'xMin' => 6,
                    'xMax' => 9,
                    'yMin' => 0,
                    'yMax' => Board::$rows - 1
                ],
                3 => [
                    'xMin' => 10,
                    'xMax' => 13,
                    'yMin' => 0,
                    'yMax' => Board::$rows - 1
                ],
                4 => [
                    'xMin' => 14,
                    'xMax' => 17,
                    'yMin' => 0,
                    'yMax' => Board::$rows - 1
                ]
            ];

            $countShipType = count($this->_shipsType);
            if (ceil($countShipType / 2) < 3) {
                $constantConfigRandom[1]['xMax'] += 4;
                $constantConfigRandom[2]['xMin'] += 4;
                $constantConfigRandom[2]['xMax'] += 4;
                $constantConfigRandom[3]['xMin'] = $constantConfigRandom[1]['xMin'];
                $constantConfigRandom[3]['xMax'] = $constantConfigRandom[1]['xMax'];
                $constantConfigRandom[4]['xMin'] = $constantConfigRandom[2]['xMin'];
                $constantConfigRandom[4]['xMax'] = $constantConfigRandom[2]['xMax'];
            }

            while($type1 = array_pop($this->_shipsType)) {
                // Init config random
                $configRandom = $constantConfigRandom[$count];
                $type2 = array_pop($this->_shipsType);

                GENERATE_SHIP :
                // Random ship 1
                try {
                    $ship1 = $this->randomShip($type1, null, $configRandom);
                } catch (\Exception $ex) {
                    goto GENERATE_SHIP;
                }
                $this->drawShip($this->_ships[$ship1['x'] . '_' . $ship1['y']]);

                if ($type2) {
                    // Random ship 2
                    try {
                        $ship2 = $this->randomShip($type2, $ship1, $configRandom);
                    } catch (\Exception $ex) {
                        $this->eraseShip($this->_ships[$ship1['x'] . '_' . $ship1['y']]);
                        unset($this->_ships[$ship1['x'] . '_' . $ship1['y']]);
                        goto GENERATE_SHIP;
                    }
                    $this->_data[] = $ship2;
                    $this->drawShip($this->_ships[$ship2['x'] . '_' . $ship2['y']]);
                }
                $this->_data[] = $ship1;

                // Reset count
                if ($count == 4) {
                    $count = 1;
                } else {
                    $count++;
                }
            }

            $this->randomShipDestroyer();
        }

        return $this->_data;
    }

    private function randomShipDestroyer()
    {
        $constantConfigRandom = [
            3 => [
                'xMin' => 0,
                'xMax' => 0,
                'yMin' => 0,
                'yMax' => Board::$rows - 1
            ],
            4 => [
                'xMin' => Board::$cols - 1,
                'xMax' => Board::$cols - 1,
                'yMin' => 0,
                'yMax' => Board::$rows - 1
            ],
            1 => [
                'xMin' => 0,
                'xMax' => Board::$cols - 1,
                'yMin' => 0,
                'yMax' => 0
            ],
            2 => [
                'xMin' => 0,
                'xMax' => Board::$cols - 1,
                'yMin' => Board::$rows - 1,
                'yMax' => Board::$rows - 1
            ],
        ];
        $constantConfigDirector = [
            1 => 0,
            2 => 0,
            3 => 1,
            4 => 1
        ];
        shuffle($this->_shipsType);
        $countShipDestroy = count($this->_shipsTypeDestroyer);
        while($type = array_pop($this->_shipsTypeDestroyer)) {
            GENERATE_SHIP_DESTROYER :
            // Init config random
            if ($countShipDestroy > 4) {
                $count = rand(1, 4);
                $configRandom = $constantConfigRandom[$count];
            } else {
                $configRandom = reset($constantConfigRandom);
                $count = key($constantConfigRandom);
                unset($constantConfigRandom[$count]);
            }

            // Random ship 1
            try {
                $ship = $this->randomShip($type, null, $configRandom, $constantConfigDirector[$count]);
            } catch (\Exception $ex) {
                goto GENERATE_SHIP_DESTROYER;
            }
            $this->drawShip($this->_ships[$ship['x'] . '_' . $ship['y']]);
            $this->_data[] = $ship;
        }
        return $this;
    }

    private function randomShip($type, $shipNext = null, $configRandom = [], $director = null)
    {
        $countRandom = 1;

        START_RANDOM :

        // Get position for ship
        if (empty($configRandom)) {
            $x = rand(0, Board::$cols - 1);
            $y = rand(0, Board::$rows - 1);
        } else {
            $x = rand($configRandom['xMin'], $configRandom['xMax']);
            $y = rand($configRandom['yMin'], $configRandom['yMax']);
        }

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

        if (in_array($x . '_' . $y, $this->_pointBlackList)) {
            if ($countRandom == 3) {
                throw new \Exception('Generate ship failed');
            }

            $countRandom++;
            goto START_RANDOM;
        }

        $ship = array(
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'direction' => 1 // $direcArr[rand(0, count($direcArr) - 1)]
        );

        if (!is_null($director)) {
            $ship['direction'] = $director;
        }

        $shipObject = new Ship($ship['type'], $ship);
        $shipData = $shipObject->toArr();
        unset($shipObject);

        if (!$this->checkAvailable($shipData, $configRandom)) {
            unset($shipData);
            $ship['direction'] = (intval(!$ship['direction']));
            $shipObject = new Ship($ship['type'], $ship);
            $shipData = $shipObject->toArr();
            unset($shipObject);
            if (!$this->checkAvailable($shipData, $configRandom)) {
                unset($shipData);
                $this->_pointBlackList[] = $x . '_' . $y;
                goto START_RANDOM;
            }
        }

        $this->_ships[$x . '_' . $y] = $shipData;

        return $ship;
    }

    private function checkAvailable($shipData, $configRandom = [])
    {
        $board = $this->_board;
        if (!empty($configRandom)) {
            $board = [];
            for ($y = $configRandom['yMin']; $y <= $configRandom['yMax']; $y++) {
                for ($x = $configRandom['xMin']; $x <= $configRandom['xMax']; $x++) {
                    $board[$y][$x] = $this->_board[$y][$x];
                }
            }
        }


        $y = $shipData['y'];
        foreach ($shipData['matrix'] as $row) {
            $x = $shipData['x'];
            foreach ($row as $cell) {
                if (!isset($board[$y][$x])) {
                    return false;
                }
                if ($board[$y][$x]) {
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

    private function eraseShip($shipData)
    {
        $y = $shipData['y'];
        foreach ($shipData['matrix'] as $row) {
            $x = $shipData['x'];
            foreach ($row as $cell) {
                if ($cell == 1) {
                    $this->_board[$y][$x] = 0;
                }
                $x++;
            }
            $y++;
        }
        return $this;
    }
}