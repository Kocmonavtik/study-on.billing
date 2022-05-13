<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api", name="app_api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route ("/v1/auth", name="api_auth", methods={"POST"})
     */
    public function auth(): Response
    {
    }
    /**
     * @Route ("/v1/register", name="api_register", methods={"POST"})
     */
    public function register(
        JWTTokenManagerInterface $JWTTokenManager,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');
        $errors = $validator->validate($userDto);

        if (\count($errors) > 0) {
            throw new UnprocessableEntityHttpException($errors);
        }
        try {
            $user = Users::fromDto($userDto, $passwordHasher);
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $exception) {
            throw new \HttpException(500, 'Error occurred while trying register.', $exception);
        }

        //$request->headers->get('Authorization');

        $token = $JWTTokenManager->create($user);
        //return new JsonResponse(['token' => $token]);
        return $this->json(['token' => $token, 'username' => $user->getEmail()], Response::HTTP_CREATED);
        //return new JsonResponse(['token' => $JWTTokenManager->create($user)]);
    }

    /**
     * @Route ("/v1/current", name="api_current", methods={"GET"})
     */
    public function current(Security $security, UsersRepository $usersRepository): Response
    {
        $currentUser = $security->getUser();

        if (!$currentUser) {
            return $this->json(['status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Пользователя не существует'], Response::HTTP_NOT_FOUND);
        }
        $user = $usersRepository->find($currentUser->getUserIdentifier());
        return $this->json(['username' => $user->getEmail(),'roles' => $user->getRoles(), 'balance' => $user->getBalance()]);

        /*$token=
        $token = $tokenStorage->getToken();
        $user = $token->getUser();
        return new JsonResponse($user);*/
      /*  $authSuccess=$this->container->get('lexik_jwt_authentication.handler.authentication_success');
        return $authSuccess->handleAuthenticationSuccess(UserDto::class);*/
        //return new JsonResponse($tokenStorage->getToken()->getUser());
    }
}
