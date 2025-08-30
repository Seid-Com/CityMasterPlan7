<?php 
$includeMapJS = true;
$title = $title ?? 'City Master Plan - Interactive Map';
$center_lat = $center_lat ?? 11.8311;
$center_lng = $center_lng ?? 39.6069;
echo view('templates/header', [
    'title' => $title,
    'includeMapJS' => true
]); 
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Map Container -->
        <div class="col-lg-9">
            <div id="map" class="map-container"></div>
            
            <!-- Map Controls -->
            <div class="map-controls">
                <div class="btn-group-vertical" role="group">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Change Base Map">
                            <i class="fas fa-layer-group"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="osm">OpenStreetMap</a></li>
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="offline">Offline Mode</a></li>
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="satellite">Satellite Imagery</a></li>
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="terrain">Terrain</a></li>
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="streets">Streets</a></li>
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="cartodb">Light Theme</a></li>
                            <li><a class="dropdown-item basemap-option" href="#" data-basemap="dark">Dark Theme</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="toggleLabels" title="Toggle Labels">
                        <i class="fas fa-tags"></i>
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="viewChanges" title="View Changes">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="refreshMap" title="Refresh Map">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Measurement Tools">
                            <i class="fas fa-ruler"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item measurement-tool" href="#" data-tool="distance">
                                <i class="fas fa-arrows-alt-h me-2"></i>Measure Distance
                            </a></li>
                            <li><a class="dropdown-item measurement-tool" href="#" data-tool="area">
                                <i class="fas fa-vector-square me-2"></i>Measure Area
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="clearMeasurements">
                                <i class="fas fa-eraser me-2"></i>Clear Measurements
                            </a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="exportData" title="Export Data">
                        <i class="fas fa-download"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Print & Reports">
                            <i class="fas fa-print"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" id="printMap">
                                <i class="fas fa-print me-2"></i>Print Current View
                            </a></li>
                            <li><a class="dropdown-item" href="#" id="generatePDF">
                                <i class="fas fa-file-pdf me-2"></i>Generate PDF Report
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="exportImage">
                                <i class="fas fa-image me-2"></i>Export as Image
                            </a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" id="toggleBoundaries" title="Administrative Boundaries">
                        <i class="fas fa-map-marked-alt"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" title="Annotation Tools">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item annotation-tool" href="#" data-tool="text">
                                <i class="fas fa-font me-2"></i>Add Text
                            </a></li>
                            <li><a class="dropdown-item annotation-tool" href="#" data-tool="arrow">
                                <i class="fas fa-arrow-right me-2"></i>Draw Arrow
                            </a></li>
                            <li><a class="dropdown-item annotation-tool" href="#" data-tool="highlight">
                                <i class="fas fa-marker me-2"></i>Highlight Area
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="clearAnnotations">
                                <i class="fas fa-eraser me-2"></i>Clear Annotations
                            </a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm" id="coordinateReference" title="Coordinate System">
                        <i class="fas fa-globe"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="sidebar">
                <div class="sidebar-header">
                    <h5><i class="fas fa-layer-group me-2"></i>Map Layers</h5>
                </div>
                
                <div class="sidebar-content">
                    <!-- Search Panel -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-search me-2"></i>Search Parcels</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <input type="text" id="ownerSearch" class="form-control form-control-sm" placeholder="Search by owner name...">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="searchBtn">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="clearSearchBtn">
                                    <i class="fas fa-times me-1"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Layer Management -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-layer-group me-2"></i>Layer Manager</h6>
                            <button class="btn btn-sm btn-outline-primary" id="addLayerGroup">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body" id="layerManager">
                            <!-- Base Layers Group -->
                            <div class="layer-group mb-3">
                                <div class="layer-group-header d-flex justify-content-between align-items-center mb-2">
                                    <strong class="small">Base Maps</strong>
                                    <button class="btn btn-sm btn-link p-0 toggle-group" data-target="baseLayersList">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div id="baseLayersList" class="layer-group-content">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="basemap" id="osmLayer" value="osm" checked>
                                        <label class="form-check-label small" for="osmLayer">OpenStreetMap</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="basemap" id="satelliteLayer" value="satellite">
                                        <label class="form-check-label small" for="satelliteLayer">Satellite</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="basemap" id="terrainLayer" value="terrain">
                                        <label class="form-check-label small" for="terrainLayer">Terrain</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Data Layers Group -->
                            <div class="layer-group mb-3">
                                <div class="layer-group-header d-flex justify-content-between align-items-center mb-2">
                                    <strong class="small">Data Layers</strong>
                                    <div>
                                        <button class="btn btn-sm btn-link p-0 me-1" id="layerStyle" title="Style Options">
                                            <i class="fas fa-palette"></i>
                                        </button>
                                        <button class="btn btn-sm btn-link p-0 toggle-group" data-target="dataLayersList">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="dataLayersList" class="layer-group-content">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="parcelsLayerToggle" checked>
                                            <label class="form-check-label small" for="parcelsLayerToggle">City Parcels</label>
                                        </div>
                                        <div class="layer-controls">
                                            <button class="btn btn-sm btn-link p-0" title="Layer Opacity">
                                                <i class="fas fa-adjust"></i>
                                            </button>
                                            <button class="btn btn-sm btn-link p-0" title="Zoom to Layer">
                                                <i class="fas fa-search-location"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label for="parcelsOpacity" class="form-label small">Opacity:</label>
                                        <input type="range" class="form-range form-range-sm" id="parcelsOpacity" min="0.1" max="1" step="0.1" value="0.8">
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="changesLayerToggle">
                                            <label class="form-check-label small" for="changesLayerToggle">Pending Changes</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Analysis Layers Group -->
                            <div class="layer-group mb-3">
                                <div class="layer-group-header d-flex justify-content-between align-items-center mb-2">
                                    <strong class="small">Analysis & Tools</strong>
                                    <button class="btn btn-sm btn-link p-0 toggle-group" data-target="analysisLayersList">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                <div id="analysisLayersList" class="layer-group-content">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="measurementsToggle">
                                        <label class="form-check-label small" for="measurementsToggle">Measurements</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="bufferAnalysisToggle">
                                        <label class="form-check-label small" for="bufferAnalysisToggle">Buffer Analysis</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="annotationsToggle">
                                        <label class="form-check-label small" for="annotationsToggle">Annotations</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Spatial Query -->
                    <div class="card mb-3 query-builder">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Spatial Query Builder</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="queryField" class="form-label small">Field:</label>
                                <select class="form-select form-select-sm" id="queryField">
                                    <option value="">Select field...</option>
                                    <option value="landuse_ti">Land Use</option>
                                    <option value="area_m2_ti">Area (m²)</option>
                                    <option value="owner_name">Owner Name</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="queryOperator" class="form-label small">Operator:</label>
                                <select class="form-select form-select-sm" id="queryOperator">
                                    <option value="equals">Equals</option>
                                    <option value="contains">Contains</option>
                                    <option value="greater">Greater than</option>
                                    <option value="less">Less than</option>
                                    <option value="within">Within distance</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="queryValue" class="form-label small">Value:</label>
                                <input type="text" class="form-control form-control-sm" id="queryValue" placeholder="Enter value...">
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm" id="executeQuery">
                                    <i class="fas fa-search me-1"></i>Execute Query
                                </button>
                                <button class="btn btn-success btn-sm" id="saveQuery">
                                    <i class="fas fa-save me-1"></i>Save Query
                                </button>
                                <button class="btn btn-secondary btn-sm" id="clearQuery">
                                    <i class="fas fa-times me-1"></i>Clear Results
                                </button>
                            </div>
                            <div class="mt-2 small">
                                <span class="text-light">Results: <span id="queryResultCount">0</span> features</span>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Legend & Symbology -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-palette me-2"></i>Legend & Symbology</h6>
                            <button class="btn btn-sm btn-outline-primary" id="customizeSymbology">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="legend-item d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="legend-color me-2" style="background-color: #ff6b6b;"></span>
                                    <small>Residential</small>
                                </div>
                                <small class="text-muted" id="residentialCount">-</small>
                            </div>
                            <div class="legend-item d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="legend-color me-2" style="background-color: #4ecdc4;"></span>
                                    <small>Commercial</small>
                                </div>
                                <small class="text-muted" id="commercialCount">-</small>
                            </div>
                            <div class="legend-item d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="legend-color me-2" style="background-color: #45b7d1;"></span>
                                    <small>Industrial</small>
                                </div>
                                <small class="text-muted" id="industrialCount">-</small>
                            </div>
                            <div class="legend-item d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="legend-color me-2" style="background-color: #96ceb4;"></span>
                                    <small>Institutional</small>
                                </div>
                                <small class="text-muted" id="institutionalCount">-</small>
                            </div>
                            <div class="legend-item d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="legend-color me-2" style="background-color: #feca57;"></span>
                                    <small>Mixed Use</small>
                                </div>
                                <small class="text-muted" id="mixedCount">-</small>
                            </div>
                            <div class="legend-item d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="legend-color me-2" style="background-color: #ff9ff3;"></span>
                                    <small>Recreation</small>
                                </div>
                                <small class="text-muted" id="recreationCount">-</small>
                            </div>
                            <hr>
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between">
                                    <span>Analysis Layers:</span>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="me-2" style="width: 20px; height: 3px; background: #28a745; border-radius: 2px; display: inline-block;"></span>
                                        <span>Buffer Zones</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="me-2" style="width: 20px; height: 3px; background: #ffc107; border-radius: 2px; display: inline-block;"></span>
                                        <span>Proximity Lines</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="me-2" style="width: 20px; height: 3px; background: #dc3545; border-radius: 2px; display: inline-block;"></span>
                                        <span>Annotations</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Filters -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Data Filters</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="landUseFilter" class="form-label">Land Use Type:</label>
                                <select class="form-select form-select-sm" id="landUseFilter">
                                    <option value="">All Types</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Residential">Residential</option>
                                    <option value="Industrial">Industrial</option>
                                    <option value="Recreation">Recreation</option>
                                    <option value="Mixed">Mixed Use</option>
                                    <option value="Institutional">Institutional</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="areaFilter" class="form-label">Min Area (m²):</label>
                                <input type="range" class="form-range" id="areaFilter" min="0" max="3000" value="0">
                                <div class="d-flex justify-content-between">
                                    <small>0</small>
                                    <small id="areaValue">0</small>
                                    <small>3000+</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="ownerFilter" class="form-label">Owner Contains:</label>
                                <input type="text" class="form-control form-control-sm" id="ownerFilter" placeholder="Search by owner name...">
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary btn-sm" id="applyFilters">
                                    <i class="fas fa-search me-1"></i>Apply Filters
                                </button>
                                <button class="btn btn-outline-secondary btn-sm mt-2" id="clearFilters">
                                    <i class="fas fa-times me-1"></i>Clear All
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Feature Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Feature Information</h6>
                        </div>
                        <div class="card-body" id="featureInfo">
                            <p class="text-muted">Click on a feature to view details</p>
                        </div>
                    </div>
                    
                    <!-- Change Management -->
                    <div class="card mb-3" id="changeManagement" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0">Pending Changes</h6>
                        </div>
                        <div class="card-body">
                            <div id="changesList"></div>
                            <div class="mt-3">
                                <button class="btn btn-success btn-sm me-2" id="applyChanges">
                                    <i class="fas fa-check"></i> Apply Selected
                                </button>
                                <button class="btn btn-danger btn-sm" id="rejectChanges">
                                    <i class="fas fa-times"></i> Reject Selected
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Administrative Boundaries -->
                    <div class="card mb-3" id="boundariesCard" style="display: none;">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Administrative Layers</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="showDistricts" checked>
                                <label class="form-check-label" for="showDistricts">
                                    Districts
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="showZones">
                                <label class="form-check-label" for="showZones">
                                    Planning Zones
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="showNeighborhoods">
                                <label class="form-check-label" for="showNeighborhoods">
                                    Neighborhoods
                                </label>
                            </div>
                            <div class="mt-3">
                                <label for="boundaryOpacity" class="form-label small">Boundary Opacity:</label>
                                <input type="range" class="form-range" id="boundaryOpacity" min="0.1" max="1" step="0.1" value="0.7">
                            </div>
                        </div>
                    </div>

                    <!-- Spatial Analysis -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-search-location me-2"></i>Spatial Analysis</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="bufferDistance" class="form-label small">Buffer Distance (m):</label>
                                <input type="number" class="form-control form-control-sm" id="bufferDistance" value="100" min="10" max="5000">
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm" id="createBuffer">
                                    <i class="fas fa-circle-notch me-1"></i>Create Buffer Zone
                                </button>
                                <button class="btn btn-info btn-sm" id="proximityAnalysis">
                                    <i class="fas fa-crosshairs me-1"></i>Proximity Analysis
                                </button>
                                <button class="btn btn-secondary btn-sm" id="clearAnalysis">
                                    <i class="fas fa-times me-1"></i>Clear Analysis
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number" id="totalParcels">-</div>
                                        <div class="stat-label">Total Parcels</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number" id="pendingChanges">-</div>
                                        <div class="stat-label">Pending Changes</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number small" id="totalArea">-</div>
                                        <div class="stat-label">Total Area (ha)</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="stat-number small" id="selectedArea">-</div>
                                        <div class="stat-label">Selected (ha)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-content">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading spatial data...</p>
    </div>
</div>

<script>
// Initialize map configuration
window.mapConfig = {
    centerLat: <?= isset($center_lat) ? $center_lat : 11.8311 ?>,
    centerLng: <?= isset($center_lng) ? $center_lng : 39.6069 ?>,
    zoom: 13,
    apiBaseUrl: '/api/'
};
</script>

<?php echo view('templates/footer'); ?>
