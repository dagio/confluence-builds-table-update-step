<?php
namespace DAG\JIRA\BuildsTable\Command;

use DAG\JIRA\BuildsTable\Client;
use DAG\JIRA\BuildsTable\ContentParser;
use DAG\JIRA\BuildsTable\JSONBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class JSONUpdateCommand
 */
final class JSONUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('update')
            ->addArgument('jira_user', InputArgument::REQUIRED, '')
            ->addArgument('jira_password', InputArgument::REQUIRED, '')
            ->addArgument('jira_url', InputArgument::REQUIRED, '')
            ->addArgument('page_id', InputArgument::REQUIRED, '')
            ->addArgument('attachment_filename', InputArgument::REQUIRED, '')
            ->addArgument('content', InputArgument::REQUIRED, '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client(
            $input->getArgument('jira_user'),
            $input->getArgument('jira_password'),
            $input->getArgument('jira_url')
        );

        $attachment = $client->getAttachment(
            $input->getArgument('page_id'),
            $input->getArgument('attachment_filename')
        );

        if (count($attachment['results']) > 0) {
            $attachmentId = $attachment['results'][0]['id'];
            $existingBuilds = $client->downloadAttachmentContent(
                $input->getArgument('page_id'),
                $input->getArgument('attachment_filename')
            );
        } else {
            $attachmentId = null;
            $existingBuilds = [];
        }

        $builder = new JSONBuilder(new ContentParser());
        $builds = $builder->build($existingBuilds, $input->getArgument('content'), $_SERVER);
        $json = json_encode($builds);

        $output->writeln("JSON generated:");
        $output->writeln($json);

        $client->uploadAttachment(
            $input->getArgument('page_id'),
            $input->getArgument('attachment_filename'),
            $json,
            $attachmentId
        );
    }
}
