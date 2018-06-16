<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Services\MailerManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontController extends Controller
{
    /**
     * @Route("/", name="nao_home")
     */
    public function index()
    {
        return $this->render('front/index.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }

    /**
     * @Route("/association", name="nao_association")
     */
    public function association()
    {
        return $this->render('front/association.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }

    /**
     * @Route("/contact", name="nao_contact")
     * @param Request $request
     * @param MailerManager $mailerManager
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function contact(Request $request, MailerManager $mailerManager)
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mailerManager->contactSend($form->getData());
            $this->addFlash('success', 'Votre message a bien été envoyé. Vous recevrez une réponse sous un délai de 24 heures.');
        }
        return $this->render('front/contact.html.twig', [
            'contact' => $form->createView(),
        ]);
    }
	
	    /**
     * @Route("/carte", name="carte")
     */

	 
	    public function carteAction(Request $request)
    {
        //Appelle le repo, le repo appelle la table observation, prend un de ses objets , et qui va retourner
        //la position lattitude et longitude de cet objet
        return $this->render('carte.html');
    }
	
	
		    /**
     * @Route("/google", name="google")
     */

	 
	    public function googleAction(Request $request)
    {
        //Appelle le repo, le repo appelle la table observation, prend un de ses objets , et qui va retourner
        //la position lattitude et longitude de cet objet
        return $this->render('googlemaps.html');
    }
}
