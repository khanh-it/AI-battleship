<?php
//
require_once('ship.php');

/**
 * 
 * @author KhanhDTP
 */
class Board {
    /**
     * @var integer
     */
    const TYPE_P = 1; // portrait

    /**
     * @var integer
     */
    const TYPE_L = 2; // landscape

    /**
     * @var integer
     */
    protected $_type = null;
    
    /**
     * @var array of Ship
     */
    protected $_ships;

    /**
     * @var integer
     */
    protected $_cols = 0;
    
    /**
     * @var integer
     */
    protected $_rows = 0;
    
    /**
     * @var array
     */
    protected $_blocks = array();
    
    /**
     * @var array History of shoot blocks order
     */
    protected $_shootBlocks = array();
    
    /**
     * @var array History of our shoots
     */
    protected $_shoots = array();
    
    /**
     * @var array History of our shoots that hit opponent's ships
     */
    protected $_hitShoots = array();
    
    /**
     * @var array History of opponents shoots
     */
    protected $_opponentsShoots = array();
    
    /**
     * @var integer Maximun number of missed shoot per block
     */
    protected $_shoots_per_block = 5;
    
    /**
     * Build string key from $row, $col
     * @param int|array $row
     * @param int|null $col
     * @return string
     */
    public static function key($row, $col) {
        if (is_array($row) && is_null($col)) {
            list($row, $col) = $row;
        }
        return "{$row}:{$col}";
    }

    /**
     * Split $row, $col from string key
     * @param string $key
     * @return array
     */
    public static function keyR($key) {
        list($row, $col) = explode(':', $key);
        return array(intval($row), intval($col));
    }

    /**
     * 
     * @param array $options
     */
    public function __construct(array $data = null) {
        $this->fromArr($data);
        $this->_initShips($data);
    }

    /**
     * Load ships (on board)
     * @param array $data Request data
     * @return Battleship
     */
    protected function _initShips($data = null) {
        if (is_null($this->_ships)) {
            require_once __DIR__ . '/generalship.php';
            $shipPresets = new Generalship([
                ['type' => Ship::TYPE_AIRCRAFT, 'number' => rand(1, 4)],
                ['type' => Ship::TYPE_BATTLESHIP, 'number' => rand(1,5)],
                ['type' => Ship::TYPE_CRUISER, 'number' => rand(1,6)],
                ['type' => Ship::TYPE_DESROYER, 'number' => rand(1,6)],
                ['type' => Ship::TYPE_SUBMARINE, 'number' => rand(2,8)],
            ]);
            $shipPreset = $shipPresets->initMatchTest(); // Pick random
            // $shipPreset = $shipPresets[4]; // debug
            foreach ($shipPreset as $shipP) {
                $this->_ships[] = (new Ship($shipP['type'], $shipP))->toArr();
            }
        }
        //
        return $this;
    }
    
    /**
     * 
     * @param unknown $row
     * @param unknown $col
     * @return array
     */
    public function getRelativeCells($row, $col, array $options = array()) {
        $tmp = array(
            array($row - 1, $col - 0),
            array($row + 1, $col - 0),
            array($row - 0, $col - 1),
            array($row - 0, $col + 1)
        );
        if ($options['include_current']) {
            array_unshift($tmp, array($row, $col));
        }
        $cells = array();
        foreach ($tmp as $idx => $cell) {
            list($_r, $_c) = $cell;
            if ($_r <= 0 || $_r > $this->_rows || $_c <= 0 || $_c > $this->_cols) {
                continue;
            }
            $cells[static::key($_r, $_c)] = $cell;
        }
        return $cells;
    }
    
    /**
     * Our shoot
     * @return array
     */
    public function shoot() {
        //
        $return = array();
        // Target mode [trace hit shoots]?
        $hitShoot = null;
        foreach ($this->_hitShoots as $_k => $val) {
            if (is_null($val) && is_null($this->_shoots[$_k])) {
                $hitShoot = $_k;
                break;
            }
            if ($hitShoot) break;
        }
        if ($hitShoot) {
            list($row, $col) = static::keyR($hitShoot);
            $return = array('x' => $col, 'y' => $row);
        }
        // #end
        //
        if (empty($return)) {
            $rowMin = null; $rowMax = null;
            $colMin = null; $colMax = null;
            $cells = array();
            $oeCells = array(); // odd or even cells
            $block = $this->_calWillShootBlock();
            if (!empty($block)) {
                list($blockRow, $blockCol) = $block;
                $rowMin = ($blockRow * 4) - 3;
                $colMin = ($blockCol * 4) - 3;
                $rowMax = $rowMin + 3;
                $colMax = $colMin + 3;
            }
            $this->_mapCell(function($row, $col, $key) use (&$cells, &$oeCells) {
                if (is_null($this->_shoots[$key])) {
                    $cells[] = ($cell = array($row, $col, $key));
                    // @TODO: odd or even?
                    if (($row + $col) % 2 == 0) { // odd
                        $oeCells[] = $cell;
                    }
                }
            }, $rowMin, $colMin, $rowMax, $colMax);
            $cells = empty($oeCells) ? $cells : $oeCells;
            list($_r, $_c) = $cells[$cellIdx = (rand(0, count($cells) - 1))];
            $return = array('x' => $_c, 'y' => $_r);
        }
        return $return;
    }

    /**
     * Helper, check ship contain cells?
     * @param array $cells An array of cells
     * @param array $ship Ship data
     * @param array $options Options
     * @return array
     */
    protected function _shipContainCells(array $cells, $ship, array &$options = array()) {
        // var_dump($cells, $ship); // debug
        $return = array();
        $this->_mapCell(function($row, $col, $key) use ($cells, $ship, &$return) {
            $cellCnt = count($cells);
            foreach (Ship::returnDirecArr() as $direc) {
                $shipStats = $this->_getShipStats($ship['type'], $direc, $row, $col);
                $containCnt = 0;
                if (!$shipStats['total_missed']) {
                    foreach ($cells as $key) {
                        $key = is_string($key) ? $key : static::key($key);
                        if ($shipStats['cells'][$key]) {
                            $contains += 1;
                        }
                    }
                }
                if ($contains == $cellCnt) {
                    $return[] = $shipStats;
                    // var_dump($shipStats); // debug
                }
            }
        });
        // Merge cells?
        if (true === $options['merge_cells'] && !empty($return)) {
            $options['merge_cells'] = $return;
            $tmp = array();
            foreach ($return as $shipStats) {
                $tmp = array_replace($tmp, $shipStats['cells']);
            }
            $return = $tmp;
        }
        // var_dump($return);die() // debug
        return $return;
    }
    
    /**
     * Their shoot
     * @return array
     */
    public function shootAt($data) {
        //
        $key = static::key($data['y'], $data['x']);
        $isHit = $data['is_hit'];
        $isHit = 0;
        // Opponent's ship sunk?
        $oShipSunk = null;
        // Case: normal hit
        if (is_numeric($isHit)) {
            $isHit = intval($isHit); // 0 | 1
        // @TODO: ship was destroy
        } else {
            $oShipSunk = $isHit;
            $isHit = 1;
            // Mark opponent ship as sunk!
            foreach ($this->_ships as &$ship) {
                if (strtolower($ship['type']) == strtolower($oShipSunk['type'])) {
                    $ship['osunk'] = 1;
                    break;
                }
            }
        }

        // Case: hit. Add relative cells to list.
        if ($isHit) {
            $cells = $this->getRelativeCells($data['y'], $data['x'], array('include_current' => true));
            // Cal relative cells
            foreach ($cells as $cell) {
                list($row, $col) = $cell;
                $_k = static::key($row, $col);
                if (($row < 1 || $row > $this->_rows)
                    || ($col < 1 || $col > $this->_cols)
                    || (!is_null($this->_shoots[$_k]) && $_k != $key)
                ) {
                    continue;
                }
                if (!array_key_exists($_k, (array)$this->_hitShoots)) {
                    $this->_hitShoots[$_k] = null;
                }
            }
            unset($_k);
        }

        //
        if (array_key_exists($key, $this->_hitShoots)) {
            $this->_hitShoots[$key] += $isHit;
        }

        // Our shoots result?
        // +++ Store shoots by key
        $this->_shoots[$key] += $isHit;
        
        // Opponent shoots?
        // +++ Store shoots by key
        $this->_opponentsShoots[$key] += $isHit;

        // @TODO: Remove unnecessary hit shoots
        $this->_removeUnnecessaryHitShoots();
        // #end
        
        // @TODO:
        // Trace hit shoots?
        if ($oShipSunk) {
            // Kiem tra lai lich su ban tau so voi vi tri tau (game engine gui len).
            // Neu, van con hit cell nam ngoai vi tri tau --> chac 100% la van con tau gan ben.
            // ...
            
            // Sau khi ban chim tau. Trong ds cells chua ban cua tau thi co 50/50% kha nang
            // La co tau ke ben tau chim. Can can doi giua viec tiep tuc ban het tat ca o (cells)
            // trong ds nay, hoac la di chuyen tiep vi tri khac?! 
            // Lay ds tat ca cells chua ban cua ship
            $nullHitShootCells = array();
            foreach ((array)$oShipSunk['position'] as $pos) {
                $relCells = $this->getRelativeCells($pos['y'], $pos['x']);
                foreach ($relCells as $_k => $relCell) {
                    if (array_key_exists($_k, $this->_hitShoots) && is_null($this->_hitShoots[$_k])) {
                        $nullHitShootCells[] = $_k;
                    }
                }
            }
            $leepCnt = count($nullHitShootCells) - 4 /* max null hit shoot count */;
            if ($leepCnt > 0) {
                for ($i = 0; $i < $leepCnt; $i++) {
                    $_k = $nullHitShootCells[rand(0, count($nullHitShootCells) - 1)];
                    // @TODO: use this feature? 
                    // $this->_hitShoots[$_k] = 0;
                }
                var_dump('$nullHitShootCells: ' . implode(' | ', $nullHitShootCells));
            }
        }
        // #end

        // Kiem tra tinh du thua cua cac o (cells) --> loai bo.
        $this->checkAvail();
        // #end
    }
    
    /**
     *
     */
    public function fromArr(array $data = null) {
        // Format data
        $data = is_array($data) ? $data: array();
        //
        $this->_type = (static::TYPE_P == $data['type']) ? static::TYPE_P : static::TYPE_L;
        if (static::TYPE_P == $this->_type) {
            $this->_cols = 8;
            $this->_rows = 20;
        } else {
            $this->_cols = 20;
            $this->_rows = 8;
        }
        /* if (is_numeric($data['cols']) && $data['cols'] > 0) {
            $this->_cols = $data['cols'];
        }
        if (is_numeric($data['rows']) && $data['rows'] > 0) {
            $this->_rows = $data['rows'];
        } */
        if (is_array($data['ships'])) {
            $this->_ships = $data['ships'];
        }
        if (is_array($data['shoots'])) {
            $this->_shoots = $data['shoots'];
        }
        if (is_array($data['hit_shoots'])) {
            $this->_hitShoots = $data['hit_shoots'];
        }
        if (is_array($data['opponents_shoots'])) {
            $this->_opponentsShoots = $data['opponents_shoots'];
        }
        if (is_array($data['shoot_blocks'])) {
            $this->_shootBlocks = $data['shoot_blocks'];
        }
        if (is_numeric($data['shoots_per_block'])) {
            $this->_shoots_per_block = $data['shoots_per_block'];
        }
        // Build data
        $this->_buildData();
    }
    
    /**
     * 
     */
    protected function _buildData() {        
        $blockCellCnt = 4;
        for ($col = 1; $col <= $this->_cols; $col++) {
            for ($row = 1; $row <= $this->_rows; $row++) {
                $blockCol = ceil($col / $blockCellCnt); 
                $blockRow = ceil($row / $blockCellCnt);
                //
                $cell = (object)array(
                    'x' => $col,
                    'y' => $row,
                    'key' => static::key($row, $col),
                    'sum' => $col + $row
                );
                //
                $this->_blocks[$blockRow][$blockCol][] = $cell; 
            }
        }
    }
    
    /**
     * Get list of cell(s) of block
     * @param int $blockRow Block row
     * @param int $blockCol Block col
     * @return array
     */
    protected function _getShootBlockCells($blockRow, $blockCol) {
        return (array)$this->_blocks[$blockRow][$blockCol];
    }
    
    /**
     * Count number of cell(s) shoot
     * @param int $blockRow Block row
     * @param int $blockCol Block col
     * @param array $options Options
     * @return int
     */
    protected function _numOfCellsShoot($blockRow, $blockCol, array $options = array()) {
        $cnt = 0;
        $cells = $this->_getShootBlockCells($blockRow, $blockCol);
        foreach ($cells as $cell) {
            if (!is_null($this->_shoots[$cell->key])) {
                $cnt += 1;
            }
        }
        if (true === $options['cells_count']) {
            return array($cnt, count($cells));
        }
        return $cnt;
    }
    
    /**
     * Calculate next block will be used for shooting
     */
    protected function _calWillShootBlock() {
        $shootBlocks = $this->_shootBlocks;
        end($shootBlocks);
        $block = current($shootBlocks);
        // Pick previous shoot block
        if ($block) {
            list($blockRow, $blockCol) = $block;
            $numOfCellsShoot = $this->_numOfCellsShoot($blockRow, $blockCol);
            if ($numOfCellsShoot >= $this->_shoots_per_block) {
                $block = null;
            }
        }
        //
        $maxCellCnts = 0;
        $shootBlocksCenter = array();
        $shootBlocksEdges = array();
        for ($bR = 1; $bR <= count($this->_blocks); $bR++) {
            for ($bC = 1; $bC <= count($this->_blocks[$bR]); $bC++) {
                list($num, $cellsCnt) = $this->_numOfCellsShoot($bR, $bC, array('cells_count' => true)); // num of cells shoot
                $lessShootPerBlock = ($num < $this->_shoots_per_block);
                // Blocks from centers
                if ($lessShootPerBlock) {
                    // Blocks from centers
                    if ($bC >= 2 && $bC <= 4) {
                        $shootBlocksCenter[] = array($bR, $bC);
                    // Blocks from edge
                    } else {
                        $shootBlocksEdges[] = array($bR, $bC);
                    }
                }
                //
                $maxCellCnts = max($maxCellCnts, $cellsCnt);
            }
        }
        // Pick shoot blocks from center?
        if (!$block && !empty($shootBlocksCenter)) {
            list($bR, $bC) = $block = $shootBlocksCenter[rand(0, count($shootBlocksCenter) - 1)];
            $this->_shootBlocks[static::key($bR, $bC)] = $block;
        }
        // Pick shoot blocks from edge
        if (!$block && !empty($shootBlocksEdges)) {
            list($bR, $bC) = $block = $shootBlocksEdges[rand(0, count($shootBlocksEdges) - 1)];
            $this->_shootBlocks[static::key($bR, $bC)] = $block;
        }
        //
        if (!$block && ($this->_shoots_per_block < $maxCellCnts /* ($maxCellCnts / 2) */)) {
            $this->_shoots_per_block += 1;
            return $this->_calWillShootBlock();
        }
        // Block is empty!
        if (!$block) {
            // die('$block is empty!');
        }
        // Return
        return $block;
    }
    
    /**
     * Sau khi ban trung 1 diem (hit shoot), ta se chuyen sang [target mode].
     * Tim nhung o (cells) lien quan cua o (cell) da ban trung de ban tiep tuc.
     * Trong nhieu truong hop, cac o (cells) nay la du thua.
     * Ham nay giup kiem tra + loai bo cac o (cells) du thua.
     */
    protected function _removeUnnecessaryHitShoots() {
        $hitCells = array();
        $removedHitShoots = array();
        // $shipStats = array();
        $options = array('merge_cells' => true);
        foreach ($this->_hitShoots as $_k => $hitShoot) {
            if (!is_null($hitShoot)) {
                continue;
            }
            list($_r, $_c) = static::keyR($_k);
            $relCells = $this->getRelativeCells($_r, $_c);
            foreach ($relCells as $__k => $relCell) {
                if (!$this->_hitShoots[$__k]) {
                    continue;
                }
                $containCells = array($_k, $__k);
                $availCells = array();
                foreach ($this->_ships as $ship) {
                    if (!$ship['osunk']) {
                        $shipAvailCells = $this->_shipContainCells($containCells, $ship, $options);
                        $availCells = array_replace($availCells, $shipAvailCells);
                        // if (is_array($options['merge_cells'])) { $shipStats[] = $options['merge_cells']; }
                    }
                }
                $hitCells[] = implode('/', $containCells) . '[' . count($availCells) . ']';
                // $availCells se la danh sach cells co the ban trung (hit shoot)
                // Neu, cells nao trong danh sach hitShoots hien tai khong nam trong mang
                // nay thi loai bo (vi chac chan se khong trung)
                if (empty($availCells)) {
                    $this->_hitShoots[$_k] = 0; // Ghi nhan da ban truot (missed)!
                    // @TODO: ???
                    // $this->_shoots[$_k] = 0; // Ghi nhan da ban truot (missed)!
                    //
                    $removedHitShoots[] = $_k;
                }
            }
        }
        // var_dump('$hitCells: ' . implode(' | ', $hitCells));
        if (!empty($removedHitShoots)) {
            var_dump('$removedHitShoots: ', $removedHitShoots);
            // var_dump('$shipStats: ', $shipStats);
        }
    }
    
    /**
     * Kiem tra tinh du thua cua cac o (cells) --> loai bo.
     */
    public function checkAvail($data = null) {
        // Started with a board that all cells are unavailable!
        $cells = array();
        //
        $this->_mapCellWithOShipAndDirc(function($ship, $dirc, $row, $col, $key) use (&$cells) {
            // Stats of a ship by row:col...
            $shipStats = $this->_getShipStats($ship['type'], $dirc, $row, $col);
            // Case: this ship placed succeed on board!
            if ($shipStats['total_missed'] <= 0) {
                $cellKeys = $shipStats['cells'];
                $cells = array_replace($cells, $cellKeys);
            }
        });
        $cells = array_filter($cells, function($input){
            list($_r, $_c, $opts) = $input;
            return intval($opts['hit']) <= 0;
        });
        //
        $autoShoots = array();
        $this->_mapCell(function($row, $col, $key) use (&$cells, &$autoShoots) {
            if (is_null($cells[$key]) && is_null($this->_shoots[$key])) {
                $this->_shoots[$key] = false;
                $autoShoots[] = $key;
            }
        });
        if (!empty($autoShoots)) {
            echo '<pre>$autoShoots '; var_dump($autoShoots); echo '</pre>';
        }
    }
    
    /**
     * 
     */
    protected function _getShipStats($type, $direction, $startRow, $startCol) {
        $totalCell = 0;
        $totalHit = 0;
        $totalMissed = 0;
        $cells = array();
        $matrix = Ship::matrixByType($type, $direction);
        foreach ($matrix as $_r => $line) { // zero based
            $row = ($startRow + $_r);
            if ($row > $this->_rows) {
                continue;
            }
            foreach ($line as $_c => $dot) {
                $col = ($startCol + $_c);
                if ($dot && $col <= $this->_cols) {
                    $key = static::key($row, $col);
                    //
                    $totalCell += 1;
                    //
                    $shoot = $this->_shoots[$key];
                    //
                    $cells[$key] = array($row, $col, array('hit' => $shoot));
                    //
                    if (!is_null($shoot)) {
                        if (!$shoot) {
                            $totalMissed += 1;
                        } else {
                            $totalHit += 1;
                        }
                    }
                }
            }
        }
        $return = array(
            'type' => $type,
            'direction' => $direction,
            'total_cell' => $totalCell,
            'total_hit' => $totalHit,
            'total_missed' => $totalMissed,
            'cells' => $cells
        );
        // echo '<pre>'; var_dump($return); echo '</pre>';die();
        return $return;
    }

    /**
     * 
     */
    protected function _mapCell($caller, $rowMin = null, $colMin = null, $rowMax = null, $colMax = null) {
        $return = null;
        for ($row = ($rowMin ?: 1); $row <= ($rowMax ?: $this->_rows); $row++) {
            for ($col = ($colMin ?: 1); $col <= ($colMax ?: $this->_cols); $col++) {
                $key = static::key($row, $col);
                $return = call_user_func($caller, $row, $col, $key);
                if (false === $return) { break; }
            }
            if (false === $return) { break; }
        }
        return $return;
    }
    
    /**
     *
     */
    protected function _mapCellWithOShipAndDirc($caller) {
        $return = null;
        $shipDirc = Ship::returnDirecArr(); // Ship directions
        foreach ($shipDirc as $dirc) {
            foreach ($this->_ships as $ship) {
                if ($ship['osunk']) { // Tau da bi ban chim --> khong xu ly
                    continue;
                }
                $return = $this->_mapCell(function($row, $col, $key) use (&$caller, $ship, $dirc) {
                    return $caller($ship, $dirc, $row, $col, $key);
                });
                if (false === $return) { break; }
            }
        }
        return $return;
    }
    
    /**
     * 
     */
    public function toArr() {
        $return = array(
            // 'type' => $this->_type,
            // 'cols' => $this->_cols,
            // 'rows' => $this->_rows,
            'hit_shoots' => $this->_hitShoots,
            'shoots' => $this->_shoots,
            'shoots_per_block' => $this->_shoots_per_block,
            'shoot_blocks' => $this->_shootBlocks,
            'ships' => $this->_ships,
            'opponents_shoots' => $this->_opponentsShoots,
        );
        return $return;
    }
}