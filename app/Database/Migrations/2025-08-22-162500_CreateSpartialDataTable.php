<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSpartialDataTable extends Migration
{
    public function up()
    {
        // Create geostore schema if it doesn't exist
        $this->db->query("CREATE SCHEMA IF NOT EXISTS geostore");
        
        // Create sequence for primary key
        $this->db->query("CREATE SEQUENCE IF NOT EXISTS spartialdata_id_seq");
        
        // Create the spartialdata table
        $sql = "
            CREATE TABLE IF NOT EXISTS geostore.spartialdata
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
                CONSTRAINT spartialdata_pkey PRIMARY KEY (id)
            )
        ";
        
        $this->db->query($sql);
        
        // Create spatial index for better performance
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_geom ON geostore.spartialdata USING GIST (geom)");
        
        // Create indexes on commonly used columns
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_owner_name ON geostore.spartialdata (owner_name)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_upin ON geostore.spartialdata (upin)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_landuse ON geostore.spartialdata (landuse_ti)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_spartialdata_kentcode ON geostore.spartialdata (kentcode)");
        
        echo "Created geostore.spartialdata table with spatial indexes\n";
    }

    public function down()
    {
        // Drop the table
        $this->db->query("DROP TABLE IF EXISTS geostore.spartialdata");
        
        // Drop the sequence
        $this->db->query("DROP SEQUENCE IF EXISTS spartialdata_id_seq");
        
        // Drop the schema if empty
        $this->db->query("DROP SCHEMA IF EXISTS geostore");
        
        echo "Dropped geostore.spartialdata table and related objects\n";
    }
}