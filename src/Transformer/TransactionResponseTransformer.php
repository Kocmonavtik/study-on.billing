<?php

namespace App\Transformer;

use App\DTO\TransactionDto;
use App\Entity\Transaction;

class TransactionResponseTransformer
{
    public static function fromObjects(array $transactions): array
    {
        $transactionsDto = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $dto = new TransactionDto();
            $dto->id = $transaction->getId();
            $dto->createdAt = $transaction->getCreatedAt();
            $dto->type = $transaction->getType();
            $dto->courseCode = $transaction->getCourse()->getCode() ?: null;
            $dto->amount = $transaction->getAmount();
            $dto->expiresAt = $transaction->getExpiresAt();
            $transactionsDto[] = $dto;
        }
        return $transactionsDto;
    }
}
