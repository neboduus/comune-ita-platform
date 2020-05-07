<?php

namespace App\Command;

use App\Entity\TerminiUtilizzo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HotfixNomeEnteCommand
 * @package App\Command
 * @deprecated
 */
class HotfixNomeEnteCommand extends AbstractCommand
{
    const STRING_FIND = 'Consorzio dei Comuni Trentini.con sede legale in Via Torre Verde, 23 Trento';

    const STRING_REPLACE = 'Comune di Tre Ville sede legale in Via Roma 4/A fraz. Ragoli 38095 Tre Ville (TN)';

    protected function configure()
    {
        $this
            ->setName('ocsdc:hotfix-nomente')
            ->setDescription('Sostituisce il nome Consorzio dei Comuni con Comune di Tre Ville in db');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $terminiUtilizzoRepo = $this->em->getRepository('App:TerminiUtilizzo');
        /** @var TerminiUtilizzo $terminiUtilizzo */
        foreach ($terminiUtilizzoRepo->findAll() as $terminiUtilizzo) {
            $text = $terminiUtilizzo->getText();
            $newText = str_replace(self::STRING_FIND, self::STRING_REPLACE, $text);
            $terminiUtilizzo->setText($newText);
            $this->em->persist($terminiUtilizzo);
        }

        $this->em->flush();

        return 0;
    }
}
