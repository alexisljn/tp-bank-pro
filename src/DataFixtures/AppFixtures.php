<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    private $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        // Création de 3 subscriptions

        for($i = 0; $i < 3; $i++) {
            $subscription = new Subscription();
            $subscription->setName(ucfirst($faker->word));
            $subscription->setSlogan($faker->text(150));
            $subscription->setUrl($faker->url);
            $manager->persist($subscription);
        }

        $manager->flush();

        // Création de 15 users et de cartes

        for($i = 0; $i < 15; $i++) {
            $user = new User();
            $user->setFirstname($faker->firstName);
            $user->setLastname($faker->lastName);
            $user->setEmail($faker->email);
            $user->setApiKey($faker->regexify('[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}'));
            $user->setCreatedAt($faker->dateTime('now'));
            $user->setAddress($faker->address);
            $user->setCountry($faker->country);
            $user->setSubscription($this->getRandomSubscription());
            $count = $this->getRandomNumberOfCardsToCreate();
            for($j = 0; $j < $count; $j++) {
                $card = new Card();
                $card->setName($faker->text(50));
                $card->setCreditCardType($faker->creditCardType);
                $card->setCreditCardNumber($faker->creditCardNumber);
                $card->setCurrencyCode($faker->currencyCode);
                $card->setValue($faker->numberBetween(0, 100000));
                $user->addCard($card);
                $manager->persist($card);
            }
            $manager->persist($user);
        }
        $manager->flush();
    }

    private function getRandomSubscription()
    {
        $subscriptionId = rand(1,3);
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $subscriptionId]);
        return $subscription;
    }

    private function getRandomNumberOfCardsToCreate()
    {
        $count = rand(1,2);
        return $count;
    }
}
