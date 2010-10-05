<?php
/**
 *  Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
    Copyright 2010 Pieter Colpaert (pieter@irail.be - http://bonsansnom.wordpress.com)

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

	source available at http://github.com/Tuinslak/iRail

 
 * @author pieterc
 */

include("Page.php");

class NationalForm extends Page {

    private $page = array(
        "title" => "iRail.be"        
        );

    function __construct() {

        //TODO: generate this in another way according to language...
        include("includes/stationlist.php");
        $this->page["stationarray"] = generate_js_array_2($stations);

        $this->page["date"] = date("D d/m/Y h:i");
        if(isset($_COOKIE["from"]))
            $this->page["autofrom"] = $_COOKIE["from"];
        else
            $this->page["autofrom"] = "";

        if(isset($_COOKIE["to"]))
            $this->page["autoto"] = $_COOKIE["to"];
        else
            $this->page["autoto"] = "";
        
        $this->page["GoogleAnalytics"] = file_get_contents("includes/googleAnalytics.php") ;
        $this->page["footer"] = file_get_contents("includes/footer.php");

    }

    protected function loadContent(){
        foreach($this ->page as $tag => $value){
            $this -> content = str_ireplace("{".$tag."}", $value, $this->content);
        }
    }

}

//__MAIN__

$page = new NationalForm();
if(isset($_COOKIE["language"])){
    $page -> setLanguage($_COOKIE["language"]);
}
$page -> buildPage("FromToForm.tpl");

?>
