<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;


class SubscriptionController extends AbstractFOSRestController
{
    private $em;
    private $subscriptionRepository;

    public function __construct(EntityManagerInterface $em, SubscriptionRepository $subscriptionRepository)
    {
        $this->em = $em;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * @Rest\Get("api/anonymous/subscriptions")
     * @SWG\Response(
     *     response=200,
     *     description="Return the data of subscriptions",
     * )
     */
    public function getSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();

        return $this->view($subscriptions, Response::HTTP_OK);
    }


    /**
     * @Rest\Get("api/anonymous/subscriptions/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The field used to filer subscription"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return the data of a given subscription",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Subscription not found",
     * )
     */
    public function getSubscription(Subscription $subscription)
    {
        return $this->view($subscription,Response::HTTP_OK);
    }
}
