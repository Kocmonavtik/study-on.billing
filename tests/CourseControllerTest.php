<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Dto\CourseDto;
use App\Dto\PaymentDto;
use App\Service\Payment;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\DTO\CourseCreationRequestDto;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseControllerTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    private string $apiPath = '/api/v1/courses';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [
            new AppFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(RefreshTokenGeneratorInterface::class),
                self::getContainer()->get(RefreshTokenManagerInterface::class)
            )
        ];
    }

    private function getToken($user)
    {
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    /**
     * @group getCourseTest
     */
    public function testGetAllCourses()
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/',
            [],
            [],
            $headers,
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(3, $response);
    }

    /**
     * @group getCourseByCodeAuthUser
     */
    public function testGetCourseByCodeAuthorizedUser()
    {
        $user = [
            'username' => 'testEmail@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $existingCourseCode = '0000';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/' . $existingCourseCode,
            [],
            [],
            $headers,
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var CourseDto $courseDto */
        $courseDto = $this->serializer->deserialize($client->getResponse()->getContent(), CourseDto::class, 'json');

        self::assertNotNull($courseDto, 'Курс не найден');
        self::assertEquals('0000', $courseDto->code);
        self::assertEquals('rent', $courseDto->type);
        self::assertEquals(1500, $courseDto->price);

        $notExistingCourseCode = '0010';
        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/' . $notExistingCourseCode,
            [],
            [],
            $headers,
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());
    }

    /**
     * @group payCourseAuthUser
     */
    public function testPayCourseAuthorizedUser()
    {
        $user = [
            'username' => 'testEmail@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCode = '0001';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            [],
            [],
            $headers,
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var PaymentDto $paymentDto */
        $paymentDto = $this->serializer->deserialize($client->getResponse()->getContent(), PaymentDto::class, 'json');

        self::assertEquals(true, $paymentDto->status);

        $courseCode = '0000';
        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            [],
            [],
            $headers,
        );

        $this->assertResponseCode(Response::HTTP_NOT_ACCEPTABLE, $client->getResponse());
    }

    /**
     * @group payCourseUnAuthUser
     */
    public function testPayCourseUnauthorizedUser()
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $courseCode = '0001';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/' . $courseCode . '/pay',
            [],
            [],
            $headers,
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }

    /**
     * @group testAddCourseAdminUser
     */
    public function testAddCourseAdminUser()
    {
        $user = [
            'username' => 'test1Email@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = 'TEST';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/new',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CREATED);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(true, $respose['success']);
    }


    /**
     * @group testAddExistingCourseAdminUser
     */
    public function testAddExistingCourseAdminUser()
    {
        $user = [
            'username' => 'test1Email@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = '0000';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/new',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CONFLICT);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(false, $respose['success']);
        self::assertEquals('Курс с таким кодом существует', $respose['message']);
    }

    /**
     * @group testAddCourseUser
     */
    public function testAddCourseUser()
    {
        $user = [
            'username' => 'testEmail@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = 'PPBI';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/new',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseCode(Response::HTTP_FORBIDDEN);
    }

    /**
     * @group testEditCourseUser
     */
    public function testEditCourseUser()
    {
        $user = [
            'username' => 'testEmail@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = '0001';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/0001/edit',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseCode(Response::HTTP_FORBIDDEN);
    }

    /**
     * @group testEditCourseAdminUser
     */
    public function testEditCourseAdminUser()
    {
        $user = [
            'username' => 'test1Email@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = '0000';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/0000/edit',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseOk();

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(true, $respose['success']);
    }

    /**
     * @group testEditCourseNewExistingCodeAdminUser
     */
    public function testEditCourseNewExistingCodeAdminUser()
    {
        $user = [
            'username' => 'test1Email@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = '0001';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/0000/edit',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CONFLICT);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(false, $respose['success']);
        self::assertEquals('Курс с таким кодом уже существует', $respose['message']);
    }

    /**
     * @group testEditNotExistingCourseCodeAdminUser
     */
    public function testEditNotExistingCourseCodeAdminUser()
    {
        $user = [
            'username' => 'test1Email@gmail.com',
            'password' => '12345'
        ];
        $token = $this->getToken($user);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $courseCreationRequest = new CourseCreationRequestDto();
        $courseCreationRequest->code = 'PPBI2';
        $courseCreationRequest->title = 'TEST';
        $courseCreationRequest->type = 'rent';
        $courseCreationRequest->price = 1000;

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/0010/edit',
            [],
            [],
            $headers,
            $this->serializer->serialize($courseCreationRequest, 'json')
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND);

        $respose = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertEquals(false, $respose['success']);
        self::assertEquals('Курс с таким кодом не найден', $respose['message']);
    }
}