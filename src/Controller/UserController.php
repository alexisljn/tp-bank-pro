<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class UserController extends AbstractFOSRestController
{
    const ATTRIBUTES_ALLOWED_TO_EDIT = ['firstname' => 'setFirstname',
                                        'lastname' => 'setLastname',
                                        'address' => 'setAddress',
                                        'country' => 'setCountry'];
    private $em;
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    /**
     * @Rest\Get("api/anonymous/users")
     * @Rest\View(serializerGroups={"anonymous_user"})
     * @SWG\Response(
     *     response=200,
     *     description="Returns the data of users",
     * )
     */
    public function getApiUsers()
    {
        $users = $this->userRepository->findAll();

        return $this->view($users, Response::HTTP_OK);
    }


    /**
     * @Rest\Get("api/anonymous/users/{email}")
     * @Rest\View(serializerGroups={"anonymous_user"})
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
     *     response=404,
     *     description="User not found",
     * )
     */
    public function getApiUser(User $user)
    {
        return $this->view($user, Response::HTTP_OK);
    }

    /**
     * @Rest\Post("api/anonymous/register")
     * @SWG\Parameter(
     *     name="user",
     *     in="body",
     *     type="string",
     *     description="The data of the user in JSON",
     *     @Model(type=User::class, groups={"profile"})
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Return the data of the new subscription",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Error(s) in request body",
     * )
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postApiUser(User $user, Request $request, ConstraintViolationListInterface $validationErrors)
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
        if(!$request->get('subscription')) {
            $errors[] = ['message' => 'Each user should have a subscription', 'propertyPath' => 'subscription'];
        }

        $subscriptionId = $request->get('subscription');
        $subscription = $this->em->getRepository(Subscription::class)->findOneBy(['id' => $subscriptionId]);

        if(is_null($subscription)) {
            throw new NotFoundHttpException('this subscription does not exist');
        }

       /* if($request->get('cards')) {
            dd''
        }*/

        if (!empty($errors)) {
            throw new BadRequestHttpException(json_encode($errors));
        }

        $user->setSubscription($subscription);
        $this->em->persist($user);
        $this->em->flush();
        return $this->view($user, Response::HTTP_CREATED);
    }

    /**
     * @Rest\Get("api/profile")
     * @Rest\View(serializerGroups={"profile"})
     * @SWG\Response(
     *     response=200,
     *     description="Return data of the user",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     */
    public function profile()
    {
       $user = $this->getUser();

       return $this->view($user, Response::HTTP_OK);
    }

    /**
     * @Rest\Patch("api/profile")
     * @Rest\View(serializerGroups={"profile"})
     * @SWG\Response(
     *     response=202,
     *     description="Return patched data of the user",
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
     *     response=404,
     *     description="Subscription not found",
     * )
     */
    public function editProfile(Request $request, ValidatorInterface $validator)
    {
        $user = $this->getUser();

        foreach(UserController::ATTRIBUTES_ALLOWED_TO_EDIT as $attribute => $setter) {
            if(is_null($request->get($attribute))) {
                continue;
            }
            $user->$setter($request->request->get($attribute));
        }

        if($request->get('subscription')) {
            $subscription = $this->em->getRepository(Subscription::class)->findOneBy(['id' => $request->request->get('subscription')]);
            if(is_null($subscription)) {
                throw new NotFoundHttpException('this subscription does not exist');
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

}
