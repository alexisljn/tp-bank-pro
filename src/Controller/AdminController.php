<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Swagger\Annotations as SWG;

class AdminController extends AbstractFOSRestController
{

    const USER_ATTRIBUTES_ALLOWED_TO_EDIT = ['firstname' => 'setFirstname',
                                            'lastname' => 'setLastname',
                                            'address' => 'setAddress',
                                            'country' => 'setCountry',
                                            'apiKey'=> 'setApiKey'];
    const SUBSCRIPTION_ATTRIBUTES_ALLOWED_TO_EDIT = ['name' => 'setName',
                                                    'slogan' => 'setSlogan',
                                                    'url' => 'setUrl'];
    const CARD_ATTRIBUTES_ALLOWED_TO_EDIT = ['name' => 'setName',
                                            'creditCardType' => 'setCreditCardType',
                                            'creditCardNumber' => 'setCreditCardNumber',
                                            'currencyCode' => 'setCurrencyCode',
                                            'value' => 'setValue'];

    private $em;
    private $userRepository;
    private $cardRepository;
    private $subscriptionRepository;

    public function __construct(EntityManagerInterface $em,
                                UserRepository $userRepository,
                                CardRepository $cardRepository,
                                SubscriptionRepository $subscriptionRepository
                                )
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->cardRepository = $cardRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * @Rest\Get("api/admin/users")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the data of users",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     */
    public function getApiUsers()
    {
        $users = $this->userRepository->findAll();

        return $this->view($users, Response::HTTP_OK);
    }

    /**
     * @Rest\Get("api/admin/users/{email}")
     * @SWG\Parameter(
     *     name="email",
     *     in="path",
     *     type="string",
     *     description="The field used to filter user"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return data of a given user",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User not found",
     * )
     */
    public function getApiUser(User $user)
    {
        return $this->view($user, Response::HTTP_OK);
    }

    /**
     * @Rest\Patch("api/admin/users/{email}")
     * @SWG\Parameter(
     *     name="email",
     *     in="path",
     *     type="string",
     *     description="The field used to filer user"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="return the patched data of a given user",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Subscription or user not found",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Error(s) in body request",
     * )
     */
    public function patchApiUser(User $user, Request $request, ValidatorInterface $validator)
    {
        if($request->request->get('email'))
        {
            $user->setEmail($request->request->get('email'));
        }

        foreach(AdminController::USER_ATTRIBUTES_ALLOWED_TO_EDIT as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $user->$setter($request->request->get($attribute));
        }

        if($request->get('subscription')) {
            $subscription = $this->em->getRepository(Subscription::class)->findOneBy(['id' => $request->request->get('subscription')]);
            if(is_null($subscription)) {
                throw new NotFoundHttpException(json_encode(['message' => 'This subscription does not exist', 'propertyPath' => 'subscription']));
            } else {
                $user->setSubscription($subscription);
            }
        }

        $validationErrors = $validator->validate($user);
        if($validationErrors->count() > 0) {
            /** @var ConstraintViolation $constraintViolation */
            foreach ($validationErrors as $constraintViolation) {
                $message = $constraintViolation->getMessage();
                $propertyPath = $constraintViolation->getPropertyPath();
                $errors[] = ['message' => $message, 'propertyPath' => $propertyPath];
            }
        }
        if (!empty($errors)) {
            throw new BadRequestHttpException(json_encode( $errors));
        }

        $this->em->flush();
        return $this->view($user, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Delete("api/admin/users/{email}")
     * @SWG\Parameter(
     *     name="email",
     *     in="path",
     *     type="string",
     *     description="The field used to filer user"
     * )
     * @SWG\Response(
     *     response=204,
     *     description="User successfully removed",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User not found",
     * )
     */
    public function deleteUser(User $user)
    {
        $cards = $user->getCards();
        $this->em->remove($user);
        foreach ($cards as $card) {
            $this->em->remove($card);
        }
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("api/admin/subscriptions")
     * @SWG\Response(
     *     response=200,
     *     description="Return the data of subscriptions",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     */
    public function getSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();

        return $this->view($subscriptions, Response::HTTP_OK);
    }

    /**
     * @Rest\Get("api/admin/subscriptions/{id}")
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
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Subscription not found",
     * )
     */
    public function getSubscription(Subscription $subscription)
    {
        return $this->view($subscription, Response::HTTP_OK);
    }

    /**
     * @Rest\Post("api/admin/subscriptions")
     * @SWG\Parameter(
     *     name="subscription",
     *     in="body",
     *     type="string",
     *     description="The data of the subscription in JSON",
     *     @Model(type=Subscription::class, groups={"anonymous_user"})
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Return the data of the new subscription",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Error(s) in request body",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @ParamConverter("subscription", converter="fos_rest.request_body")
     */
    public function postSubscription(Subscription $subscription, Request $request, ConstraintViolationListInterface $validationErrors)
    {
        $errors = [];
        if($validationErrors->count() > 0) {
            /** @var ConstraintViolation $constraintViolation */
            foreach ($validationErrors as $constraintViolation) {
                $message = $constraintViolation->getMessage();
                $propertyPath = $constraintViolation->getPropertyPath();
                $errors[] = ['message' => $message, 'propertyPath' => $propertyPath];
            }
        }

        if (!empty($errors)) {
            throw new BadRequestHttpException(json_encode($errors));
        }
        $this->em->persist($subscription);
        $this->em->flush();
        return $this->view($subscription, Response::HTTP_CREATED);

    }

    /**
     * @Rest\Patch("api/admin/subscriptions/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The field used to filter subscription"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="Return the patched data of a given subscription",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Error(s) in request body",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Subscription not found",
     * )
     */
    public function patchSubscription(Subscription $subscription, Request $request, ValidatorInterface $validator)
    {
        foreach(AdminController::SUBSCRIPTION_ATTRIBUTES_ALLOWED_TO_EDIT as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $subscription->$setter($request->request->get($attribute));
        }

        $validationErrors = $validator->validate($subscription);
        if($validationErrors->count() > 0) {
            /** @var ConstraintViolation $constraintViolation */
            foreach ($validationErrors as $constraintViolation) {
                $message = $constraintViolation->getMessage();
                $propertyPath = $constraintViolation->getPropertyPath();
                $errors[] = ['message' => $message, 'propertyPath' => $propertyPath];
            }
        }
        if (!empty($errors)) {
            throw new BadRequestHttpException(json_encode( $errors));
        }

        $this->em->flush();
        return $this->view($subscription, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Delete("api/admin/subscriptions/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The field used to filter subscription"
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Subscription successfully removed",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure or Subscription has at least one user",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Subscription not found",
     * )
     */
    public function deleteSubscription(Subscription $subscription)
    {
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
           if($user->getSubscription() === $subscription){
               throw new AccessDeniedHttpException('Subscription has at least one user');
           }
        }

        $this->em->remove($subscription);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Get("api/admin/cards")
     * @SWG\Response(
     *     response=200,
     *     description="Return the data of cards",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     */
    public function getCards()
    {
        $cards = $this->cardRepository->findAll();

        return $this->view($cards, Response::HTTP_OK);
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Get("api/admin/cards/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The field used to filter card"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return the data of a given card",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Card not found",
     * )
     */
    public function getCard(Card $card)
    {
        return $this->view($card, Response::HTTP_OK);
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Post("api/admin/cards")
     * @SWG\Parameter(
     *     name="card",
     *     in="body",
     *     type="string",
     *     description="The data of the card in JSON",
     *     @Model(type=Card::class)
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Return data of the new card",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Error(s) in request",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User not found",
     * )
     * @ParamConverter("card", converter="fos_rest.request_body")
     */
    public function postCard(Card $card, Request $request, ConstraintViolationListInterface $validationErrors)
    {
        $errors = [];
        if($validationErrors->count() > 0) {
            /** @var ConstraintViolation $constraintViolation */
            foreach ($validationErrors as $constraintViolation) {
                $message = $constraintViolation->getMessage();
                $propertyPath = $constraintViolation->getPropertyPath();
                $errors[] = ['message' => $message, 'propertyPath' => $propertyPath];
            }
        }

        if(!$request->get('user')){
            $errors[] = ['message' => "You have to pick an user by his id for this card", 'propertyPath' => 'user'];
        }

        $user = $this->userRepository->findOneBy(['id' => $request->request->get('user')]);
        if(is_null($user))
        {
            throw new NotFoundHttpException('This user does not exist');
        }

        if (!empty($errors)) {
            throw new BadRequestHttpException(json_encode($errors));
        }

        $user->addCard($card);
        $this->em->persist($card);
        $this->em->flush();

        return $this->view($card, Response::HTTP_CREATED);
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Patch("api/admin/cards/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The field used to filter card"
     * )
     * @SWG\Response(
     *     response=202,
     *     description="Return the patched data of a given card",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Error(s) in body request",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Card not found",
     * )
     */
    public function patchCard(Card $card, Request $request, ValidatorInterface $validator)
    {
        foreach(AdminController::CARD_ATTRIBUTES_ALLOWED_TO_EDIT as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $card->$setter($request->request->get($attribute));
        }

        $validationErrors = $validator->validate($card);
        if($validationErrors->count() > 0) {
            /** @var ConstraintViolation $constraintViolation */
            foreach ($validationErrors as $constraintViolation) {
                $message = $constraintViolation->getMessage();
                $propertyPath = $constraintViolation->getPropertyPath();
                $errors[] = ['message' => $message, 'propertyPath' => $propertyPath];
            }
        }
        if (!empty($errors)) {
            throw new BadRequestHttpException(json_encode( $errors));
        }

        $this->em->flush();
        return $this->view($card, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Delete("api/admin/cards/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     type="integer",
     *     description="The field used to filter card"
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Card successfully removed",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Admin authentication failure",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Card not found",
     * )
     */
    public function deleteCard(Card $card)
    {
        $users = $this->userRepository->findAll();
        foreach ($users as $singleUser) {
            $cards = $singleUser->getCards();
            foreach ($cards as $singleCard) {
                if ($singleCard === $card) {
                    $user = $singleUser;
                }
            }
        }
        $user->removeCard($card);
        $this->em->remove($card);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


}
