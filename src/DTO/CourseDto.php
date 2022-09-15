<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class CourseDto
{
    /**
     * @Serializer\Type("string")
     */
    public string $code;

    /**
     * @Serializer\Type("string")
     */
    public string $type;

    /**
     * @Serializer\Type("float")
     */
    public float $price;

}