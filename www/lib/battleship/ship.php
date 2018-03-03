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
    const TYPE_AIRCRAFT = 'Aircraft';
    
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
     * @var integer
     */
    protected $_x = 1;
    
    /**
     * @var integer
     */
    protected $_y = 1;

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
                $options['x'] = 2;
                $options['y'] = 2;
                break;
            case static::TYPE_BATTLESHIP:
                $rows = 4;
                $options['x'] = 6;
                $options['y'] = 3;
                break;
            case static::TYPE_CRUISER:
                $rows = 3;
                $options['x'] = 3;
                $options['y'] = 19;
                break;
            case static::TYPE_SUBMARINE:
                $rows = 3;
                $options['x'] = 5;
                $options['y'] = 14;
                break;
            case static::TYPE_DESROYER:
                $rows = 2;
                $options['x'] = 6;
                $options['y'] = 22;
                break;
            default:
                throw new Exception('Ship type is unknown!');
        }
        $this->_type = $type;
        $this->_cols = $cols;
        $this->_rows = $rows;
		
		// Set pos?
		$this->setPos($options['x'], $options['y']);
    }
	
	/**
     * 
     */
	public function getType() {
		return $this->_type;
	}
	
	/**
     * 
     * @param string $type
     * @param array $options
     */
	public function setPos($x, $y = null) {
		if (is_numeric($x) && $x >= 0) {
			$this->_x = abs(intval($x));
		}
		if (is_numeric($y) && $y >= 0) {
			$this->_y = abs(intval($y));
		}
	}
	
	public function getPos() {
		return array(
			$this->_x, $this->_y
		);
	}
	
	public function toArr() {
		return array(
			'type' => $this->_type,
			'cols' => $this->_cols,
			'rows' => $this->_rows,
			'x' => $this->_x,
			'y' => $this->_y
		);
	}
}