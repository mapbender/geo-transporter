<?php

namespace Mapbender\GeoTransporterBundle\Command;

use Mapbender\GeoTransporterBundle\Component\GeoTransporter;
use Mapbender\GeoTransporterBundle\Events\Event;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class VectordataExportAllCommand
 * @package AppBundle\Command
 */
class ExportCommand extends ContainerAwareCommand {

    /** @var OutputInterface */
    protected $output;

    protected function configure() {
        $this
            ->setDefinition(array(
                new InputOption('all', null, InputOption::VALUE_NONE , "Export all locations and mappings"),
                new InputOption('l', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY , "Export location by name"),
                new InputOption('m', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY , "Export mapping by object name")
            ))

            ->setDescription('Export all Tables in spatialite.')
            ->setHelp('Export all Tables in spatialite.')
            ->setName('geo:export');
    }

    /**
     * Executes the export command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output   = $output;
        $command        = $this;
        $geoTransporter = $this->getContainer()->get('geo_transporter');
        $eventHandler   = function (Event $e) use ($command) {
            $data = $e->getData();
            switch ($e->getName()) {
                case GeoTransporter::EVENT_START_EXPORT_MAPPING:
                    $command->log("Start export: " . $data["id"]);
                    break;
                case GeoTransporter::EVENT_START_EXPORT_LOCATION:
                    $command->log("Start export: " . $data["id"]);
                    break;
                case GeoTransporter::EVENT_GET_DATABASE:
                    $command->log("Get Database: " . $data['db']);
                    break;
                case GeoTransporter::EVENT_CREATE_TEMPLATE:
                    $command->log("Template dosn't exist, create template, just a moment...");
                    break;
                case GeoTransporter::EVENT_CREATE_NEW_DATABASE:
                    $dbName = $data['db'];
                    $command->log("Create new Database: " . $dbName);
                    break;
                case GeoTransporter::EVENT_DELETE_TABLE:
                    $tableName = $data['tableName'];
                    $command->log("Delete Table: " . $tableName);
                    break;
                case GeoTransporter::EVENT_CREATE_TABLE:
                    $location = $data["location"];
                    $command->log("Start export location: " . $location["name"]);
                    break;
            }
        };

        $geoTransporter->on(GeoTransporter::EVENT_START_EXPORT_MAPPING, $eventHandler);
        $geoTransporter->on(GeoTransporter::EVENT_GET_DATABASE, $eventHandler);
        $geoTransporter->on(GeoTransporter::EVENT_CREATE_TEMPLATE, $eventHandler);
        $geoTransporter->on(GeoTransporter::EVENT_CREATE_NEW_DATABASE, $eventHandler);
        $geoTransporter->on(GeoTransporter::EVENT_DELETE_TABLE, $eventHandler);
        $geoTransporter->on(GeoTransporter::EVENT_CREATE_TABLE, $eventHandler);
        $geoTransporter->on(GeoTransporter::EVENT_START_EXPORT_LOCATION, $eventHandler);

        if ($input->getOption('all')) {
            $geoTransporter->exportAll();
            return;
        }
        $locationIds = $input->getOption('l');
        $locationIds = empty($locationIds) ? 'all' : $locationIds;
        $mappingIds  = $input->getOption('m');
        $mappingIds  = empty($mappingIds[0]) ? 'all' : $mappingIds;

        $geoTransporter->exportDataHandler($locationIds, $mappingIds);
    }

    /**
     * Log or output message
     *
     * @return mixed
     * @internal param $data
     */
    protected function log($msg)
    {
        return $this->output->writeln($msg);
    }
}