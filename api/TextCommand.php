<?php

class TextCommand extends PdfObject
{
    public function __construct($contents, $type = PDF_OBJECT_BLOCK, $name = "", $value = "", $parent = null){
        parent::__construct($contents, $type, $name, $value, $parent);
    }

    public function getValue(): string
    {
        switch($this->getName()){
            case "TJ": {
                $ret = "";
                if(preg_match_all("/\((?P<text>(?s)(.*?)+)\)([-\d.]+)?/m", parent::getValue(), $matches)) {
                    foreach($matches['text'] as $match) {
                        if(strpos($match, "\\r")) {
                            $test = "";
                        }
                        $ret .= strip_tags($match);
                    }
                }
                return $ret;
            }
            case "'":
            case "Tj": {
                $ret = "";
                if(preg_match("/\((?P<text>(?s)(.*?)+)\)/m", parent::getValue(), $matches)) {
                    $ret .= $matches['text'];
                }
                return $ret;
            }
            default:
                return parent::getValue();
        }
    }
}
