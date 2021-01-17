<?php

require_once 'XRefTableSection.php';
require_once 'XRefTrailer.php';

class XRefTable {
    private XRef $parent;
    private ?XRefTable $prev = null;
    private XRefTrailer $trailer;
    private array $sections = [];
    public function __construct(XRef $parent, $content)
    {
        $this->parent = $parent;
        if(preg_match("/xref((?s).*)trailer(\s*<<(?s).*>>)|(\s*\[(?s).*\])|(\s*\((?s).*\))|(\s*true|false)|(\s*\/\w+)|(\s*\d+)?/m", $content, $match)) {
            $this->trailer = new XRefTrailer($match[2]);
            if(preg_match_all("/\s*\d+\s+\d+(\s*\d+\s+\d+\s*(f|n))+/m", $match[1], $subMatches)) {
                foreach($subMatches[0] as $subMatch) {
                    array_push($this->sections, new XRefTableSection($subMatch));
                }
            }
        }

        if(isset($this->trailer)) {
            $prevPos = $this->trailer->getObject("Prev");
            if(is_numeric($prevPos)) {
                $this->prev = new XRefTable($parent, substr($parent, intval($prevPos)));
            }
        }
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getTrailer(): XRefTrailer
    {
        return $this->trailer;
    }

    public function getPrev(): ?XRefTable
    {
        return $this->prev;
    }
}