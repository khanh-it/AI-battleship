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
     * Direction: 0 (portrail)
     * @var integer
     */
    const DIREC_P = 0;
    
    /**
     * Direction: 1 (landscape)
     * @var integer
     */
    const DIREC_L = 1;
    
    /**
     * 
     */
    public static function matrixByType($type, $direction = null) {
        $dP = static::DIREC_P;
        $dL = static::DIREC_L;
        $direction = ($dL == $direction) ? $dL : $dP;
        
        $matrix = array(
            static::TYPE_AIRCRAFT => array(
                $dP => array(
                    array(1, 0),
                    array(1, 1),
                    array(1, 0),
                    array(1, 0),
                ),
                $dL => array(
                    array(0, 0, 1, 0),
                    array(1, 1, 1, 1),
                )
            ),
            // #end
            static::TYPE_BATTLESHIP => array(
                $dP => array(
                    array(1),
                    array(1),
                    array(1),
                    array(1),
                ),
                $dL => array(
                    array(1, 1, 1, 1),
                )
            ),
            // #end
            static::TYPE_CRUISER => array(
                $dP => array(
                    array(1),
                    array(1),
                ),
                $dL => array(
                    array(1, 1),
                )
            ),
            // #end
            static::TYPE_SUBMARINE => array(
                $dP => array(
                    array(1, 1),
                    array(1, 1),
                ),
                $dL => array(
                    array(1, 1),
                    array(1, 1),
                )
            ),
            // #end
            static::TYPE_DESROYER => array(
                $dP => array(
                    array(1),
                    array(1),
                    array(1),
                ),
                $dL => array(
                    array(1, 1, 1),
                )
            ),
            // #end
        );
        
        // Return
        return $matrix[$type][$direction];
    }
    
    /**
     * 
     * @var string
     */
    protected $_type = '';
    
    /**
     * @var integer
     */
    protected $_direction = 0;
    
    /**
     * @var array
     */
    protected $_matrix = array();
	
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
        $matrix = static::matrixByType($type, $options['direction']);
        switch ($type) {
            case static::TYPE_AIRCRAFT:
                $options['x'] = 2;
                $options['y'] = 2;
                break;
            case static::TYPE_BATTLESHIP:
                $options['x'] = 6;
                $options['y'] = 3;
                break;
            case static::TYPE_CRUISER:
                $options['x'] = 19;
                $options['y'] = 3;
                break;
            case static::TYPE_SUBMARINE:
                $options['x'] = 14;
                $options['y'] = 5;
                break;
            case static::TYPE_DESROYER:
                $options['x'] = 12;
                $options['y'] = 6;
                break;
            default:
                throw new Exception('Ship type is unknown!');
        }
        $this->_type = $type;
        $this->_matrix = $matrix;
		
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
			'matrix' => $this->_matrix,
			'x' => $this->_x,
			'y' => $this->_y
		);
	}
}