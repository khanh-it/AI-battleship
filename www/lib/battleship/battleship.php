<?php
//
require_once('board.php');

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
			// Request shoot
			case 'shoot':
			    $this->_shoot($request['data']);
			    break;
			// Response shoot
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
        file_put_contents($filename, json_encode($data/*,JSON_PRETTY_PRINT*/));
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
	    //
	    $dboard = $this->_data['board'];
	    if (is_null($dboard)) {
	        $dboard = $this->_data['board'] = $this->_board->toArr();
	    }
	    // @TODO:
	    // $this->_saveGameData();
	    //
	    $shoots = $dboard['shoots'];
	    unset($shoots['_']);
	    $resData = array(
	        'ships' => $dboard['ships'],
	        'shoots' => $shoots
	    );
        //		
	    return $this->response($resData);
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
	    //
	    $this->_saveGameData();
	}
}