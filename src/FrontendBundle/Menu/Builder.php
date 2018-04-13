<?php

namespace FrontendBundle\Menu;

use DataModelBundle\Entity\Network;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Trading tools', ['route' => 'trading_tools_main']);
        $menu->addChild('Create Network', ['route' => 'frontend_create_network']);
        $menu->addChild('Show Networks', ['route' => 'frontend_list_network']);

        return $menu;
    }

    public function networkMenu(FactoryInterface $factory, array $options)
    {
        /** @var Network $network */
        $network = $options['network'];

        $menu = $factory->createItem('root');

        $menu->addChild('Show', [
            'route' => 'frontend_network_show',
            'routeParameters' => ['id' => $network->getId()]
        ]);

        $menu->addChild('Predict', [
            'route' => 'frontend_network_predict',
            'routeParameters' => ['id' => $network->getId()]
        ]);
        if (!empty($network->getFilePath())) {
            $menu->addChild('Train', [
                'route' => 'frontend_network_train',
                'routeParameters' => ['id' => $network->getId()],
                'extras' => [
                    'routes' => ['frontend_network_train', 'frontend_network_training_status']
                ]
            ]);
            $menu->addChild('Plot', [
                'route' => 'frontend_network_plot',
                'routeParameters' => ['id' => $network->getId()]
            ]);

            $menu->addChild('Edit', [
                'route' => 'frontend_network_edit',
                'routeParameters' => ['id' => $network->getId()],
            ]);
        }

        return $menu;
    }
}