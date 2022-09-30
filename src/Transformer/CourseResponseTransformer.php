<?php

namespace App\Transformer;

use App\DTO\CourseDto;
use App\Entity\Course;

class CourseResponseTransformer
{
    public static function fromObjects(array $courses): array
    {
        $coursesDto = [];
        /** @var Course $course */
        foreach ($courses as $course) {
            $dto = new CourseDto();
            $dto->code = $course->getCode();
            $dto->type = $course->getType();
            $dto->price = $course->getPrice();
            $dto->title = $course->getTitle();
            $coursesDto[] = $dto;
        }
        return $coursesDto;
    }
    public static function fromObject(Course $course): CourseDto
    {
        $dto = new CourseDto();
        $dto->code = $course->getCode();
        $dto->type = $course->getType();
        $dto->price = $course->getPrice();
        $dto->title = $course->getTitle();
        return $dto;
    }
}