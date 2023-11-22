<?php

namespace App\Controller;

use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentFormType;
use App\Form\PostFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class BlogController extends AbstractController
{
    #[Route("/blog/buscar/{page}", name: 'blog_buscar')]
    public function buscar(ManagerRegistry $doctrine,  Request $request, int $page = 1): Response
    {
       return new Response("Buscar");
    } 
   
    #[Route("/blog/new", name: 'new_post')]
    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
  {
    $post = new Post();
    
    $formulario = $this->createForm(PostFormType::class, $post);

    $formulario->handleRequest($request);

    if ($formulario->isSubmitted() && $formulario->isValid()) {
        $file = $formulario->get('Image')->getData();
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();


            // Move the file to the directory where images are stored
            try {


                $file->move(
                    $this->getParameter('post_image_directory'), $newFilename
                );
                /*$filesystem = new Filesystem();
                 $filesystem->copy(
                    $this->getParameter('images_directory') . '/'. $newFilename,
                    $this->getParameter('portfolio_directory') . '/'.  $newFilename, true); */


            } catch (FileException $e) {
                return new Response("Error: " . $e->getMessage());
            }


            // updates the 'file$filename' property to store the PDF file name
            // instead of its contents
            $post->setImage($newFilename);
        }
       
        $post = $formulario->getData();


        $post->setUser($this->getUser());


        $post->setNumLikes(0);
        $post->setNumComments(0);
        $post->setNumViews(0);


        $post->setSlug($slugger->slug($post->getTitle()));
        $entityManager = $doctrine->getManager();
        $entityManager->persist($post);


        try {
            $entityManager->flush();
            return $this->redirectToRoute('blog');
        } catch (\Exception $e) {
            return new Response("Error: " . $e->getMessage());
        }
    
        $post = $formulario->getData();
        $entityManager = $doctrine->getManager();
        $entityManager->persist($post);
        $entityManager->flush();
        return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);
    }    return $this->render('blog/new_post.html.twig', array(
        'form' => $formulario->createView()
    ));
}
#[Route('/blog/{page}', name: 'blog', requirements: ['page' => '\d+'])]
public function index8(ManagerRegistry $doctrine, int $page = 1): Response
{
    $repository = $doctrine->getRepository(Post::class);
    $posts = $repository->findAllPaginated($page);

    return $this->render('blog/blog.html.twig', [
        'posts' => $posts,
    ]);
}

    
    
    #[Route("/single_post/{slug}/like", name: 'post_like')]
    public function like(ManagerRegistry $doctrine, $slug): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $post = $repository->findOneBy(["Slug"=>$slug]);
        if ($post){
            $post->setNumLikes($post->getNumLikes() + 1);
            $entityManager = $doctrine->getManager();    
            $entityManager->persist($post);
            $entityManager->flush();
        }
        return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);

    }



    #[Route("/blog", name: 'blog')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll();
        
        return $this->render('blog/blog.html.twig', [
            'posts' => $posts,
        ]);
    }
    
    #[Route("/blog2", name: 'blog2')]
    public function index2(ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll();
        
        return $this->render('blog/blog2.html.twig', [
            'posts' => $posts,
        ]);
    }
    #[Route("/blog3", name: 'blog3')]
    public function index3(ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll();
        
        return $this->render('blog/blog3.html.twig', [
            'posts' => $posts,
        ]);
    }


    #[Route("/single_post/{slug}", name: 'single_post')]
    public function post(ManagerRegistry $doctrine, Request $request, $slug): Response
    {
        
        $repository = $doctrine->getRepository(Post::class);

        $post = $repository->findOneBy(["Slug"=>$slug]);
        $recents = $repository->findRecents();
        
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setPost($post);  
            //Aumentamos en 1 el número de comentarios del post
            $post->setNumComments($post->getNumComments() + 1);
            $entityManager = $doctrine->getManager();    
            $entityManager->persist($comment);
            $entityManager->flush();
            return $this->redirectToRoute('single_post', ["slug" => $post->getSlug()]);
        }
        return $this->render('blog/single_post.html.twig', [
            'post' => $post,
            'recents' => $recents,
            'commentForm' => $form->createView()
        ]);
    }
}
