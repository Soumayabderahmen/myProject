<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\UserProfileType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/patient')]
class PatientController extends AbstractController
{
    private $userPasswordEncoder;
    public function __construct( UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }
    #[Route('/', name: 'app_patient_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('patient/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, SluggerInterface $slugger): Response
    {
        $user = new Patient();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photo->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                 
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setImage($newFilename);
            }
            $this->addFlash('success','Ajout avec Success');
            if ($user->getPassword()&&$user->getConfirmPassword()) {
                $user->setPassword(
                    $this->userPasswordEncoder->encodePassword($user, $user->getPassword())
                );
                $user->setConfirmPassword(
                    $this->userPasswordEncoder->encodePassword($user, $user->getConfirmPassword())
                );
                $user->eraseCredentials();
            }
            
         
            $roles[]='ROLE_PATIENT';
            $user->setRoles($roles);
            $userRepository->save($user, true);

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('patient/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
//Mobile
    #[Route('/All', name: 'app_patients_liste')]
    public function ListePatient(UserRepository $patient, SerializerInterface $serializer)
    {
        $patient = $patient->findAll();
        $patientNormailize = $serializer->serialize($patient, 'json', ['groups' => "medecin"]);

        $json = json_encode($patientNormailize);
        return  new response($json);
    }
    #[Route('/patientJson/{id}', name: 'app_patient_seule')]
    public function PatientId($id,UserRepository $patient, SerializerInterface $serializer)
    {
        $patient = $patient->find($id);
        $patientNormailize = $serializer->serialize($patient, 'json', ['groups' => "medecin"]);

        $json = json_encode($patientNormailize);
        return  new response($json);
    }

    #[Route('/delete/patientJson/{id}', name: 'app_patient_delete_seule')]
    public function deletePatientJson($id,UserRepository $patient, NormalizerInterface $normalizerInterface,Request $request)
    {
        $em=$this->getDoctrine()->getManager();
        $patient=$em->getRepository(Patient::class)->find($id);
        $em->remove($patient);
        $em->flush();
        $jsonContent=$normalizerInterface->normalize($patient,'json',['groups'=>'medecin']);
        return  new Response("Patient deleted successfully". json_encode($jsonContent));
    }
    #[Route('/add/PatientJson', name: 'app_patient_new_json')]
    public function addPatientJson(Request $request, NormalizerInterface $normalizerInterface): Response
    {   
        $em=$this->getDoctrine()->getManager();
        $patient = new Patient();
        $patient->setEmail($request->get('email'));
        $patient->setPassword($request->get('password'));
        $patient->setConfirmPassword($request->get('confirm_password'));
        $patient->setNom($request->get('nom'));
        $patient->setPrenom($request->get('prenom'));
        $patient->setCin($request->get('cin'));
        $patient->setSexe($request->get('sexe'));
        $patient->setTelephone($request->get('telephone'));
        $patient->setGouvernorat($request->get('gouvernorat'));
        $patient->setAdresse($request->get('adresse'));
        $patient->setImage($request->get('photo'));
        $em->persist($patient);
        $em->flush();
        $jsonContent=$normalizerInterface->normalize($patient,'json',['groups'=>'medecin']);
        return new Response(json_encode($jsonContent));

      
       

    }
    #[Route('/edit/{id}/PatientJson', name: 'app_patient_edit_json')]
    public function editPatientJson(Request $request, $id,NormalizerInterface $normalizerInterface): Response
    {   
        $em=$this->getDoctrine()->getManager();
        $patient=$em->getRepository(Patient::class)->find($id);
       
        $patient->setEmail($request->get('email'));
        $patient->setPassword($request->get('password'));
        $patient->setConfirmPassword($request->get('confirm_password'));
        $patient->setNom($request->get('nom'));
        $patient->setPrenom($request->get('prenom'));
        $patient->setCin($request->get('cin'));
        $patient->setSexe($request->get('sexe'));
        $patient->setTelephone($request->get('telephone'));
        $patient->setGouvernorat($request->get('gouvernorat'));
        $patient->setAdresse($request->get('adresse'));
        $patient->setImage($request->get('photo'));
       
        

       
        $em->flush();
        $jsonContent=$normalizerInterface->normalize($patient,'json',['groups'=>'medecin']);
        return new Response(json_encode($jsonContent));

      
       

    }


    #[Route('/{id}', name: 'app_patient_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('patient/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository,SluggerInterface $slugger): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photo) {
                $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photo->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                 
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setImage($newFilename);
            }
            $this->addFlash('success','Modifier avec Success');

            $userRepository->save($user, true);

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('patient/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_patient_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/profile/patient', name: 'app_patient_profile')]
    public function profile(Request $request, SluggerInterface $slugger): Response
    {

        $patient = $this->getUser();

        if ($patient instanceof Patient) {
            $form = $this->createForm(UserProfileType::class, $patient);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $photo = $form->get('photo')->getData();

                // this condition is needed because the 'brochure' field is not required
                // so the PDF file must be processed only when a file is uploaded
                if ($photo) {
                    $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photo->guessExtension();
    
                    // Move the file to the directory where brochures are stored
                    try {
                        $photo->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }
    
                    // updates the 'brochureFilename' property to store the PDF file name
                    // instead of its contents
                    $patient->setImage($newFilename);
                }
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();

                $this->addFlash('success', 'Profil mis à jour avec succès.');

                return $this->redirectToRoute('app_patient_profile');
            }

            return $this->render('profile/patient_profile.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        throw new \LogicException('Erreur : l\'utilisateur courant n\'est pas un admin.');
    
        // return $this->render('profile/med_profile.html.twig');
    }


    #[Route('/patient/change-password', name: 'app_patient_change-password')]
    public function changePassword(Request $request)
    {
        $form = $this->createForm(ChangePasswordType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $password = $form->get('password')->getData();
            $ConfirmPassword = $form->get('confirm_password')->getData();
            $encoder = $this->userPasswordEncoder->encodePassword($user, $password, $ConfirmPassword);
            $user->setPassword($encoder);
            $user->setConfirmPassword($encoder);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été changé avec succès.');

            return $this->redirectToRoute('app_patient_profile');
        }

        return $this->render('patient/change_password_patient.html.twig', [
            'form' => $form->createView(),
        ]);
    }













}