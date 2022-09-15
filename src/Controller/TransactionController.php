<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use App\Transformer\TransactionResponseTransformer;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("api/v1/transactions")
 */
class TransactionController extends AbstractController
{
    private TransactionRepository $transactionRepository;
    private SerializerInterface $serializer;

    public function __construct
    (
        TransactionRepository $transactionRepository,
        SerializerInterface $serializer
    )
    {
        $this->transactionRepository = $transactionRepository;
        $this->serializer = $serializer;
    }

    private const TYPE_OPERATION = [
        'payment' => 1,
        'deposit' => 2
    ];
    /**
     * @Route('/', name="app_transaction", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $filters = [];
        $filters['type'] = $request->query->get('type') ? self::TYPE_OPERATION[$request->query->get('type')] : null;
        $filters['course_code'] = $request->query->get('course_code');
        $filters['skip_expired'] = $request->query->get('skip_expired');

        $transactions = $this->transactionRepository->findTransactionUserByFilters($this->getUser(), $filters);
        $transactionDto = TransactionResponseTransformer::fromObjects($transactions);
        $transactionResponse = $this->serializer->serialize($transactionDto, 'json');

        $response = new JsonResponse();
        $response->setContent($transactionResponse);
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
