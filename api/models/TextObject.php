<?php

namespace PdfIndexer\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="textObjects")
 */
class TextObject
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="textObjects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $page;

    /**
     * @ORM\Column(type="string")
     */
    protected string $text;
    /**
     * @ORM\Column(type="float")
     */
    protected float $x;
    /**
     * @ORM\Column(type="float")
     */
    protected float $y;
    /**
     * @ORM\Column(type="integer")
     */
    protected int $idx;
    /**
     * @ORM\Column(type="integer")
     */
    protected int $rawIdx;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @param float $x
     */
    public function setX(float $x): void
    {
        $this->x = $x;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @param float $y
     */
    public function setY(float $y): void
    {
        $this->y = $y;
    }

    /**
     * @return int
     */
    public function getIdx(): int
    {
        return $this->idx;
    }

    /**
     * @param int $idx
     */
    public function setIdx(int $idx): void
    {
        $this->idx = $idx;
    }

    /**
     * @return int
     */
    public function getRawIdx(): int
    {
        return $this->rawIdx;
    }

    /**
     * @param int $rawIdx
     */
    public function setRawIdx(int $rawIdx): void
    {
        $this->rawIdx = $rawIdx;
    }

    /**
     * @return ?Page
     */
    public function getPage(): ?Page
    {
        return $this->page;
    }

    /**
     * @param mixed $page
     */
    public function setPage(?Page $page): self
    {
        $this->page = $page;
        return $this;
    }


}