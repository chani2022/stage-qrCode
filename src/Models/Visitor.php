<?php

namespace App\Models;

use DateTime;

class Visitor
{
    private ?int $id;
    private ?string $lastName;
    private ?string $firstName;
    private ?string $pictureCin;
    private ?DateTime $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getPictureCin(): ?string
    {
        return $this->pictureCin;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setPictureCin(?string $pictureCin): static
    {
        $this->pictureCin = $pictureCin;

        return $this;
    }

    public function setCreatedAt(?DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
