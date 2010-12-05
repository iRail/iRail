<?php
/**
 * Description of Station
 *
 * @author pieterc
 */
class Station {
    private $names;
    private $x;
    private $y;
    private $id;
    private $additional;

    function __construct($id, $locationX, $locationY, $additional = null) {
        $this->x = $locationX;
        $this->y = $locationY;
        $this->id = $id;
        $this->additional = $additional;
    }

    public function getAdditional() {
        return $this->additional;
    }

    public function addName($lang, $name){
        $this->names[$lang] = ucwords(strtolower($name));
    }
    public function getName($lang = "EN"){
        $lang = strtoupper($lang);
        if(isset($this-> names[$lang])){
            return $this->names[$lang];
        }
        return "";
    }
    public function getNames() {
        return $this->names;
    }

    public function getId(){
        return $this->id;
    }

    public function getX() {
        return $this->x;
    }

    public function getY() {
        return $this->y;
    }

    
}
?>
