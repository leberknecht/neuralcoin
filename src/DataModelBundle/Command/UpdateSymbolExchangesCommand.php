<?php

namespace DataModelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSymbolExchangesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('neuralcoin:update-symbol-exchanges');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $symbolRepo = $em->getRepository('DataModelBundle:Symbol');
        $exchanges = $em->getRepository('DataModelBundle:Trade')->getKnownExchanges();
        foreach($exchanges as $exchange) {
            $symbols = $symbolRepo->findSymbolsForExchange($exchange);
            foreach($symbols as $symbol) {
                if (!in_array($exchange, $symbol->getExchanges())) {
                    $symbol->setExchanges(array_merge($symbol->getExchanges(), [$exchange]));
                    $output->writeln(sprintf(
                        '<fg=green>adding exchange "</fg=green>%s<fg=green>"to symbol "</fg=green>%s<fg=green>"</fg=green>',
                        $exchange, $symbol->getName()
                        ));
                }
            }
        }
        $em->flush();
    }
}
