<?php

namespace App\Controller;

use App\DTO\PaymentDto;
use App\Entity\Users;
use App\Repository\CourseRepository;
use App\Service\Payment;
use App\Transformer\CourseResponseTransformer;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("api/v1/courses")
 */
class CourseController extends AbstractController
{
    private CourseRepository $courseRepository;
    private SerializerInterface $serializer;
    private Payment $payment;
    public function __construct(
        CourseRepository $courseRepository,
        SerializerInterface $serializer,
        Payment $payment
    ) {
        $this->courseRepository = $courseRepository;
        $this->serializer = $serializer;
        $this->payment = $payment;
    }

    /**
    * @Route("/", name="app_course", methods={"GET"})
    */
    public function index(): Response
    {
        $courses = $this->courseRepository->findAll();
        $coursesDto = CourseResponseTransformer::fromObjects($courses);
        $coursesResponse = $this->serializer->serialize($coursesDto, 'json');
        $response = new JsonResponse();
        $response->setContent($coursesResponse);
        return $response;
    }

    /**
     * @Route("/{code}", name="app_course_show", methods={"GET"})
     */
    public function show(string $code): Response
    {
        $course = $this->courseRepository->findOneBy(['code' => $code]);
        $response = new JsonResponse();
        if ($course !== null) {
            $responseCode = Response::HTTP_OK;
            $responseData = CourseResponseTransformer::fromObject($course);
        } else {
            $responseCode = Response::HTTP_NOT_FOUND;
            $responseData = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Курс с кодом ' . $code . ' не найден'
            ];
        }
        $responseData = $this->serializer->serialize($responseData, 'json');
        $response->setStatusCode($responseCode);
        $response->setContent($responseData);
        return $response;
    }
    /**
     * @Route("/{code}/pay", name="app_course_pay", methods={"POST"})
     */
    public function pay(string $code): Response
    {
        $course = $this->courseRepository->findOneBy(['code' => $code]);
        $response = new JsonResponse();
      /*  $responseCode = '';
        $responseData = [];*/
        if (!$course) {
          /*  $responseData = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Курс не найден',
            ];*/
            /*throw new \Exception('Курс не найден', Response::HTTP_NOT_FOUND);*/
        }
        /** @var Users $user */
        $user = $this->getUser();
        try {
            $transaction = $this->payment->payment($user, $course);
        } catch (\Exception $exception) {
            throw new \HttpException($exception->getMessage(), $exception->getCode());
        }
        $expiresAt = $transaction->getExpiresAt();
        $paymentDto = new PaymentDto();
        $paymentDto->status = true;
        $paymentDto->courseType = $course->getType();
        $paymentDto->expiresAt = $expiresAt ?: null;

        $responseData = $this->serializer->serialize($paymentDto, 'json');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($responseData);

        return $response;
    }
}
