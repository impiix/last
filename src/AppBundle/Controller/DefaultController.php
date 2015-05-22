<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/app/example", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $i = 5;
        if( $request->query->get("access_token")) {
            $grabService = $this->get("last.service");
            $tracks = $grabService->grab("icesahara", $request->query->get("access_token"));


            print_r($tracks);
        }

        return $this->render('default/index.html.twig',
            [
                'client_id' => $this->container->getParameter("spotify_client_id")
            ]);
    }
}
