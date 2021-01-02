<?php

class XRefObject {

    private int $position = 0;
    private int $revision = 0;
    private bool $inUse = false;

    public function __construct(int $position, int $revision, bool $inUse)
    {
        $this->position = $position;
        $this->revision = $revision;
        $this->inUse = $inUse;
    }

    public function isInUse(): bool
    {
        return $this->inUse;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}