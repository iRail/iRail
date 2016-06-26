<?php

namespace IRail;

use Tac\Tac;

class JsonLog
{
  protected $tac;

  public function __construct($filename) {
    $this->tac = new Tac($filename);
  }

  public function getLastEntries($n) {
    return array_map("json_decode", $this->tac->tail($n));
  }

}

?>
