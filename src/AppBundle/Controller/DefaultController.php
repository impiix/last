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
     * @param $username
     *
     * @return Response
     */
    public function followAction(Request $request, $username)
    {
        $response = new Response();
        $response->headers->set("Content-Type", "application/json");

        $description = "";

        try {
            $updated = $this->get("last.service")->follow($username, $this->get("session")->get('token'));
            if ($updated) {
                $description = $updated['artist'] . ' - ' . $updated['name'];
            }
            $updated =  $updated ? 'yes' : 'no';
        } catch (LastException $e) {
            $updated = 'error';
        }
        $response->setContent(json_encode(['updated' => $updated, 'description' => $description]));

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

        $response = new Response();
        $response->headers->set("Content-Type", "application/json");

        if (!$form->isValid()) {
            if ($form->isSubmitted()) {
                $errors = $form->getErrors(true);
                $response->setContent(json_encode(['error' => $errors]));
            }

            return $response;
        }

        $submit = $form->getClickedButton()->getName();
        $username = $form['name']->getData();
        $type = $form['type']->getData();

        if (!$token = $this->get("session")->get('token')) {
            $this->get("session")->set("name", $username);
            $this->get("session")->set("type", $type);
            $this->get("session")->set("submit", $submit);

            $url = str_replace(
                [
                    "{client_id}",
                    "{redirect_uri}"
                ],
                [
                    $this->container->getParameter("spotify_client_id"),
                    $request->getSchemeAndHttpHost() . $request->getPathInfo()
                ],
                $this->container->getParameter("spotify_auth_url")
            );

            $response->setContent(json_encode([
                'result'    => 'redirect',
                'url'       => $url,
            ]));
        } else {
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

        if ($token = $request->query->get("access_token")) {
            $this->get('session')->set('token', $token);
            if ($this->get("session")->get("submit") != LastFormType::SUBMIT_FOLLOW) {
                $this->get("old_sound_rabbit_mq.last_producer")->publish(
                    serialize(
                        [
                            "username" => $this->get("session")->get("name"),
                            "token" => $token,
                            "type" => $this->get("session")->get("type")
                        ]
                    )
                );
                $this->get("session")->getFlashBag()->add("info", "Added to queue.");
            } else {
                $this->get("session")->set("follow", true);
            }

            return $this->redirectToRoute("homepage");
        }

        $follow = false;

        if ($this->get("session")->get("follow")) {
            $this->get('session')->remove('follow');
            $follow = $this->get('session')->get('name');
        }

        $viewData = [
            'form'      => $form->createView(),
            'follow'    => $follow
        ];

        return $this->render('AppBundle:default:index.html.twig', $viewData);
    }
}
