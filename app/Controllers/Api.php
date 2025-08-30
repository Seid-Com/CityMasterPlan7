<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Api extends ResourceController
{
    protected $format = 'json';
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get all parcels as GeoJSON
     */
    public function parcels()
    {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        try {
            // Fetch from main geostore.spartialdata table with all attributes
            $query = $this->db->query("
                SELECT json_build_object(
                    'type', 'FeatureCollection',
                    'features', COALESCE(json_agg(
                        json_build_object(
                            'type', 'Feature',
                            'geometry', ST_AsGeoJSON(ST_Transform(geom, 4326))::json,
                            'properties', json_build_object(
                                'id', id,
                                'objectid', objectid,
                                'owner_name', owner_name,
                                'upin', upin,
                                'region_cod', region_cod,
                                'city_code', city_code,
                                'kebele_cod', kebele_cod,
                                'nhd_code', nhd_code,
                                'block_code', block_code,
                                'parcel_cod', parcel_cod,
                                'first_name', first_name,
                                'fathers_na', fathers_na,
                                'grandfathe', grandfathe,
                                'titledeed_', titledeed_,
                                'land_acqui', land_acqui,
                                'acquisitio', acquisitio,
                                'land_tenur', land_tenur,
                                'landuse_ti', landuse_ti,
                                'landuse_ex', landuse_ex,
                                'area_m2_ti', area_m2_ti,
                                'area_m2_ta', area_m2_ta,
                                'last_tax_p', last_tax_p,
                                'file_no', file_no,
                                'link_statu', link_statu,
                                'area_diffe', area_diffe,
                                'associatio', associatio,
                                'fullname', fullname,
                                'file_type', file_type,
                                'shape_leng', shape_leng,
                                'shape_area', shape_area,
                                'kentcode', kentcode,
                                'ha', ha,
                                'registerda', registerda,
                                'change_type', 'existing'
                            )
                        )
                    ) FILTER (WHERE geom IS NOT NULL), '[]'::json)
                ) AS geojson
                FROM geostore.spartialdata
                WHERE geom IS NOT NULL
                LIMIT 20000
            ");
            
            $row = $query->getRow();
            
            if ($row && $row->geojson) {
                return $this->respond(json_decode($row->geojson));
            } else {
                return $this->respond([
                    'type' => 'FeatureCollection',
                    'features' => $this->getDemoFeatures()
                ]);
            }
        } catch (\Exception $e) {
            // Return demo data if database is not available
            return $this->respond([
                'type' => 'FeatureCollection',
                'features' => $this->getDemoFeatures()
            ]);
        }
    }

    /**
     * Get demo features for when database is not available
     */
    private function getDemoFeatures()
    {
        return [
            [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [39.6050, 11.8300],
                        [39.6080, 11.8300],
                        [39.6080, 11.8330],
                        [39.6050, 11.8330],
                        [39.6050, 11.8300]
                    ]]
                ],
                'properties' => [
                    'id' => 1,
                    'owner_name' => 'Demo Property 1',
                    'landuse_ti' => 'Residential',
                    'area_m2_ti' => 500.5,
                    'change_type' => 'demo'
                ]
            ],
            [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [39.6100, 11.8350],
                        [39.6130, 11.8350],
                        [39.6130, 11.8380],
                        [39.6100, 11.8380],
                        [39.6100, 11.8350]
                    ]]
                ],
                'properties' => [
                    'id' => 2,
                    'owner_name' => 'Demo Property 2',
                    'landuse_ti' => 'Commercial',
                    'area_m2_ti' => 750.2,
                    'change_type' => 'demo'
                ]
            ]
        ];
    }

    /**
     * Get single parcel by ID
     */
    public function parcel($id = null)
    {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if (!$id) {
            return $this->fail('Parcel ID is required', 400);
        }

        try {
            $query = $this->db->query("
                SELECT json_build_object(
                    'type', 'Feature',
                    'geometry', ST_AsGeoJSON(geom)::json,
                    'properties', json_build_object(
                        'id', id,
                        'objectid', objectid,
                        'owner_name', owner_name,
                        'upin', upin,
                        'first_name', first_name,
                        'fathers_na', fathers_na,
                        'landuse_ti', landuse_ti,
                        'area_m2_ti', area_m2_ti,
                        'fullname', fullname,
                        'kentcode', kentcode
                    )
                ) AS feature
                FROM geostore.spartialdata
                WHERE id = ?
            ", [$id]);

            $row = $query->getRow();
            
            if ($row) {
                return $this->respond(json_decode($row->feature));
            } else {
                return $this->failNotFound('Parcel not found');
            }
        } catch (\Exception $e) {
            return $this->failServerError('Error fetching parcel: ' . $e->getMessage());
        }
    }

    /**
     * Create new parcel
     */
    public function createParcel()
    {
        $data = $this->request->getJSON();
        
        if (!$data || !isset($data->geometry) || !isset($data->properties)) {
            return $this->fail('Invalid GeoJSON format', 400);
        }

        try {
            $properties = $data->properties;
            $geometry = json_encode($data->geometry);

            $sql = "
                INSERT INTO geostore.spartialdata 
                (owner_name, upin, first_name, fathers_na, landuse_ti, area_m2_ti, fullname, kentcode, geom)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromGeoJSON(?))
                RETURNING id
            ";

            $query = $this->db->query($sql, [
                $properties->owner_name ?? null,
                $properties->upin ?? null,
                $properties->first_name ?? null,
                $properties->fathers_na ?? null,
                $properties->landuse_ti ?? null,
                $properties->area_m2_ti ?? null,
                $properties->fullname ?? null,
                $properties->kentcode ?? null,
                $geometry
            ]);

            $result = $query->getRow();
            
            return $this->respondCreated(['id' => $result->id, 'status' => 'Parcel created successfully']);
        } catch (\Exception $e) {
            return $this->failServerError('Error creating parcel: ' . $e->getMessage());
        }
    }

    /**
     * Update parcel
     */
    public function updateParcel($id = null)
    {
        if (!$id) {
            return $this->fail('Parcel ID is required', 400);
        }

        $data = $this->request->getJSON();
        
        if (!$data || !isset($data->properties)) {
            return $this->fail('Invalid data format', 400);
        }

        try {
            $properties = $data->properties;
            $geometry = isset($data->geometry) ? json_encode($data->geometry) : null;

            $sql = "
                UPDATE geostore.spartialdata 
                SET owner_name = ?, upin = ?, first_name = ?, fathers_na = ?, 
                    landuse_ti = ?, area_m2_ti = ?, fullname = ?, kentcode = ?";
            
            $params = [
                $properties->owner_name ?? null,
                $properties->upin ?? null,
                $properties->first_name ?? null,
                $properties->fathers_na ?? null,
                $properties->landuse_ti ?? null,
                $properties->area_m2_ti ?? null,
                $properties->fullname ?? null,
                $properties->kentcode ?? null
            ];

            if ($geometry) {
                $sql .= ", geom = ST_GeomFromGeoJSON(?)";
                $params[] = $geometry;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $this->db->query($sql, $params);
            
            return $this->respond(['status' => 'Parcel updated successfully']);
        } catch (\Exception $e) {
            return $this->failServerError('Error updating parcel: ' . $e->getMessage());
        }
    }

    /**
     * Delete parcel
     */
    public function deleteParcel($id = null)
    {
        if (!$id) {
            return $this->fail('Parcel ID is required', 400);
        }

        try {
            $this->db->query("DELETE FROM geostore.spartialdata WHERE id = ?", [$id]);
            return $this->respond(['status' => 'Parcel deleted successfully']);
        } catch (\Exception $e) {
            return $this->failServerError('Error deleting parcel: ' . $e->getMessage());
        }
    }

    /**
     * Get pending changes from temporary table
     */
    public function getChanges()
    {
        try {
            // Try our new spatial_changes table first
            $query = $this->db->query("
                SELECT json_build_object(
                    'type', 'FeatureCollection',
                    'features', COALESCE(json_agg(
                        json_build_object(
                            'type', 'Feature',
                            'geometry', ST_AsGeoJSON(p.geometry)::json,
                            'properties', json_build_object(
                                'id', c.id,
                                'parcel_id', c.parcel_id,
                                'owner_name', p.owner_name,
                                'upin', p.upin,
                                'landuse_ti', p.landuse_ti,
                                'area_m2_ti', p.area_m2_ti,
                                'fullname', p.fullname,
                                'change_type', c.change_type,
                                'change_date', c.change_date
                            ) || COALESCE(c.new_data, '{}'::jsonb)
                        )
                    ) FILTER (WHERE c.change_type IS NOT NULL), '[]'::json)
                ) AS geojson
                FROM spatial_changes c
                LEFT JOIN spatial_parcels p ON c.parcel_id = p.id
                WHERE c.approved = false
            ");

            $row = $query->getRow();
            
            if ($row && isset($row->geojson) && $row->geojson) {
                $geojsonData = json_decode($row->geojson, true);
                if (isset($geojsonData['features']) && count($geojsonData['features']) > 0) {
                    return $this->respond($geojsonData);
                }
            }
            
            // Fallback to geostore.spartialdata_temp1 if exists
            $query = $this->db->query("
                SELECT json_build_object(
                    'type', 'FeatureCollection',
                    'features', COALESCE(json_agg(
                        json_build_object(
                            'type', 'Feature',
                            'geometry', ST_AsGeoJSON(geom)::json,
                            'properties', json_build_object(
                                'id', id,
                                'objectid', objectid,
                                'owner_name', owner_name,
                                'upin', upin,
                                'first_name', first_name,
                                'fathers_na', fathers_na,
                                'landuse_ti', landuse_ti,
                                'area_m2_ti', area_m2_ti,
                                'fullname', fullname,
                                'kentcode', kentcode,
                                'change_type', change_type
                            )
                        )
                    ) FILTER (WHERE change_type IS NOT NULL), '[]'::json)
                ) AS geojson
                FROM geostore.spartialdata_temp1
                WHERE change_type IN ('new', 'modified', 'deleted')
            ");

            $row = $query->getRow();
            
            if ($row && isset($row->geojson) && $row->geojson) {
                $geojsonData = json_decode($row->geojson, true);
                return $this->respond($geojsonData);
            } else {
                return $this->respond([
                    'type' => 'FeatureCollection',
                    'features' => []
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error in getChanges: ' . $e->getMessage());
            return $this->respond([
                'type' => 'FeatureCollection', 
                'features' => []
            ]);
        }
    }

    /**
     * Apply approved changes to main table
     */
    public function applyChanges()
    {
        $data = $this->request->getJSON();
        $approvedIds = $data->approved_ids ?? [];

        if (empty($approvedIds)) {
            return $this->fail('No changes selected for approval', 400);
        }

        try {
            log_message('info', 'Starting approval process for IDs: ' . json_encode($approvedIds));
            $this->db->transStart();

            // Apply new features - only essential columns
            $idList = implode(',', array_map('intval', $approvedIds));
            $this->db->query("
                INSERT INTO geostore.spartialdata 
                (objectid, owner_name, upin, first_name, fathers_na, landuse_ti, 
                 area_m2_ti, fullname, kentcode, geom)
                SELECT objectid, owner_name, upin, first_name, fathers_na, landuse_ti, 
                       area_m2_ti, fullname, kentcode, geom
                FROM geostore.spartialdata_temp1 
                WHERE change_type = 'new' AND id IN ($idList)"
            );

            // Apply modified features
            $this->db->query("
                UPDATE geostore.spartialdata s
                SET owner_name = t.owner_name, upin = t.upin, first_name = t.first_name,
                    fathers_na = t.fathers_na, landuse_ti = t.landuse_ti, 
                    area_m2_ti = t.area_m2_ti, fullname = t.fullname, 
                    kentcode = t.kentcode, geom = t.geom
                FROM geostore.spartialdata_temp1 t
                WHERE s.id = t.original_id AND t.change_type = 'modified' AND t.id IN ($idList)"
            );

            // Apply deleted features
            $this->db->query("
                DELETE FROM geostore.spartialdata 
                WHERE id IN (
                    SELECT original_id FROM geostore.spartialdata_temp1 
                    WHERE change_type = 'deleted' AND id IN ($idList)
                )"
            );

            // Remove processed changes from temp table
            $this->db->query("
                DELETE FROM geostore.spartialdata_temp1 
                WHERE id IN ($idList)"
            );

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->failServerError('Error applying changes');
            }

            return $this->respond(['status' => 'Changes applied successfully']);
        } catch (\Exception $e) {
            log_message('error', 'Error applying changes: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->failServerError('Error applying changes: ' . $e->getMessage());
        }
    }

    /**
     * Reject changes
     */
    public function rejectChanges()
    {
        $data = $this->request->getJSON();
        $rejectedIds = $data->rejected_ids ?? [];

        if (empty($rejectedIds)) {
            return $this->fail('No changes selected for rejection', 400);
        }

        try {
            $idList = implode(',', array_map('intval', $rejectedIds));
            $this->db->query("
                DELETE FROM geostore.spartialdata_temp1 
                WHERE id IN ($idList)"
            );

            return $this->respond(['status' => 'Changes rejected successfully']);
        } catch (\Exception $e) {
            return $this->failServerError('Error rejecting changes: ' . $e->getMessage());
        }
    }
}
