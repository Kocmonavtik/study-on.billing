<?php

namespace App\Controller;

use App\DTO\UserDto;
use App\Entity\Users;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\SerializerBuilder;
use Lcobucci\JWT\Validation\Validator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/api", name="app_api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route ("/v1/auth", name="api_auth")
     */
    public function auth(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php',
        ]);
    }
    /**
     * @Route ("/v1/register", name="api_register", methods={"POST"})
     */
    public function register(JWTTokenManagerInterface $JWTTokenManager, Request $request, Validator $validator, ObjectManager $manager): JsonResponse
    {
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');
        $errors = $validator->validate($userDto);
        if (\count($errors) > 0) {
            throw new UnprocessableEntityHttpException($errors);
        }
        try {
            $user = Users::fromDto($userDto);
            $manager->persist($user);
            $manager->flush();
        } catch (\Exception $exception) {
            throw new \HttpException(500, 'Error occurred while trying register.', $exception);
        }

        return new JsonResponse(['token' => $JWTTokenManager->create($user)]);
    }
}
