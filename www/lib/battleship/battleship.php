<?php
//
require_once('board.php');

//
define('PLAYER_ID', getenv('AI_NAME') ?: 'ea_team_no1');
define('DEBUG', !(PLAYER_ID == 'ea_team_no1'));

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
     * @var array Last game saved data
     */
    public $data = null;

    /**
     * @var array
     */
	protected $_response = array();
	
	/**
     * @var Board
     */
	protected $_board;

    /**
     * Format game-engine incomming request
     * @param array $request
     * @return array
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
	 * Response data to client (game-engine)
	 * @param array $data
	 * @param mixed $error
	 * @return string JSON
	 */
	public function response($data = null, $error = null) {
	    // Headers
	    header('X-SESSION-ID: ' . $this->_X_SESSION_ID);
	    header('X-TOKEN: ' . $this->_X_TOKEN);
	    // Body
	    if ($error) {
	        // https://stackoverflow.com/questions/4162223/how-to-send-500-internal-server-error-error-from-a-php-script
	        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	        $this->_response['error'] = $error;
	    } else {
	        $this->_response = $data;
	    }
		// Log
		@$this->_log('[RES] ' . @json_encode($this->_response, JSON_PRETTY_PRINT));
		@$this->_log(str_repeat('-', 80), false);
	    //
	    return static::resJSON($this->_response);
	}

	/**
     * Response data to client (game-engine) with format of JSON
	 * @param array $data
	 * @return string JSON
     */
	public static function resJSON($data) {
		header('content-type: text/json;charset=UTF-8');
		$json = @json_encode($data);
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
	        return $this->response(null, 'Data directory info is required!');
	    }
		$this->_data_dir = realpath($dataDir) . "/";
	    // +++ load game data
	    $this->_loadGameData();
	    // #end

	    // Init board?
	    $this->_board = new Board($this->data['board']);
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
		// Log
		@$this->_log('[REQ] ' . @json_encode($request, JSON_PRETTY_PRINT));
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
            // Game over
			case 'game-over':
			    $this->_gameOver($request['data']);
			    break;
		}
		// default
		static::response($this->_response);
    }
    
    /**
     * Get game ID
     * @return string
     */
    protected function _gameID() {
        return $saveGameID = md5($this->_X_SESSION_ID) . "_" . PLAYER_ID;
    }
    
    /**
     * Save game data
     * @return Battleship
     */
    protected function _saveGameData($data = null) {
        // Format data
        $data = (array)(is_null($data) ? $this->data : $data);
        // Save (replace) data
        $gameID = $this->_gameID();
		$filename = "{$this->_data_dir}{$gameID}.json";
		file_put_contents($filename, @json_encode($data, DEBUG ? JSON_PRETTY_PRINT : null));
        return $this;
    }
    
    /**
     * Load game data
     * @return array
     */
    protected function _loadGameData() {
        $gameID = $this->_gameID();
        $filename = "{$this->_data_dir}{$gameID}.json";
        $json = trim(@file_get_contents($filename));
        return $this->data = (array)(@json_decode($json, true));
    }
    
    /**
     * Log game-engine request
	 * @param $log Log str
     * @return this
     */
    protected function _log($log, $prependDatetime = true) {
        $gameID = $this->_gameID();
		$filename = "{$this->_data_dir}{$gameID}.log";
        file_put_contents($filename, ($prependDatetime ? date('[Y-m-d H:i:s] ') : '') . $log . PHP_EOL, FILE_APPEND);
        return $this;
    }
	
	/**
	 * GE/invite
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
     * GE/place-ships
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
     * GE/shoot
     * @param array $data Request data
     * @return string (JSON)
	 */
	protected function _shoot($data) {
	    // Format data
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
	        $key = Board::key($shoot['y'], $shoot['x']);
	        $coordinates[$key] = array($shoot['x'], $shoot['y']);
	    }
	    $resData = array('coordinates' => array_values($coordinates));
	    // Debug
	    if (count($coordinates) > 1) {
	        $this->debug('shoot', 'turn_' . $data['turn'] . '#maxShots_' . $data['maxShots'] . '#coordinates' . @json_encode($coordinates));
        }
	    //
	    return $this->response($resData);
	}
	
	/**
     * GE/notify
     * @param array $data Request data
     * @return string (JSON)
	 */
	protected function _notify($data) {
	    // Format data
	    $data['shots'] = (array)$data['shots'];
	    $data['sunkShips'] = (array)($data['sunk_ships'] ?: $data['sunkShips']);
	    unset($data['sunk_ships']);
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
     * GE/notify
     * @param array $data Request data
     * @return string (JSON)
	 */
	protected function _gameOver($data) {
	    $resData = $this->_board->gameOver($data);
	    //
	    return $this->response($resData);
	}
	
	/**
     * Debug helper
     * @param string $info
     * @return string
	 */
	public function debug($label, $info) {
	    if (DEBUG) {
	        $parts = explode('.', (string)microtime(true));
	        $key = date('H:i:s:') . $parts[1];
	        $this->data['DEBUG'][$label][$key] = $info;
	    }
	    return $this;
	}

	/**
	 * 
	 */
	public function __destruct() {
	    // Save game data
	    $this->data['board'] = $this->_board->toArr();
		$this->_saveGameData();
	}
}