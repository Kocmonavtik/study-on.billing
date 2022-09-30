<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="CourseCreationRequestDto",
 *     description="CoursCreationRequestDto"
 * )
 * Class CourseDto
 * @package App\DTO
 */
class CourseCreationRequestDto
{
    /**
     * @OA\Property(
     *     format="string",
     *     title="title",
     *     description="Название курса",
     *     example="Программирование"
     * )
     * @Serializer\Type("string")
     */
    public string $type;

    /**
     * @OA\Property(
     *     format="string",
     *     title="title",
     *     description="Название курса",
     *     example="Программирование"
     * )
     * @Serializer\Type("string")
     */
    public string $title;

    /**
     * @OA\Property(
     *     format="string",
     *     title="code",
     *     description="Код курса",
     *     example="0011FHFEH152"
     * )
     * @Serializer\Type("string")
     */
    public string $code;

    /**
     * @OA\Property(
     *     format="float",
     *     title="price",
     *     description="Стоимость курса",
     *     example="15000"
     * )
     * @Serializer\Type("float")
     */
    public float $price;
}