<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Upload extends BaseController
{
    protected $db;
    protected $extractionPath = null;

    public function __construct()
    {
        // Try to connect to database, but don't fail if it's not available
        try {
            $testConnection = \Config\Database::connect();
            $testConnection->simpleQuery('SELECT 1'); // Test the connection
            $this->db = $testConnection;
        } catch (\Exception $e) {
            $this->db = null;
            log_message('info', 'Database not available, running in demo mode: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $data = [
            'title' => 'City Master Plan - Upload Shapefile'
        ];

        return view('upload', $data);
    }

    /**
     * Test endpoint to verify JSON response
     */
    public function testJson()
    {
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $this->response->setContentType('application/json');
        $this->response->setBody(json_encode(['test' => 'success', 'message' => 'JSON working']));
        $this->response->send();
        exit();
    }

    /**
     * Process uploaded shapefile
     */
    public function processShapefile()
    {
        // Clear all output buffers to ensure clean JSON response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start clean output buffer
        ob_start();
        
        // Disable all error reporting to prevent PHP warnings from corrupting JSON
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('html_errors', 0);
        
        // Set JSON content type header immediately
        $this->response->setContentType('application/json');
        $this->response->setHeader('Cache-Control', 'no-cache');
        
        // Basic file validation without complex mime type checking
        $file = $this->request->getFile('shapefile');
        
        if (!$file || !$file->isValid()) {
            ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file upload: ' . ($file ? $file->getErrorString() : 'No file provided')
            ]);
            exit();
        }
        
        // Check file size (500MB limit)
        if ($file->getSize() > 500 * 1024 * 1024) {
            ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'File size exceeds 500MB limit'
            ]);
            exit();
        }
        
        // Check file extension
        $allowedExtensions = ['zip', 'shp'];
        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, $allowedExtensions)) {
            ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Only ZIP and SHP files are allowed.'
            ]);
            exit();
        }

        try {
            // Create upload directory if it doesn't exist
            $uploadPath = WRITEPATH . 'uploads/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Generate unique filename
            $fileName = time() . '_' . $file->getClientName();
            $filePath = $uploadPath . $fileName;

            // Move uploaded file
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('Failed to move uploaded file');
            }

            // Process the shapefile
            $result = $this->processShapefileData($filePath, $file->getClientExtension());

            // Clean up uploaded file and extraction directory
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Clean up extraction directory if it was created
            if (isset($this->extractionPath) && is_dir($this->extractionPath)) {
                $this->removeDirectory($this->extractionPath);
                $this->extractionPath = null;
            }

            // Clean output buffer
            ob_clean();
            
            // Ensure clean JSON output
            $json = json_encode($result);
            if ($json === false) {
                throw new \Exception('Failed to encode JSON response');
            }
            
            // Send clean JSON response directly
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-cache');
            echo $json;
            exit();

        } catch (\Exception $e) {
            // Ensure JSON response even on error
            log_message('error', 'Upload error: ' . $e->getMessage());
            
            // Clean output buffer
            ob_clean();
            
            $errorResponse = [
                'success' => false,
                'message' => 'Error processing shapefile: ' . $e->getMessage()
            ];
            
            // Send clean JSON error response directly
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-cache');
            echo json_encode($errorResponse);
            exit();
        }
    }

    /**
     * Process shapefile data and store in temporary table
     */
    private function processShapefileData($filePath, $extension)
    {
        try {
            // If no database connection, simulate processing with demo data
            if (!$this->db) {
                return [
                    'success' => true,
                    'message' => 'Shapefile processed successfully (demo mode - database not available)',
                    'changes' => [
                        'new' => 2,
                        'modified' => 1,
                        'deleted' => 0
                    ],
                    'center' => [11.8311, 39.6069] // Woldia coordinates
                ];
            }

            // Create temporary table if it doesn't exist
            $this->createTempTable();

            // Clear existing temporary data
            $this->db->query("TRUNCATE TABLE geostore.spartialdata_temp1");

            if ($extension === 'zip') {
                // Extract zip file
                $zip = new \ZipArchive();
                $extractPath = dirname($filePath) . '/extracted_' . time() . '/';
                
                if ($zip->open($filePath) === TRUE) {
                    // Create extraction directory
                    if (!is_dir($extractPath)) {
                        mkdir($extractPath, 0777, true);
                    }
                    
                    $zip->extractTo($extractPath);
                    $zip->close();
                    
                    // Find .shp file in extracted contents (recursively)
                    $shpFile = $this->findShapefileInDirectoryRecursive($extractPath);
                    if (!$shpFile) {
                        // Clean up extraction directory
                        $this->removeDirectory($extractPath);
                        throw new \Exception('No shapefile found in zip archive');
                    }
                    $filePath = $shpFile;
                    
                    // Store extraction path for cleanup later
                    $this->extractionPath = $extractPath;
                } else {
                    throw new \Exception('Cannot open zip file');
                }
            }

            // Use ogr2ogr to import shapefile to temporary table
            $dbHost = getenv('PGHOST') ?: 'localhost';
            $dbName = getenv('PGDATABASE') ?: 'city_masterplan';
            $dbUser = getenv('PGUSER') ?: 'postgres';
            $dbPass = getenv('PGPASSWORD') ?: 'root';
            $dbPort = getenv('PGPORT') ?: '5432';

            $connectionString = "PG:host={$dbHost} port={$dbPort} dbname={$dbName} user={$dbUser} password={$dbPass}";
            
            $command = sprintf(
                'ogr2ogr -f "PostgreSQL" "%s" "%s" -nln "geostore.spartialdata_temp1" -overwrite -lco GEOMETRY_NAME=geom -a_srs EPSG:4326 2>&1',
                $connectionString,
                $filePath
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                // Fallback: try to read shapefile with PHP
                $this->readShapefileWithPHP($filePath);
            }

            // Detect changes
            $changes = $this->detectChanges();

            return [
                'success' => true,
                'message' => 'Shapefile processed successfully',
                'changes' => $changes,
                'center' => [11.8311, 39.6069] // Woldia coordinates
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to process shapefile: ' . $e->getMessage());
        } catch (\TypeError $e) {
            // Handle type errors from invalid shapefile format
            throw new \Exception('Invalid shapefile format. The uploaded file appears to be corrupted or not a valid shapefile. Please ensure you are uploading shapefiles created by GIS software.');
        } catch (\Error $e) {
            // Handle any other errors
            throw new \Exception('Error reading shapefile: The file format is invalid or corrupted.');
        }
    }

    /**
     * Find shapefile in extracted directory
     */
    private function findShapefileInDirectory($directory)
    {
        $files = scandir($directory);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'shp') {
                return $directory . $file;
            }
        }
        return null;
    }
    
    /**
     * Find shapefile in directory recursively
     */
    private function findShapefileInDirectoryRecursive($directory)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'shp') {
                return $file->getPathname();
            }
        }
        
        return null;
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Create temporary table for shapefile data
     */
    private function createTempTable()
    {
        if (!$this->db) return;
        
        try {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS geostore.spartialdata_temp1 (
                    LIKE geostore.spartialdata INCLUDING ALL
                )
            ");

            // Add change tracking columns
            $this->db->query("
                ALTER TABLE geostore.spartialdata_temp1 
                ADD COLUMN IF NOT EXISTS change_type VARCHAR(20),
                ADD COLUMN IF NOT EXISTS original_id INTEGER
            ");
        } catch (\Exception $e) {
            log_message('error', 'Failed to create temp table: ' . $e->getMessage());
        }
    }
    
    /**
     * Get attribute value with fallback options
     */
    private function getAttributeValue($data, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key])) {
                $value = trim($data[$key]);
                return $value === '' ? null : $value;
            }
        }
        return null;
    }
    
    /**
     * Get numeric value with fallback options
     */
    private function getNumericValue($data, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key])) {
                $value = trim($data[$key]);
                if (is_numeric($value)) {
                    return (float) $value;
                }
            }
        }
        return null;
    }
    
    /**
     * Get date value with fallback options
     */
    private function getDateValue($data, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key])) {
                $value = trim($data[$key]);
                if ($value && $value !== '') {
                    try {
                        $date = new \DateTime($value);
                        return $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Try parsing as timestamp
                        if (is_numeric($value)) {
                            $date = new \DateTime('@' . $value);
                            return $date->format('Y-m-d');
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Read shapefile using PHP (fallback method)
     */
    private function readShapefileWithPHP($filePath)
    {
        try {
            // Check if companion files exist for .shp file
            if (pathinfo($filePath, PATHINFO_EXTENSION) === 'shp') {
                $basePath = preg_replace('/\.shp$/i', '', $filePath);
                $dbfFile = $basePath . '.dbf';
                $shxFile = $basePath . '.shx';
                
                if (!file_exists($dbfFile)) {
                    throw new \Exception('Missing required .dbf file. Shapefiles require .shp, .dbf, and .shx files together. Please upload all files in a ZIP archive.');
                }
                if (!file_exists($shxFile)) {
                    throw new \Exception('Missing required .shx file. Shapefiles require .shp, .dbf, and .shx files together. Please upload all files in a ZIP archive.');
                }
            }
            
            // Check if the file exists and is readable
            if (!file_exists($filePath)) {
                throw new \Exception('Uploaded file not found at: ' . $filePath);
            }
            
            if (!is_readable($filePath)) {
                throw new \Exception('Uploaded file is not readable: ' . $filePath);
            }
            
            // Log file information for debugging
            log_message('info', 'Processing shapefile: ' . $filePath);
            log_message('info', 'File size: ' . filesize($filePath) . ' bytes');
            log_message('info', 'File permissions: ' . substr(sprintf('%o', fileperms($filePath)), -4));
            
            // Use the installed PHP Shapefile library with polygon ring handling
            $options = [
                \Shapefile\Shapefile::OPTION_POLYGON_CLOSED_RINGS_ACTION => \Shapefile\Shapefile::ACTION_FORCE,
                \Shapefile\Shapefile::OPTION_POLYGON_OUTPUT_ORIENTATION => \Shapefile\Shapefile::ORIENTATION_CLOCKWISE,
                \Shapefile\Shapefile::OPTION_FORCE_MULTIPART_GEOMETRIES => false
            ];
            
            try {
                $shapefile = new \Shapefile\ShapefileReader($filePath, $options);
            } catch (\Exception $e) {
                // Check if this is a file format issue
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'must be of type array') !== false || 
                    strpos($errorMessage, 'Invalid') !== false ||
                    strpos($errorMessage, 'corrupted') !== false) {
                    throw new \Exception('Invalid shapefile format. Please ensure you are uploading a valid shapefile created by GIS software (e.g., QGIS, ArcGIS, etc.). The file appears to be corrupted or not a valid shapefile.');
                } else {
                    throw new \Exception('Failed to open shapefile: ' . $errorMessage . '. Make sure all required files (.shp, .dbf, .shx) are present.');
                }
            }
            
            // Clear the temporary table
            $this->db->query("DELETE FROM geostore.spartialdata_temp1");
            
            $recordCount = 0;
            
            // Read each record from the shapefile
            while ($record = $shapefile->fetchRecord()) {
                if ($record->isDeleted()) {
                    continue;
                }
                
                // Get geometry as WKT directly from record
                $wkt = $record->getWKT();
                
                // Validate WKT format
                if (empty($wkt) || strlen($wkt) < 10) {
                    log_message('warning', "Skipping record with invalid geometry: {$wkt}");
                    continue;
                }
                
                // Clean and validate the WKT string
                $wkt = trim($wkt);
                
                // Final validation - ensure geometry string is complete
                if (!$this->isValidWKT($wkt)) {
                    log_message('warning', "Skipping record with malformed WKT: " . substr($wkt, 0, 100) . "...");
                    continue;
                }
                
                // Auto-detect coordinate system based on coordinate ranges
                $srid = $this->detectCoordinateSystem($wkt);
                
                // Get all data attributes
                $data = $record->getDataArray();
                
                // Map shapefile attributes to database columns with all your schema fields
                $mappedData = [
                    'objectid' => $this->getAttributeValue($data, ['objectid', 'OBJECTID', 'FID', 'id']),
                    'owner_name' => $this->getAttributeValue($data, ['owner_name', 'OWNER_NAME', 'owner', 'name']),
                    'upin' => $this->getAttributeValue($data, ['upin', 'UPIN', 'pin', 'parcel_id']),
                    'region_cod' => $this->getAttributeValue($data, ['region_cod', 'REGION_COD', 'region']),
                    'city_code' => $this->getAttributeValue($data, ['city_code', 'CITY_CODE', 'city']),
                    'kebele_cod' => $this->getAttributeValue($data, ['kebele_cod', 'KEBELE_COD', 'kebele']),
                    'nhd_code' => $this->getAttributeValue($data, ['nhd_code', 'NHD_CODE', 'nhd']),
                    'block_code' => $this->getAttributeValue($data, ['block_code', 'BLOCK_CODE', 'block']),
                    'parcel_cod' => $this->getAttributeValue($data, ['parcel_cod', 'PARCEL_COD', 'parcel']),
                    'first_name' => $this->getAttributeValue($data, ['first_name', 'FIRST_NAME', 'fname']),
                    'fathers_na' => $this->getAttributeValue($data, ['fathers_na', 'FATHERS_NA', 'father']),
                    'grandfathe' => $this->getAttributeValue($data, ['grandfathe', 'GRANDFATHE', 'grandfather']),
                    'titledeed_' => $this->getAttributeValue($data, ['titledeed_', 'TITLEDEED_', 'title']),
                    'land_acqui' => $this->getAttributeValue($data, ['land_acqui', 'LAND_ACQUI', 'acquisition']),
                    'acquisitio' => $this->getAttributeValue($data, ['acquisitio', 'ACQUISITIO', 'acq_year']),
                    'land_tenur' => $this->getAttributeValue($data, ['land_tenur', 'LAND_TENUR', 'tenure']),
                    'landuse_ti' => $this->getAttributeValue($data, ['landuse_ti', 'LANDUSE_TI', 'landuse', 'land_use']),
                    'landuse_ex' => $this->getAttributeValue($data, ['landuse_ex', 'LANDUSE_EX', 'existing_use']),
                    'area_m2_ti' => $this->getNumericValue($data, ['area_m2_ti', 'AREA_M2_TI', 'area_m2', 'area']),
                    'area_m2_ta' => $this->getNumericValue($data, ['area_m2_ta', 'AREA_M2_TA', 'area_tax']),
                    'last_tax_p' => $this->getAttributeValue($data, ['last_tax_p', 'LAST_TAX_P', 'tax_year']),
                    'file_no' => $this->getAttributeValue($data, ['file_no', 'FILE_NO', 'file_num']),
                    'link_statu' => $this->getAttributeValue($data, ['link_statu', 'LINK_STATU', 'status']),
                    'area_diffe' => $this->getNumericValue($data, ['area_diffe', 'AREA_DIFFE', 'area_diff']),
                    'associatio' => $this->getAttributeValue($data, ['associatio', 'ASSOCIATIO', 'association']),
                    'fullname' => $this->getAttributeValue($data, ['fullname', 'FULLNAME', 'full_name']),
                    'file_type' => $this->getAttributeValue($data, ['file_type', 'FILE_TYPE', 'type']),
                    'shape_leng' => $this->getNumericValue($data, ['shape_leng', 'SHAPE_LENG', 'length']),
                    'shape_area' => $this->getNumericValue($data, ['shape_area', 'SHAPE_AREA', 'shape_area']),
                    'kentcode' => $this->getAttributeValue($data, ['kentcode', 'KENTCODE', 'kent']),
                    'ha' => $this->getNumericValue($data, ['ha', 'HA', 'hectares']),
                    'registerda' => $this->getDateValue($data, ['registerda', 'REGISTERDA', 'reg_date'])
                ];
                
                // Build SQL insert statement with proper coordinate system handling
                $sql = "INSERT INTO geostore.spartialdata_temp1 (
                    geom, objectid, owner_name, upin, region_cod, city_code, kebele_cod, nhd_code,
                    block_code, parcel_cod, first_name, fathers_na, grandfathe, titledeed_, 
                    land_acqui, acquisitio, land_tenur, landuse_ti, landuse_ex, area_m2_ti, 
                    area_m2_ta, last_tax_p, file_no, link_statu, area_diffe, associatio, 
                    fullname, file_type, shape_leng, shape_area, kentcode, ha, registerda
                ) VALUES (
                    " . ($srid == 20137 ? "ST_GeomFromText(?, 20137)" : "ST_Transform(ST_GeomFromText(?, ?), 20137)") . ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
                
                // Prepare parameters based on coordinate system
                $params = [$wkt];
                if ($srid != 20137) {
                    $params[] = $srid;
                }
                $params = array_merge($params, [
                    $mappedData['objectid'],
                    $mappedData['owner_name'],
                    $mappedData['upin'],
                    $mappedData['region_cod'],
                    $mappedData['city_code'],
                    $mappedData['kebele_cod'],
                    $mappedData['nhd_code'],
                    $mappedData['block_code'],
                    $mappedData['parcel_cod'],
                    $mappedData['first_name'],
                    $mappedData['fathers_na'],
                    $mappedData['grandfathe'],
                    $mappedData['titledeed_'],
                    $mappedData['land_acqui'],
                    $mappedData['acquisitio'],
                    $mappedData['land_tenur'],
                    $mappedData['landuse_ti'],
                    $mappedData['landuse_ex'],
                    $mappedData['area_m2_ti'],
                    $mappedData['area_m2_ta'],
                    $mappedData['last_tax_p'],
                    $mappedData['file_no'],
                    $mappedData['link_statu'],
                    $mappedData['area_diffe'],
                    $mappedData['associatio'],
                    $mappedData['fullname'],
                    $mappedData['file_type'],
                    $mappedData['shape_leng'],
                    $mappedData['shape_area'],
                    $mappedData['kentcode'],
                    $mappedData['ha'],
                    $mappedData['registerda']
                ]);
                
                $this->db->query($sql, $params);
                
                $recordCount++;
            }
            
            log_message('info', "Successfully imported {$recordCount} records using PHP Shapefile reader");
            
        } catch (\Exception $e) {
            throw new \Exception('PHP Shapefile reading failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate WKT geometry string
     */
    private function isValidWKT($wkt)
    {
        // Basic validation - check for proper opening/closing parentheses
        $openCount = substr_count($wkt, '(');
        $closeCount = substr_count($wkt, ')');
        
        // Check for valid geometry types and proper structure
        $validTypes = ['POLYGON', 'MULTIPOLYGON', 'POINT', 'MULTIPOINT', 'LINESTRING', 'MULTILINESTRING'];
        $hasValidType = false;
        
        foreach ($validTypes as $type) {
            if (strpos($wkt, $type) === 0) {
                $hasValidType = true;
                break;
            }
        }
        
        return $openCount === $closeCount && $openCount > 0 && $hasValidType && 
               !preg_match('/\s[0-9]+\s*$/', $wkt); // Check for truncated coordinates
    }

    /**
     * Detect coordinate system based on coordinate ranges
     */
    private function detectCoordinateSystem($wkt)
    {
        // Extract first coordinate pair to analyze
        preg_match('/([0-9]+\.?[0-9]*)\s+([0-9]+\.?[0-9]*)/', $wkt, $matches);
        
        if (count($matches) >= 3) {
            $x = (float) $matches[1];
            $y = (float) $matches[2];
            
            // Check if coordinates are in geographic range (lat/lon)
            if ($x >= -180 && $x <= 180 && $y >= -90 && $y <= 90) {
                return 4326; // WGS84 Geographic
            }
            
            // Check for common Ethiopian projected coordinate systems
            if ($x > 100000 && $x < 1000000) {
                // Likely Adindan UTM Zone 37N (Ethiopia)
                return 20137;
            }
        }
        
        // Default to WGS84 if unable to determine
        return 4326;
    }

    /**
     * Detect changes between temporary and main table
     */
    public function detectChanges()
    {
        try {
            // If no database connection, return demo change data
            if (!$this->db) {
                return [
                    'new' => rand(1, 5),
                    'modified' => rand(1, 3),
                    'deleted' => rand(0, 2)
                ];
            }

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
                    s.owner_name != t.owner_name OR
                    s.landuse_ti != t.landuse_ti OR
                    s.area_m2_ti != t.area_m2_ti
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
                )
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
