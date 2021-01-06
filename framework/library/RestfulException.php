<?php
/**
 * @Filename         : Exception.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-5 12:21
 * @Description      : this is base on Swoole tcp server
 **/

class RestfulException extends Exception {

    /**
     * $_errors
     * @var array
     */
    protected $_errors = array();

    /**
     * $response code
     * @var int 
     */
    protected $response_code = 0;

    
    /**
     * Restful_Exception constructor.
     * @param $message
     * @param int $code
     * @param array $errors
     * @param int $response_code
     */
    public function __construct($message, $code = 0, $errors = array(), $response_code = 0 ) {

        if ($message instanceof Exception) {
            parent::__construct($message->getMessage(), $message->getCode());

            if (method_exists($message, 'get_errors')) {
                $errors = $message->get_errors();
            }
        } else {
            parent::__construct($message, intval($code));
        }

        $this->set_errors($errors);
        $this->set_response_code($response_code);
    }

    /**
     * set exception error data
     *
     * @param mixed $data error data
     */
    public function set_errors($errors) {
        $this->_errors = $errors;
    }

    /**
     * get exception error data
     * 
     * @return mixed
     */
    public function get_errors() {
        return $this->_errors;
    }

    /**
     * set response code
     * 
     * @param $response_code
     */
    public function set_response_code($response_code) {
        $this->response_code = $response_code;
    }
    
    /**
     * return response code 
     * 
     * @return response_code
     */
    public function get_response_code() {
        return $this->response_code;
    }
}
