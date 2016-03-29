<?php

namespace Mapbender\GeoTransporterBundle\Tests;

use Eslider\SpatialiteNativeDriver;
use Mapbender\GeoTransporterBundle\Command\ExportCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends WebTestCase
{

    const DB_PATH = "app/db/spatilite.sqlite";

    /** @var  SpatialiteNativeDriver */
    protected $db;

    protected function setUp()
    {
        $this->db = new SpatialiteNativeDriver(self::DB_PATH);
    }

    /**
     *
     */
    public function testSpatialite()
    {
        $client = self::createClient();
        $container = $client->getContainer();
        $geoTransporter = $container->get('geo_transporter');
        $application = new Application($client->getKernel());
        $exportCommand = new ExportCommand();
        $exportCommand->setContainer($container);
        $application->add($exportCommand);

        // test get find command by name
        $command = $application->find($exportCommand->getName());
        $this->assertEquals($command->getName(), $exportCommand->getName());


        $commandTester = new CommandTester($exportCommand);
        $commandTester->execute(array(
            'command' => $exportCommand->getName(),
            '--l' => array("Landkreise"),
        ));

        return array();
    }

    public function testindex(){

        $client = self::createClient();
        $container = $client->getContainer();

        $geoTransporter = $container->get('geo_transporter');

        $mapping = array(
            0 => 'Hessen'
        );

        $geoTransporter->exportDataHandler('all',$mapping);
    }
}
