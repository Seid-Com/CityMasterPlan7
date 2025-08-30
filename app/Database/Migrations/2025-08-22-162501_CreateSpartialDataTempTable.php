<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSpartialDataTempTable extends Migration
{
    public function up()
    {
        // Create temporary table for shapefile uploads and change detection
        $sql = "
            CREATE TABLE IF NOT EXISTS geostore.spartialdata_temp1
            (
                id integer NOT NULL DEFAULT nextval('spartialdata_id_seq'::regclass),
                geom geometry(MultiPolygon,20137),
                objectid bigint,
                owner_name character varying(50),
                upin character varying(50),
                region_cod character varying(2),
                city_code character varying(3),
                kebele_cod character varying(2),
                nhd_code character varying(2),
                block_code character varying(2),
                parcel_cod character varying(3),
                first_name character varying(50),
                fathers_na character varying(50),
                grandfathe character varying(50),
                titledeed_ character varying(50),
                land_acqui character varying(20),
                acquisitio bigint,
                land_tenur character varying(20),
                landuse_ti character varying(50),
                landuse_ex character varying(50),
                area_m2_ti double precision,
                area_m2_ta double precision,
                last_tax_p bigint,
                file_no character varying(5),
                link_statu character varying(5),
                area_diffe double precision,
                associatio character varying(50),
                fullname character varying(50),
                file_type character varying(15),
                shape_leng double precision,
                shape_area double precision,
                kentcode character varying(7),
                ha double precision,
                registerda date,
                change_type character varying(20),
                original_id integer,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT spartialdata_temp1_pkey PRIMARY KEY (id)
            )
        ";
        
        $this->db->query($sql);
        
        // Create spatial index
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_temp1_geom ON geostore.spartialdata_temp1 USING GIST (geom)");
        
        // Create indexes for change detection
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_temp1_change_type ON geostore.spartialdata_temp1 (change_type)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_temp1_original_id ON geostore.spartialdata_temp1 (original_id)");
        
        echo "Created geostore.spartialdata_temp1 table for change management\n";
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS geostore.spartialdata_temp1");
        echo "Dropped geostore.spartialdata_temp1 table\n";
    }
}