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
        $arr = array();
        $this
            ->setDefinition(array(
                new InputOption('all', null, InputOption::VALUE_NONE, "Export all locations and mappings"),
                new InputOption(
                    'l',
                    '',
                    InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                    "Export location by name", array()), // all
                new InputOption(
                    'm',
                    '',
                    InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY ,
                    "Export mapping by object name",
                    array())
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
                    $command->log("Export object: " . $data["id"]);
                    break;
                case GeoTransporter::EVENT_START_EXPORT_LOCATION:
                    $location = $data["location"];
                    $command->log("Export location: " . $location["name"]);
                    break;
                case GeoTransporter::EVENT_GET_DATABASE:
                    $command->log("Get database: " . $data['db']);
                    break;
                case GeoTransporter::EVENT_CREATE_TEMPLATE:
                    $command->log("Create spatialite template file. It's take some time. Please wait!");
                    break;
                case GeoTransporter::EVENT_CREATE_NEW_DATABASE:
                    $dbName = $data['db'];
                    $command->log("Create database: " . $dbName);
                    break;
                case GeoTransporter::EVENT_DELETE_TABLE:
                    $command->log("Delete table: " . $data['tableName']);
                    break;
                case GeoTransporter::EVENT_CREATE_TABLE:
                    $command->log("Create table: " . $data["tableName"]);
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
        } else {
            $locations = $input->getOption('l');
            $mappings  = $input->getOption('m');
            $geoTransporter->exportDataHandler(
                count($locations)?$locations:'all',
                count($mappings)?$mappings:'all'
            );
        }
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