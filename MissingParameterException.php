<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MissingParameterException
 *
 * @author barbarka
 */
class MissingParameterException extends Exception{
    public function __construct($name) {
        parent::__construct("Missing parameter: $name");
    }
}

?>
