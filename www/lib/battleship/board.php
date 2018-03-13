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
    protected $_matrix = array();
    
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
    protected $_shoots = array(
        '_' => array() // special key, store values by rows, cols 
    );
    
    /**
     * @var array History of our shoots that hit opponent's ships
     */
    protected $_hitShoots = array(
        '_' => array() // special key, store values by rows, cols
    );
    
    /**
     * @var array History of opponents shoots
     */
    protected $_opponentsShoots = array(
        '_' => array() // special key, store values by rows, cols
    );
    
    /**
     * @var integer Maximun number of missed shoot per block
     */
    protected $_shoots_per_block = 4;
    
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
        return array($row, $col);
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
            $shipPresets = require_once __DIR__ . '/ship-presets.php';
            $shipPreset = $shipPresets[rand(0, count($shipPresets) - 1)]; // Pick random
            // $shipPreset = $shipPresets[4]; // debug
            foreach ($shipPreset as $shipP) {
                $this->_ships[] = (new Ship($shipP['type'], $shipP))->toArr();
            }
        }
        //
        return $this;
    }
    
    /**
     * Our shoot
     * @return array
     */
    public function shoot() {
        //
        $return = array();
        // Trace hit shoots?
        $hitShoot = null;
        foreach ($this->_hitShoots as $row => $colWVal) {
            if (!is_numeric($row)) { continue; }
            foreach ($colWVal as $col => $val) {
                if (is_null($val) && is_null($this->_shoots[$row][$col])) {
                    $hitShoot = array($row, $col);
                    break;
                }
            }
            if ($hitShoot) break;
        }
        if ($hitShoot) {
            list($row, $col) = $hitShoot;
            $return = array(
                'x' => $col, 'y' => $row
            );
        }
        
        //
        if (empty($return)) {
            $block = $this->_calWillShootBlock();
            // @TODO: $block is empty!
            if (empty($block)) {
                for ($col = 1; $col <= $this->_cols; $col++) {
                    for ($row = 1; $row <= $this->_rows; $row++) {
                        $key = static::key($row, $col);
                        if (is_null($this->_shoots['_'][$key])) {
                            return array(
                                'x' => $col, 'y' => $row
                            );
                        }
                    }
                }
            } else {
                list($blockRow, $blockCol) = $block;
                $cells = $this->_getShootBlockCells($blockRow, $blockCol);
            }
            $rand = array();
            foreach ($cells as $cell) {
                // @TODO: odd or even?
                if ($cell->sum % 2 == 0) { // odd
                    if (!is_null($this->_shoots[$cell->y][$cell->x])) {
                        continue;
                    }
                    $rand[] = $cell;
                }
            }
            $cell = $rand[$cellIdx = (rand(1, count($rand)) - 1)];
            if ($cell) {
                $key = static::key($cell->y, $cell->x);
                $this->_shoots['_'][$key] = 0;
                $this->_shoots[$cell->y][$cell->x] = 0;
                $return = array(
                    'x' => $cell->x, 'y' => $cell->y
                );
            }
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
        // Opponent's ship sunk?
        $oShipSunk = false;
        // Case: normal hit
        if (is_numeric($isHit)) {
            $isHit = intval($isHit); // 0 | 1
        // @TODO: ship was destroy
        } else {
            $oShipSunk = true;
            // Mark opponent ship as sunk!
            foreach ($this->_ships as &$ship) {
                if (strtolower($ship['type']) == strtolower($isHit['type'])) {
                    $ship['osunk'] = 1;
                    break;
                }
            }
            // #end
            $isHit = 1;
        }

        // Case: hit. Add relative cells to list.
        if ($isHit) {
            $cells = array(
                array($data['y'] - 0, $data['x'] - 0),
                array($data['y'] - 1, $data['x'] - 0),
                array($data['y'] + 1, $data['x'] - 0),
                array($data['y'] - 0, $data['x'] - 1),
                array($data['y'] - 0, $data['x'] + 1)
            );
            // Cal relative cells
            foreach ($cells as $cell) {
                list($row, $col) = $cell;
                $_k = static::key($row, $col);
                if (($row < 1 || $row > $this->_rows)
                    || ($col < 1 || $col > $this->_cols)
                    || (!is_null($this->_shoots['_'][$_k]) && $_k != $key)
                ) {
                    continue;
                }
                if (!array_key_exists($_k, (array)$this->_hitShoots['_'])) {
                    $this->_hitShoots['_'][$_k] = null;
                    $this->_hitShoots[$row][$col] = null;
                }
            }
            unset($_k);
        }

        // Trace hit shoots?
        if ($oShipSunk) {
            // @TODO: 
            // Kiem tra lai lich su ban tau so voi vi tri tau (game engine gui len).
            // Neu, van con hit cell nam ngoai vi tri tau --> chac 100% la van con tau gan ben.
            /* foreach ($this->_hitShoots['_'] as $key => $val) {
                list($row, $col) = static::keyR($key);
                $val = (1 == $val) ? '' : 0;
                $this->_hitShoots['_'][$key] = $val;
                $this->_hitShoots[$row][$col] = $val;
            } */
        } else {
            //
            if (array_key_exists($key, $this->_hitShoots['_'])) {
                $this->_hitShoots['_'][$key] += $isHit;
                $this->_hitShoots[$data['y']][$data['x']] += $isHit;
            }
        }
        // #end

        // Our shoots result?
        // +++ Store shoots by key
        $this->_shoots['_'][$key] += $isHit;
        // +++ Store shoot by rows, cols
        $this->_shoots[$data['y']][$data['x']] += $isHit;
        
        // Opponent shoots?
        // +++ Store shoots by key
        $this->_opponentsShoots['_'][$key] += $isHit;
        // +++ Store shoot by rows, cols
        $this->_opponentsShoots[$data['y']][$data['x']] += $isHit;

        // @TODO
        $hitCells = array();
        $__shipStats = array();
        $availCells = array();
        foreach ($this->_hitShoots['_'] as $_k => $hitShoot) {
            if ($hitShoot) {
                $hitCells[] = $_k;
                foreach ($this->_ships as $ship) {
                    if (!$ship['osunk']) {
                        $options = array('merge_cells' => true);
                        $_availCells = $this->_shipContainCells(array($_k), $ship, $options);
                        $availCells = array_replace($availCells, $_availCells);
                        if (is_array($options['merge_cells'])) {
                            $__shipStats[] = $options['merge_cells'];
                        }
                    }
                }
            }
        } unset($_k, $_availCells, $options, $hitShoot);
        // $availCells se la danh sach cells co the ban trung (hit shoot)
        // Neu, cells nao trong danh sach hitShoots hien tai khong nam trong mang
        // nay thi loai bo (vi chac chan se khong trung)
        if (!empty($availCells)) {
            $_removedHitShoots = array();
            foreach ($this->_hitShoots['_'] as $_k => $hitShoot) {
                if (is_null($hitShoot) /* Chua ban */ && is_null($availCells[$_k]) /* Khong nam trong ds co the ban duoc */) {
                    list($_r, $_c) = static::keyR($_k);
                    $this->_hitShoots['_'][$_k] = 0; // Ghi nhan da ban truot (missed)!
                    $this->_hitShoots[$_r][$_c] = 0; // Ghi nhan da ban truot (missed)!
                    // @TODO: ???
                    // $this->_shoots['_'][$_k] = 0; // Ghi nhan da ban truot (missed)!
                    // $this->_shoots[$_r][$_c] = 0; // Ghi nhan da ban truot (missed)!
                    //
                    $_removedHitShoots[] = $_k; // debug
                }
            }
        }
        if (!empty($_removedHitShoots)) {
            var_dump('$hitCells: ', $hitCells);
            var_dump('$removedHitShoots: ', $_removedHitShoots);
            var_dump('$shipStats: ', $__shipStats);
            var_dump('$availCells: ', $availCells);
            die(); // debug
        }
        // #end

        //
        // $this->checkAvail();
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
                    'sum' => $col + $row
                );
                $this->_matrix['_'][static::key($row, $col)] = $cell;
                $this->_matrix[$row][$col] = $cell;
                $this->_matrixR['_'][static::key($col, $row)] = $cell;
                $this->_matrixR[$col][$row] = $cell;
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
            if (!is_null($this->_shoots[$cell->y][$cell->x])) {
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
        if (!$block && ($this->_shoots_per_block < ($maxCellCnts / 2))) {
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
     *
     */

    public function checkAvail($data = null) {
        // Started with a board 
        $matrix = array();
        
        $cnt = 0;
        $shipDirc = array(Ship::DIREC_P, Ship::DIREC_L);
        foreach ($this->_ships as $ship) {
            // @TODO: tau da bi ban chim --> khong xu ly
            if ($ship['osunk']) {
                continue;
            }
            for ($row = 1; $row <= $this->_rows; $row++) {
                for ($col = 1; $col <= $this->_cols; $col++) {
                    // Neu cell do da ban roi --> skip..!
                    if (!is_null($this->_shoots[$row][$col])) {
                        continue;
                    }
                    foreach ($shipDirc as $dirc) {
                        // Cells of a ship by row:col...
                        $shipStats = $this->_getShipStats($ship['type'], $dirc, $row, $col);
                        // Case: this ship placed succeed on board!
                        if ($shipStats['total_missed'] <= 0) {
                            $cellKeys = array_flip(array_keys($shipStats['cells']));
                            $matrix = array_replace($matrix, $cellKeys);
                        }
                    }
                }
            }
        }
        var_dump('cnt 1) ' . count($this->_shoots['_']));
        $matrix = array_filter($matrix, function($hit){ return $hit <= 0; });
        for ($row = 1; $row <= $this->_rows; $row++) {
            for ($col = 1; $col <= $this->_cols; $col++) {
                $key = static::key($row, $col);
                if (is_null($matrix[$key]) && is_null($this->_shoots['_'][$key])) {
                    $this->_shoots['_'][$key] = 0;
                    $this->_shoots[$row][$col] = 0;
                }
            }
        }
        var_dump('cnt 2) ' . count($this->_shoots['_']));
        echo '<pre>$matrix '; var_dump($matrix); echo '</pre>';
        // echo '<pre>$matrix 2 '; var_dump($this->_matrix['_']); echo '</pre>';
        die();
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
                    $shoot = $this->_shoots[$row][$col];
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
    protected function _mapCell($caller) {
        $return = null;
        for ($row = 1; $row <= $this->_rows; $row++) {
            for ($col = 1; $col <= $this->_cols; $col++) {
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
    public function toArr() {
        $return = array(
            'type' => $this->_type,
            // 'cols' => $this->_cols,
            // 'rows' => $this->_rows,
            'ships' => $this->_ships,
            'shoots' => $this->_shoots,
            'hit_shoots' => $this->_hitShoots,
            'opponents_shoots' => $this->_opponentsShoots,
            'shoot_blocks' => $this->_shootBlocks,
            'shoots_per_block' => $this->_shoots_per_block,
        );
        return $return;
    }
}