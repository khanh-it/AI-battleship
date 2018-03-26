<?php
/**
 * 
 * @author KhanhDTP
 */
class Ship {
    /**
     * Carrier
     * @var string
     */
    const TYPE_CARRIER = 'CV';
    
    /**
     * Battleship
     * @var string
     */
    const TYPE_BATTLESHIP = 'BB';
    
    /**
     * OilRig
     * @var string
     */
    const TYPE_OILRIG = 'OR';
    
    /**
     * Cruiser
     * @var string
     */
    const TYPE_CRUISER = 'CA';
    
    /**
     * Destroyer
     * @var string
     */
    const TYPE_DESROYER = 'DD';
    
    /**
     * Direction: 0 (horizontal)
     * @var integer
     */
    const DIREC_H = 0;
    
    /**
     * Direction: 1 (vertical)
     * @var integer
     */
    const DIREC_V = 1;

    /**
     * @return array
     */
    public static function returnDirecArr() {
        return [static::DIREC_H, static::DIREC_V];
    }
    
    /**
     * 
     */
    public static function matrixByType($type, $direction = null) {
        $dH = static::DIREC_H;
        $dV = static::DIREC_V;
        $direction = ($dV == $direction) ? $dV : $dH;

        $matrix = [
            static::TYPE_CARRIER => [
                $dH => [
                    [0, 1, 0, 0],
                    [1, 1, 1, 1],
                ],
                $dV => [
                    [0, 1],
                    [1, 1],
                    [0, 1],
                    [0, 1],
                ]
            ],
            // #end
            static::TYPE_BATTLESHIP => [
                $dH => [
                    [1, 1, 1, 1],
                ],
                $dV => [
                    [1],
                    [1],
                    [1],
                    [1],
                ]
            ],
            // #end
            static::TYPE_OILRIG => [
                $dH => [
                    [1, 1],
                    [1, 1],
                ],
                $dV => [
                    [1, 1],
                    [1, 1],
                ]
            ],
            // #end
            static::TYPE_DESROYER => [
                $dH => [
                    [1, 1],
                ],
                $dV => [
                    [1],
                    [1],
                ]
            ],
            // #end
            static::TYPE_CRUISER => [
                $dH => [
                    [1, 1, 1],
                ],
                $dV => [
                    [1],
                    [1],
                    [1],
                ]
            ],
            // #end
        ];
        
        // Return
        $result = $matrix[$type][$direction];
        unset($matrix);
        return $result;
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
    protected $_matrix = [];
	
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
    public function __construct($type, array $options = []) {
        $matrix = static::matrixByType($type, $options['direction']);
        switch ($type) {
            case static::TYPE_CARRIER:
                break;
            case static::TYPE_BATTLESHIP:
                break;
            case static::TYPE_CRUISER:
                break;
            case static::TYPE_OILRIG:
                break;
            case static::TYPE_DESROYER:
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
		return [
			$this->_x, $this->_y
		];
	}
	
	public function getCoordinates() {
	    $posY = $this->_y;
	    $coordinates = [];
	    foreach ($this->_matrix as $row) {
	        foreach ($row as $index => $cell) {
	            if ($cell) {
	                $coordinates[] = [
	                    $this->_x + $index, $posY
                    ];
                }
            }
            $posY++;
        }
        return $coordinates;
    }
	
	public function toArr() {
		return [
			'type' => $this->_type,
		    'coordinates' => $this->getCoordinates(),
			'matrix' => $this->_matrix,
			'x' => $this->_x,
            'y' => $this->_y,
            // 'direction' => $this->_direction,
            'sunk' => 0,
            // opponent data
            // 'ox' => 0,
            // 'oy' => 0,
            'osunk' => 0
		];
	}
}