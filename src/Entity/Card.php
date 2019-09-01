<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\CardRepository")
 * @UniqueEntity(fields={"creditCardNumber"}, message="This credit card number is already used by an user")
 */
class Card
{
    /**
     * @Groups("ownCard")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups("anonymous_user")
     * @Groups("ownCard")
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Groups("ownCard")
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $creditCardType;

    /**
     * @Groups("ownCard")
     * @ORM\Column(type="string", unique=true)
     */
    private $creditCardNumber;

    /**
     * @Groups("ownCard")
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=3)
     */
    private $currencyCode;

    /**
     * @Groups("ownCard")
     * @Assert\NotBlank()
     * @ORM\Column(type="integer")
     */
    private $value;

    /*/**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
   // private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreditCardType(): ?string
    {
        return $this->creditCardType;
    }

    public function setCreditCardType(string $creditCardType): self
    {
        $this->creditCardType = $creditCardType;

        return $this;
    }

    public function getCreditCardNumber(): ?int
    {
        return $this->creditCardNumber;
    }

    public function setCreditCardNumber(int $creditCardNumber): self
    {
        $this->creditCardNumber = $creditCardNumber;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getUser(): ?User
    {
        return null;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
