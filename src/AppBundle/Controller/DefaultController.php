<?php

namespace AppBundle\Controller;

use AppBundle\FormType\LastFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {


        $formType = new LastFormType();

        $form = $this->createForm($formType);



        $form->handleRequest($request);

        if ($form->isValid()) {
            $redirectUri = $request->getSchemeAndHttpHost() . $request->getPathInfo();

            $this->get("session")->set("name", $form['name']->getData());
            $this->get("session")->set("type", $form['type']->getData());

            $clientId = $this->container->getParameter("spotify_client_id");

            $url = str_replace(
                ["{client_id}", "{redirect_uri}"],
                [$clientId, $redirectUri],
                $this->container->getParameter("spotify_auth_url")
            );

            return $this->redirect($url);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            $this->get("session")->getFlashBag()->add("error", (string)$errors);
        }

        if ($token = $request->query->get("access_token")) {
            $username = $this->get("session")->get("name");
            $this->get("old_sound_rabbit_mq.last_producer")->publish(
                serialize(
                    [
                        "username" => $username,
                        "token"    => $token,
                        "type"     => $this->get("session")->get("type")
                    ]
                )
            );
            $this->get("session")->getFlashBag()->add("info", "Added to queue.");
            $this->redirect($request->getUri());
        }


        return $this->render(
            'AppBundle:default:index.html.twig',
            [
                'form'      => $form->createView()
            ]
        );
    }
}
