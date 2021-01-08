<?php

require_once 'global.php';

class PdfPageContent extends PdfObject {

    private PdfPage $page;
    private int $searchRadius = 15;
    private array $rawTextArray;
    private array $textArray;
    private string $rawText;
    private string $text;

    public function __construct(PdfObject $object, PdfPage $page)
    {
        $this->content = $object->getContent();
        $this->name = $object->getName();
        $this->type = $object->getType();
        $this->id = $object->getId();
        $this->value = $object->getValue();
        $this->objects = $object->getObjects();
        $this->revision = $object->getRevision();
        $this->parent = $object->getParent();

        $this->page = $page;

        $this->rawText = $this->parseRawText();
        $this->text = $this->parseText();
        $this->rawTextArray = $this->parseRawTextArray();
        $this->textArray = $this->parseTextArray();
    }

    public function getTextCommands(): array {
        $commands = [];

        foreach($this->getObjectsByType(PDF_OBJECT_STREAM) as $stream) {
            foreach($stream->getObjectsByType(PDF_OBJECT_TEXT_BLOCK) as $block) {
                foreach($block->getObjectsByType(PDF_OBJECT_TEXT_COMMAND) as $command) {
                    array_push($commands, $command);
                }
            }
        }

        return $commands;
    }

    public function parseText(): string {
        $text = "";

        foreach($this->getObjectsByType(PDF_OBJECT_STREAM) as $stream) {
            foreach ($stream->getObjectsByType(PDF_OBJECT_TEXT_BLOCK) as $block) {
                $matrix = null;
                foreach ($block->getObjectsByType(PDF_OBJECT_TEXT_COMMAND) as $command) {

                    switch ($command->getName()) {
                        case 'Td':
                            $args = preg_split('/\s/s', $command->getValue());
                            $x = (float)array_shift($args);
                            $y = (float)array_shift($args);

                            $oldXY = $this->getXY($matrix);

                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [$x, $y, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [$x, $y, 1]]))->multiply($matrix);
                            }

                            $newXY = $this->getXY($matrix);

                            if ($newXY['x'] <= 0 || $newXY['y'] < $oldXY['y']) {
                                $text .= "\n";
                            }
//                            elseif ($newXY['x'] > $oldXY['x'] && $newXY['y'] == $oldXY['y']) {
//                                $text .= ' ';
//                            }
                            break;
                        case 'TD':
                            $args = preg_split('/\s/s', $command->getValue());
                            $x = array_shift($args);
                            $y = array_shift($args);

                            $tl = -(float)$y;
                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]))->multiply($matrix);
                            }

                            $matrix = (new Matrix\Matrix([
                                [1, 0, 0],
                                [0, 1, 0],
                                [$x, $y, 1]]))->multiply($matrix);

                            $newXY = $this->getXY($matrix);

                            if ($newXY['y'] < 0) {
                                $text .= "\n";
                            } elseif ($newXY['x'] <= 0) {
                                $text .= ' ';
                            }
                            break;
                        case 'Tf':
                            if (preg_match("/(\w+)\s+(\d+)/", $command->getValue(), $match)) {
                                $current_font = $this->page->getFont($match[1]);
                            }
                            break;
                        case "'":
                        case 'Tj':
                        case 'TJ':
                            $text .= $current_font->translateText($command);
                            break;
                        case 'Tm':
                            $args = preg_split('/\s/s', rtrim($command->getValue()));
                            $f = array_pop($args);
                            $e = array_pop($args);
                            $d = array_pop($args);
                            $c = array_pop($args);
                            $b = array_pop($args);
                            $a = array_pop($args);

                            $oldXY = $this->getXY($matrix);
                            $matrix = new Matrix\Matrix([
                                [$a, $b, 0],
                                [$c, $d, 0],
                                [$e, $f, 1]]);
                            $newXY = $this->getXY($matrix);

                            if (abs($newXY['x'] - $oldXY['x']) > 10) {
                                $text .= "\t";
                            }
                            if (abs($newXY['y']-$oldXY['y']) > 10) {
                                $text .= "\n";
                            }
                            break;
                        case 'TL':
                            $args = preg_split('/\s/s', $command->getValue());
                            $tl = (float)array_shift($args);

                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]))->multiply($matrix);
                            }

                            $text .= ' ';
                            break;
                        case 'Tz':
                        case 'T*':
                            $text .= "\n";
                            break;

                    }
                }
            }
        }
        return $text;
    }

    private function parseRawText() {
        $text = "";

        foreach($this->getObjectsByType(PDF_OBJECT_STREAM) as $stream) {
            foreach ($stream->getObjectsByType(PDF_OBJECT_TEXT_BLOCK) as $block) {
                foreach ($block->getObjectsByType(PDF_OBJECT_TEXT_COMMAND) as $command) {
                    switch ($command->getName()) {
                        case 'Tf':
                            if (preg_match("/(\w+)\s+(\d+)/", $command->getValue(), $match)) {
                                $current_font = $this->page->getFont($match[1]);
                            }
                            break;
                        case "'":
                        case 'Tj':
                        case 'TJ':
                            $text .= $current_font->translateText($command);
                            break;
                    }
                }
            }
        }

        return $text;
    }

    private function getXY(\Matrix\Matrix|null $matrix): array {

        if(isset($matrix)) {
            $m = (new Matrix\Matrix([[0, 0, 1]]))->multiply($matrix);
            $x = (float)$m->getValue(1, 1);
            $y = (float)$m ->getValue(1, 2);
            return ['x'=>$x, 'y'=>$y];
        }
        else {
            return ['x'=>0,'y'=>0];
        }
    }

    private function parseRawTextArray() {
        $text = array();
        $textString = "";

        foreach($this->getObjectsByType(PDF_OBJECT_STREAM) as $stream) {
            foreach ($stream->getObjectsByType(PDF_OBJECT_TEXT_BLOCK) as $block) {
                $matrix = null;
                foreach ($block->getObjectsByType(PDF_OBJECT_TEXT_COMMAND) as $command) {

                    switch ($command->getName()) {
                        case 'TL':
                            $args = preg_split('/\s/s', $command->getValue());
                            $tl = (float)array_shift($args);

                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]))->multiply($matrix);
                            }
                            break;
                        case 'Td':
                            $args = preg_split('/\s/s', $command->getValue());
                            $x = array_shift($args);
                            $y = array_shift($args);

                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [$x, $y, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [$x, $y, 1]]))->multiply($matrix);
                            }
                            break;
                        case 'TD':
                            $args = preg_split('/\s/s', $command->getValue());
                            $x = array_shift($args);
                            $y = array_shift($args);

                            $tl = -(float)$y;
                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]))->multiply($matrix);
                            }

                            $matrix = (new Matrix\Matrix([
                                [1, 0, 0],
                                [0, 1, 0],
                                [$x, $y, 1]]))->multiply($matrix);
                            break;

                        case 'Tf':
                            if (preg_match("/(\w+)\s+(\d+)/", $command->getValue(), $match)) {
                                $current_font = $this->page->getFont($match[1]);
                            }
                            break;

                        case "'":
                        case 'Tj':
                        case 'TJ':
                            $subText = $current_font->translateText($command);

                            $xy = $this->getXY($matrix);

                            $count = count($text);
                            $text[$count] = [
                                'text' => $subText,
                                'x' => $xy['x'],
                                'y' => $xy['y'],
                                'index' => strlen($textString) == 0 ? 0 : strlen($textString) - 1
                            ];
                            $textString .= $subText;
                            break;
                        case 'Tm':
                            $args = preg_split('/\s/s', rtrim($command->getValue()));
                            $f = array_pop($args);
                            $e = array_pop($args);
                            $d = array_pop($args);
                            $c = array_pop($args);
                            $b = array_pop($args);
                            $a = array_pop($args);

                            $matrix = new Matrix\Matrix([
                                [$a, $b, 0],
                                [$c, $d, 0],
                                [$e, $f, 1]]);
                            break;
                    }
                }
            }
        }

        return $text;
    }

    public function parseTextArray(): array {
        $text = array();
        $textString = "";

        foreach($this->getObjectsByType(PDF_OBJECT_STREAM) as $stream) {
            foreach ($stream->getObjectsByType(PDF_OBJECT_TEXT_BLOCK) as $block) {
                $matrix = null;
                foreach ($block->getObjectsByType(PDF_OBJECT_TEXT_COMMAND) as $command) {

                    switch ($command->getName()) {
                        case 'Td':
                            $args = preg_split('/\s/s', $command->getValue());
                            $x = (float)array_shift($args);
                            $y = (float)array_shift($args);

                            $oldXY = $this->getXY($matrix);

                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [$x, $y, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [$x, $y, 1]]))->multiply($matrix);
                            }

                            $newXY = $this->getXY($matrix);

                            if ($newXY['x'] <= 0 || $newXY['y'] < $oldXY['y']) {
                                $textString .= "\n";
                            }
                            break;
                        case 'TD':
                            $args = preg_split('/\s/s', $command->getValue());
                            $x = array_shift($args);
                            $y = array_shift($args);

                            $tl = -(float)$y;
                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]))->multiply($matrix);
                            }

                            $matrix = (new Matrix\Matrix([
                                [1, 0, 0],
                                [0, 1, 0],
                                [$x, $y, 1]]))->multiply($matrix);

                            $newXY = $this->getXY($matrix);

                            if ($newXY['y'] < 0) {
                                $textString .= "\n";
                            } elseif ($newXY['x'] <= 0) {
                                $textString .= ' ';
                            }
                            break;
                        case 'Tf':
                            if (preg_match("/(\w+)\s+(\d+)/", $command->getValue(), $match)) {
                                $current_font = $this->page->getFont($match[1]);
                            }
                            break;
                        case "'":
                        case 'Tj':
                        case 'TJ':
                            $subText = $current_font->translateText($command);
                            $xy = $this->getXY($matrix);

                            $count = count($text);
                            $text[$count] = [
                                'text' => $subText,
                                'x' => $xy['x'],
                                'y' => $xy['y'],
                                'index' => strlen($textString) == 0 ? 0 : strlen($textString) - 1
                            ];
                            $textString .= $subText;

                            break;
                        case 'Tm':
                            $args = preg_split('/\s/s', rtrim($command->getValue()));
                            $f = array_pop($args);
                            $e = array_pop($args);
                            $d = array_pop($args);
                            $c = array_pop($args);
                            $b = array_pop($args);
                            $a = array_pop($args);

                            $oldXY = $this->getXY($matrix);
                            $matrix = new Matrix\Matrix([
                                [$a, $b, 0],
                                [$c, $d, 0],
                                [$e, $f, 1]]);
                            $newXY = $this->getXY($matrix);

                            if (abs($newXY['x'] - $oldXY['x']) > 10) {
                                $textString .= "\t";
                            }
                            if (abs($newXY['y']-$oldXY['y']) > 10) {
                                $textString .= "\n";
                            }
                            break;
                        case 'TL':
                            $args = preg_split('/\s/s', $command->getValue());
                            $tl = (float)array_shift($args);

                            if(!$matrix) {
                                $matrix = new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]);
                            }
                            else {
                                $matrix = (new Matrix\Matrix([
                                    [1, 0, 0],
                                    [0, 1, 0],
                                    [0, -$tl, 1]]))->multiply($matrix);
                            }

                            $textString .= ' ';
                            break;
                        case 'Tz':
                        case 'T*':
                            $textString .= "\n";
                            break;

                    }
                }
            }
        }
        return $text;
    }

    public function getRawTextArray(): array
    {
        return $this->rawTextArray;
    }

    public function getRawText(): string
    {
        return $this->rawText;
    }

    public function getNearbyRawTexts($x, $y): array {
        $ret = [];

        foreach($this->rawTextArray as $text) {
            if(($text['x'] >= $x-$this->searchRadius) && $text['y'] >= $y-$this->searchRadius && $text['y'] <= $y +$this->searchRadius) {
                array_push($ret, $text);
            }
        }

        return $ret;
    }

    public function getNearbyTexts($x, $y): array {
        $ret = [];

        foreach($this->textArray as $text) {
            if(($text['x'] >= $x-$this->searchRadius) && $text['y'] >= $y-$this->searchRadius && $text['y'] <= $y +$this->searchRadius) {
                $ret[$text['index']] = $text;
            }
        }

        return $ret;
    }

    public function getNearbyYCoordinateRawTexts($y): array {
        $ret = [];

        foreach($this->rawTextArray as $text) {
            if($text['y'] >= $y-$this->searchRadius && $text['y'] <= $y +$this->searchRadius) {
                array_push($ret, $text);
            }
        }

        return $ret;
    }

    public function getNearbyYCoordinateTexts($y): array {
        $ret = [];

        foreach($this->textArray as $text) {
            if($text['y'] >= $y-$this->searchRadius && $text['y'] <= $y +$this->searchRadius) {
                array_push($ret, $text);
            }
        }

        return $ret;
    }

    public function getTextValueFromRawIndex($index) {
        for($i = count($this->rawTextArray) - 1; $i >= 0; $i--) {
            $text = $this->rawTextArray[$i];
            if($index >= $text['index']) {
                return $text;
            }
        }

        return null;
    }

    public function getTextValueFromIndex($index) {
        for($i = count($this->textArray) - 1; $i >= 0; $i--) {
            $text = $this->textArray[$i];
            if($index >= $text['index']) {
                return $text;
            }
        }

        return null;
    }

    /**
     * @param int $searchRadius
     */
    public function setSearchRadius(int $searchRadius): void
    {
        $this->searchRadius = $searchRadius;
    }

    /**
     * @return array|string
     */
    public function getTextArray()
    {
        return $this->textArray;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }
}

