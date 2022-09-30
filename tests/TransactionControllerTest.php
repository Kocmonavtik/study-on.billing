<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class TransactionControllerTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    private string $apiPath = '/api/v1/transactions';

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
     * @group testTransactionsUnAuthUser
     */
    public function testGetTransactionUnathorizedUser()
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath. '/',
            [],
            [],
            $headers,
        );
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }

    /**
     * @group testGetTransactionsAuthUser
     */
    public function testGetTransactionsAthorizedUser()
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
            $this->apiPath . '/',
            [],
            [],
            $headers,
        );

        $this->assertResponseOk();

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(4, $response);
    }

    /**
     * @group testGetTransactionsWithFilters
     */
    public function testGetTransactionsWithFilters()
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

        $filters = [
            'type' => 'deposit'
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/?' . http_build_query($filters),
            [],
            [],
            $headers,
        );

        $this->assertResponseOk();

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertCount(1, $response);

        $filters = [
            'course_code' => 'PPBI'
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/?' . http_build_query($filters),
            [],
            [],
            $headers,
        );

        $this->assertResponseOk();

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertCount(0, $response);

        $filters = [
            'course_code' => 'PPBI123'
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/?' . http_build_query($filters),
            [],
            [],
            $headers,
        );

        $this->assertResponseOk();

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertCount(0, $response);

        $filters = [
            'skip_expired' => true,
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->apiPath . '/?' . http_build_query($filters),
            [],
            [],
            $headers,
        );

        $this->assertResponseOk();

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertCount(3, $response);
    }

}