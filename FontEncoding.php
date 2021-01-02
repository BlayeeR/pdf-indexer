<?php

class FontEncoding extends PdfObject
{
    private string $base;

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

        foreach($this->getObjectsByType(PDF_OBJECT_DICTIONARY) as $dictionary) {
            $this->base = !empty($dictionary->getObjectByName("BaseEncoding"))?$dictionary->getObjectByName("BaseEncoding")->getValue():'';

            if(!empty($this->base)) {
                break;
            }
        }
    }

    public function getBase(): string
    {
        return $this->base;
    }
}