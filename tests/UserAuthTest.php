<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class UserAuthTest extends AbstractTest
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

    /**
     * @group authExistingUser
     */
    public function testAuthExistingUser(): void
    {
        $user = [
            'username' => 'testEmail@gmail.com',
            'password' => '12345'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    /**
     * @group authNotExistingUser
     */
    public function testAuthNotExistingUser(): void
    {
        $user = [
            'username' => 'testdfhfdjEmail@gmail.com',
            'password' => '12345'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['code']);
        self::assertNotEmpty($json['message']);

        self::assertEquals('401', $json['code']);
        self::assertEquals('Invalid credentials.', $json['message']);
    }

    /**
     * @group registerSuccessful
     */
    public function testRegisterSuccessful(): void
    {
        $user = [
            'username' => 'testRegiter@register.com',
            'password' => 'registerTest'
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->apiPath . '/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CREATED);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
        self::assertNotEmpty($json['roles']);

        self::assertContains('ROLE_USER', $json['roles']);
    }

    /**
     * @group registerFailed
     * @group registerFailedEmail
     */
    public function testRegisterFailedEmail(): void
    {
        $user = [
            'username' => 'testRegiterNotSobakaregister.com',
            'password' => 'registerTest'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['username']);

        self::assertContains("Invalid email address", $json['errors']['username']);
    }
    /**
     * @group registerFailed
     * @group registerFailedLengthPass
     */
    public function testRegisterFailedPass(): void
    {
        $user = [
            'username' => 'testRegiter@register.com',
            'password' => '123'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['password']);

        self::assertContains("Пароль должен состоять минимум из 6 символов", $json['errors']['password']);
    }
    /**
     * @group registerFailed
     * @group registerFailedNotBlankField
     */
    public function testRegisterFailedNotBlank(): void
    {
        $user = [
            'username' => '',
            'password' => ''
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['errors']);
        self::assertNotEmpty($json['errors']['password']);
        self::assertNotEmpty($json['errors']['username']);

        self::assertContains("Пароль должен состоять минимум из 6 символов", $json['errors']['password']);
        self::assertContains("Поле пароля не должно быть пустым", $json['errors']['password']);
        self::assertContains("Поле пользователя не должно быть пустым", $json['errors']['username']);
    }
    /**
     * @group registerFailed
     * @group registerFailedUniqueEmail
     */
    public function testRegisterFailedUniqueEmail(): void
    {
        $user = [
            'username' => 'testEmail@gmail.com',
            'password' => '123456'
        ];

        $client = self::getClient();

        $client->request(
            'POST',
            $this->apiPath . '/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST);

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['error']);
        $this->assertEquals('Пользователь ' . $user['username'] . ' уже существует', $json['error']);
    }
}
