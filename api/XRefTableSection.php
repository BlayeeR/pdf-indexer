<?php

require_once 'XRefObject.php';

class XRefTableSection {
    private int $objectCount = 0;
    private int $objectFirstID = 0;
    private array $objects = [];

    public function __construct(string $content)
    {
        if(preg_match("/(\d+)\s+(\d+)(?s)(.*)/m", $content, $match)) {
            $this->objectCount = $match[2];
            $this->objectFirstID = $match[1];
            if(preg_match_all("/\s*(\d+)\s*(\d+)\s*(f|n)/m", $match[3], $matches)) {
                for($i = 0; $i < count($matches[0]);$i++) {
                    array_push($this->objects, new XRefObject(intval($matches[1][$i]), intval($matches[2][$i]), $matches[3][$i]=="f"?false:true));
                }
            }
        }
    }

    public function getObjects(): array
    {
        return $this->objects;
    }

    public function getObjectFirstID(): int
    {
        return $this->objectFirstID;
    }

    public function getObjectCount(): int
    {
        return $this->objectCount;
    }
}