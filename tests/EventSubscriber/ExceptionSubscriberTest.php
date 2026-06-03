<?php

declare(strict_types=1);

namespace S2low\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use S2low\EventSubscriber\ExceptionSubscriber;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = ExceptionSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $subscribedEvents);
    }

    public function testOnKernelExceptionRedirectsOnAccessDeniedException(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);

        $exception = new AccessDeniedException('Access Denied');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $request->setSession($session);
        $requestStack->method('getCurrentRequest')->willReturn($request);
        $session->method('getFlashBag')->willReturn($flashBag);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', "Accès refusé : Vous n'avez pas les droits pour accéder à cette page.");

        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('index')
            ->willReturn('/home');

        $subscriber = new ExceptionSubscriber($requestStack, $urlGenerator);
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/home', $response->getTargetUrl());
    }

    public function testOnKernelExceptionRedirectsOnAccessDeniedHttpException(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);

        $exception = new AccessDeniedHttpException('Access Denied Http');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $request->setSession($session);
        $requestStack->method('getCurrentRequest')->willReturn($request);
        $session->method('getFlashBag')->willReturn($flashBag);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', "Accès refusé : Vous n'avez pas les droits pour accéder à cette page.");

        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('index')
            ->willReturn('/home');

        $subscriber = new ExceptionSubscriber($requestStack, $urlGenerator);
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/home', $response->getTargetUrl());
    }

    public function testOnKernelExceptionDoesNothingOnOtherExceptions(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $exception = new \RuntimeException('Some other exception');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $subscriber = new ExceptionSubscriber($requestStack, $urlGenerator);
        $subscriber->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}
