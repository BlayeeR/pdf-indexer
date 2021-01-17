<?php

require_once 'PdfObject.php';
require_once 'PdfPageContent.php';
require_once 'Font.php';

class PdfPage extends PdfObject {
    private PdfPageContent $pageContents;
    private array $fonts;

    public function __construct(PdfObject $object)
    {
        $this->content = $object->getContent();
        $this->name = $object->getName();
        $this->type = $object->getType();
        $this->id = $object->getId();
        $this->value = $object->getValue();
        $this->objects = $object->getObjects();
        $this->revision = $object->getRevision();
        $this->parent = $object->getParent();

        $this->fonts = $this->parseFonts();
        $this->pageContents = $this->parsePageContents();
    }

    private function parsePageContents(): PdfPageContent {
        $pagesContent = "";

        foreach($this->getObjectsByType(PDF_OBJECT_DICTIONARY) as $object) {
            $obj = $object->getObjectByName("Contents");
            if($obj) {
                $pagesContent = $obj->getValue();
                break;
            }
        }

        if(preg_match("/\s*(\d+)\s+(\d+)\s(\w+)/", $pagesContent, $matches)) {
            return new PdfPageContent($this->parent->getObjectById($matches[1]), $this);
        }
    }

    public function getFont($name): Font|null {
        $ret = array_filter($this->fonts, function($e) use (&$name) {
            return $e->getFontId() == $name;
        });
        $key = array_key_first($ret);
        if(!is_null($key)) {
            return $ret[$key];
        }
        return null;
    }

    public function getPageContents(): PdfPageContent
    {
        return $this->pageContents;
    }

    private function parseFonts(): array {
        $fontsContent = "";
        $fonts = [];

        foreach($this->getObjectsByType(PDF_OBJECT_DICTIONARY) as $dictionary) {
            $resources = $dictionary->getObjectByName("Resources");
            if($resources) {
                foreach($resources->getObjectsByType(PDF_OBJECT_DICTIONARY) as $font) {
                    if($font && $font->getObjectByName("Font")) {
                        $fontsContent = $font->getObjectByName("Font")->getValue();
                        break;
                    }
                }
                if($fontsContent != "") {
                    break;
                }
            }
        }

        if(preg_match("/\s*(\d+)\s+(\d+)\s(\w+)/", $fontsContent, $matches)) {
            foreach($this->getParent()->getObjectById($matches[1])->getObjectsByType(PDF_OBJECT_DICTIONARY) as $dictionary) {
                foreach($dictionary->getObjects() as $object) {
                    if(preg_match("/\s*(\d+)\s+(\d+)\s(\w+)/", $object->getValue(), $matches)) {
                        array_push($fonts, new Font($this->getParent()->getObjectById($matches[1]), $object->getName()));
                    }
                }
            }
        }

        return $fonts;
    }

    public function getFonts(): array
    {
        return $this->fonts;
    }
}