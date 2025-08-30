<?php 
$includeUploadJS = true;
echo view('templates/header', ['title' => 'City Master Plan - Upload', 'includeUploadJS' => $includeUploadJS]); 
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-upload me-2"></i>
                        Upload Shapefile
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Instructions:</h6>
                        <ul class="mb-0">
                            <li><strong>Recommended:</strong> Upload a ZIP file containing all shapefile components (.shp, .dbf, .shx, .prj files)</li>
                            <li>Individual .shp files require companion .dbf and .shx files to be uploaded together</li>
                            <li>The system will automatically detect changes compared to existing data</li>
                            <li>Review and approve changes before applying to the main database</li>
                        </ul>
                    </div>
                    
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="shapefileInput" class="form-label">Select Shapefile</label>
                            <input type="file" class="form-control" id="shapefileInput" name="shapefile" 
                                   accept=".zip,.shp" required>
                            <div class="form-text">Supported formats: ZIP archives or SHP files (max 500MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload and Process
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" id="clearForm">
                                <i class="fas fa-times me-2"></i>Clear
                            </button>
                        </div>
                    </form>
                    
                    <!-- Progress Bar -->
                    <div id="uploadProgress" class="mb-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">
                                <span class="progress-text">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results -->
                    <div id="uploadResults" style="display: none;">
                        <hr>
                        <h5>Processing Results</h5>
                        <div id="resultsContent"></div>
                    </div>
                </div>
            </div>
            
            <!-- Change Preview -->
            <div class="card mt-4" id="changePreview" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Detected Changes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3" id="changeSummary">
                        <!-- Change statistics will be populated here -->
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped" id="changesTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllChanges" class="form-check-input">
                                    </th>
                                    <th>Type</th>
                                    <th>Feature ID</th>
                                    <th>Owner Name</th>
                                    <th>Land Use</th>
                                    <th>Area (m²)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="changesTableBody">
                                <!-- Changes will be populated here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button class="btn btn-success me-2" id="approveSelected" disabled>
                            <i class="fas fa-check me-2"></i>Approve Selected
                        </button>
                        <button class="btn btn-danger me-2" id="rejectSelected" disabled>
                            <i class="fas fa-times me-2"></i>Reject Selected
                        </button>
                        <button class="btn btn-primary" id="viewOnMap">
                            <i class="fas fa-map me-2"></i>View on Map
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="successMessage">Changes have been processed successfully.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage">An error occurred while processing your request.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize upload configuration
window.uploadConfig = {
    apiBaseUrl: '/api/',
    maxFileSize: 500 * 1024 * 1024, // 500MB
    allowedExtensions: ['zip', 'shp']
};
</script>

<?php echo view('templates/footer'); ?>
