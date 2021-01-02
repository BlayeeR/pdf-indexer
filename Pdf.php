<?php

require_once 'PdfPage.php';
require_once 'PdfObject.php';
require_once 'XRef.php';
require_once 'global.php';


class Pdf {
    private XRef $XRef;
    private array $objects = [];
    private PdfObject $root;
    private array $pages;

    public function __construct(string $fileName)
    {
        $fileContents = file_get_contents($fileName, FILE_BINARY);
        if($fileContents != "") {
            $this->XRef = new XRef($fileContents);
            if($this->XRef) {
                $table = $this->XRef->getTable();
                while($table) {
                    foreach($table->getSections() as $section) {
                        foreach($section->getObjects() as $object) {
                            if($object->getPosition() == 0) {
                                continue;
                            }
                            $obj = new PdfObject(substr($fileContents, $object->getPosition()), PDF_OBJECT_BLOCK, "", "", $this);
                            if(!array_key_exists($obj->getId(), $this->objects)) { //jesli nie istnieje nowsza rewizja
                                $this->objects[$obj->getId()] = $obj;
                            }
                        }
                    }
                    $table = $table->getPrev();
                }

                if(preg_match("/\s*(\d+)\s*\d+\s*\w/", $this->XRef->getTable()->getTrailer()->getObject("Root"), $match)) {
                    $rootId = intval($match[1]);
                    $this->root = $this->objects[$rootId];
                }
            }
            $this->pages = $this->parsePages();
        }
    }

    public function objectsToString() {
        $arr = [];
        foreach($this->objects as $object) {
            array_push($arr, $object->objectToString());
        }
        return $arr;
    }

    public function getObjects(): array
    {
        return $this->objects;
    }

    public function getObjectById($id): PdfObject {
        return $this->objects[$id];
    }

    public function getObjectsByType($type): array {
        return array_filter($this->getObjects(), function($e) use (&$type) {
            return $e->getType() == $type;
        });
    }

    private function parsePages(): array {
        $pagesContent = "";
        $pages = [];

        foreach($this->getRoot()->getObjectsByType(PDF_OBJECT_DICTIONARY) as $object) {
            $obj = $object->getObjectByName("Pages");
            if($obj) {
                $pagesContent = $obj->getValue();
                break;
            }
        }
        if(preg_match("/\s*(\d+)\s+(\d+)\s(\w+)/", $pagesContent, $matches)) {
            foreach($this->getObjectById($matches[1])->getObjectsByType(PDF_OBJECT_DICTIONARY) as $obj) {
                if($obj && $obj->getObjectByName("Type")->getValue() == "Pages") {
                    $count = intval($obj->getObjectByName("Count")->getValue());
                    for($i = 0; $i < $count * 3; $i+=3) {
                        array_push($pages, new PdfPage($this->getObjectById(intval($obj->getObjectByName("Kids")->getObjects()[0]->getObjects()[$i]->getValue()))));
                    }
                }
            }
        }

        return $pages;
    }

    public function getRoot(): PdfObject
    {
        return $this->root;
    }

    public function getPages(): array
    {
        return $this->pages;
    }
}