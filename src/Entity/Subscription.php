<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 */
class Subscription
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups("profile")
     * @Groups("anonymous_user")
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Groups("profile")
     * @Groups("anonymous_user")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

   /*/**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="subscription", orphanRemoval=true)
     */
    /*/**
     * Many Subscription have Many Users.(OneToMany Unidirectional = One Sub has many Users)
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="subscriptions_users",
     *      joinColumns={@ORM\JoinColumn(name="subscription_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true)}
     *      )
     */
   // private $users;

    /**
     * @Groups("profile")
     * @Groups("anonymous_user")
     * @ORM\Column(type="string", length=255)
     */
    private $slogan;

    public function __construct()
    {
        //$this->users = new ArrayCollection();
    }

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

   /* /**
     * @return Collection|User[]
     */
    /*public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setSubscription($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getSubscription() === $this) {
                $user->setSubscription(null);
            }
        }

        return $this;
    }*/

    public function getSlogan(): ?string
    {
        return $this->slogan;
    }

    public function setSlogan(string $slogan): self
    {
        $this->slogan = $slogan;

        return $this;
    }
}
