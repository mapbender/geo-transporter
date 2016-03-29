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
        $geoTransporter = $this->getContainer()->get('geo_transporter');
        $geoTransporter->on(GeoTransporter::EVENT_START_EXPORT_MAPPING, function (Event $e) use ($output) {
            $data = $e->getData();
            $output->writeln("Start export: ". $data["id"]);
        });
        $geoTransporter->on(GeoTransporter::EVENT_GET_DATABASE, function (Event $e) use ($output) {
            $data = $e->getData();
            $dbName = $data['db'];
            $output->writeln("get Database: " . $dbName);
        });
        $geoTransporter->on(GeoTransporter::EVENT_CREATE_TEMPLATE, function (Event $e) use ($output) {
            $output->writeln("template dosn't exist, create template, just a moment...");
        });
        $geoTransporter->on(GeoTransporter::EVENT_CREATE_NEW_DATABASE, function (Event $e) use ($output) {
            $data = $e->getData();
            $dbName = $data['db'];
            $output->writeln("create new Database: " . $dbName);
        });
        $geoTransporter->on(GeoTransporter::EVENT_DELETE_TABLE, function (Event $e) use ($output) {
            $data = $e->getData();
            $tableName = $data['tableName'];
            $output->writeln("delete Table: " . $tableName);
        });
        $geoTransporter->on(GeoTransporter::EVENT_CREATE_TABLE, function (Event $e) use ($output) {
            $data = $e->getData();
            $tableName = $data['tableName'];
            $output->writeln("create Table: " . $tableName);
        });
        $geoTransporter->on(GeoTransporter::EVENT_START_EXPORT_LOCATION, function (Event $e) use ($output) {
            $data = $e->getData();
            $location = $data["location"];
            $output->writeln("Start export location: " . $location["name"]);
        });


        if($input->getOption('all')){
            $geoTransporter->exportAll();
            return;
        }
        $locationIds = $input->getOption('l');
        $locationIds = empty($locationIds) ? 'all' : $locationIds;

        $mappingIds = $input->getOption('m');
        $mappingIds = empty($mappingIds[0]) ? 'all' : $mappingIds;


        $geoTransporter->exportDataHandler($locationIds,$mappingIds);

    }
}