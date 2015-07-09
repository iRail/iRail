<?php
/* Copyright (C) 2010, 2011 by iRail vzw/asbl */
/*
  This file is part of iRail.

  iRail is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  iRail is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with iRail.  If not, see <http://www.gnu.org/licenses/>.

  http://project.irail.be - http://irail.be

  Source available at http://github.com/Tuinslak/iRail
 */


/**
 * This is the API request handler
 */
include_once("APICall.php");
date_default_timezone_set("Europe/Brussels");

class ConnectionsCall extends APICall {
    protected function logRequest() {
          $r = $this->request;
	       parent::writeLog($_SERVER['HTTP_USER_AGENT'], $r->getFrom(), $r->getTo(), "none (connections)", $_SERVER['REMOTE_ADDR']);
    }
}

$call = new ConnectionsCall("connections");
$call->executeCall();
?>
