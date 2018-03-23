<?php
//
require_once('board.php');

//
define('DEBUG', false);
define('PLAYER_ID', getenv('AI_NAME') ?: 'ea_team_no1');

/**
 * Class for working with game engine!
 * @author KhanhDTP
 */
class Battleship {
    /**
     * @var string X-SESSION-ID
     */
    protected $_X_SESSION_ID = '';

    /**
     * @var string X-TOKEN
     */
    protected $_X_TOKEN = '';

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
	protected $_response = array();
	
	/**
     * @var Board
     */
	protected $_board;

    /**
     * 
     */
    protected function _formatRequest($request) {
        preg_match('/\/([^\/]+)\/?/', $_SERVER['REQUEST_URI'], $type) && ($type = $type[1]);
        return array(
            'type' => strtolower($type),
            'data' => json_decode(@file_get_contents('php://input'), true) ?: $_REQUEST
        );
	}
	
	/**
	 * Get game-engine header 'X-SESSION-ID'
	 * @return string
	 */
	public static function getHeaderSessionID() {
	    $headers = array_map(strtoupper, array_replace(
	        $_SERVER, (array)headers_list()
        ));
	    $return = $headers['X-SESSION-ID'] ?: ($headers['X_SESSION_ID'] ?: (
            $headers['HTTP-X-SESSION-ID'] ?: $headers['HTTP_X_SESSION_ID']
        ));
	    return $return;
	}
	
	/**
	 * Get game-engine header 'X-TOKEN'
	 * @return string
	 */
	public static function getHeaderToken() {
	    $headers = array_map(strtoupper, array_replace(
	        $_SERVER, (array)headers_list()
        ));
	    $return = $headers['X-TOKEN'] ?: ($headers['X_TOKEN'] ?: (
	        $headers['HTTP-X-TOKEN'] ?: $headers['HTTP_X_TOKEN']
        ));
	    return $return;
	}
	
	/**
	 *
	 */
	public function response($data = null, $error = null) {
	    // Headers
	    header('X-SESSION-ID: ' . $this->_X_SESSION_ID);
	    header('X-TOKEN: ' . $this->_X_TOKEN);
	    // Body
	    if ($error) {
	        $this->_response['error'] = $error;
	    } else {
	        $this->_response = $data;
	    }
	    //
	    return static::resJSON($this->_response);
	}

	/**
     * 
     */
	public static function resJSON($data) {
		header('content-type: text/json;charset=UTF-8');
		$json = json_encode($data);
		die($json);
	}

	/**
	 * 
	 * @param array $options
	 */
	public function __construct(array $options = array()) {
	    // Init
	    // +++ game engine headers
	    $this->_X_SESSION_ID = static::getHeaderSessionID();
	    $this->_X_TOKEN = static::getHeaderToken();
	    // +++ game data
	    $dataDir = realpath($options['data_dir']);
	    if (!$dataDir) {
	        throw new Exception('Data directory info is required!');
	    }
	    $this->_data_dir = "{$dataDir}/";
	    // +++ load game data
	    $this->_loadGameData();
	    // #end

	    // Init board?
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
			// Invite (start new game)
			case 'invite':
			    $this->_invite($request['data']);
			    break;
			// Place ships
			case 'place-ships':
			    $this->_placeShips($request['data']);
			    break;
			// Request shoot
			case 'shoot':
			    $this->_shoot($request['data']);
			    break;
			// Response shoot
			case 'notify':
			    $this->_notify($request['data']);
			    break;
            // Check available
			case 'game-over':
			    $this->_gameOver($request['data']);
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
        $saveGameID = $this->_X_SESSION_ID . "_" . PLAYER_ID;
        $filename = "{$this->_data_dir}{$saveGameID}.json";
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT, 10));
        return $this;
    }
    
    /**
     * LOad game data
     * @return array
     */
    protected function _loadGameData() {
        $saveGameID = $this->_X_SESSION_ID . "_" . PLAYER_ID;
        $filename = "{$this->_data_dir}{$saveGameID}.json";
        $json = trim(@file_get_contents($filename));
        return $this->_data = (array)(@json_decode($json, true));
    }
	
	/**
     * @param array $data Request data
     * @return string (JSON)
     */
    protected function _invite($data) {
		// Reset game data
        $this->_board = new Board(array());
        $this->_board->invite($data);
        //		
	    return $this->response();
    }
    
    /**
     * @param array $data Request data
     * @return string (JSON)
     */
    protected function _placeShips($data) {
        $this->_board->placeShips($data);
        $dboard = $this->_board->toArr();
        //
        $ships = array();
        foreach ((array)$dboard['ships'] as $ship) {
            $ships[] = array(
                ($key = 'type') => $ship[$key],
                ($key = 'coordinates') => $ship[$key],
            );
        }
        $resData = array('ships' => $ships);
        //
        return $this->response($resData);
    }
	
	/**
	 * Request fire
	 */
	protected function _shoot($data) {
	    // Format data
	    $data = (array)$data;
	    $data['maxShots'] = ($data['max_shots'] ?: $data['maxShots']);
	    $data['maxShots'] = (intval($data['maxShots']) <= 0) ? 1 : $data['maxShots'];
	    unset($data['max_shots']);
	    // Request fire
	    $coordinates = array();
	    $shoots = (array)$this->_board->shoot($data);
	    if (!is_null($shoots['x']) || !is_null($shoots['y'])) {
	        $shoots = array($shoots);
	    }
	    foreach ($shoots as $shoot) {
	        $coordinates[] = array($shoot['x'], $shoot['y']);
	    }
	    $resData = array('coordinates' => $coordinates);
	    
	    if (count($coordinates) > 1) {
	        $this->_data['_shoot___']['turn_' . $data['turn'] . '#maxShots_' . $data['maxShots']] = $coordinates;
        }
	    //
	    return $this->response($resData);
	}
	
	/**
	 *
	 */
	protected function _notify($data) {
	    $data['shots'] = (array)$data['shots'];
	    $data['sunkShips'] = (array)($data['sunk_ships'] ?: $data['sunkShips']);
	    unset($data['sunk_ships']);
	    
	    /* if (!empty($data['sunkShips'])) {
	        $this->_data['_notify___'] = $data;
	    } */
	    
	    foreach ($data['shots'] as $shot) {
	        $_data = array(
	            'playerId' => $data['playerId'],
	            'x' => $shot['coordinate'][0],
	            'y' => $shot['coordinate'][1],
	            'isHit' => intval('HIT' === $shot['status']),
	            'sunkShips' => $data['sunkShips'],
	        );
	        $this->_board->notify($_data);
	    }
	    return $this->response();
	}
	
	/**
	 *
	 */
	protected function _gameOver($data) {
	    // Request fire
	    $resData = $this->_board->gameOver($data);
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