<?php

require_once 'TextCommand.php';

class PdfObject {
    protected string $content;
    protected int $id;
    protected int $type;
    protected int $revision;
    protected array $objects = [];
    protected string $name;
    protected string $value;
    protected Pdf|null $parent;
    public function __construct(string $contents, int $type = PDF_OBJECT_BLOCK, string $name = "", string $value = "", Pdf|null $parent = null)
    {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
        $this->parent = $parent;
        if($this->type == PDF_OBJECT_BLOCK) {
            if(preg_match("/\s*(\d+)\s*(\d+)\s*obj(?s)(.*?)/m", $contents, $match, PREG_OFFSET_CAPTURE)) {
                //echo json_encode($match);
                //echo "\r\n\r\n";
                $this->id = $match[1][0];
                $this->revision = $match[2][0];
                $this->content = substr(explode("endobj", $contents)[0], $match[3][1]);
                $this->parseObjects();
            }
        }
        else {
            $this->content = $contents;
            if($this->type == PDF_OBJECT_NUMERIC) {
                $this->value = $this->content;
            }
            $this->parseObjects();
        }
    }

    public function getObjectsByType($type): array {
        return array_filter($this->getObjects(), function($e) use (&$type) {
            return $e->getType() == $type;
        });
    }

    public function getObjectByName($name): PdfObject|null {
        $ret = array_filter($this->getObjects(), function($e) use (&$name) {
            return $e->getName() == $name;
        });
        $key = array_key_first($ret);
        if(!is_null($key)) {
            return $ret[$key];
        }
        return null;
    }

    public function objectToString(): string {
        $arr = [];
        $arr['type'] = $this->typeStr();
        if($this->getType() == PDF_OBJECT_NAME || $this->getType() == PDF_OBJECT_TEXT_COMMAND) {
            $arr['name'] = $this->getName();
        }
        if($this->getType() == PDF_OBJECT_NAME || $this->getType() == PDF_OBJECT_NUMERIC || $this->getType() == PDF_OBJECT_STRING || $this->getType() == PDF_OBJECT_BOOLEAN || $this->getType() == PDF_OBJECT_TEXT_COMMAND  || $this->getType() == PDF_OBJECT_CHAR) {
            $arr['value'] = $this->getValue();
        }
        if($this->getType() == PDF_OBJECT_BLOCK) {
            $arr['id'] = $this->getId();
            $arr['revision'] = $this->getRevision();
        }
        //$arr['content'] = json_encode($this->getContent());
        $objects = [];
        foreach($this->objects as $object) {
            array_push($objects, $object->objectToString());
        }
        if(count($objects) > 0) {
            $arr['objects'] = $objects;
        }

        return $arr;
    }

    public function typeStr(): string {
        switch ($this->getType()) {
            case PDF_OBJECT_BLOCK:
                return "block";
            case PDF_OBJECT_BOOLEAN:
                return "bool";
            case PDF_OBJECT_STRING:
                return "string";
            case PDF_OBJECT_NAME:
                return "name";
            case PDF_OBJECT_ARRAY:
                return "array";
            case PDF_OBJECT_DICTIONARY:
                return "dictionary";
            case PDF_OBJECT_NUMERIC:
                return "numeric";
            case PDF_OBJECT_STREAM:
                return "stream";
            case PDF_OBJECT_TEXT_BLOCK:
                return "text block";
            case  PDF_OBJECT_TEXT_COMMAND:
                return "text command";
            case PDF_OBJECT_CHAR:
                return "char";
            default:
                return "null";
        }
    }

    public function parseObjects(): void {
        switch ($this->type) {
            case PDF_OBJECT_BLOCK:
                $objects = self::getObjectsContent($this->getContent(), $this->id);
                foreach($objects as $match) {
                    $type = PdfObject::getObjectType($match);
                    $obj = new PdfObject($match, $type);
                    array_push($this->objects, $obj);
                }
                break;
            case PDF_OBJECT_DICTIONARY:
                if(preg_match_all("/(\/[\w\+\-,]+)\s*(<<[\S\s]*>>)?|(\[[\S\s]*?\])?|(\([\S\s]*?\))?|([\w\s]+)?/m", $this->getStrippedContent(), $matches, PREG_UNMATCHED_AS_NULL)) {
                    $flat = [];
                    for($i = 0; $i < count($matches[0]); $i++) {//18
                        for($j = 1; $j < count($matches); $j++) {//5
                            if($matches[$j][$i] != null && trim($matches[$j][$i]) != "") {
                                array_push($flat, $matches[$j][$i]);
                            }
                        }
                    }

                    for($i = 0; $i < count($flat); $i+=2) {
                        $type = self::getObjectType($flat[$i]);
                        $contentType = self::getObjectType($flat[$i+1]);
                        $obj = new PdfObject($flat[$i+1], $type, self::stripContent($flat[$i], $type), self::stripContent($flat[$i+1], $contentType));
                        array_push($this->objects, $obj);
                    }
                }
                break;
            case PDF_OBJECT_NAME:
                $type = self::getObjectType($this->getContent());
                switch($type) {
                    case PDF_OBJECT_DICTIONARY:
                    case PDF_OBJECT_STRING:
                    case PDF_OBJECT_ARRAY:
                        $obj = new PdfObject($this->getContent(), $type);
                        array_push($this->objects, $obj);
                        break;
                }
                break;
            case PDF_OBJECT_ARRAY:
                if(preg_match_all("/([\/\w.]+)/m",$this->getStrippedContent(), $matches )) {
                    foreach($matches[1] as $match) {
                        $type = self::getObjectType($match);
                        $obj = new PdfObject($match, $type, "", self::stripContent($match, $type));
                        array_push($this->objects, $obj);
                    }
                }
                break;
            case PDF_OBJECT_STREAM:
                if(preg_match_all("/BT(?s)(.*?)ET/m",$this->getStrippedContent(), $matches )) {
                    foreach($matches[1] as $match) {
                        $obj = new PdfObject($match, PDF_OBJECT_TEXT_BLOCK);
                        array_push($this->objects, $obj);
                    }
                }
                break;
            case PDF_OBJECT_TEXT_BLOCK:
                if(preg_match_all("/^(?s)(.*?)(Tc|Td|TD|Tf|'|Tj|TJ|TL|Tm|Ts|Tw|Tz|T\*|Da|Do|rg|RG|re|co|cs|gs|en|sc|SC|g|G|V|vo|Vo)$/m",$this->getStrippedContent(), $matches )) {
                    for($i = 0; $i < count($matches[0]); $i++) {
                        $obj = new TextCommand($matches[0][$i], PDF_OBJECT_TEXT_COMMAND, $matches[2][$i], $matches[1][$i]);
                        array_push($this->objects, $obj);
                    }
                }
                break;
        }
    }

    public static function getObjectsContent(string $content): array {
        $output = [];
        if(self::getObjectType($content) != PDF_OBJECT_STREAM && strpos($content, "<<") != false) {
            $positionCounter = strpos($content, "<<") + 2;
            $openBr = 1;

            while($openBr > 0 && $positionCounter < strlen($content)-1) {
                if($content[$positionCounter] == '<' && $content[$positionCounter +1] == '<') {
                    $openBr += 1;
                }
                else if($content[$positionCounter] == '>' && $content[$positionCounter +1] == '>') {
                    $openBr -= 1;
                }
                $positionCounter += 1;
            }

            $strBefore = substr($content, 0, strpos($content, "<<"));
            if(trim($strBefore) != "") {
                foreach(self::getObjectsContent($strBefore) as $obj) {
                    array_push($output, $obj);
                }
            }

            array_push($output, substr($content, strpos($content, "<<"), $positionCounter+1));

            $strAfter = substr($content, $positionCounter+1);


            if(trim($strAfter) != "") {
                foreach(self::getObjectsContent($strAfter) as $obj) {
                    array_push($output, $obj);
                }
            }
        }
        else {
            if(preg_match_all("/(<<[\S\s]*?>>)|(\[[\S\s]*\])|(\([\S\s]*?\))|(true?|false?)|([\d.]+)|(\/\w+?)|(stream(?s).*endstream)/m", $content, $matches, PREG_UNMATCHED_AS_NULL)) {
                for ($i = 1; $i < count($matches); $i++) {
                    $match = $matches[$i];
                    foreach ($match as $subMatch) {
                        if ($subMatch) {
                            array_push($output, $subMatch);
                        }
                    }
                }
            }
        }
        return $output;
    }

    public static function stripContent(string $content, int $type): string {
        $pattern = "";
        switch ($type) {
            case PDF_OBJECT_DICTIONARY:
                $pattern = "/(?<=<<)([\S\s]*)(?=>>)/m";
                break;
            case PDF_OBJECT_BLOCK:
                $pattern = "/(?<=obj)([\S\s]*)(?=endobj)/m";
                break;
            case PDF_OBJECT_STREAM:
                $pattern = "/(?<=stream)([\S\s]*)(?=endstream)/m";
                break;
            case PDF_OBJECT_NAME:
                $pattern = "/(?<=\/)([\S\s]*)/m";
                break;
            case PDF_OBJECT_ARRAY:
                $pattern = "/(?<=\[)([\S\s]*)(?=\])/m";
                break;
            case PDF_OBJECT_STRING:
                $pattern = "/(?<=\()([\S\s]*)(?=\))/m";
                break;
            case PDF_OBJECT_TEXT_BLOCK:
                $pattern = "/(?<=BT)(?s)(.*?)(?=ET)/m";
                break;
            case PDF_OBJECT_TEXT_COMMAND:
                $pattern = "/(?s)(.*?)(Tc|Td|TD|Tf|'|Tj|TJ|TL|Tm|Ts|Tw|Tz|T\*|Da|Do|rg|RG|re|co|cs|gs|en|sc|SC|g|G|V|vo|Vo)/";
                break;
        }
        if($pattern != "") {
            if(preg_match($pattern, $content, $match)){
                return $match[1];
            }
        }
        return $content;
    }

    public static function getObjectType(string $content): int {
        $str = trim($content);
        if($str != "") {
            switch ($str[0]) {
                case '[':
                    return PDF_OBJECT_ARRAY;
                case '(':
                    return PDF_OBJECT_STRING;
                case '/':
                    return PDF_OBJECT_NAME;
                default:
                    if(str_starts_with($str, "<<")) {
                        return PDF_OBJECT_DICTIONARY;
                    }
                    if(is_numeric($str)) {
                        return PDF_OBJECT_NUMERIC;
                    }
                    if($str == "true" || $str == "false") {
                        return PDF_OBJECT_BOOLEAN;
                    }
                    if(str_starts_with($str, "stream")) {
                        return PDF_OBJECT_STREAM;
                    }
                    if(strlen($str) == 1) {
                        return PDF_OBJECT_CHAR;
                    }
                    return PDF_OBJECT_NULL;
            }
        }
        else {
            return PDF_OBJECT_NULL;
        }
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStrippedContent(): string
    {
        return self::stripContent($this->content, self::getObjectType($this->getContent()));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function getObjects(): array
    {
        return $this->objects;
    }

    public function getParent(): Pdf
    {
        return $this->parent;
    }
}