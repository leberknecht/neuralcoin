<?php

namespace FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class QueueController extends Controller
{
    public function getQueueLoadAction()
    {
        $result = $this->get('nc.queue_info')->getAllQueueInformation();
        return new Response($this->get('nc.serializer')->serialize($result));
    }
}
