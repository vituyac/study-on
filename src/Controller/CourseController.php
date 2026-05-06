<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use App\Service\CourseViewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses')]
final class CourseController extends AbstractController
{
    #[Route('/courses/{id}/pay', name: 'app_course_pay', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function pay(Course $course, BillingClient $billingClient, CourseViewService $courseViewService): Response
    {
        $user = $this->getUser();
        if ($user) {
            try {
                $billingUser = $billingClient->getCurrentUser($user->getApiToken());
            } catch (\Throwable) {
                $billingUser = null;
            }
            if ($billingUser) {
                $user->setBalance($billingUser['balance']);
            }
        }

        $courseView = $courseViewService->createList(
            [$course],
            $user
        )[0];

        if (!$billingUser) {
            $this->addFlash('danger', 'Сервис временно не доступен');
        } elseif (
            !$courseView->isFree()
            && $courseView->price !== null
            && ((float) $user->getBalance() < (float) $courseView->price)
        ) {
            $this->addFlash('danger', 'У вас недостаточно средств для оплаты курса');
        }

        try {
            $billingClient->pay($user->getApiToken(), $course->getCode());
            $this->addFlash('success', 'Курс успешно оплачен');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage() ?: 'Произошла ошибка при оплате курса');
        }

        return $this->redirectToRoute('app_course_show', [
            'id' => $course->getId(),
        ]);
    }

    #[Route(name: 'app_course_index', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function index(
        CourseRepository $courseRepository,
        CourseViewService $courseViewService
    ): Response {
        $courses = $courseRepository->findAll();

        $courseViews = $courseViewService->createList(
            $courses,
            $this->getUser()
        );

        return $this->render('course/index.html.twig', [
            'courses' => $courseViews,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, CourseViewService $courseViewService, BillingClient $billingClient): Response
    {
        $user = $this->getUser();
        if ($user) {
            try {
                $billingUser = $billingClient->getCurrentUser($user->getApiToken());
            } catch (\Throwable) {
                $billingUser = null;
            }
            if ($billingUser) {
                $user->setBalance($billingUser['balance']);
            }
        }

        $courseView = $courseViewService->createList(
            [$course],
            $user
        )[0];

        if (
            $user
            && $billingUser
            && $courseView->billingAvailable
            && !$courseView->isFree()
            && !$courseView->purchased
            && $courseView->price !== null
        ) {
            $canPay = (float) $user->getBalance() >= (float) $courseView->price;
        } else {
            $canPay = true;
        }

        return $this->render('course/show.html.twig', [
            'course' => $courseView,
            'canPay' => $canPay,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
