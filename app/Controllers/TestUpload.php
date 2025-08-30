<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class TestUpload extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Simulate shapefile upload workflow with test data
     */
    public function simulateUpload()
    {
        try {
            // Clear temporary table
            $this->db->query("DELETE FROM geostore.spartialdata_temp1");

            // Simulate uploading new parcels (modified versions of existing + new ones)
            $testData = [
                // Modified existing parcel (John Doe with updated area)
                [
                    'objectid' => 1,
                    'owner_name' => 'John Doe',
                    'upin' => 'UP001',
                    'first_name' => 'John',
                    'fathers_na' => 'Smith',
                    'landuse_ti' => 'Residential',
                    'area_m2_ti' => 750.0, // Changed from 500
                    'fullname' => 'John Smith Doe',
                    'kentcode' => 'KNT001',
                    'geom' => 'ST_Multi(ST_GeomFromText(\'POLYGON((38.740 9.034, 38.741 9.034, 38.741 9.036, 38.740 9.036, 38.740 9.034))\', 20137))'
                ],
                // New parcel
                [
                    'objectid' => 4,
                    'owner_name' => 'Sarah Wilson',
                    'upin' => 'UP004',
                    'first_name' => 'Sarah',
                    'fathers_na' => 'Michael',
                    'landuse_ti' => 'Commercial',
                    'area_m2_ti' => 1000.0,
                    'fullname' => 'Sarah Michael Wilson',
                    'kentcode' => 'KNT004',
                    'geom' => 'ST_Multi(ST_GeomFromText(\'POLYGON((38.746 9.034, 38.747 9.034, 38.747 9.035, 38.746 9.035, 38.746 9.034))\', 20137))'
                ],
                // Another new parcel
                [
                    'objectid' => 5,
                    'owner_name' => 'David Brown',
                    'upin' => 'UP005',
                    'first_name' => 'David',
                    'fathers_na' => 'James',
                    'landuse_ti' => 'Industrial',
                    'area_m2_ti' => 1500.0,
                    'fullname' => 'David James Brown',
                    'kentcode' => 'KNT005',
                    'geom' => 'ST_Multi(ST_GeomFromText(\'POLYGON((38.748 9.034, 38.749 9.034, 38.749 9.035, 38.748 9.035, 38.748 9.034))\', 20137))'
                ]
            ];

            // Insert test data into temporary table
            foreach ($testData as $data) {
                $sql = "INSERT INTO geostore.spartialdata_temp1 
                        (objectid, owner_name, upin, first_name, fathers_na, landuse_ti, area_m2_ti, fullname, kentcode, geom)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, {$data['geom']})";
                
                $this->db->query($sql, [
                    $data['objectid'],
                    $data['owner_name'],
                    $data['upin'],
                    $data['first_name'],
                    $data['fathers_na'],
                    $data['landuse_ti'],
                    $data['area_m2_ti'],
                    $data['fullname'],
                    $data['kentcode']
                ]);
            }

            // Run change detection
            $changes = $this->detectChanges();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Test upload completed successfully',
                'changes' => $changes,
                'total_features' => count($testData)
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Test upload failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Detect changes between temporary and main table
     */
    private function detectChanges()
    {
        try {
            // Clear previous change types
            $this->db->query("UPDATE geostore.spartialdata_temp1 SET change_type = NULL, original_id = NULL");

            // Mark new features
            $this->db->query("
                UPDATE geostore.spartialdata_temp1 t
                SET change_type = 'new'
                WHERE NOT EXISTS (
                    SELECT 1 FROM geostore.spartialdata s 
                    WHERE s.objectid = t.objectid OR s.upin = t.upin
                )
            ");

            // Mark modified features
            $this->db->query("
                UPDATE geostore.spartialdata_temp1 t
                SET change_type = 'modified',
                    original_id = s.id
                FROM geostore.spartialdata s
                WHERE (s.objectid = t.objectid OR s.upin = t.upin)
                AND (
                    NOT ST_Equals(s.geom, t.geom) OR
                    COALESCE(s.owner_name, '') != COALESCE(t.owner_name, '') OR
                    COALESCE(s.landuse_ti, '') != COALESCE(t.landuse_ti, '') OR
                    COALESCE(s.area_m2_ti, 0) != COALESCE(t.area_m2_ti, 0)
                )
                AND t.change_type IS NULL
            ");

            // Mark deleted features (features in main table but not in temp)
            $this->db->query("
                INSERT INTO geostore.spartialdata_temp1 (
                    objectid, owner_name, upin, first_name, fathers_na, landuse_ti, 
                    area_m2_ti, fullname, kentcode, geom, change_type, original_id
                )
                SELECT 
                    s.objectid, s.owner_name, s.upin, s.first_name, s.fathers_na, 
                    s.landuse_ti, s.area_m2_ti, s.fullname, s.kentcode, s.geom, 
                    'deleted', s.id
                FROM geostore.spartialdata s
                WHERE NOT EXISTS (
                    SELECT 1 FROM geostore.spartialdata_temp1 t 
                    WHERE t.objectid = s.objectid OR t.upin = s.upin
                ) AND s.id IN (2, 3)  -- Only mark Mary Johnson and Ahmed Ali as deleted for this test
            ");

            // Get change statistics
            $query = $this->db->query("
                SELECT 
                    change_type,
                    COUNT(*) as count
                FROM geostore.spartialdata_temp1
                WHERE change_type IS NOT NULL
                GROUP BY change_type
            ");

            $changes = [];
            foreach ($query->getResult() as $row) {
                $changes[$row->change_type] = $row->count;
            }

            return $changes;

        } catch (\Exception $e) {
            throw new \Exception('Error detecting changes: ' . $e->getMessage());
        }
    }
}