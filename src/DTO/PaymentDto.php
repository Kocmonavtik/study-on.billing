<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class PaymentDto
{
    /**
     * @Serializer\Type("bool")
     */
    public bool $status;

    /**
     * @Serializer\Type("string")
     */
    public string $courseType;

    /**
     * @Serializer\Type("DateTimeImmutable")
     * @Serializer\SkipWhenEmpty
     */
    public ?\DateTimeImmutable $expiresAt;
}