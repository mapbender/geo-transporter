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

    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('all', null, InputOption::VALUE_NONE, "Export all locations and mappings"),
                new InputOption('l', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Export location by names"),
                new InputOption('m', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Export mapping by object names")
            ))
            ->setDescription('Transport spatial data from doctrine to spatialite(sqlite) files.')
            ->setHelp('Transport spatial data from doctrine database connection(PostgresSQL, Oracle, MySQL) to spatialite(sqlite) files.')
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

        // Attach event handler
        foreach (array(
                     GeoTransporter::EVENT_CREATE_TEMPLATE,
                     GeoTransporter::EVENT_GET_DATABASE,
                     GeoTransporter::EVENT_CREATE_NEW_DATABASE,
                     GeoTransporter::EVENT_CREATE_TABLE,
                     GeoTransporter::EVENT_DELETE_TABLE,
                     GeoTransporter::EVENT_START_EXPORT_LOCATION,
                     GeoTransporter::EVENT_START_EXPORT_MAPPING,
                 ) as $eventName) {
            $geoTransporter->on($eventName, $eventHandler);
        }

        if ($input->getOption('all')) {
            $geoTransporter->exportAll();
        } else {
            $locations = $input->getOption('l');
            $mappings  = $input->getOption('m');
            $geoTransporter->exportDataHandler(
                current($locations) ? $locations : null,
                current($mappings) ? $mappings : null
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