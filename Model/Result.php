<?php
/**
 * Created by PhpStorm.
 * User: ahmed
 * Date: 13/02/18
 * Time: 11:44 م
 */

namespace DPDFrance\Shipping\Model;


class Result
{
    public $success;
    public $result;

    public function __construct($success, $result=null) {
        $this->success = $success;
        $this->result = $result;
    }

    public function __toString() {
        return is_bool($this->result) ? ($this->result ? 'true' : 'false') : (string)$this->result;
    }
}