<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Repository\UsersRepository;
use App\Service\Twig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentEndingNotificationCommand extends Command
{
    protected static $defaultName = 'payment:ending:notification';
    private Twig $twig;
    private TransactionRepository $transactionRepository;
    private UsersRepository $usersRepository;
    private MailerInterface $mailer;

    public function __construct(
        Twig $twig,
        TransactionRepository $transactionRepository,
        UsersRepository $usersRepository,
        MailerInterface $mailer,
        string $name = null
    ) {
        $this->twig = $twig;
        $this->transactionRepository = $transactionRepository;
        $this->usersRepository = $usersRepository;
        $this->mailer = $mailer;
        parent::__construct($name);
    }
    protected function configure()
    {
        $this->setDescription('Description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->usersRepository->findAll();
        foreach ($users as $user) {
            $expiresTransactions = $this->transactionRepository->findRecentlyExpiredTransactions($user);
            if ($expiresTransactions) {
                $mailTemplate = $this->twig->render(
                    'mail/paymentEndingMailTemplate.html.twig',
                    ['transactions' => $expiresTransactions]
                );
                $mail = (new Email())
                    ->to($user->getEmail())
                    ->from('notifier@study_on.local')
                    ->subject('Окончание срока аренды')
                    ->html($mailTemplate);
                try {
                    $this->mailer->send($mail);
                } catch (TransportException $exception) {
                    $output->writeln($exception->getMessage());
                    $output->writeln('Ошибка при отправке письма пользователю ' . $user->getEmail());
                    return Command::FAILURE;
                }
            }
        }
        $output->writeln('Письма отправлены!');

        return Command::SUCCESS;
    }
}
