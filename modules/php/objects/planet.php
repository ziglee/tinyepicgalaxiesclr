<?php

class PlanetCard {
    public int $id;
    public string $location;
    public int $location_arg;
    public string $type;
    public int $type_arg;
    public PlanetInfo $info;

    public function __construct($dbCard) {
        $this->id = intval($dbCard['id']);
        $this->location = $dbCard['location'];
        $this->location_arg = intval($dbCard['location_arg']);
        $this->type = $dbCard['type'];
        $this->type_arg = intval($dbCard['type_arg']);
        $this->info = PLANETS_BY_TYPE[$this->type][$this->type_arg];
    }
}

?>
