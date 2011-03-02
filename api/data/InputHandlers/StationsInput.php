<?php
  /**
   * This class has to be a provider for all Stations available
   *
   * @author pieterc
   */
ini_set("include_path", ".:DataStructs:api/:../includes");
include_once("Input.php");
include_once("DataStructs/Station.php");

class StationsInput extends Input {

     public function fetchData(Request $request) {
	  $stations = array();
	  parent::connectToDB();
	  try {
	       $query = "SELECT `stations`.`ID`,`railtime`.`RT`, `stations`.`X`, `stations`.`Y`, `stations`.`STD`,`stations`.`NL`, `stations`.`FR`, `stations`.`EN`, `stations`.`DE`, `stations`.`ES` FROM stations LEFT JOIN railtime ON `stations`.`ID` = `railtime`.`ID`";
	       $result = mysql_query($query) or die("Could not get stationslist from DB");
	       while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		    $stations[$line["ID"]] = new Station($line["ID"], $line["X"], $line["Y"],$line["RT"] );
		    foreach(parent::AVAILABLE_LANG as $lang){
			 $stations[$line["ID"]] -> addName($lang, $line[$lang]);
		    }
	       }
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 3);
	  }
	  return $stations;
     }

     public function transformData($serverData) {
	  return $serverData;
     }

}
?>
