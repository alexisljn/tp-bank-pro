<?php

namespace App\Controller;

use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class CardController extends AbstractFOSRestController
{
    const ATTRIBUTES_ALLOWED_TO_EDIT = ['name' => 'setName',
                                        'creditCardType' => 'setCreditCardType',
                                        'creditCardNumber' => 'setCreditCardNumber',
                                        'currencyCode' => 'setCurrencyCode',
                                        'value' => 'setValue'];
    private $em;
    private $cardRepository;

    public function __construct(EntityManagerInterface $em, CardRepository $cardRepository)
    {
        $this->em = $em;
        $this->cardRepository = $cardRepository;
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Get("api/cards")
     * @SWG\Response(
     *     response=200,
     *     description="Return the data of cards",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Authentication is required",
     * )
     */
    public function getCards()
    {
        $user = $this->getUser();
        $cards = $user->getCards();

        return $this->view($cards, Response::HTTP_OK);
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Get("api/cards/{id}")
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
     *     response=404,
     *     description="Card not found",
     * )
     */
    public function getOneCard(Card $card)
    {
        $user = $this->getUser();
        $cards = $user->getCards();
        foreach ($cards as $oneCard) {
            if ($oneCard->getId() == $card->getId()) {
                return $this->view($card, Response::HTTP_OK);
            }
        }
        throw new AccessDeniedHttpException('You are not the owner of this card');
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Post("api/cards")
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
     * @ParamConverter("card", converter="fos_rest.request_body")
     */
    public function postCard(Card $card, ConstraintViolationListInterface $validationErrors)
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
        $user = $this->getUser();
        $user->addCard($card);
        $this->em->persist($card);
        $this->em->flush();

        return $this->view($card, Response::HTTP_CREATED);
    }

    /**
     * @Rest\View(serializerGroups={"ownCard"})
     * @Rest\Patch("api/cards/{id}")
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
     *     response=404,
     *     description="Card not found",
     * )
     */
    public function patchCard(Card $card, Request $request, ValidatorInterface $validator)
    {
        $continueValidation = false;
        $user = $this->getUser();
        $cards = $user->getCards();
        foreach($cards as $oneCard) {
            if($oneCard->getId() == $card->getId()) {
                $continueValidation = true;
            }
        }

        if(!$continueValidation) {
            throw new AccessDeniedHttpException('You are not the owner of this card');
        }

        foreach(CardController::ATTRIBUTES_ALLOWED_TO_EDIT as $attribute => $setter) {
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
     * @Rest\Delete("api/cards/{id}")
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
     *     response=404,
     *     description="Card not found",
     * )
     */
    public function deleteCard(Card $card)
    {
        $continueValidation = false;
        $user = $this->getUser();
        $cards = $user->getCards();
        foreach($cards as $oneCard) {
            if($oneCard->getId() == $card->getId()) {
                $continueValidation = true;
            }
        }

        if(!$continueValidation) {
            throw new AccessDeniedHttpException('You are not the owner of this card');
        }
        $user->removeCard($card);
        $this->em->remove($card);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
