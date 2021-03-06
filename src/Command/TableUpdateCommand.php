<?php
namespace DAG\JIRA\BuildsTable\Command;

use DAG\JIRA\BuildsTable\Client;
use DAG\JIRA\BuildsTable\HTMLTableBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TableUpdateCommand
 */
final class TableUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->addArgument('jira_user', InputArgument::REQUIRED, '')
            ->addArgument('jira_password', InputArgument::REQUIRED, '')
            ->addArgument('jira_url', InputArgument::REQUIRED, '')
            ->addArgument('content', InputArgument::REQUIRED, '')
            ->addArgument('page_id', InputArgument::REQUIRED, '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentToAdd = $input->getArgument('content');

        $client = new Client(
            $input->getArgument('jira_user'),
            $input->getArgument('jira_password'),
            $input->getArgument('jira_url')
        );

        $page = $client->getPage($input->getArgument('page_id'));

        $output->writeln("Current HTML content is:");
        $output->writeln($page['body']['view']['value']);

        $output->writeln("The content to add is:");
        $output->writeln($contentToAdd);

        $builder = new HTMLTableBuilder();
        $html = $builder->build($page['body']['view']['value'], $contentToAdd);

        $output->writeln("HTML content generated:");
        $output->writeln($html);

        $newPage = [
            'title' => $page['title'],
            'type' => $page['type'],
            'version' => [
                'number' => $page['version']['number'] + 1,
            ],
            'body' => [
                'storage' => [
                    'value' => $html,
                    "representation" => "storage",
                ],
            ],
        ];

        if (isset($page['ancestors'])) {
            $newPage['ancestors'] = [];
            foreach ($page['ancestors'] as $ancestor) {
                $newPage['ancestors'][] = ['id' => $ancestor['id']];
            }
        }

        $client->sendPage($input->getArgument('page_id'), $newPage);
    }
}
