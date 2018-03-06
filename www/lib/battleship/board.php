<?php
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
    protected $_shoots = array();
    
    /**
     * @var array History of opponents shoots
     */
    protected $_opponentsShoots = array();
    
    /**
     * 
     */
    public static function key($row, $col) {
        return "{$row}:{$col}";
    }

    /**
     * 
     * @param array $options
     */
    public function __construct(array $data = null) {
        $this->fromArr($data);
    }
    
    /**
     * Our shoot
     * @return array
     */
    public function shoot() {
        $return = array(
            'x' => 1, 'y' => 1
        );
        list($blockRow, $blockCol) = $this->_calWillShootBlock();
        $cells = $this->_getShootBlockCells($blockRow, $blockCol);
        foreach ($cells as $cell) {
            if ($cell->sum % 2 == 0) {
                if (!is_null($this->_shoots[$cell->y][$cell->x])) {
                    continue;
                }
                $this->_shoots[$cell->y][$cell->x] = 0;
                $return = array(
                    'x' => $cell->x, 'y' => $cell->y
                );
                break;
            }
        }
        return $return;
    }
    
    /**
     * Their shoot
     * @return array
     */
    public function shootAt($data) {
        $this->_opponentsShoots[$data['y']][$data['x']] += intval($data['is_hit']); 
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
        if (is_array($data['shoots'])) {
            $this->_shoots = $data['shoots'];
        }
        if (is_array($data['opponents_shoots'])) {
            $this->_opponentsShoots = $data['opponents_shoots'];
        }
        if (is_array($data['shoot_blocks'])) {
            $this->_shootBlocks = $data['shoot_blocks'];
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
                $this->_matrix[$row][$col] = $cell;
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
     * @return int
     */
    protected function _numOfCellsShoot($blockRow, $blockCol) {
        $cnt = 0;
        $cells = $this->_getShootBlockCells($blockRow, $blockCol);
        foreach ($cells as $cell) {
            if (!is_null($this->_shoots[$cell->y][$cell->x])) {
                $cnt += 1;
            }
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
        //
        if ($block) {
            list($blockRow, $blockCol) = $block;
            $numOfCellsShoot = $this->_numOfCellsShoot($blockRow, $blockCol);
            // @TODO
            if ($numOfCellsShoot >= 5) {
                $block = null;
            }
        }
        
        if (!$block) {
            do {
                $blockRow = rand(1, count($this->_blocks));
                $blockCol01 = rand(2, 4 /* count($this->_blocks[$blockRow]) */);
                $blockCol02 = rand(1, 2);
                if (2 == $blockCol02) {
                    $blockCol02 = 5;
                }
                $block = array($blockRow, $blockCol01);
                $key = static::key($blockRow, $blockCol01);
                $this->_shootBlocks[$key] = $block;
            } while (!$block);
        }
        // Return
        return $block;
    }
    
    /**
     * 
     */
    public function toArr() {
        $return = array(
            'type' => $this->_type,
            'cols' => $this->_cols,
            'rows' => $this->_rows,
            'shoots' => $this->_shoots,
            'opponents_shoots' => $this->_opponentsShoots,
            'shoot_blocks' => $this->_shootBlocks,
        );
        return $return;
    }
}