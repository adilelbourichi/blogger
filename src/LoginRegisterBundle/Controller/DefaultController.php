<?php

namespace LoginRegisterBundle\Controller;

use UserBundle\Form\UserType;
use UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        /* fetch all blog entries from the database and sort them by date, from the newest to the oldest*/
        $em = $this->getDoctrine()->getManager();

        $blogs = $em->getRepository('BlogBundle:Blog')->findBy(array(), array('publishedDate'=>'desc'));

        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
            'blogs'=>$blogs
        ]);
    }

    /**
 * @Route("/login/", name="login")
 */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
    }

    /**
     * @Route("/loginWelcome/{username}-{firstName}-{lastName}", name="login-upon-registration")
     */
    public function loginUponRegistrationAction(Request $request, $username,$firstName,$lastName)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login-upon-registration.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
                'username'          => $username,
                'firstName' =>$firstName,
                'lastName' =>$lastName
            )
        );
    }

    /**
     * @Route("/logout/", name="logout")
     */
    public function logoutAction()
    {

    }

    /**
     * @Route("/register/new", name="user_registration")
     */
    public function registerAction(Request $request)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setUsername();

            // $file stores the uploaded PDF file
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $user->getProfilePic();

            // Generate a unique name for the file before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            // Move the file to the directory where brochures are stored
            $picsDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/pics';
            $file->move($picsDir, $fileName);

            // Update the 'profilePic' property to store the image file name
            // instead of its contents
            $user->setProfilePic($fileName);

            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user
            $this->sendMail($user);

            return $this->redirectToRoute('login-upon-registration',
                array(
                    'username'=>$user->getUsername(),
                    'firstName'=>$user->getFirstName(),
                    'lastName'=>$user->getLastName()
                    ));
        }

        return $this->render(
            'registration/register.html.twig',
            array('form' => $form->createView())
        );
    }

    private function sendMail($username){

        $message = \Swift_Message::newInstance()
            ->setSubject('Hello Email')
            ->setFrom('send@example.com')
            ->setTo($username->getEmail())
            ->setBody(
                $this->renderView(
                // app/Resources/views/Emails/registration.html.twig
                    'Emails/registration.html.twig',
                    array('name' => $username)
                ),
                'text/html'
            );
        $this->get('mailer')->send($message);

        /* return $this->render();  */

    }
}
