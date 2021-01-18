<?php

namespace PdfIndexer\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(name="files")
 */
class PdfFile
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected string $name;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected string $title;

    /**
     * @ORM\OneToMany(targetEntity="Page", mappedBy="pdfFile", orphanRemoval=true)
     */
    private $pages;

    /**
     * @ORM\OneToMany(targetEntity="Date", mappedBy="pdfFile", orphanRemoval=true)
     */
    private $dates;

    /**
     * @ORM\OneToMany(targetEntity="Info", mappedBy="pdfFile", orphanRemoval=true)
     */
    private $infos;

    /**
     * @ORM\OneToMany(targetEntity="Amount", mappedBy="pdfFile", orphanRemoval=true)
     */
    private $amounts;

    public function __construct() {
        $this->pages = new ArrayCollection();
        $this->dates = new ArrayCollection();
        $this->amounts = new ArrayCollection();
        $this->infos = new ArrayCollection();
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

    /**
     * @return Collection|Page[]
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }
    public function addPage(Page $page): self
    {
        if (!$this->pages->contains($page)) {
            $this->pages[] = $page;
            $page->setPdfFile($this);
        }
        return $this;
    }
    public function removePage(Page $page): self
    {
        if ($this->pages->contains($page)) {
            $this->pages->removeElement($page);
            // set the owning side to null (unless already changed)
            if ($page->getPdfFile() === $this) {
                $page->setPdfFile(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Date[]
     */
    public function getDates(): Collection
    {
        return $this->dates;
    }
    public function addDate(Date $date): self
    {
        if (!$this->dates->contains($date)) {
            $this->dates[] = $date;
            $date->setPdfFile($this);
        }
        return $this;
    }
    public function removeDate(Date $date): self
    {
        if ($this->dates->contains($date)) {
            $this->dates->removeElement($date);
            // set the owning side to null (unless already changed)
            if ($date->getPdfFile() === $this) {
                $date->setPdfFile(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Amount[]
     */
    public function getAmounts(): Collection
    {
        return $this->amounts;
    }
    public function addAmount(Amount $amount): self
    {
        if (!$this->amounts->contains($amount)) {
            $this->amounts[] = $amount;
            $amount->setPdfFile($this);
        }
        return $this;
    }
    public function removeAmount(Amount $amount): self
    {
        if ($this->amounts->contains($amount)) {
            $this->amounts->removeElement($amount);
            // set the owning side to null (unless already changed)
            if ($amount->getPdfFile() === $this) {
                $amount->setPdfFile(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection|Info[]
     */
    public function getInfos(): Collection
    {
        return $this->infos;
    }
    public function addInfo(Info $info): self
    {
        if (!$this->infos->contains($info)) {
            $this->infos[] = $info;
            $info->setPdfFile($this);
        }
        return $this;
    }
    public function removeInfo(Info $info): self
    {
        if ($this->infos->contains($info)) {
            $this->infos->removeElement($info);
            // set the owning side to null (unless already changed)
            if ($info->getPdfFile() === $this) {
                $info->setPdfFile(null);
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}