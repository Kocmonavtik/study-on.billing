<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Payment
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    private const TYPE_OPERATION = [
        'payment' => 1,
        'deposit' => 2
    ];
    public function deposit(Users $user, float $amount)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $transaction = new Transaction();
            $transaction->setCustomer($user)
                ->setType(self::TYPE_OPERATION['deposit'])
                ->setAmount($amount);
            $user->setBalace($user->getBalance() + $amount);
            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->em->getConnection()->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
    }
    public function payment(Users $user, Course $course): Transaction
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() < $course->getPrice()) {
                throw new \Exception('На счету недостаточно средств', Response::HTTP_NOT_ACCEPTABLE);
            }
            $transaction = new Transaction();

            $transaction->setCustomer($user)
                ->setType(self::TYPE_OPERATION['payment'])
                ->setAmount($course->getPrice())
                ->setCourse($course);
            if ($course->getType() === 'rent') {
                $expiresAt = (new \DateTimeImmutable())->add(new \DateInterval('P1W'));
                $transaction->setExpiresAt($expiresAt);
            }
            $user->setBalace($user->getBalance() - $course->getPrice());
            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->em->getConnection()->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
        return $transaction;
    }
}
