<?php

require_once 'XRefTable.php';

class XRef {

    private XRefTable $table;

    public function __construct(string $contents)
    {
        $XRefPos = $this->getXRefTablePos($contents);
        if($XRefPos > -1) {
            $this->table = new XRefTable($this, substr($contents, $XRefPos));
        }
    }

    function getXRefTablePos($contents): int {
        $xrefPos = -1;
        if(preg_match("/startxref\s*(\d*)/m", $contents, $matches)) {
            $xrefPos = $matches[1];
        }
        return intval($xrefPos);
    }

    public function getTable(): XRefTable
    {
        return $this->table;
    }
}