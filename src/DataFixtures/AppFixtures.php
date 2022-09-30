<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Users;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;
    private $refreshTokenGenerator;
    private $refreshTokenManager;
    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager
    )
    {
        $this->passwordHasher = $passwordHasher;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $user1 = new Users();
        $user1->setEmail('testEmail@gmail.com');
        $user1->setRoles(['ROLE_USER']);
        $user1->setPassword($this->passwordHasher->hashPassword(
            $user1,
            '12345'
        ));
        $user1->setBalace(16000);
        $manager->persist($user1);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user1, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

        $user2 = new Users();
        $user2->setEmail('test1Email@gmail.com');
        $user2->setRoles(['ROLE_SUPER_ADMIN']);
        $user2->setPassword($this->passwordHasher->hashPassword(
            $user2,
            '12345'
        ));
        $user2->setBalace(0);
        $manager->persist($user2);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user2, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

        $course1 = new Course();
        $course1->setCode('0000')
            ->setType(1)
            ->setPrice(1500)
            ->setTitle('Программирование на С#');
        $course2 = new Course();
        $course2->setType(3)
            ->setCode('0001')
            ->setPrice(15000)
            ->setTitle('Java-разработчик');
        $course3 = new Course();
        $course3->setCode('0002')
            ->setType(2)
            ->setPrice(1000)
            ->setTitle('Frontend-разработчик');
        $manager->persist($course1);
        $manager->persist($course2);
        $manager->persist($course3);
        $transactions = [
            [
                'type' => 2,
                'amount' => 16600,
                'customer' => $user1,
                'createdAt' => new \DateTimeImmutable('2022-09-15 10:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $course1->getPrice(),
                'expiresAt' => new \DateTimeImmutable('2022-08-15 10:00:00'),
                'course' => $course1,
                'customer' => $user1,
                'createdAt' => new \DateTimeImmutable('2022-07-15 10:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $course1->getPrice(),
                'expiresAt' => new \DateTimeImmutable('2022-10-10 10:00:00'),
                'course' => $course1,
                'customer' => $user1,
                'createdAt' => new \DateTimeImmutable('2022-09-10 10:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $course2->getPrice(),
                'course' => $course2,
                'customer' => $user1,
                'createdAt' => new \DateTimeImmutable('2022-09-10 10:00:00'),
            ],
        ];
        foreach ($transactions as $transaction) {
            $tmp = new Transaction();
            $tmp->setType($transaction['type'])
                ->setCourse($transaction['course'] ?? null)
                ->setCustomer($transaction['customer'])
                ->setCreatedAt($transaction['createdAt'])
                ->setAmount($transaction['amount']);
            if (isset($transaction['expiresAt'])) {
                $tmp->setExpiresAt($transaction['expiresAt']);
            }
            $manager->persist($tmp);
        }
        $manager->flush();
    }
}
