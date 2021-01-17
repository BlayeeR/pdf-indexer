<?php

require_once 'FontEncoding.php';

class Font extends PdfObject
{
    private string $fontId;
    private bool $toUnicode;
    private array $table;
    private int $charFrom;
    private int $charTo;
    private FontEncoding $encoding;

    public function __construct(PdfObject $object, string $id)
    {
        $this->content = $object->getContent();
        $this->name = $object->getName();
        $this->type = $object->getType();
        $this->id = $object->getId();
        $this->value = $object->getValue();
        $this->objects = $object->getObjects();
        $this->revision = $object->getRevision();
        $this->parent = $object->getParent();

        $this->fontId = $id;

        foreach($this->getObjectsByType(PDF_OBJECT_DICTIONARY) as $dictionary) {
            $this->toUnicode = !empty($dictionary->getObjectByName("ToUnicode"));

            $encoding = $dictionary->getObjectByName("Encoding");
            if($encoding) {
                if(preg_match("/\s*(\d+)\s+(\d+)\s(\w+)/", $encoding->getValue(), $matches)) {
                    $this->encoding = new FontEncoding($this->getParent()->getObjectById($matches[1]));
                }
            }
        }

        $this->parseTranslationTable();
    }

    public function getFontId(): string
    {
        return $this->fontId;
    }

    private function parseTranslationTable()
    {
        $this->table = [];
        $this->charFrom = 1;
        $this->charTo = 1;

        if ($this->toUnicode) {
            foreach($this->getObjectsByType(PDF_OBJECT_DICTIONARY) as $dictionary) {
                $unicode = $dictionary->getObjectByName("ToUnicode");
                if($unicode) {
                    if(preg_match("/\s*(\d+)\s+(\d+)\s(\w+)/", $unicode->getValue(), $matches)) {
                        $content = $this->getParent()->getObjectById($matches[1])->getContent();
                        break;
                    }
                }
            }

            if (preg_match_all('/begincodespacerange(?s)(.*?)endcodespacerange/', $content, $matches)) {
                foreach ($matches[1] as $section) {
                    if(preg_match_all("/<([0-9A-F]+)> *<([0-9A-F]+)>[ \r\n]+/i", $section, $matches2)) {
                        $this->charFrom = strlen($matches2[1][0]) / 2;
                        $this->charTo = strlen($matches2[2][0]) / 2;
                    }
                }
            }

            if (preg_match_all('/beginbfchar(?s)(.*?)endbfchar/', $content, $bfchars)) {
                foreach ($bfchars[1] as $section) {
                    if(preg_match_all("/<([0-9A-F]+)> +<([0-9A-F]+)>[ \r\n]+/i", $section, $sections)) {
                        $this->charFrom = strlen($sections[1][0]) / 2;

                        for($i = 0; $i < count($sections[1]); $i++) {
                            $parts = preg_split('/([0-9A-F]{4})/i', $sections[2][0], 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                            $text = '';
                            foreach ($parts as $part) {
                                $text .= mb_convert_encoding('&#'.((int) hexdec($part)).';', 'UTF-8', 'HTML-ENTITIES');
                            }
                            $this->table[hexdec($sections[1][0])] = $text;
                        }
                    }
                }
            }

            if (preg_match_all('/beginbfrange(?s)(.*?)endbfrange/', $content, $matches)) {
                foreach ($matches[1] as $section) {
                    if(preg_match_all("/<([0-9A-F]+)> *<([0-9A-F]+)> *\[?([\r\n<>0-9A-F ]+?)\]?[ \r\n]+/i", $section, $matches2)) {
                        for($i = 0; $i < count($matches2[1]); $i++) {
                            $char_from = hexdec($matches2[1][$i]);
                            if(preg_match_all('/<([0-9A-F]+)> */i', $matches2[3][$i], $matches3)) {
                                for($j = 0; $j < count($matches3[1]); $j++) {
                                    $parts = preg_split('/([0-9A-F]{4})/i', $matches3[1][$j], 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                                    $text = '';
                                    foreach ($parts as $part) {
                                        $text .= mb_convert_encoding('&#'.((int) hexdec($part)).';', 'UTF-8', 'HTML-ENTITIES');
                                    }
                                    $this->table[$char_from + $j] = $text;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function translateCharacter(string $char)
    {
        $dec = hexdec(bin2hex($char));
        if (array_key_exists($dec, $this->table)) {
            return $this->table[$dec];
        }
        return false;
    }

    public function translateText(TextCommand $command)
    {
        $text = str_replace(
            ['\\\\', '\(', '\)', '\n', '\r', '\t', '\f', '\ '],
            ['\\', '(', ')', "\n", "\r", "\t", "\f", ' '],
            $command->getValue()
        );

        $text = $this->translateContent($text);

        return $text;
    }

    private function translateContent($text)
    {
        if ($this->toUnicode) {
            $result = '';

            for ($i = 0; $i < strlen($text); $i += 1) {
                $char = substr($text, $i, 1);
                $translatedChar = $this->translateCharacter($char);
                if ($translatedChar !== false) {
                    $char = $translatedChar;
                }
                else {
                    $char = substr($text, $i, 2);
                    $translatedChar = $this->translateCharacter($char);
                    if ($translatedChar !== false) {
                        $char = $translatedChar;
                        $i += 1;
                    }
                    else {
                        $char = "?";
                    }
                }
                $result .= $char;
            }
            $text = $result;
        }
        elseif (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        return $text;
    }
}