<?php

namespace AppBundle\Controller;

use AppBundle\Exception\LastException;
use AppBundle\FormType\LastFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/orders.json", name="orders")
     * @param Request $request
     *
     * @return Response
     */
    public function getOrdersAction(Request $request)
    {
        $orders = $this->get("order.service")->getRecent();

        $json = $this->get("jms_serializer")->serialize($orders, 'json');

        $response = new Response($json);
        $response->headers->set("Content-Type", "application/json");

        return $response;
    }

    /**
     * @Route("/follow/{username}", name="follow")
     * @param Request $request
     *
     * @return Response
     */
    public function followAction(Request $request, $username)
    {
        $this->get("last.service")->follow($username);

        $response = new Response(['message' => 'ok']);

        return $response;
    }

    /**
     * @Route("/", name="post")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $form = $this->createForm(LastFormType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $redirectUri = $request->getSchemeAndHttpHost() . $request->getPathInfo();

            $submit = $form->getClickedButton()->getName();

            $clientId = $this->container->getParameter("spotify_client_id");

            $url = str_replace(
                ["{client_id}", "{redirect_uri}"],
                [$clientId, $redirectUri],
                $this->container->getParameter("spotify_auth_url")
            );

            $this->get("session")->set("name", $form['name']->getData());
            $this->get("session")->set("type", $form['type']->getData());
            $this->get("session")->set("submit", $submit);

            $response = new Response();
            $response->headers->set("Content-Type", "application/json");

            if (!$token = $this->get("session")->get('token')) {
                $response->setContent(json_encode([
                    'result'    => 'redirect',
                    'url'       => $url,
                ]));
            } else {
                $username = $form['name']->getData();
                $type = $this->get("session")->get("type");
                try {
                    $this->get("last.service")->grabFromLast($username, $type);
                } catch (LastException $e) {
                    $response->setContent(json_encode([
                        'result'    => 'error',
                        'message'   => $e->getMessage(),
                    ]));

                    return $response;
                }

                if ($submit != 'follow') {
                    $this->get("old_sound_rabbit_mq.last_producer")->publish(
                        serialize(
                            [
                                "username"  => $username,
                                "token"     => $token,
                                "type"      => $type
                            ]
                        )
                    );
                }

                $response->setContent(json_encode([
                    'result' => 'ok',
                ]));
            }

            return $response;
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            $this->get("session")->getFlashBag()->add("error", (string)$errors);
        }
    }

    /**
     * @Route("/", name="homepage")
     * @Method("GET")
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(LastFormType::class);


        $form->handleRequest($request);

        $follow = false;

        if ($token = $request->query->get("access_token")) {
            $this->get('session')->set('token', $token);
            $username = $this->get("session")->get("name");
            if ($this->get("session")->get("submit") == LastFormType::SUBMIT_FOLLOW) {
                $follow = true;
            } else {
                $this->get("old_sound_rabbit_mq.last_producer")->publish(
                    serialize(
                        [
                            "username" => $username,
                            "token" => $token,
                            "type" => $this->get("session")->get("type")
                        ]
                    )
                );
                $this->get("session")->getFlashBag()->add("info", "Added to queue.");
                return $this->redirectToRoute("homepage");
            }
        }

        return $this->render(
            'AppBundle:default:index.html.twig',
            [
                'form'      => $form->createView(),
                'follow'    => $follow
            ]
        );
    }
}
