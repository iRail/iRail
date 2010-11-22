<?php
/**
 * Description of Station
 *
 * @author pieterc
 */
class Station {
    private $name;
    private $x;
    private $y;
    private $id;
    private $additional;

    function __construct($name, $locationX, $locationY, $id, $additional = null) {
        $this->name = $name;
        $this->x = $locationX;
        $this->y = $locationY;
        $this->id = $id;
        $this->additional = $additional;
    }

    public function getAdditional() {
        return $this->additional;
    }
    
    public function getName() {
        return $this->name;
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
