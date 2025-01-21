<?php

namespace SpecShaper\EncryptBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use SpecShaper\EncryptBundle\Encryptors\EncryptorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * bin/console encrypt:database decrypt --manager=default
 */
#[AsCommand(
    name: 'encrypt:database',
    description: 'Encrypts or Decrypts the database'
)]
class EncryptDatabaseCommand extends Command
{

    private ?EntityManagerInterface $em;
    
    private array $encryptedFields = [];

    public function __construct(
        private readonly EncryptorInterface $encryptor,
        private readonly ManagerRegistry $registry,
        private readonly array $annotationArray
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('direction', InputArgument::REQUIRED,'Encrypt or Decrypt db.');
        $this->addOption('manager', null,InputOption::VALUE_OPTIONAL,'Nominate the database connection manager name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);

        $direction = $input->getArgument('direction');
        $managerName = $this->registry->getDefaultManagerName();

        $managerNameOption = $input->getOption('manager');

        if(!empty($managerNameOption)) {
            $managerName = $managerNameOption;
        }

        $this->em = $this->registry->getManager($managerName);
        
        $this->getEncryptedFields();

        $io->title('Decrypting the database');

        $tables = count($this->encryptedFields);

        $io->writeln($tables . ' tables to decrypt');

        $io->progressStart($tables);

        foreach ($this->encryptedFields as $tableName => $fieldArray){

            $this->decryptTable($tableName, $fieldArray, $direction);
            $io->progressAdvance();
        }

        $io->progressFinish();

        return Command::SUCCESS;
    }

    private function decryptTable(string $tableName, array $fieldArray, string $direction): void
    {
        // Get all the field names that have been encrypted as an array.
        // Convert those to comma seperated string like lastname, firstname.
        $fields = implode(', ', $fieldArray);

        $selectQuery = sprintf('SELECT id, %s FROM %s', $fields, $tableName);

        // Fetch these encrypted rows
        $resultRows = $this->em->getConnection()->fetchAllAssociative($selectQuery);

        $decryptedFields = [];

        foreach($resultRows as $resultRow)
        {
            foreach ($resultRow as $fieldName => $value) {

                if('id' === $fieldName){
                    continue;
                }

                if ('encrypt' === $direction) {
                    $newValue = $this->encryptor->encrypt($value);
                } else {
                    $newValue = $this->encryptor->decrypt($value);
                }

                $decryptedFields[$fieldName] = $newValue;
            }

            $this->em->getConnection()->update($tableName, $decryptedFields, ['id' => $resultRow['id']]);
        }
    }

    private function getEncryptedFields(): array
    {

        /** @var ClassMetadata[] $meta */
        $meta = $this->em->getMetadataFactory()->getAllMetadata();

        // Loop through entities
        foreach ($meta as $entityMeta) {

            if($entityMeta->isMappedSuperclass){
                continue;
            }

            $reflectionClass = new \ReflectionClass($entityMeta->getName());

            $properties = $reflectionClass->getProperties();

            $tableName = $entityMeta->getTableName();

            $classMeta = $this->em->getClassMetadata($entityMeta->getName());

            foreach ($properties as $key => $refProperty) {

                if ($this->isEncryptedProperty($refProperty)) {

                    if (!isset($this->encryptedFields[$tableName])) {
                        $this->encryptedFields[$tableName] = [];
                    }

                    $columnName = $classMeta->getColumnName($key);
                    $this->encryptedFields[$tableName][$columnName] = $refProperty->getName();
                }
            }
        }

        return $this->encryptedFields;
    }

    private function isEncryptedProperty(\ReflectionProperty $refProperty): bool
    {

        foreach ($refProperty->getAttributes() as $refAttribute) {

            if (in_array($refAttribute->getName(), $this->annotationArray)) {
                return true;
            }
        }

        return false;
    }
}
