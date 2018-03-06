<?php
//
require_once('board.php');
require_once('battleship.php');
require_once('ship.php');

/**
 * Class for working with game engine!
 * @author KhanhDTP
 */
class Battleship {
    /**
     * @var string Data directory path
     */
    protected $_data_dir = '';

    /**
     * @var stdClass Last game saved data
     */
    protected $_data = null;

    /**
     * 
     */
	protected $_response = array(
		'status' => 1,
		'msg' => '',
		'data' => array()
	);
	
	/**
     * @var Board
     */
	protected $_board;
	
	/**
     * @var array of Ship
     */
	protected $_ships;

    /**
     * 
     */
    protected function _formatRequest($request) {
        return array(
            'type' => $request['type'],
            'data' => $request['data']
        );
	}
	
	/**
	 * @return string
	 */
	public static function getGameSessionID() {
	    $ID = trim($_REQUEST['data']['sessionid']);
	    $ID = $ID ?: date('Ymd');
	    // $ID .= date('_H');
	    /* $i = date('i'); // minutes
	    if ($i >= 30) {
	        $i = 30;
	    } else {
	        $i = 0;
	    }
	    $ID .= str_pad($i, 2, '0', STR_PAD_LEFT); */
	    return $ID;
	}
	
	/**
	 *
	 */
	public function response($data, $msg = null) {
	    if ($msg) {
	        $this->_response['status'] = 0;
	        $this->_response['msg'] = $msg;
	    }
	    $this->_response['data'] = $data;
	    //
	    return static::resJSON($this->_response);
	}

	/**
     * 
     */
	public static function resJSON($data) {
		header('text/json');
		$json = json_encode($data);
		die($json);
	}

	/**
	 * 
	 * @param array $options
	 */
	public function __construct(array $options = array()) {
	    // Init game data
	    $dataDir = realpath($options['data_dir']);
	    if (!$dataDir) {
	        throw new Exception('Data directory info is required!');
	    }
	    $this->_data_dir = "{$dataDir}/";
	    // +++ load game data
	    $this->_loadGameData();
	    // #end

	    // Init board
	    $this->_board = new Board($this->_data['board']);
	    // #end
	    
	    // @TODO: init and place ships?
	    $this->_ships = array(
	        new Ship(Ship::TYPE_AIRCRAFT, array(
	            'direction' => Ship::DIREC_L
	        )),
	        new Ship(Ship::TYPE_BATTLESHIP),
	        new Ship(Ship::TYPE_SUBMARINE),
	        new Ship(Ship::TYPE_CRUISER),
	        new Ship(Ship::TYPE_DESROYER)
	    );
	    // #end
	    
	    // Save game data on reqquest shutdown!
	    register_shutdown_function(array($this, '__destruct'));
	}
    
    /**
     * Receive data from game engine!
     * @param array $data Request data
     * @return array
     */
    public function resolveRequest(array $request = null) {
		// Get, format request!
        $request = $this->_formatRequest(
			(array)(is_null($request) ? $_REQUEST : $request)
		);
		//
		switch ($request['type']) {
			// Start new game
			case 'new_game':
				$this->_newGame($request['data']);
				break;
			// Our shoot
			case 'shoot':
			    $this->_shoot($request['data']);
			    break;
            // Their shoot
			case 'shoot_at':
			    $this->_shootAt($request['data']);
			    break;
		}
		//
		static::response($this->_response);
    }
    
    /**
     * Save game data
     * @return Battleship
     */
    protected function _saveGameData($data = null) {
        // Format data
        $data = (array)(is_null($data) ? $this->_data : $data);
        // Save (replace) data
        $saveGameID = static::getGameSessionID();
        $filename = "{$this->_data_dir}{$saveGameID}.json";
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT /*JSON_FORCE_OBJECT*/));
        return $this;
    }
    
    /**
     * LOad game data
     * @return array
     */
    protected function _loadGameData() {
        $saveGameID = static::getGameSessionID();
        $filename = "{$this->_data_dir}{$saveGameID}.json";
        $json = trim(@file_get_contents($filename));
        return $this->_data = (array)(@json_decode($json, true));
    }
	
	/**
     * @param array $data Request data
     * @return string (JSON)
     */
	protected function _newGame($data) {
		// Reset game data
		// @TODO:
	    // $this->_data['board'] = array();
	    // $this->_data['ships'] = array();
	    // $this->_saveGameData();
	    $this->_initBoard($data);
	    $this->_initShips($data);
	    //
	    $resData = array(
	        'ships' => $this->_data['ships'],
	        'shoots' => $this->_data['board']['shoots']
	    );
        //		
	    return $this->response($resData);
	}
	
	/**
	 * Init board
	 * @param array $data Request data
	 * @return Battleship
	 */
	protected function _initBoard($data) {
	    //
	    if (!isset($this->_data['board'])) {
	        $this->_data['board'] = $this->_board->toArr();
	    }
	    //
	    return $this;
	}
	
	/**
	 * Load ships (on board)
     * @param array $data Request data
     * @return Battleship
	 */
	protected function _initShips($data) {
	    if (!isset($this->_data['ships'])) {
    	    $this->_data['ships'] = array();
    	    foreach ($this->_ships as $ship) {
    	        $this->_data['ships'][] = $ship->toArr();
    	    }
	    }
	    //
	    return $this;
	}
	
	/**
	 *
	 */
	protected function _shoot($data) {
	    // Request fire
	    $resData = $this->_board->shoot($data);
	    //
	    return $this->response($resData);
	}
	
	/**
	 *
	 */
	protected function _shootAt($data) {
	    // Request fire
	    $resData = $this->_board->shootAt($data);
	    //
	    return $this->response($resData);
	}
	
	/**
	 * 
	 */
	public function __destruct() {
	    //
	    // +++
	    $this->_data['board'] = $this->_board->toArr();
	    // +++
	    // $this->_data['board'] = $this->_board->toArr();
	    //
	    $this->_saveGameData();
	}
}