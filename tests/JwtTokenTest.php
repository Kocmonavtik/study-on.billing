<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Users;
use Symfony\Component\HttpFoundation\Response;

class JwtTokenTest extends AbstractTest
{
    private $serializer;
    private $apiPath = '/api';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    private function getToken($user)
    {
        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/v1/auth',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    /**
     * @group GetCurrentUser
     */
    public function testgetCurrentUser(): void
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

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/v1/current',
            [],
            [],
            $headers
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        $userRepository = self::getEntityManager()->getRepository(Users::class);
        $currentUser = $userRepository->findOneBy(['email' => $json['username']]);
        self::assertEquals($currentUser->getEmail(), $json['username']);
        self::assertEquals($currentUser->getRoles(), $json['roles']);
        self::assertEquals($currentUser->getBalance(), $json['balance']);
    }

    /**
     * @group GetCurrentUserFakeToken
     */
    public function testGetCurrentUserInvalidToken(): void
    {
        $token = 'fakeToken';

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/v1/current',
            [],
            [],
            $headers
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json);

        self::assertEquals('401', $json['code']);
        self::assertEquals('Invalid JWT Token', $json['message']);
    }
    /**
     * @group GetCurrentUserNotBlankToken
     */
    public function testGetCurrentUserNotValidToken(): void
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/v1/current',
            [],
            [],
            $headers
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json);

        self::assertEquals('401', $json['code']);
        self::assertEquals('JWT Token not found', $json['message']);
    }
}
