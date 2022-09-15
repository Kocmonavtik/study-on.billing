<?php

namespace App\Controller;

use App\DTO\Transfromer\CurrentUserTransformer;
use App\DTO\UserDto;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use http\Message;
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
use Symfony\Component\Validator\ConstraintViolation;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security as NelmioSecurity;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * @Route("/api", name="app_api")
 */
class ApiController extends AbstractController
{
    /**
     * @OA\Post (
     *     path="api/v1/auth",
     *     description="Аутентификация пользователя",
     * )
     * @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          @OA\Property(
     *              property="username",
     *              type="string",
     *              example="example@example.com"
     *          ),
     *          @OA\Property(
     *              property="password",
     *              type="string",
     *              example="yourPassword"
     *          )
     *       )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращает токен",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string"
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Ошибка авторизации",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Непредвиденная ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @Route ("/v1/auth", name="api_auth", methods={"POST"})
     */
    public function login(): Response
    {
    }

    /**
     *
     * @OA\Post(
     *     path="api/v1/register",
     *     description="Регистрация нового пользователя",
     * )
     * @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          @OA\Property(
     *              property="username",
     *              type="string",
     *              example="example@example.com"
     *          ),
     *          @OA\Property(
     *              property="password",
     *              type="string",
     *              example="yourPassword"
     *          )
     *       )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Пользователь успешно создан",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property = "refresh_token",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="roles",
     *          type="array",
     *          @OA\Items(type="string")
     *        )
     *
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка валидации",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="errors",
     *          type="array",
     *          @OA\Items(
     *              @OA\Property(
     *                  type="string",
     *                  property="property_name"
     *              )
     *          )
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Ошибка аутетнтификации пользователя",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Непредвиденная ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @Route ("/v1/register", name="api_register", methods={"POST"})
     */
    public function register(
        JWTTokenManagerInterface $JWTTokenManager,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UsersRepository $usersRepository,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager
    ): Response {
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');
        $errors = $validator->validate($userDto);

        $jsonErrors = [];
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                $jsonErrors[$error->getPropertyPath()][] = $error->getMessage();
            }
            return $this->json(['errors' => $jsonErrors], Response::HTTP_BAD_REQUEST);
        }
        if ($usersRepository->findOneBy(['email' => $userDto->username])) {
            return $this->json(['error' => 'Пользователь '.$userDto->username.' уже существует'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $user = Users::fromDto($userDto, $passwordHasher);
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $exception) {
            return $this->json(['trow' => 500, 'code' => 'Error occurred while trying register.', $exception]);
        }
        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $refreshTokenManager->save($refreshToken);

        $token = $JWTTokenManager->create($user);
        return $this->json(['token' => $token, 'roles' => $user->getRoles(), 'refreshToken' => $refreshToken->getRefreshToken()], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="api/v1/current",
     *     description="Получение текущего пользователя",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращение информации о текущем пользователе",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *        ),
     *        @OA\Property(
     *          property="roles",
     *          type="array",
     *          @OA\Items(type="string")
     *        ),
     *        @OA\Property(
     *          property="balance",
     *          type="float",
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Пользователь не был авторизирован",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Непредвиденная ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @NelmioSecurity(name="Bearer")
     *
     * @Route ("/v1/current", name="api_current", methods={"GET"})
     */
    public function current(Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Пользователь не авторизован'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            ['username' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
            ],
            Response::HTTP_OK
        );
    }
}
