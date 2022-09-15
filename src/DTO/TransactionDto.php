<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class TransactionDto
{
    /**
     * @Serializer\Type("integer")
     */
    public int $id;

    /**
     * @Serializer\Type("DateTimeImmutable")
     */
    public \DateTimeImmutable $createdAt;

    /**
     * @Serializer\Type("string")
     */
    public string $type;

    /**
     * @Serializer\Type("string")
     * @Serializer\SkipWhenEmpty
     */
    public ?string $courseCode;

    /**
     * @Serializer\Type("float")
     */
    public float $amount;

    /**
     * @Serializer\Type("DateTimeImmutable")
     * @Serializer\SkipWhenEmpty
     */
    public ?\DateTimeImmutable $expiresAt;


}