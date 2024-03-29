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
 * This is the API request handler.
 */

use Irail\api\APIPost;

include_once '../../../vendor/autoload.php';
include_once '../APIPost.php';
date_default_timezone_set('Europe/Brussels');

/*
 * Required:
 * - connection
 * - occupancy
 * Optional:
 * - to
 */
$postdata = file_get_contents("php://input");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Request-Method: POST, OPTIONS');
    header('Access-Control-Request-Headers: Content-Type');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Allow: POST, OPTIONS');
} else {
    $post = new APIPost('occupancy', $postdata, $_SERVER['REQUEST_METHOD']);
    $post->writeToMongo($_SERVER['REMOTE_ADDR']);
}
