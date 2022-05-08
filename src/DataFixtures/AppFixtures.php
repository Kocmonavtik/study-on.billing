<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Users;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
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
        $manager->persist($user1);

        $user2 = new Users();
        $user2->setEmail('test1Email@gmail.com');
        $user2->setRoles(['ROLE_SUPER_ADMIN']);
        $user2->setPassword($this->passwordHasher->hashPassword(
            $user2,
            '12345'
        ));
        $manager->persist($user2);
        $manager->flush();
    }
}
