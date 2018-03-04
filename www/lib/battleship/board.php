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
     * 
     * @param array $options
     */
    public function __construct($type = null, array $options = array()) {
        $type = (static::TYPE_L == $type) ? $type : static::TYPE_P;
        $this->_type = $type;
        if (static::TYPE_P == $type) {
            $this->_cols = 8;
            $this->_rows = 25;
        } else {
            $this->_cols = 25;
            $this->_rows = 8;
        }
    }
}