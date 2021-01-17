<?php

class XRefTrailer {
    private array $objects = [];

    public function __construct(string $content)
    {
        if(preg_match_all("/\s?\/(\w+)\s*([\w \[\]<>]+)/m", $content, $matches)) {
            for($i = 1; $i < count($matches[0]); $i++) {
                $this->objects[$matches[1][$i]] = $matches[2][$i];
            }
        }
    }

    public function getObjects(): array
    {
        return $this->objects;
    }

    public function getObject($key): string
    {
        if(array_key_exists($key, $this->objects)) {
            return $this->objects[$key];
        }
        return "";
    }
}
