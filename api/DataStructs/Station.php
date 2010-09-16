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

    function __construct($name, $locationX, $locationY) {
        $this->name = $name;
        $this->x = $locationX;
        $this->y = $locationY;
    }

    public function getName() {
        return $this->name;
    }

    public function getX() {
        return $this->x;
    }

    public function getY() {
        return $this->y;
    }

    
}
?>
