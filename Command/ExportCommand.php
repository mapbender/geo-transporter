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

    protected function configure() {
        $this
            ->setDefinition(array(
                new InputOption('all', null, InputOption::VALUE_NONE , "all"),
                new InputOption('l', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY , "l"),
                new InputOption('m', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY , "m")
            ))

            ->setDescription('Export all Tables in spatialite.')
            ->setHelp('Export all Tables in spatialite.')
            ->setName('geo:export');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $geoTransporter = $this->getContainer()->get('geo_transporter');
        $geoTransporter->on(GeoTransporter::EVENT_START_EXPORT_MAPPING, function (Event $e) use ($output) {
            $data = $e->getData();
            $output->writeln("Start export: ". $data["id"]);
        });
        $geoTransporter->on(GeoTransporter::EVENT_START_EXPORT_LOCATION, function (Event $e) use ($output) {
            $data = $e->getData();
            $location = $data["location"];
            $output->writeln("Start export location: " . $location["name"]);
        });
        if($input->getOption('all')){
            $geoTransporter->exportAll();
            die;
        }

        $locationIds = $input->getOption('l');
        $locationIds = empty($locationIds) ? 'all' : $locationIds;

        $mappingIds = $input->getOption('m');
        $mappingIds = empty($mappingIds[0]) ? 'all' : $mappingIds;


        $geoTransporter->exportDataHandler($locationIds,$mappingIds);

    }
}