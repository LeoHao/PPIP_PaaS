<?php
/**
 * @Filename         : Exception.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-31 12:21
 * @Description      : db exception
 **/

class DB_Exception extends PDOException  {

    public function __construct($message, $code = 0) {
        if ($message instanceof Exception) {
            parent::__construct($message->getMessage(), $message->getCode());
        } else {
            parent::__construct($message, $code);
        }
    }

}