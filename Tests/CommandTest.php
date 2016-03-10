<?php

namespace Mapbender\GeoTransporterBundle\Tests;

use Eslider\SpatialGeometry;
use Eslider\SpatialiteShellDriver;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommandTest extends WebTestCase
{

    const DB_PATH = "app/db/spatilite.sqlite";
    /** @var  SpatialiteShellDriver */
    protected $db;

    protected function setUp()
    {
        $this->db = new SpatialiteShellDriver(self::DB_PATH,
            "vendor/eslider/spatialite/bin/x64/mod_spatialite",
            "vendor/eslider/spatialite/bin/x64/sqlite3");
    }

    public function testSpatialite()
    {
        $tableName = "testgeo";
        $geometryColumnName = 'geom';
        $srid = 4326;

        // http://www.gaia-gis.it/gaia-sins/spatialite-cookbook/html/new-geom.html
        $geomType = "POINT";
        $wkt = 'POINT(30 10)';
        $db = $this->db;

        $db->dropTable($tableName);

        if (!$db->hasTable($tableName)) {
            $db->createTable($tableName);
            $db->addGeometryColumn(
                $tableName,
                $geometryColumnName,
                $srid,
                $geomType
            );
        }

//        $tableInfo = $db->getTableInfo($tableName);

        for($i = 0; $i < 100; $i++){
            // insert geo results
            $id = $db->insert($tableName, array(
                $geometryColumnName => new SpatialGeometry($wkt, SpatialGeometry::TYPE_WKT, $srid)
            ));

            // get results
            $wktFromDb = $db->fetchColumn('SELECT
            ST_ASTEXT(' . $db->quote($geometryColumnName) . ') AS ' . $db->quote($geometryColumnName) . '
            FROM ' . $db->quote($tableName) . '
            WHERE id=' . $id);
            $this->assertEquals($wkt,$wktFromDb);
        }


        // daten löschen
//        $db->emptyTable($tableName);

    }
}
