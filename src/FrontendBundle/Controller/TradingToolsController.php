<?php

namespace FrontendBundle\Controller;

use DataModelBundle\Entity\Symbol;
use DataModelBundle\Repository\NetworkRepository;
use DataModelBundle\Repository\SymbolRepository;
use DataModelBundle\Repository\TradeRepository;
use Doctrine\ORM\Mapping\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TradingToolsController extends Controller
{
    /**
     * @param Symbol $symbol
     * @param $exchange
     * @param $timeOffsetSeconds
     * @return Response
     */
    public function getSymbolDataAction($symbol, $exchange, $timeOffsetSeconds)
    {
        /** @var Symbol $symbol */
        $symbol = $this->get('nc.repo.symbol')->findOneBy(['name' => $symbol]);
        /** @var TradeRepository $tradeRepo */
        $tradeRepo = $this->get('nc.repo.trade');
        $trades = $tradeRepo->findSymbolTrades($symbol, $exchange, new \DateTime('-' . $timeOffsetSeconds . ' seconds'));
        $serializerService = $this->get('nc.serializer');
        return new Response($serializerService->serialize($trades, ['symbol-data']));
    }

    public function tradingToolsAction()
    {
        /** @var TradeRepository $tradeRepo */
        $tradeRepo = $this->get('nc.repo.trade');
        /** @var SymbolRepository $symbolRepo */
        $symbolRepo = $this->get('nc.repo.symbol');
        /** @var NetworkRepository $networkRepo */
        $networkRepo = $this->get('nc.repo.network');
        $supportedNetworks = $networkRepo->findGenericNetworks();
        return $this->render('@Frontend/TradingTools/tradingTools.main.html.twig', [
            'knownExchanges' => $tradeRepo->getKnownExchanges(),
            'supportedNetworks' => $supportedNetworks,
            'knownSymbols' => $symbolRepo->findSupportedSymbols(false, false)
        ]);
    }

    public function getHighRaisesAction(Request $request)
    {
        $serializer = $this->get('nc.serializer');
        /** @var TradeRepository $tradesRepo */
        $tradesRepo = $this->get('nc.repo.trade');
        /** @var SymbolRepository $symbolRepo */
        $symbolRepo = $this->get('nc.repo.symbol');
        $knownSymbols = $symbolRepo->findKnownSymbols();
        $raiseLimit = $request->get('raiseLimit', 5);
        $exchange = $request->get('exchangeName', 'bittrex');
        $timescope = $request->get('timeScope', '-10 minutes');

        $highRaises = [];
        foreach ($knownSymbols as $knownSymbol) {
            list($old, $current) = $tradesRepo->findTradesForTimespan($knownSymbol, $exchange, new \DateTime($timescope), new \DateTime());
            if (!empty($old) && !empty($current)) {
                if ( (($current->getPrice() / $old->getPrice()) - 1) * 100 > $raiseLimit) {
                    $highRaises[] = [
                        'old' => $old,
                        'current' => $current
                    ];
                }
            }
        }

        $result = $serializer->serialize($highRaises, ['high-raises']);
        return new Response($result);
    }

    public function getKnownSymbolsAction()
    {
        /** @var SymbolRepository $symbolRepo */
        $symbolRepo = $this->get('nc.repo.symbol');
        $responseList = [];
        foreach($symbolRepo->findKnownSymbols() as $symbol) {
            $strip = [
                'BTC',
                'ETH',
                'USDT',
                'USD',
                'XMR',
            ];
            $name = $symbol->getName();
            foreach($strip as $trigger) {
                if (strpos($name, $trigger) === 0) {
                    $name = substr($name, strlen($trigger));
                }
            }
            if (!empty($name)) {
                $responseList[$name] = 1;
            }
        }
        return new Response(json_encode(array_keys($responseList)));
    }
}
