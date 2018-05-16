<?php

namespace Gorgo\Bundle\EmailDebugBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugEmailTemplateCommand extends Command
{
    const NAME = 'gorgo:debug:email:template';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @inheritDoc
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct(self::NAME);

        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Displays current email templates for an application')
            ->addOption(
                'template',
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of email template to be debugged.'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('template')) {
            return $this->processList($output);
        } else {
            return $this->processTemplate($input, $output);
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function processList(OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([
            'ID',
            'NAME',
            'ENTITY CLASS',
            'TYPE',
            'SYSTEM',
            'VISIBLE',
            'EDITABLE',
            'PARENT',
        ])->setRows([]);

        $templates = $this->getRepository()->findAll();
        /** @var EmailTemplate $template */
        foreach ($templates as $template) {
            $table->addRow([
                $template->getId(),
                $template->getName(),
                $template->getEntityName(),
                $template->getType(),
                $this->processBool($template->getIsSystem()),
                $this->processBool($template->isVisible()),
                $this->processBool($template->getIsEditable()),
                $template->getParent() ?: 'N/A',
            ]);
        }

        $table->render();

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function processTemplate(InputInterface $input, OutputInterface $output)
    {
        $templateName = $input->getOption('template');
        $template = $this->getRepository()->findByName($templateName);
        if (!$template) {
            $output->writeln(sprintf('Template "%s" not found', $templateName));

            return 1;
        }

        $output->writeln(sprintf('@name = %s', $template->getName()));
        if ($template->getEntityName()) {
            $output->writeln(sprintf('@entityName = %s', $template->getEntityName()));
        }
        $output->writeln(sprintf('@subject = %s', $template->getSubject()));
        $output->writeln(sprintf('@isSystem = %s', $template->getIsSystem() ? 1 : 0));
        $output->writeln(sprintf('@isEditable = %s', $template->getIsEditable() ? 1 : 0));
        $output->writeln('');
        $output->writeln($template->getContent());

        return 0;
    }

    /**
     * @return ObjectRepository|EmailTemplateRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(EmailTemplate::class);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function processBool($value)
    {
        return $value ? 'Yes' : 'No';
    }
}