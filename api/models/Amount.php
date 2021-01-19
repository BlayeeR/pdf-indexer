<?php

namespace PdfIndexer\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(name="amounts")
 */
class Amount
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(type="float")
     */
    protected float $gross;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $vat;

    /**
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * @ORM\ManyToOne(targetEntity="PdfFile", inversedBy="amounts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $pdfFile;

    public function getPdfFile(): ?PdfFile
    {
        return $this->pdfFile;
    }
    public function setPdfFile(?PdfFile $pdfFile): self
    {
        $this->pdfFile = $pdfFile;
        return $this;
    }

    /**
     * @return float
     */
    public function getGross(): float
    {
        return $this->gross;
    }

    /**
     * @param float $gross
     */
    public function setGross(float $gross): void
    {
        $this->gross = $gross;
    }

    /**
     * @return int
     */
    public function getVat(): int
    {
        return $this->vat;
    }

    /**
     * @param int $vat
     */
    public function setVat(int $vat): void
    {
        $this->vat = $vat;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}