<?php
/**
 * 
 * @author KhanhDTP
 */
class Board {
    /**
     * @var integer
     */
    const TYPE_P = 0; // portrait

    /**
     * @var integer
     */
    const TYPE_L = 1; // landscape

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
     * @var array History of our shoots
     */
    protected $_shoots = array();
    
    /**
     * @var array History of opponents shoots
     */
    protected $_opponentsShoots = array();

    /**
     * 
     * @param array $options
     */
    public function __construct(array $data = null) {
        $this->fromArr($data);
    }
    
    /**
     * Fire (shoot)
     * @return array
     */
    public function shoot() {
        $x = 6; $y = 21;
        $this->_shoots[$x][$y] = 0;
        return array(
            'x' => $x, 'y' => $y
        );
    }
    
    /**
     *
     */
    public function fromArr(array $data = null) {
        // Format data
        $data = is_array($data) ? $data: array();
        //
        $data['type'] = (static::TYPE_L == $data['type']) ? $data['type'] : static::TYPE_P;
        $this->_type = $data['type'];
        if (static::TYPE_P == $data['type']) {
            $this->_cols = 8;
            $this->_rows = 25;
        } else {
            $this->_cols = 25;
            $this->_rows = 8;
        }
        if (is_numeric($data['cols']) && $data['cols'] > 0) {
            $this->_cols = $data['cols'];
        }
        if (is_numeric($data['rows']) && $data['rows'] > 0) {
            $this->_rows = $data['rows'];
        }
        if (is_array($data['shoots'])) {
            $this->_shoots = $data['shoots'];
        }
        if (is_array($data['opponents_shoots'])) {
            $this->_opponentsShoots = $data['opponents_shoots'];
        }
    }
    
    /**
     * 
     */
    public function toArr() {
        return array(
            'type' => $this->_type,
            'cols' => $this->_cols,
            'rows' => $this->_rows,
            'shoots' => $this->_shoots,
            'opponents_shoots' => $this->_opponentsShoots
        );       
    }
}