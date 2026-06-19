<?php

namespace S2low\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedHttpException || $exception instanceof AccessDeniedException) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request && $request->hasSession()) {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    "Accès refusé : Vous n'avez pas les droits pour accéder à cette page."
                );
            }

            $url = $this->urlGenerator->generate('index');
            $response = new RedirectResponse($url);

            $event->setResponse($response);
        }
    }
}
