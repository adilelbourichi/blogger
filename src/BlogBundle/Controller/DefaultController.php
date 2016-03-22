<?php

namespace BlogBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use BlogBundle\Entity\Blog;
use BlogBundle\Form\BlogType;
use Doctrine\DBAL\Query;

class DefaultController extends Controller
{
    /**
     * @Route("/blogs/{username}")
     */
    public function indexAction($username)
    {
        /* search the blog database for blogs where username == firstName.lastName and print the list*/
        $repository = $this->getDoctrine()->getRepository('BlogBundle:Blog');
        $query = $repository->createQueryBuilder('b')
            ->where('b.username = :username')
            ->setParameter('username', $username)
            ->orderBy('b.publishedDate', 'desc')
            ->getQuery();

        $my_blog = $query->getResult();

        $repository2 = $this->getDoctrine()->getRepository('UserBundle:User');
        $query2 = $repository2->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery();

        $my_user = $query2->getResult();
        $firstName = $my_user[0]->getFirstName();
        $lastName = $my_user[0]->getLastName();

        return $this->render('BlogBundle:Default:index.html.twig',
            array(
                'firstName' => $firstName, 'lastName' => $lastName, 'my_blog' => $my_blog, 'my_user'=>$my_user
            ));
    }

    /**
     * @Route("/{title}", name="show_blog")
     */
    public function showBlogAction($title)
    {
        $repository = $this->getDoctrine()->getRepository('BlogBundle:Blog');
        $query = $repository->createQueryBuilder('b')
            ->where('b.title = :title')
            ->setParameter('title', $title)
            ->getQuery();
        $my_blog = $query->getResult();

        $username = $my_blog[0]->getUsername();

        $repository2 = $this->getDoctrine()->getRepository('UserBundle:User');
        $query2 = $repository2->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery();

        $my_user = $query2->getResult();
        $firstName = $my_user[0]->getFirstName();
        $lastName = $my_user[0]->getLastName();

        return $this->render('BlogBundle:Default:blog.html.twig',
            array(
                'firstName' => $firstName, 'lastName' => $lastName, 'my_blog' => $my_blog, 'my_user'=>$my_user
            ));
    }

    /**
     * @Route("/{title}/edit")
     */
    public function editBlogAction(Request $request, $title){
        $repository = $this->getDoctrine()->getRepository('BlogBundle:Blog');
        $query = $repository->createQueryBuilder('b')
            ->where('b.title = :title')
            ->setParameter('title', $title)
            ->getQuery();
        $my_blog = $query->getResult();

        $blog = $my_blog[0];

        $deleteForm = $this->createDeleteForm($blog);
        $editForm = $this->createForm('BlogBundle\Form\BlogType', $blog);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($blog);
            $em->flush();

            return $this->redirectToRoute('show_blog', array('title'=>$title));
        }

        return $this->render('BlogBundle:Default:editblog.html.twig',
            array(
                'my_blog' => $my_blog,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
    }

    /**
     * Creates a form to delete a Blog entity.
     *
     * @param Blog $blog The Blog entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Blog $blog)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('blog_delete', array('id' => $blog->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

} /* end of class definition */