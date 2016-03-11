<?php

namespace Mapbender\GeoTransporterBundle\Component;

use Doctrine\ORM\Mapping as ORM;
use Eslider\SpatialGeometry;
use Eslider\SpatialiteShellDriver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User: egert
 * Date: 02.03.16
 * Time: 10:32
 */
class GeoTransporter
{
    /**
     * path to database template
     *
     * @var string
     */
    protected $templateDbPath = "app/db/template.sqlite";

    /**
     * @var ContainerInterface $container The container
     */
    protected $container;

    /**
     * @var SpatialiteShellDriver
     */
    protected $db;


    /**
     * Location definitions
     */
    private $locations;

    /**
     * GeoExporter constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->locations = $this->container->getParameter("locations");

        //var_dump($this->locations);
    }

    public function chanceTemplateDbPath($newPath){
        $this->templateDbPath = $newPath;
    }

    /**
     * Get location list as array
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Get location by id
     *
     * @param $id
     */
    public function getLocation($id)
    {
        return $this->locations[$id];
    }



    /**
     * Export by location and mapping id
     * @param $locationId
     * @param $mappingId
     */
    public function exportByMapping($locationId, $mappingId)
    {

        /** @var \Doctrine\DBAL\Driver\PDOSqlsrv\Connection $connection */
        $mapping = $this->getMapping($locationId, $mappingId);
        $source = $mapping["source"];
        $connection = $this->container->get("doctrine")->getConnection($source["connection"]);
        $tableName = $mapping["target"]["table"];
        $results = $connection->query($source['sql'])->fetchAll();
        $columns = $this->getColumns($results,$source['geomColumn']);
        $hasExternalId = array_search('externId',$columns) !== false;

        /** @var SpatialiteShellDriver $db */
        $this->db = $db = $this->getDB($mapping['target']['path']);

        if($db->hasTable($tableName)) {
            //$db->exec('SELECT DiscardGeometryColumn('.$db->quote($tableName).', '.$db->quote($source['geomColumn']).')');
            $sql = 'SELECT DiscardGeometryColumn(' . $db->escapeValue($tableName) . ', ' . $db->escapeValue($source['geomColumn']) . ')';
            $db->exec($sql);
            $db->dropTable($tableName);
        }

        $this->createTable($tableName,$columns,$source['geomColumn'],$source['srid'],$source['type']);

        $this->insertTable($results,$db,$tableName,$hasExternalId,$source['geomColumn'],$source ['srid']);

    }


    /**
     * insert sqlite tables
     *
     * @param $results
     * @param $db
     * @param $tableName
     * @param $hasExternalId
     * @param $geomColumn
     * @param $srid
     */
    public function insertTable($results,$db,$tableName,$hasExternalId,$geomColumn,$srid){
        $insertArray = array();
        foreach($results as $row){
            $columns = array_keys($row);
            $key = array_search($geomColumn, $columns);
            unset($columns[$key]);

            foreach($columns as $column){
                $insertArray[$column] = $row[$column];
            }

            $insertArray[$geomColumn] = new SpatialGeometry($row[$geomColumn], SpatialGeometry::TYPE_WKT, $srid);

            if($hasExternalId){
                $insertArray['externId'] = $insertArray['id'];
                unset($insertArray['id']);
            }
            $db->insert($tableName, $insertArray);
        }
    }

    /**
     * create a new table in sqlite
     *
     * @param $tableName
     * @param $columns
     * @param $geomColumn
     * @param $srid
     * @param $type
     *
     */
    public function createTable($tableName, $columns, $geomColumn, $srid, $type)
    {
        $db = $this->db;
        $db->createTable($tableName);
        foreach ($columns as $columnName) {
            $db->addColumn($tableName, $columnName, 'TEXT');
        }
        $db->addGeometryColumn(
            $tableName,
            $geomColumn,
            $srid,
            $type
        );
    }

    /**
     * get columns
     *
     * @param $results
     * @param $geomColumn
     *
     * @return array
     */
    public function getColumns($results, $geomColumn){

        $columns = array_keys(current($results));

        if(array_search('id',$columns) !== false ){
            unset($columns[array_search('id',$columns)]);
            $columns[] = "externId";
        }

        if(array_search($geomColumn,$columns) !== false ){
            unset($columns[array_search($geomColumn,$columns)]);
        }

        return $columns;
    }

    /**
     * handle db
     *
     * @param $dbpath
     * @return SpatialiteShellDriver
     */
    public function getDB($dbpath)
    {
        if ($this->existsDB($dbpath)) {
            return $this->createDB($dbpath);
        }

        if (!$this->existsDB($this->templateDbPath)) {
             $this->createDB($this->templateDbPath);
        }

        return $this->createNewDB($dbpath);
    }

    /**
     * copy db template and create a new db
     *
     * @param $dbpath
     * @return SpatialiteShellDriver
     */
    public function createNewDB($dbpath){
        copy($this->templateDbPath, $dbpath);
        return $this->createDB($dbpath);
    }

    /**
     * creat or get a DB
     *
     * @param $dbpath
     * @return SpatialiteShellDriver
     */
    public function createDB($dbpath){
        return new SpatialiteShellDriver($dbpath,
            "vendor/eslider/spatialite/bin/x64/mod_spatialite",
            "vendor/eslider/spatialite/bin/x64/sqlite3");
    }

    /**
     * @param $dbname
     * @return bool
     */
    public function existsDB($dbname){
        return (file_exists($dbname) ? true : false);
    }


    /**
     * Export by location id
     * and export all mappings of a location
     *
     * @param $locationId
     */
    public function exportLocation($locationId)
    {
        $location = $this->getLocation($locationId);
        foreach ($location["mapping"] as $mappingId => $mapping) {
            $mapping = $this->getMapping($locationId, $mappingId);
            $this->exportByMapping($locationId, $mappingId);
        }
    }

    /**
     * Export all locations
     */
    public function exportAll()
    {
        foreach ($this->getLocations() as $locationId => $location) {
            $this->exportLocation($locationId);
        }
    }

    /**
     * Get mapping by location and mapping id's
     * @return mixed
     */
    public function getMapping($locationId, $mappingId)
    {
        $location = $this->getLocation($locationId);
        return $location["mapping"][$mappingId];
    }

    /**
     * handle console command
     *
     * @param $locations
     * @param $mappings
     */
    public function exportDataHandler($locations,$mappings){



        if(!is_array($locations)){

            $locations = $this->getLocations();

            if(!is_array($mappings)){
                foreach($locations as $locationId => $location){
                    $this->exportLocation($locationId);
                }
            } else {
                foreach($locations as $locationId => $location){
                    $this->exportLocationWithDefindMapping($locationId,$mappings);
                }
            }
        }

        if(!is_array($mappings)){
            foreach($locations as $locationId){
                $this->exportLocation($locationId);
            }
        } else {
            foreach($locations as $locationId){
                $this->exportLocationWithDefindMapping($locationId,$mappings);
            }
        }

    }

    /**
     * export Location with defind mappings
     *
     * @param $locationId
     * @param $mappings
     */
    public function exportLocationWithDefindMapping($locationId,$mappings){
        foreach ($mappings as $mappingId) {
            $this->exportByMapping($locationId, $mappingId);
        }
    }
}