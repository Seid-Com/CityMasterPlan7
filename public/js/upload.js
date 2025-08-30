// Upload functionality and change management
class UploadManager {
    constructor(config) {
        this.config = config;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFileValidation();
    }

    setupEventListeners() {
        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFileUpload();
        });

        // Clear form
        document.getElementById('clearForm').addEventListener('click', () => {
            this.clearForm();
        });

        // Select all changes checkbox
        document.getElementById('selectAllChanges').addEventListener('change', (e) => {
            this.toggleAllChanges(e.target.checked);
        });

        // Approve selected changes
        document.getElementById('approveSelected').addEventListener('click', () => {
            this.approveSelectedChanges();
        });

        // Reject selected changes
        document.getElementById('rejectSelected').addEventListener('click', () => {
            this.rejectSelectedChanges();
        });

        // View on map button
        document.getElementById('viewOnMap').addEventListener('click', () => {
            // Store flag to center on Woldia after redirect
            localStorage.setItem('centerOnWoldia', 'true');
            localStorage.setItem('uploadSuccess', 'true');
            window.location.href = '/map';
        });

        // Change selection handling
        document.addEventListener('change', (e) => {
            if (e.target.matches('#changesTableBody input[type="checkbox"]')) {
                this.updateActionButtons();
            }
        });
    }

    setupFileValidation() {
        const fileInput = document.getElementById('shapefileInput');
        
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                this.validateFile(file);
            }
        });
    }

    validateFile(file) {
        const maxSize = this.config.maxFileSize;
        const allowedExtensions = this.config.allowedExtensions;
        const fileExtension = file.name.split('.').pop().toLowerCase();

        // Check file size
        if (file.size > maxSize) {
            this.showError(`File size exceeds ${Math.round(maxSize / (1024 * 1024))}MB limit`);
            this.clearFileInput();
            return false;
        }

        // Check file extension
        if (!allowedExtensions.includes(fileExtension)) {
            this.showError(`Invalid file type. Allowed: ${allowedExtensions.join(', ')}`);
            this.clearFileInput();
            return false;
        }

        return true;
    }

    async handleFileUpload() {
        const fileInput = document.getElementById('shapefileInput');
        const file = fileInput.files[0];

        if (!file) {
            this.showError('Please select a file to upload');
            return;
        }

        if (!this.validateFile(file)) {
            return;
        }

        try {
            this.showProgress(0);
            this.disableForm(true);

            const formData = new FormData();
            formData.append('shapefile', file);

            const response = await this.uploadWithProgress(formData);
            
            // Get response as text first
            const responseText = await response.text();
            
            if (!response.ok) {
                let errorMessage = `Upload failed with status ${response.status}`;
                try {
                    const errorData = JSON.parse(responseText);
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    // If response is not JSON, show the raw text (might be PHP error)
                    if (responseText) {
                        console.error('Non-JSON error response:', responseText);
                        errorMessage = 'Server error: Check console for details';
                    }
                }
                throw new Error(errorMessage);
            }

            let result;
            try {
                // Log the raw response for debugging
                console.log('Raw response length:', responseText.length);
                console.log('First 500 chars:', responseText.substring(0, 500));
                
                // First try to parse as-is (if it's valid JSON)
                try {
                    result = JSON.parse(responseText);
                    console.log('Successfully parsed JSON response:', result);
                } catch (initialError) {
                    // If direct parsing fails, try to extract JSON from response
                    console.warn('Direct JSON parsing failed, attempting to extract JSON from response');
                    
                    // Clean response text - remove any non-JSON content before parsing
                    const jsonStart = responseText.indexOf('{');
                    const jsonEnd = responseText.lastIndexOf('}');
                    
                    if (jsonStart === -1 || jsonEnd === -1) {
                        console.error('No JSON markers found in response:', responseText);
                        
                        // Check if response is empty
                        if (!responseText || responseText.trim() === '') {
                            throw new Error('Empty response from server. Please check your network connection.');
                        }
                        
                        throw new Error('Server returned non-JSON response. Please try again.');
                    }
                    
                    const cleanJson = responseText.substring(jsonStart, jsonEnd + 1);
                    console.log('Extracted JSON:', cleanJson);
                    result = JSON.parse(cleanJson);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Full response text:', responseText);
                
                // Check if this might be a PHP error
                if (responseText.includes('Fatal error') || responseText.includes('Warning') || responseText.includes('Notice')) {
                    throw new Error('Server error occurred. Please contact support.');
                } else if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                    throw new Error('Server returned an HTML page instead of data. Please refresh and try again.');
                } else {
                    throw new Error('Unable to process server response. Please try again.');
                }
            }
            
            if (result.success) {
                this.showSuccess('File uploaded and processed successfully');
                this.displayResults(result);
                
                if (result.changes) {
                    await this.loadAndDisplayChanges();
                }
                
                // Auto-redirect to map after successful upload with centering
                setTimeout(() => {
                    localStorage.setItem('centerOnWoldia', 'true');
                    localStorage.setItem('uploadSuccess', 'true');
                    window.location.href = '/map';
                }, 2000);
            } else {
                throw new Error(result.message || 'Processing failed');
            }

        } catch (error) {
            console.error('Upload error:', error);
            let errorMessage = 'Upload failed: ' + error.message;
            
            // Provide more helpful error messages for common issues
            if (error.message.includes('PHP Shapefile reading failed')) {
                errorMessage = 'Cannot read shapefile. Please ensure you upload all required files (.shp, .dbf, .shx) in a ZIP archive.';
            } else if (error.message.includes('Missing required .dbf')) {
                errorMessage = 'Missing .dbf file. Please upload all shapefile components together in a ZIP archive.';
            } else if (error.message.includes('Missing required .shx')) {
                errorMessage = 'Missing .shx file. Please upload all shapefile components together in a ZIP archive.';
            }
            
            this.showError(errorMessage);
        } finally {
            this.hideProgress();
            this.disableForm(false);
        }
    }

    async uploadWithProgress(formData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.updateProgress(percentComplete);
                }
            });

            xhr.addEventListener('load', () => {
                // Log the raw response for debugging
                console.log('Upload response status:', xhr.status);
                console.log('Upload response text:', xhr.responseText);
                
                resolve({
                    ok: xhr.status >= 200 && xhr.status < 300,
                    status: xhr.status,
                    json: () => {
                        try {
                            return Promise.resolve(JSON.parse(xhr.responseText));
                        } catch (e) {
                            console.error('JSON parse error:', e, 'Response:', xhr.responseText);
                            return Promise.reject(new Error('Invalid JSON response from server'));
                        }
                    },
                    text: () => Promise.resolve(xhr.responseText)
                });
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });

            xhr.open('POST', `${this.config.apiBaseUrl}upload/shapefile-clean`);
            xhr.send(formData);
        });
    }

    async loadAndDisplayChanges() {
        try {
            const response = await fetch(`${this.config.apiBaseUrl}changes`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const geojsonData = await response.json();
            
            if (geojsonData.features && geojsonData.features.length > 0) {
                this.displayChanges(geojsonData.features);
            } else {
                this.hideChangePreview();
            }

        } catch (error) {
            console.error('Error loading changes:', error);
            this.showError('Error loading changes: ' + error.message);
        }
    }

    displayResults(result) {
        const resultsDiv = document.getElementById('uploadResults');
        const contentDiv = document.getElementById('resultsContent');

        let content = '<div class="alert alert-success">File processed successfully!</div>';

        if (result.changes) {
            content += '<h6>Change Summary:</h6><div class="row">';
            
            Object.entries(result.changes).forEach(([type, count]) => {
                const badgeClass = this.getChangeBadgeClass(type);
                content += `
                    <div class="col-md-4 mb-2">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <span class="badge ${badgeClass}">${count}</span>
                                </h5>
                                <p class="card-text">${this.capitalizeFirst(type)} Features</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            content += '</div>';
        }

        contentDiv.innerHTML = content;
        resultsDiv.style.display = 'block';
    }

    displayChanges(changes) {
        const previewDiv = document.getElementById('changePreview');
        const summaryDiv = document.getElementById('changeSummary');
        const tableBody = document.getElementById('changesTableBody');

        // Update summary
        const summary = this.calculateChangeSummary(changes);
        summaryDiv.innerHTML = this.createSummaryCards(summary);

        // Populate table
        tableBody.innerHTML = '';
        changes.forEach(change => {
            const row = this.createChangeRow(change);
            tableBody.appendChild(row);
        });

        previewDiv.style.display = 'block';
        this.updateActionButtons();
    }

    calculateChangeSummary(changes) {
        const summary = { new: 0, modified: 0, deleted: 0 };
        changes.forEach(change => {
            const type = change.properties.change_type;
            if (summary.hasOwnProperty(type)) {
                summary[type]++;
            }
        });
        return summary;
    }

    createSummaryCards(summary) {
        let cards = '';
        Object.entries(summary).forEach(([type, count]) => {
            const badgeClass = this.getChangeBadgeClass(type);
            cards += `
                <div class="col-md-4 mb-2">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <span class="badge ${badgeClass}">${count}</span>
                            </h5>
                            <p class="card-text">${this.capitalizeFirst(type)}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        return cards;
    }

    createChangeRow(change) {
        const row = document.createElement('tr');
        const props = change.properties;
        
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input change-checkbox" 
                       value="${props.id}" data-change-type="${props.change_type}">
            </td>
            <td>
                <span class="change-type-badge ${props.change_type}">${props.change_type}</span>
            </td>
            <td>${props.id || 'N/A'}</td>
            <td>${props.owner_name || 'Unknown'}</td>
            <td>${props.landuse_ti || 'Unknown'}</td>
            <td>${props.area_m2_ti ? props.area_m2_ti.toLocaleString() : 'N/A'}</td>
            <td>
                <button class="btn btn-outline-primary btn-sm" onclick="uploadManager.viewFeatureDetails(${props.id})">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        
        return row;
    }

    toggleAllChanges(checked) {
        const checkboxes = document.querySelectorAll('#changesTableBody .change-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateActionButtons();
    }

    updateActionButtons() {
        const checkedBoxes = document.querySelectorAll('#changesTableBody .change-checkbox:checked');
        const approveBtn = document.getElementById('approveSelected');
        const rejectBtn = document.getElementById('rejectSelected');
        
        const hasSelection = checkedBoxes.length > 0;
        approveBtn.disabled = !hasSelection;
        rejectBtn.disabled = !hasSelection;
        
        // Update select all checkbox state
        const allBoxes = document.querySelectorAll('#changesTableBody .change-checkbox');
        const selectAllBox = document.getElementById('selectAllChanges');
        
        if (allBoxes.length === 0) {
            selectAllBox.indeterminate = false;
            selectAllBox.checked = false;
        } else if (checkedBoxes.length === allBoxes.length) {
            selectAllBox.indeterminate = false;
            selectAllBox.checked = true;
        } else if (checkedBoxes.length === 0) {
            selectAllBox.indeterminate = false;
            selectAllBox.checked = false;
        } else {
            selectAllBox.indeterminate = true;
        }
    }

    async approveSelectedChanges() {
        const selectedIds = this.getSelectedChangeIds();
        
        if (selectedIds.length === 0) {
            this.showError('No changes selected');
            return;
        }

        if (!confirm(`Are you sure you want to approve ${selectedIds.length} change(s)?`)) {
            return;
        }

        try {
            const response = await fetch(`${this.config.apiBaseUrl}changes/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ approved_ids: selectedIds })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Approval failed');
            }

            const result = await response.json();
            this.showSuccess('Changes approved and applied successfully');
            
            // Reload changes
            await this.loadAndDisplayChanges();
            
        } catch (error) {
            console.error('Error approving changes:', error);
            this.showError('Error approving changes: ' + error.message);
        }
    }

    async rejectSelectedChanges() {
        const selectedIds = this.getSelectedChangeIds();
        
        if (selectedIds.length === 0) {
            this.showError('No changes selected');
            return;
        }

        if (!confirm(`Are you sure you want to reject ${selectedIds.length} change(s)?`)) {
            return;
        }

        try {
            const response = await fetch(`${this.config.apiBaseUrl}changes/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ rejected_ids: selectedIds })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Rejection failed');
            }

            const result = await response.json();
            this.showSuccess('Changes rejected successfully');
            
            // Reload changes
            await this.loadAndDisplayChanges();
            
        } catch (error) {
            console.error('Error rejecting changes:', error);
            this.showError('Error rejecting changes: ' + error.message);
        }
    }

    getSelectedChangeIds() {
        const checkboxes = document.querySelectorAll('#changesTableBody .change-checkbox:checked');
        return Array.from(checkboxes).map(cb => parseInt(cb.value));
    }

    viewFeatureDetails(featureId) {
        // This would open a detailed view of the feature
        alert(`Feature details for ID: ${featureId}\n\nThis would open a detailed view in a real implementation.`);
    }

    getChangeBadgeClass(changeType) {
        switch (changeType) {
            case 'new': return 'bg-success';
            case 'modified': return 'bg-warning text-dark';
            case 'deleted': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    showProgress(percent) {
        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = progressDiv.querySelector('.progress-bar');
        const progressText = progressDiv.querySelector('.progress-text');
        
        progressBar.style.width = percent + '%';
        progressText.textContent = Math.round(percent) + '%';
        progressDiv.style.display = 'block';
    }

    updateProgress(percent) {
        this.showProgress(percent);
    }

    hideProgress() {
        document.getElementById('uploadProgress').style.display = 'none';
    }

    disableForm(disabled) {
        const form = document.getElementById('uploadForm');
        const inputs = form.querySelectorAll('input, button');
        inputs.forEach(input => {
            input.disabled = disabled;
        });
    }

    clearForm() {
        document.getElementById('uploadForm').reset();
        document.getElementById('uploadResults').style.display = 'none';
        document.getElementById('changePreview').style.display = 'none';
        this.hideProgress();
    }

    clearFileInput() {
        document.getElementById('shapefileInput').value = '';
    }

    hideChangePreview() {
        document.getElementById('changePreview').style.display = 'none';
    }

    showSuccess(message) {
        this.showModal('successModal', message);
    }

    showError(message) {
        this.showModal('errorModal', message);
    }

    showModal(modalId, message) {
        const modal = document.getElementById(modalId);
        const messageElement = modal.querySelector('#' + modalId.replace('Modal', 'Message'));
        
        if (messageElement) {
            messageElement.textContent = message;
        }
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Initialize upload manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (window.uploadConfig) {
        window.uploadManager = new UploadManager(window.uploadConfig);
    }
});
