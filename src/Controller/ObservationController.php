<?php
namespace App\Controller;
use App\Entity\Bird;
use App\Entity\Player;
use App\Entity\Tournament;
use App\Form\ObservationEditType;
use App\Services\MailerManager;
use App\Services\TournamentManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Observation;
use App\Form\ObservationType;
/**
 * Class ObservationController
 * @package App\Controller
 * @Route("interface/", name="nao_interface_")
 */
class ObservationController extends Controller
{
    /**
     * List of observations by pages
     * @Route("observations/{page}", requirements={"page" = "\d+"}, name="observations")
     * @Template("interface/observations.html.twig")
     * @param $page
     * @return array
     */
    public function obsListShow($page)
    {
        $em = $this->getDoctrine()->getManager();
        $observations = $em->getRepository(Observation::class)->findObservationsPublished($page);
        $nbObservations = $em->getRepository(Observation::class)->findAllObservationsCountByStatut();
        $nbPages = ceil($nbObservations / 10);

        if($page != 1 && $page > $nbPages)
        {
            throw new NotFoundHttpException("La page que vous essayez d'atteindre n'existe pas");
        }
        $pagination = [
              'page' => $page,
              'nbPages' => $nbPages,
              'nbObservations' => $nbObservations
        ];
        return array(
            'observations' => $observations,
            'pagination' => $pagination,
        );
    }

    /**
     * List of observations by pages of the current user
     * @Route("mes-observations/{page}", requirements={"page" = "\d+"}, name="mes_observations")
     * @Template("interface/mes-observations.html.twig")
     * @param $page
     * @return array
     */
    public function obsMyListShow($page)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $observations = $em->getRepository(Observation::class)->findAllObsByUser($page, $user->getId());
        $nbObservations = $em->getRepository(Observation::class)->getCountOfAllByUser($user->getId());

        $nbPages = ceil($nbObservations / 10);


        if($page != 1 && $page > $nbPages)
        {
            throw new NotFoundHttpException("La page que vous essayez d'atteindre n'existe pas");
        }
        $pagination = [
            'page' => $page,
            'nbPages' => $nbPages,
            'nbObservations' => $nbObservations
        ];
        return array(
            'observations' => $observations,
            'pagination' => $pagination,
        );
    }

    /**
     * @Route("observation/ajouter", name="obs_ajouter")
     * @Template("interface/observation/ajouter.html.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function newAction(Request $request)
    {
        $observation = new Observation();

        $form = $this->createForm(ObservationType::class, $observation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $currentUser = $this->getUser();
            if ($currentUser->hasRole('ROLE_NATURALIST')) {
                $observation->setStatut(2);
            }
            $observation->setUser($currentUser);
            $em->persist($observation);
            $em->flush();
            return $this->redirectToRoute("nao_interface_mes_observations", ['page' => 1]);
        }
        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("observation/edit/{id}", requirements={"id" = "\d+"}, name="obs_edit")
     * @Template("interface/observation/editer.html.twig")
     * @param Request $request
     * @param $id
     * @param TournamentManager $tournamentManager
     * @param MailerManager $mailerManager
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function editAction(Request $request, $id, TournamentManager $tournamentManager, MailerManager $mailerManager)
    {
        $this->denyAccessUnlessGranted('ROLE_NATURALIST', null, 'Vous ne pouvez pas accéder à cette page');

        $em = $this->getDoctrine()->getManager();

        $observation = $em->getRepository(Observation::class)->findOneBy(['id' => $id]);

        if(!$observation)
        {
            throw new NotFoundHttpException("L'observation que vous voulez éditer n'existe pas");
        }

        $form = $this->createForm(ObservationEditType::class, $observation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $observation->setStatut(2);
            $tournamentManager->addPointsToTournament($observation->getUser());
            $mailerManager->sendConfirmObservation($observation->getUser(), $observation);
            $em->persist($observation);
            $em->flush();
            $this->addFlash('success', 'Vous avez correctement modifié et validé une observation');
            return $this->redirectToRoute("nao_interface_validations", ['page' => 1]);
        }
        return array(
            'form' => $form->createView(),
            'observation' => $observation
        );
    }

    /**
     * @Route("observation/statut/{id}/{statut}", requirements={"id" = "\d+", "statut" = "\d+"}, name="obs_statut")
     * @param $id
     * @param $statut
     * @param MailerManager $mailerManager
     * @param TournamentManager $tournamentManager
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function statutAction($id, $statut, MailerManager $mailerManager, TournamentManager $tournamentManager)
    {
        $em = $this->getDoctrine()->getManager();

        $observation = $em->getRepository(Observation::class)->findOneBy(['id' => $id]);

        if(!$observation)
        {
            throw new NotFoundHttpException("L'observation que vous voulez éditer n'existe pas");
        }

        if($statut != 0 AND $statut != 2) {
            throw new NotFoundHttpException("La page que vous essayez d'atteindre n'existe pas");
        }

        if($statut == 2)
        {
            $observation->setStatut(2);
            $tournamentManager->addPointsToTournament($observation->getUser());
            $mailerManager->sendConfirmObservation($observation->getUser(), $observation);
            $em->persist($observation);
            $em->flush();
            $this->addFlash('success', 'Vous avez correctement validé une observation');
            return $this->redirectToRoute("nao_interface_validations", ['page' => 1]);
        }

        if($statut == 0)
        {
            $observation->setStatut(0);
            $mailerManager->sendDeclinedObservation($observation->getUser(), $observation);
            $em->persist($observation);
            $em->flush();
            $this->addFlash('info', 'Vous avez correctement rejeté cette observation');
            return $this->redirectToRoute("nao_interface_validations", ['page' => 1]);
        }

    }
    /**
     * @Route("observation/{id}", name="obs_show")
     * @Template("interface/observation/afficher.html.twig")
     * @param $id
     * @return array
     */
    public function showAction($id)
    {

        $id = intval($id);

        $em = $this->getDoctrine()->getManager();

        $observation = $em->getRepository(Observation::class)->findOneBy(['id' => $id]);

        if(!$observation)
        {
            throw new NotFoundHttpException("L'observation que vous voulez voir n'existe pas");
        }

        $user = $observation->getUser();

        return array(
            'observation' => $observation,
            'user' => $user
        );

    }

    /**
     * @Route("/oiseau/{name}", name="oiseau")
     * @param Request $request
     * @param String $name
     * @return Response
     */
    public function findBirdAction(Request $request, String $name)
    {
        $em = $this->getDoctrine()->getManager();
        $observation = $this->getDoctrine()
            ->getRepository(Observation::class)
            ->findBylatitude($name);
        if (!$observation) {
            throw $this->createNotFoundException(
                'rien trouvé pour '.$name
            );
        } else {
            echo 'Votre oiseau est : ' . $name;
            dump($observation);
        }
        return new Response($name);
    }
    /**
     * @Route("carte", name="carte")
     * @Template("interface/observation/carte.html.twig")
     * @param Request $request
     */
    public function carteAction(Request $request)
    {
    }

    /**
     * @Route("map-search", name="map_search")
     * @param Request $request
     * @return JsonResponse
     */
    public function searchBirdMap(Request $request)
    {
        $birdNomVern = $request->get('nomVern');
        $em = $this->getDoctrine()->getManager();
        $observations = $em->getRepository(Observation::class)->findBirdWithObservation($birdNomVern);
        return new JsonResponse($observations);
    }
}