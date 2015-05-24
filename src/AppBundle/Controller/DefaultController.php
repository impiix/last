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
        if( $token = $request->query->get("access_token")) {
            $username = "icesahara";
            $this->get("old_sound_rabbit_mq.last_producer")->publish(serialize([
                "username"  => $username,
                "token"     => $token
            ]));
            $grabService = $this->get("last.service");
           // $tracks = $grabService->grab($username, $token);



        }

        return $this->render('default/index.html.twig',
            [
                'client_id' => $this->container->getParameter("spotify_client_id")
            ]);
    }
}
