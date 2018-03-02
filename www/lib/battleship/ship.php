<?php
/**
 * 
 * @author KhanhDTP
 */
class Ship {
    /**
     * Aircraft Carrier
     * @var string
     */
    const TYPE_AIRCRAFT = 'Aircraft Carrier';
    
    /**
     * Battleship
     * @var string
     */
    const TYPE_BATTLESHIP = 'Battleship';
    
    /**
     * Submarine
     * @var string
     */
    const TYPE_SUBMARINE = 'Submarine';
    
    /**
     * Cruiser
     * @var string
     */
    const TYPE_CRUISER = 'Cruiser';
    
    /**
     * Destroyer
     * @var string
     */
    const TYPE_DESROYER = 'Destroyer';
    
    /**
     * 
     * @var string
     */
    protected $_type = '';
    
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
     * @param string $type
     * @param array $options
     */
    public function __construct($type, array $options = array()) {
        $cols = 1; $rows = 0;
        switch ($type) {
            case static::TYPE_AIRCRAFT:
                $rows = 5;
                break;
            case static::TYPE_BATTLESHIP:
                $rows = 4;
                break;
            case static::TYPE_CRUISER:
                $rows = 3;
                break;
            case static::TYPE_SUBMARINE:
                $rows = 3;
                break;
            case static::TYPE_DESROYER:
                $rows = 2;
                break;
            default:
                throw new Exception('Ship type is unknown!');
        }
        $this->_type = $type;
        $this->_cols = $cols;
        $this->_rows = $rows;
    }
}