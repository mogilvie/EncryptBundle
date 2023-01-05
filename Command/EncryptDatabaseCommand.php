<?php

namespace SpecShaper\EncryptBundle\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use SpecShaper\EncryptBundle\Exception\EncryptException;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\Reader;

class EncryptDatabaseCommand extends Command
{
    protected static $defaultName = 'encrypt:database:encode';

    public function __construct(
        private Reader $annotationReader,
        private EncryptorInterface $encryptor,
        private EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Encrypts the database')
            ->setHelp('This command encrypt the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Encrypting the database',
            '======================',
            '',
        ]);

        $this->processAllEntities();

        return Command::SUCCESS;
    }

    private function processAllEntities()
    {
        // Get all entities
        $meta = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Loop through entities
        foreach ($meta as $m) {
            $repository = $this->entityManager->getRepository($m->getName());
            $rows = $repository->findAll();

            foreach ($rows as $row) {

                $hasFieldsEncrypted = $this->processFields($row);

                if ($hasFieldsEncrypted) {
                    $this->entityManager->persist($row);
                }
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @return \ReflectionProperty[]
     */
    private function getEncryptedFields($entity): array
    {
        $className = get_class($entity);
        $meta = $this->entityManager->getClassMetadata($className);

        $encryptedFields = [];

        foreach ($meta->getReflectionProperties() as $refProperty) {
            /** @var \ReflectionProperty $refProperty */

            $propertyAnnotations = array_map(function ($annotation) {
                return get_class($annotation);
            }, $this->annotationReader->getPropertyAnnotations($refProperty));

            if (in_array(\SpecShaper\EncryptBundle\Annotations\Encrypted::class, $propertyAnnotations)) {
                $encryptedFields[] = $refProperty;
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

        $unitOfWork = $this->entityManager->getUnitOfWork();
        $oid = spl_object_id($entity);

        foreach ($properties as $key => $refProperty) {

            // Get the value in the entity.
            $value = $refProperty->getValue($entity);

            // Skip any null values.
            if (null === $value) {
                continue;
            }

            if (is_object($value)) {
                throw new EncryptException('Cannot encrypt an object at ' . $refProperty->class . ':' . $refProperty->getName(), $value);
            }

            // If the field has already been decrypted by the onLoad event, and the flushed value is the same
            if (isset($this->decodedValues[$oid][$refProperty->getName()]) && $this->decodedValues[$oid][$refProperty->getName()][1] === $value) {

                // Remove the field from the UoW change set.
                unset($unitOfWork->getEntityChangeSet($entity)[$refProperty->getName()]);

                // Get the originally created encrypted value.
                $encryptedValue = $this->decodedValues[$oid][$refProperty->getName()][0];

                // Reset that to the original in the UoW.
                $unitOfWork->setOriginalEntityProperty($oid, $refProperty->getName(), $encryptedValue);
            } else {
                // The field is part of an insert or the value of the field has changed, then create a new encrypted value.
                $encryptedValue = $this->encryptor->encrypt($value);
            }

            // Replace the unencrypted value with the encrypted value on the entity.
            $refProperty->setValue($entity, $encryptedValue);
        }

        return true;
    }
}
