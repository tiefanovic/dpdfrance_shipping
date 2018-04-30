<?php
/**
 * Created by PhpStorm.
 * User: ahmed
 * Date: 13/02/18
 * Time: 11:45 م
 */

namespace DPDFrance\Shipping\Model;


class Message
{
    public $type;
    public $message;
    public $args;

    public function __construct($type, $args) {
        $this->type = $type;
        $this->message = array_shift($args);
        $this->args = $args;
    }

    public function toString() {
        return vsprintf($this->message,$this->args);
    }
}