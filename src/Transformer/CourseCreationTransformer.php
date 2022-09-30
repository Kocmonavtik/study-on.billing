<?php

namespace App\Transformer;

use App\DTO\CourseCreationRequestDto;
use App\Entity\Course;

class CourseCreationTransformer
{
    public static function transformtoObject(CourseCreationRequestDto $courseCreation)
    {
        $courseTypes = [
            'rent' => 1,
            'free' => 2,
            'buy' => 3
        ];
        $course = new Course();
        $course->setCode($courseCreation->code)
            ->setTitle($courseCreation->title)
            ->setType($courseTypes[$courseCreation->type])
            ->setPrice($courseCreation->price);

        return $course;
    }
}