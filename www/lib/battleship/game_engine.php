<?php
/**
 * Class for working with game engine!
 * @author KhanhDTP
 */
class GameEngine {
    
    /**
     * 
     */
    protected function _gaData($type, array $data = null) {
        return array(
            'type' => $type,
            'data' => $data
        );
    }
    
    /**
     * Receive data from game engine!
     * @param array $data Request data
     * @return array
     */
    public function receiveRequest(array $data = null) {
        $data = (array)(is_null($data) ? $_REQUEST : $data);
        
        $return = $this->_gaData();
    }
}