<?php

namespace PdfIndexer\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity
 * @ORM\Table(name="pages")
 */

class Page
{
    /**
     * @ORM\OneToMany(targetEntity="TextObject", mappedBy="page")
     */
    private $textObjects;

    /**
     * @ORM\ManyToOne(targetEntity="PdfFile", inversedBy="pages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $pdfFile;

    public function __construct() {
        $this->textObjects = new ArrayCollection();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $pageNumber;

    /**
     * @ORM\Column(type="string",length=10000)
     */
    protected string $text;

    /**
     * @ORM\Column(type="string",length=10000)
     */
    protected string $rawText;

    /**
     * @return int
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * @param int $pageNumber
     */
    public function setPageNumber(int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

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
    public function getRawText(): string
    {
        return $this->rawText;
    }

    /**
     * @param string $rawText
     */
    public function setRawText(string $rawText): void
    {
        $this->rawText = $rawText;
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
     * @return Collection|TextObject[]
     */
    public function getTextObjects(): Collection
    {
        return $this->textObjects;
    }
    public function addTextObject(TextObject $textObject): self
    {
        if (!$this->textObjects->contains($textObject)) {
            $this->textObjects[] = $textObject;
            $textObject->setPage($this);
        }
        return $this;
    }
    public function removeTextObject(TextObject $textObject): self
    {
        if ($this->textObjects->contains($textObject)) {
            $this->textObjects->removeElement($textObject);
            // set the owning side to null (unless already changed)
            if ($textObject->getPage() === $this) {
                $textObject->setPage(null);
            }
        }
        return $this;
    }

    public function getPdfFile(): ?PdfFile
    {
        return $this->pdfFile;
    }
    public function setPdfFile(?PdfFile $pdfFile): self
    {
        $this->pdfFile = $pdfFile;
        return $this;
    }
}
