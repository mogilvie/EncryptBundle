<?php

namespace SpecShaper\EncryptBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use SpecShaper\EncryptBundle\Exception\EncryptException;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\Reader;

#[AsCommand(
    name: 'encrypt:database:decrypt',
    description: 'Decrypts the database'
)]
class DecryptDatabaseCommand extends Command
{

    private ?EntityManagerInterface $em;

    public function __construct(
        private readonly Reader $annotationReader,
        private readonly EncryptorInterface $encryptor,
        private readonly ManagerRegistry $registry
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('manager_name', null,null,'Nominate the database connection manager name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $managerName = $this->registry->getDefaultManagerName();

        $managerNameOption = $input->getOption('manager_name');

        if(!empty($managerNameOption)) {
            $managerName = $managerNameOption;
        }

        dump($managerName);


        $this->em = $this->registry->getManager($managerName);

        $output->writeln([
            'Decrypting the database',
            '======================',
            '',
        ]);

        $this->processAllEntities();

        return Command::SUCCESS;
    }

    private function processAllEntities()
    {
        // Get all entities
        $meta =  $this->em->getMetadataFactory()->getAllMetadata();

        // Loop through entities
        foreach ($meta as $m) {
            $repository =  $this->em->getRepository($m->getName());
            $rows = $repository->findAll();

            foreach ($rows as $row) {

                $hasFieldsEncrypted = $this->processFields($row);

                if ($hasFieldsEncrypted) {
                    $this->em->persist($row);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * @return \ReflectionProperty[]
     */
    private function getEncryptedFields($entity): array
    {
        $className = get_class($entity);
        $meta =  $this->em->getClassMetadata($className);

        $encryptedFields = [];

        foreach ($meta->getReflectionProperties() as $propertyName => $refProperty) {
            /** @var \ReflectionProperty $refProperty */

            $propertyAnnotations = array_map(function ($annotation) {
                return get_class($annotation);
            }, $this->annotationReader->getPropertyAnnotations($refProperty));

            if (in_array(\SpecShaper\EncryptBundle\Annotations\Encrypted::class, $propertyAnnotations)) {
                $encryptedFields[$propertyName] = $refProperty;
            }
        }

        return $encryptedFields;
    }

    /**
     * Process (encrypt) entities fields.
     */
    protected function processFields(object $entity): bool
    {
        // Get the encrypted properties in the entity.
        $properties = $this->getEncryptedFields($entity);

        // If no encrypted properties, return false.
        if (empty($properties)) {
            return false;
        }

        $unitOfWork =  $this->em->getUnitOfWork();
        $oid = spl_object_id($entity);

        foreach ($properties as $propertyName => $refProperty) {

            // Get the value in the entity.
            $value = $refProperty->getValue($entity);

            // Skip any null values.
            if (null === $value) {
                continue;
            }

            if (is_object($value)) {
                throw new EncryptException('Cannot decrypt an object at ' . $refProperty->class . ':' . $propertyName, $value);
            }

            // If the field has already been decrypted by the onLoad event, and the flushed value is the same
            if (isset($this->decodedValues[$oid][$propertyName]) && $this->decodedValues[$oid][$propertyName][1] === $value) {

                // Remove the field from the UoW change set.
                unset($unitOfWork->getEntityChangeSet($entity)[$propertyName]);

                // Get the originally created encrypted value.
                $encryptedValue = $this->decodedValues[$oid][$propertyName][0];

                // Reset that to the original in the UoW.
                $unitOfWork->setOriginalEntityProperty($oid, $propertyName, $encryptedValue);
            } else {
                // The field is part of an insert or the value of the field has changed, then create a new encrypted value.
                $encryptedValue = $this->encryptor->decrypt($value);
            }

            // Replace the unencrypted value with the encrypted value on the entity.
            $refProperty->setValue($entity, $encryptedValue);
        }

        return true;
    }
}
