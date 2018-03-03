<?php
//
require_once('board.php');
require_once('battleship.php');
require_once('ship.php');

/**
 * Class for working with game engine!
 * @author KhanhDTP
 */
class GameEngine {
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
     * 
     */
	public static function resJSON($data) {
		header('text/json');
		$json = json_encode($data);
		die($json);
	}
	
	/**
     * 
     */
	public static function response($data) {
		return static::resJSON($data);
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
			case 'start_new_game':
				$this->_startNewGame($request['data']);
				break;
		}
		//
		static::response($this->_response);
    }
	
	/**
     * 
     */
	protected function _startNewGame($data) {
		// Init board
		$this->_board = new Board();
		// #end

		// @TODO: init and place ships?
		$this->_ships = array(
			new Ship(Ship::TYPE_AIRCRAFT),
			new Ship(Ship::TYPE_BATTLESHIP),
			new Ship(Ship::TYPE_SUBMARINE),
			new Ship(Ship::TYPE_CRUISER),
			new Ship(Ship::TYPE_DESROYER)
		);
		// #end

		//
		$resData = array(
			'ships' => array()
		);
		foreach ($this->_ships as $ship) {
			$resData['ships'][] = $ship->toArr();
		}
		
		$this->_response['data'] = $resData;
		return static::response($this->_response);
	}
}