<?php

declare(strict_types=1);

namespace Andante\SoftDeletableBundle\DependencyInjection\Compiler;

use Andante\SoftDeletableBundle\EventSubscriber\SoftDeletableEventSubscriber;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineEventSubscriberPass implements CompilerPassInterface
{
    public const SOFT_DELETABLE_SUBSCRIBER_SERVICE_ID = 'andante_soft_deletable.doctrine.soft_deletable_subscriber';

    public function process(ContainerBuilder $container): void
    {
        $container
            ->register(
                self::SOFT_DELETABLE_SUBSCRIBER_SERVICE_ID,
                SoftDeletableEventSubscriber::class
            )
            ->addArgument(new Reference('andante_soft_deletable.configuration'))
            ->addTag('doctrine.event_listener', ['event' => Events::onFlush, 'method' => 'onFlush'])
            ->addTag('doctrine.event_listener', ['event' => Events::loadClassMetadata, 'method' => 'loadClassMetadata']);

    }
}
