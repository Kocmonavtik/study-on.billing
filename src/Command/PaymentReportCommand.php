<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use App\Repository\TransactionRepository;
use App\Repository\UsersRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use App\Service\Twig;
use Symfony\Component\Mime\Email;
use Symfony\Component\Console\Attribute\AsCommand;

class PaymentReportCommand extends Command
{
    protected static $defaultName = 'payment:report';
    private Twig $twig;
    private TransactionRepository $transactionRepository;
    private MailerInterface $mailer;

    public function __construct(
        Twig $twig,
        TransactionRepository $transactionRepository,
        MailerInterface $mailer,
        string $name = null
    ) {
        $this->twig = $twig;
        $this->transactionRepository = $transactionRepository;
        $this->mailer = $mailer;
        parent::__construct($name);
    }
    protected function configure()
    {
        $this->setDescription('Report selling courses');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transactions = $this->transactionRepository->getPayStatisticPerMonth();

        if($transactions) {
            $endDate = (new \DateTime())->format('d.m.Y');
            $startDate = (new \DateTime())->modify('-1 month')->format('d.m.Y');

            $total = array_sum(array_column($transactions, 'total_amount'));

            $reportTemplate = $this->twig->render(
                'mail/paymentReport.html.twig',
                [
                    'transactions' => $transactions,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'total' => $total
                ]
            );
            $mail = (new Email())
                ->to($_ENV['REPORT_EMAIL'])
                ->from('notifier@study_on.local')
                ->subject('Отчет об оплаченных курсах')
                ->html($reportTemplate);
            try{
                $this->mailer->send($mail);
            } catch (TransportException $exception) {
                $output->writeln($exception->getMessage());
                $output->writeln('Ошибка при отправке отчета');

                return Command::FAILURE;
            }
        }
        $output->writeln('Отчет успешно отправлен!');
        return Command::SUCCESS;
    }
}
