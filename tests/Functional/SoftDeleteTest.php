<?php

declare(strict_types=1);

namespace Andante\SoftDeletableBundle\Tests\Functional;

use Andante\SoftDeletableBundle\Doctrine\Filter\SoftDeletableFilter;
use Andante\SoftDeletableBundle\Tests\Fixtures\Entity\Address;
use Andante\SoftDeletableBundle\Tests\Fixtures\Entity\Organization;
use Andante\SoftDeletableBundle\Tests\HttpKernel\AndanteSoftDeletableKernel;
use Andante\SoftDeletableBundle\Tests\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class SoftDeleteTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    protected static function createKernel(array $options = []): AndanteSoftDeletableKernel
    {
        /** @var AndanteSoftDeletableKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addConfig('/config/andante_soft_deletable_custom.yaml');
        return $kernel;
    }

    public function testShouldSoftDelete(): void
    {
        $this->createSchema();
        $address1 = (new Address())->setName('Address1');
        $address2 = (new Address())->setName('Address2');
        $organization1 = (new Organization())->setName('Organization1');
        $organization2 = (new Organization())->setName('Organization2');
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = self::$container->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = $managerRegistry->getManagerForClass(Address::class);
        $em->persist($address1);
        $em->persist($address2);
        $em->persist($organization1);
        $em->persist($organization2);
        $em->flush();

        $addressRepository = $em->getRepository(Address::class);
        $organizationRepository = $em->getRepository(Organization::class);

        self::assertCount(2, $addressRepository->findAll());
        self::assertCount(2, $organizationRepository->findAll());

        $em->remove($address1);
        $em->remove($organization1);

        $em->flush();
        sleep(1); //Giving time to mysqlite to update file
        $addresses = $addressRepository->findAll();
        $organizations = $organizationRepository->findAll();
        self::assertCount(1, $addresses);
        self::assertCount(1, $organizations);

        /** @var Address $address2 */
        $address2 = \reset($addresses);
        /** @var Organization $organization2 */
        $organization2 = \reset($organizations);

        self::assertSame($address2->getName(), 'Address2');
        self::assertSame($organization2->getName(), 'Organization2');

        $em->getFilters()->disable(SoftDeletableFilter::NAME);

        $addresses = $addressRepository->findAll();
        $organizations = $organizationRepository->findAll();
        self::assertCount(2, $addresses);
        self::assertCount(2, $organizations);

        /** @var Address $address1 */
        $address1 = $addressRepository->findOneBy(['name' => 'Address1']);
        /** @var Organization $organization1 */
        $organization1 = $organizationRepository->findOneBy(['name' => 'Organization1']);
        self::assertNotNull($address1->getDeletedAt());
        self::assertNotNull($organization1->getDeletedAt());

        $address1DeletedAt = $address1->getDeletedAt();
        $organization1DeletedAt = $organization1->getDeletedAt();

        $em->remove($address1);
        $em->remove($organization1);
        $em->flush();
        sleep(1); //Giving time to mysqlite to update file
        self::assertNotSame($address1->getDeletedAt()->format(\DateTimeInterface::ATOM), $address1DeletedAt->format(\DateTimeInterface::ATOM));
        self::assertSame($organization1->getDeletedAt()->format(\DateTimeInterface::ATOM), $organization1DeletedAt->format(\DateTimeInterface::ATOM));

        //TODO: insert tests on enable/disable filter only for class
    }
}