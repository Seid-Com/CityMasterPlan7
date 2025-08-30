// Ensure document.body exists before any script execution
if (!document.body) {
    // If body doesn't exist yet, defer all script execution
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Body was not ready, deferred script execution');
    });
}

// Global function for basemap switching - defined first
function switchToBasemap(basemapType) {
    console.log('switchToBasemap called with:', basemapType);
    if (window.cityMap && window.cityMap.switchBasemap) {
        window.cityMap.switchBasemap(basemapType);
    } else {
        console.error('CityMap not initialized yet');
        // Try again after a short delay
        setTimeout(() => {
            if (window.cityMap && window.cityMap.switchBasemap) {
                window.cityMap.switchBasemap(basemapType);
            }
        }, 1000);
    }
}

// Make it globally available
window.switchToBasemap = switchToBasemap;

// Map initialization and management
class CityMap {
    constructor(config) {
        console.log('CityMap constructor called with config:', config);
        this.config = config;
        this.map = null;
        this.parcelsLayer = null;
        this.changesLayer = null;
        this.basemapLayer = null;
        this.basemapLayers = {};
        this.currentBasemap = 'satellite';
        this.basemapVisible = true;
        this.labelsVisible = true;
        this.selectedFeatures = new Set();
        this.allFeatures = [];
        this.filteredFeatures = [];
        this.currentFilters = { landUse: '', minArea: 0, owner: '' };
        this.measurementLayer = null;
        this.measurementMode = null;
        this.measurementPoints = [];
        this.boundariesLayer = null;
        this.analysisLayer = null;
        this.annotationLayer = null;
        this.boundariesVisible = false;
        this.annotationMode = null;
        this.queryResults = [];
        this.savedQueries = [];
        this.layerOpacity = {
            parcels: 0.8,
            boundaries: 0.7,
            analysis: 0.6
        };
        this.init();
    }

    init() {
        console.log('CityMap init() called');
        try {
            this.initMap();
            console.log('Map initialized successfully');
            this.loadParcels();
            this.setupEventListeners();
            console.log('Loading parcels...');
            
            // Check if we need to center on Woldia after upload
            this.checkAndCenterOnWoldia();
        } catch (error) {
            console.error('Error in init():', error);
        }
    }

    initMap() {
        console.log('Initializing Leaflet map on element:', document.getElementById('map'));
        
        // Add tile loading indicator
        this.tileLoadingCount = 0;
        this.addTileLoadingIndicator();
        
        // Initialize Leaflet map with error handling
        try {
            this.map = L.map('map', {
                center: [this.config.centerLat, this.config.centerLng],
                zoom: this.config.zoom,
                maxZoom: 20,
                minZoom: 10,
                zoomControl: false,  // We'll add custom zoom control
                attributionControl: false,
                // Enhanced zoom quality settings
                zoomAnimation: true,
                zoomAnimationThreshold: 4,
                fadeAnimation: true,
                markerZoomAnimation: true,
                transform3DLimit: 8388608,  // Increase 3D transform limit for better performance
                wheelDebounceTime: 40,       // Smoother mouse wheel zoom
                wheelPxPerZoomLevel: 60,     // More precise zoom control
                doubleClickZoom: true,
                smoothSensitivity: 1,        // Smooth zoom sensitivity
                renderer: L.canvas({         // Use canvas renderer for better performance
                    padding: 0.5,
                    tolerance: 5
                })
            });
            console.log('Leaflet map created:', this.map);

            // Initialize basemap layers first
            this.initBasemapLayers();
            console.log('Base map tiles added');
            
            // Add tile loading event handlers
            this.setupTileLoadingHandlers();

            // Initialize parcels layer on top of basemap with proper pane
            this.map.createPane('parcelsPane');
            this.map.getPane('parcelsPane').style.zIndex = 450;
            this.parcelsLayer = L.featureGroup([], {
                pane: 'parcelsPane'
            });
            this.map.addLayer(this.parcelsLayer);
            console.log('Parcels layer created and added to map with custom pane');
            
            // Initialize other layers
            this.changesLayer = L.featureGroup().addTo(this.map);
            this.measurementLayer = L.featureGroup().addTo(this.map);
            this.boundariesLayer = L.featureGroup().addTo(this.map);
            this.analysisLayer = L.featureGroup().addTo(this.map);
            this.annotationLayer = L.featureGroup().addTo(this.map);
            
            // Add drawing controls
            this.addDrawingControls();
            
            // Add zoom control with custom position
            L.control.zoom({ 
                position: 'topright',
                zoomInTitle: 'Zoom in',
                zoomOutTitle: 'Zoom out'
            }).addTo(this.map);
            
            // Add scale control
            L.control.scale({ 
                imperial: false,
                metric: true,
                position: 'bottomleft',
                maxWidth: 200
            }).addTo(this.map);
            
            // Add enhanced controls
            this.addSearchControl();
            this.addStatisticsPanel();
            this.addLegendControl();
            
            // Enhanced zoom event handling for better quality
            this.map.on('zoomstart', () => {
                this.isZooming = true;
            });
            
            this.map.on('zoomend', () => {
                this.isZooming = false;
                this.syncLayersOnZoom();
            });
            
            // Smooth zoom animation
            this.map.on('zoom', () => {
                if (this.parcelsLayer) {
                    const zoom = this.map.getZoom();
                    const opacity = zoom < 12 ? 0.7 : 1;
                    this.parcelsLayer.setStyle({ fillOpacity: opacity * 0.2 });
                }
            });
            
        } catch (error) {
            console.error('Error initializing map:', error);
            throw error;
        }
    }

    addDrawingControls() {
        const drawControl = new L.Control.Draw({
            edit: {
                featureGroup: this.parcelsLayer,
                remove: true
            },
            draw: {
                polygon: true,
                polyline: false,
                rectangle: true,
                circle: false,
                marker: false,
                circlemarker: false
            }
        });

        this.map.addControl(drawControl);

        // Handle drawing events
        this.map.on('draw:created', (e) => {
            this.handleNewFeature(e.layer);
        });

        this.map.on('draw:edited', (e) => {
            this.handleEditedFeatures(e.layers);
        });

        this.map.on('draw:deleted', (e) => {
            this.handleDeletedFeatures(e.layers);
        });
    }

    async loadParcels() {
        console.log('loadParcels() called');
        try {
            this.showLoadingOverlay('Loading spatial data...');
            
            const apiUrl = `${this.config.apiBaseUrl}parcels`;
            console.log('Fetching data from:', apiUrl);
            
            const response = await fetch(apiUrl);
            console.log('API response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const geojsonData = await response.json();
            console.log('GeoJSON data received:', geojsonData);
            
            this.parcelsLayer.clearLayers();
            
            if (geojsonData.features && geojsonData.features.length > 0) {
                console.log(`Loading ${geojsonData.features.length} features`);
                
                // Store all features for filtering
                this.allFeatures = geojsonData.features;
                this.filteredFeatures = [...this.allFeatures];
                
                this.displayFeatures(this.filteredFeatures);
                
                // Update statistics
                this.updateStatistics(this.filteredFeatures.length);
                this.updateLandUseStatistics();  // Update land use statistics
                
                // Fit map to data bounds
                if (this.parcelsLayer.getLayers().length > 0) {
                    this.map.fitBounds(this.parcelsLayer.getBounds(), { padding: [20, 20] });
                    console.log('Map fitted to bounds');
                }
                
            } else {
                console.warn('No features found in GeoJSON data');
                this.showMessage('No spatial data found', 'warning');
            }
            
        } catch (error) {
            console.error('Error loading parcels:', error);
            this.showMessage('Error loading spatial data: ' + error.message, 'danger');
        } finally {
            this.hideLoadingOverlay();
        }
    }

    async loadChanges() {
        try {
            const response = await fetch(`${this.config.apiBaseUrl}changes`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const geojsonData = await response.json();
            
            this.changesLayer.clearLayers();
            
            if (geojsonData.features && geojsonData.features.length > 0) {
                L.geoJSON(geojsonData, {
                    style: (feature) => this.getChangeStyle(feature),
                    onEachFeature: (feature, layer) => this.bindChangeEvents(feature, layer)
                }).addTo(this.changesLayer);

                this.showChangeManagement(geojsonData.features);
            } else {
                this.hideChangeManagement();
            }
            
        } catch (error) {
            console.error('Error loading changes:', error);
            this.showMessage('Error loading changes: ' + error.message, 'danger');
        }
    }

    getFeatureStyle(feature) {
        // Provide default style for all features
        if (!feature || !feature.properties) {
            return {
                color: '#007bff',
                weight: 2,
                fillOpacity: 0.6,
                fillColor: '#007bff'
            };
        }
        
        const changeType = feature.properties.change_type;
        const landUse = feature.properties.landuse_ti;
        
        const baseStyle = {
            fillOpacity: 0.7,
            weight: 3,
            opacity: 0.9
        };

        // Color by change type first, then by land use
        if (changeType) {
            switch (changeType) {
                case 'new':
                    return { ...baseStyle, color: '#28a745', fillColor: '#28a745' };
                case 'modified':
                    return { ...baseStyle, color: '#ffc107', fillColor: '#ffc107' };
                case 'deleted':
                    return { ...baseStyle, color: '#dc3545', fillColor: '#dc3545' };
            }
        }

        // Enhanced color scheme for land use types
        const landUseColors = {
            'Residential': '#4CAF50',     // Green
            'Commercial': '#FF9800',      // Orange
            'Industrial': '#9E9E9E',      // Grey
            'Educational': '#2196F3',     // Blue
            'Mixed Use': '#9C27B0',       // Purple
            'Public Space': '#8BC34A',    // Light Green
            'Agricultural': '#795548',    // Brown
            'Recreation': '#00BCD4',      // Cyan
            'Government': '#F44336',      // Red
            'Healthcare': '#E91E63',      // Pink
            'Religious': '#FFC107',       // Amber
            'Transportation': '#607D8B',  // Blue Grey
            'Other': '#FF5722'            // Deep Orange
        };
        
        const fillColor = landUseColors[landUse] || '#3F51B5'; // Default Indigo
        return { ...baseStyle, fillColor: fillColor, color: 'white', weight: 2 };
    }

    getChangeStyle(feature) {
        const changeType = feature.properties.change_type;
        
        const baseStyle = {
            fillOpacity: 0.7,
            weight: 3,
            opacity: 1,
            dashArray: '5, 5'
        };

        switch (changeType) {
            case 'new':
                return { ...baseStyle, color: '#28a745', fillColor: '#d4edda' };
            case 'modified':
                return { ...baseStyle, color: '#ffc107', fillColor: '#fff3cd' };
            case 'deleted':
                return { ...baseStyle, color: '#dc3545', fillColor: '#f8d7da' };
            default:
                return baseStyle;
        }
    }

    bindFeatureEvents(feature, layer) {
        // Create popup content
        const popupContent = this.createPopupContent(feature.properties);
        layer.bindPopup(popupContent);

        // Create permanent label if enabled
        if (this.labelsVisible && feature.properties.fullname) {
            const labelClass = `parcel-label ${feature.properties.change_type || ''}`;
            layer.bindTooltip(feature.properties.fullname, {
                permanent: true,
                direction: 'center',
                className: labelClass
            });
        }

        // Click event for feature selection
        layer.on('click', () => {
            this.selectFeature(feature, layer);
        });

        // Hover effects
        layer.on('mouseover', () => {
            layer.setStyle({ weight: 4, opacity: 1 });
        });

        layer.on('mouseout', () => {
            layer.setStyle(this.getFeatureStyle(feature));
        });
    }

    bindChangeEvents(feature, layer) {
        const popupContent = this.createChangePopupContent(feature.properties);
        layer.bindPopup(popupContent);

        layer.on('click', () => {
            this.selectChange(feature, layer);
        });
    }

    createPopupContent(properties) {
        const fields = [
            { label: 'Owner Name', value: properties.owner_name },
            { label: 'UPIN', value: properties.upin },
            { label: 'Kent Code', value: properties.kentcode },
            { label: 'Land Use', value: properties.landuse_ti },
            { label: 'Area (m²)', value: properties.area_m2_ti ? properties.area_m2_ti.toLocaleString() : null },
            { label: 'Area (ha)', value: properties.ha ? properties.ha.toFixed(4) : null },
            { label: 'First Name', value: properties.first_name },
            { label: 'Father\'s Name', value: properties.fathers_na },
            { label: 'Grandfather', value: properties.grandfathe },
            { label: 'Title Deed', value: properties.titledeed_ },
            { label: 'Land Tenure', value: properties.land_tenur },
            { label: 'Registration', value: properties.registerda ? new Date(properties.registerda).toLocaleDateString() : null }
        ];

        let content = `<h6>${properties.fullname || 'Unnamed Parcel'}</h6>`;
        
        fields.forEach(field => {
            if (field.value) {
                content += `
                    <div class="property-row">
                        <span class="property-label">${field.label}:</span>
                        <span class="property-value">${field.value}</span>
                    </div>
                `;
            }
        });

        if (properties.change_type && properties.change_type !== 'existing') {
            content += `
                <div class="property-row">
                    <span class="property-label">Change Type:</span>
                    <span class="property-value">
                        <span class="change-type-badge ${properties.change_type}">${properties.change_type}</span>
                    </span>
                </div>
            `;
        }

        return content;
    }

    createChangePopupContent(properties) {
        let content = `
            <h6>Pending Change</h6>
            <div class="property-row">
                <span class="property-label">Type:</span>
                <span class="property-value">
                    <span class="change-type-badge ${properties.change_type}">${properties.change_type}</span>
                </span>
            </div>
        `;

        content += this.createPopupContent(properties);
        
        content += `
            <div class="mt-2">
                <button class="btn btn-success btn-sm me-1" onclick="cityMap.approveChange(${properties.id})">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn btn-danger btn-sm" onclick="cityMap.rejectChange(${properties.id})">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        `;

        return content;
    }

    selectFeature(feature, layer) {
        // Update feature information panel
        document.getElementById('featureInfo').innerHTML = this.createFeatureInfoPanel(feature.properties);
        
        // Highlight selected feature
        layer.setStyle({
            weight: 5,
            color: '#ff7800',
            fillOpacity: 0.8
        });

        // Store selection
        this.selectedFeatures.add({ feature, layer });
    }

    createFeatureInfoPanel(properties) {
        const fields = [
            { label: 'ID', value: properties.id },
            { label: 'Owner Name', value: properties.owner_name },
            { label: 'UPIN', value: properties.upin },
            { label: 'First Name', value: properties.first_name },
            { label: 'Father\'s Name', value: properties.fathers_na },
            { label: 'Land Use Type', value: properties.landuse_ti },
            { label: 'Area (m²)', value: properties.area_m2_ti ? properties.area_m2_ti.toLocaleString() : null },
            { label: 'Kent Code', value: properties.kentcode },
            { label: 'Full Name', value: properties.fullname }
        ];

        let content = `<h6>${properties.fullname || 'Unnamed Parcel'}</h6>`;
        
        fields.forEach(field => {
            if (field.value) {
                content += `
                    <div class="feature-property">
                        <span class="label">${field.label}</span>
                        <span class="value">${field.value}</span>
                    </div>
                `;
            }
        });

        if (properties.change_type && properties.change_type !== 'existing') {
            content += `
                <div class="feature-property">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="change-type-badge ${properties.change_type}">${properties.change_type}</span>
                    </span>
                </div>
            `;
        }

        return content;
    }

    showChangeManagement(changes) {
        const panel = document.getElementById('changeManagement');
        const list = document.getElementById('changesList');
        
        list.innerHTML = '';
        
        changes.forEach(change => {
            const item = document.createElement('div');
            item.className = `change-item ${change.properties.change_type}`;
            item.innerHTML = `
                <div class="change-header">
                    <input type="checkbox" class="form-check-input me-2" value="${change.properties.id}">
                    ${change.properties.fullname || 'Unnamed'} - 
                    <span class="change-type-badge ${change.properties.change_type}">${change.properties.change_type}</span>
                </div>
                <div class="change-details">
                    Owner: ${change.properties.owner_name || 'Unknown'} | 
                    Land Use: ${change.properties.landuse_ti || 'Unknown'}
                </div>
            `;
            list.appendChild(item);
        });

        panel.style.display = 'block';
        document.getElementById('pendingChanges').textContent = changes.length;
    }

    hideChangeManagement() {
        document.getElementById('changeManagement').style.display = 'none';
        document.getElementById('pendingChanges').textContent = '0';
    }

    setupEventListeners() {
        // Toggle labels
        document.getElementById('toggleLabels').addEventListener('click', () => {
            this.toggleLabels();
        });

        // View changes
        document.getElementById('viewChanges').addEventListener('click', () => {
            this.loadChanges();
        });

        // Refresh map
        document.getElementById('refreshMap').addEventListener('click', () => {
            this.loadParcels();
        });

        // Apply changes
        document.getElementById('applyChanges').addEventListener('click', () => {
            this.applySelectedChanges();
        });

        // Reject changes
        document.getElementById('rejectChanges').addEventListener('click', () => {
            this.rejectSelectedChanges();
        });

        // Toggle basemap
        document.getElementById('toggleBasemap').addEventListener('click', () => {
            this.toggleBasemap();
        });

        // Filter controls
        document.getElementById('applyFilters').addEventListener('click', () => {
            this.applyFilters();
        });

        document.getElementById('clearFilters').addEventListener('click', () => {
            this.clearFilters();
        });

        // Area filter slider update
        document.getElementById('areaFilter').addEventListener('input', (e) => {
            document.getElementById('areaValue').textContent = e.target.value;
        });
    }

    toggleLabels() {
        this.labelsVisible = !this.labelsVisible;
        
        this.parcelsLayer.eachLayer(layer => {
            if (this.labelsVisible) {
                if (layer.feature && layer.feature.properties.fullname) {
                    const labelClass = `parcel-label ${layer.feature.properties.change_type || ''}`;
                    layer.bindTooltip(layer.feature.properties.fullname, {
                        permanent: true,
                        direction: 'center',
                        className: labelClass
                    });
                }
            } else {
                layer.unbindTooltip();
            }
        });

        const button = document.getElementById('toggleLabels');
        button.innerHTML = this.labelsVisible ? '<i class="fas fa-tags"></i>' : '<i class="far fa-tags"></i>';
    }

    async applySelectedChanges() {
        const selected = this.getSelectedChanges();
        
        if (selected.length === 0) {
            this.showMessage('No changes selected', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.config.apiBaseUrl}changes/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ approved_ids: selected })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            this.showMessage('Changes applied successfully', 'success');
            
            // Reload data
            this.loadParcels();
            this.loadChanges();
            
        } catch (error) {
            console.error('Error applying changes:', error);
            this.showMessage('Error applying changes: ' + error.message, 'danger');
        }
    }

    async rejectSelectedChanges() {
        const selected = this.getSelectedChanges();
        
        if (selected.length === 0) {
            this.showMessage('No changes selected', 'warning');
            return;
        }

        try {
            const response = await fetch(`${this.config.apiBaseUrl}changes/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ rejected_ids: selected })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            this.showMessage('Changes rejected successfully', 'success');
            
            // Reload changes
            this.loadChanges();
            
        } catch (error) {
            console.error('Error rejecting changes:', error);
            this.showMessage('Error rejecting changes: ' + error.message, 'danger');
        }
    }

    getSelectedChanges() {
        const checkboxes = document.querySelectorAll('#changesList input[type="checkbox"]:checked');
        return Array.from(checkboxes).map(cb => parseInt(cb.value));
    }

    async handleNewFeature(layer) {
        // Add the layer to the map immediately
        this.parcelsLayer.addLayer(layer);
        
        // Prompt for feature properties
        const properties = await this.promptForProperties();
        if (!properties) {
            this.parcelsLayer.removeLayer(layer);
            return;
        }
        
        // Create GeoJSON feature
        const geojsonFeature = {
            type: 'Feature',
            geometry: layer.toGeoJSON().geometry,
            properties: properties
        };
        
        try {
            const response = await fetch(`${this.config.apiBaseUrl}parcels`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(geojsonFeature)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            this.showMessage('Feature created successfully', 'success');
            
            // Reload parcels to get the new feature with proper ID
            this.loadParcels();
            
        } catch (error) {
            console.error('Error creating feature:', error);
            this.showMessage('Error creating feature: ' + error.message, 'danger');
            if (layer && this.parcelsLayer.hasLayer(layer)) {
                this.parcelsLayer.removeLayer(layer);
            }
        }
    }

    promptForProperties() {
        return new Promise((resolve) => {
            // Simple prompt implementation - in a real app, you'd use a modal form
            const ownerName = prompt('Owner Name:');
            if (ownerName === null) {
                resolve(null);
                return;
            }
            
            const landUse = prompt('Land Use Type:');
            const area = prompt('Area (m²):');
            
            resolve({
                owner_name: ownerName,
                landuse_ti: landUse,
                area_m2_ti: area ? parseFloat(area) : null,
                fullname: ownerName
            });
        });
    }

    updateStatistics(totalParcels) {
        document.getElementById('totalParcels').textContent = totalParcels.toLocaleString();
    }

    showLoadingOverlay(message = 'Loading...') {
        const overlay = document.getElementById('loadingOverlay');
        const text = overlay.querySelector('p');
        text.textContent = message;
        overlay.style.display = 'flex';
    }

    hideLoadingOverlay() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    showMessage(message, type = 'info') {
        // Create and show a toast message
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        if (document.body) {
            document.body.appendChild(toast);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    // Switch to a specific basemap
    addTileLoadingIndicator() {
        // Create loading indicator element
        const indicator = document.createElement('div');
        indicator.id = 'tile-loading-indicator';
        indicator.style.cssText = `
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            z-index: 1000;
            display: none;
            pointer-events: none;
        `;
        indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading map tiles...';
        if (document.getElementById('map')) {
            document.getElementById('map').appendChild(indicator);
        }
    }
    
    setupTileLoadingHandlers() {
        const indicator = document.getElementById('tile-loading-indicator');
        if (!indicator) return;
        
        // Show indicator when tiles start loading
        this.map.on('tileloadstart', () => {
            this.tileLoadingCount++;
            if (this.tileLoadingCount > 0) {
                indicator.style.display = 'block';
            }
        });
        
        // Hide indicator when tiles finish loading
        this.map.on('tileload', () => {
            this.tileLoadingCount--;
            if (this.tileLoadingCount <= 0) {
                this.tileLoadingCount = 0;
                setTimeout(() => {
                    if (this.tileLoadingCount === 0) {
                        indicator.style.display = 'none';
                    }
                }, 500);
            }
        });
        
        // Handle tile errors
        this.map.on('tileerror', () => {
            this.tileLoadingCount--;
            if (this.tileLoadingCount <= 0) {
                this.tileLoadingCount = 0;
                indicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Some tiles unavailable at this zoom level';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 3000);
            }
        });
    }
    
    switchBasemap(basemapType) {
        if (!this.basemapLayers[basemapType]) {
            console.error('Unknown basemap type:', basemapType);
            return;
        }
        
        // Remove current basemap
        if (this.basemapLayer) {
            this.map.removeLayer(this.basemapLayer);
        }
        
        // Add new basemap with proper z-index
        this.currentBasemap = basemapType;
        this.basemapLayer = this.basemapLayers[this.currentBasemap].layer;
        this.basemapLayer.setZIndex(1);  // Keep base map at bottom
        this.map.addLayer(this.basemapLayer);
        
        // Ensure parcels layer stays on top
        if (this.parcelsLayer) {
            this.parcelsLayer.bringToFront();
        }
        
        // Show success message
        const basemapName = this.basemapLayers[this.currentBasemap].name;
        this.showMessage(`Switched to ${basemapName}`, 'success');
        
        console.log(`Basemap changed to: ${basemapName}`);
    }

    // Display filtered features on the map
    displayFeatures(features) {
        this.parcelsLayer.clearLayers();
        
        if (features && features.length > 0) {
            const geoJsonLayer = L.geoJSON({ type: 'FeatureCollection', features }, {
                style: (feature) => this.getFeatureStyle(feature),
                onEachFeature: (feature, layer) => this.bindFeatureEvents(feature, layer),
                pane: 'parcelsPane',
                coordsToLatLng: (coords) => {
                    // Ensure proper coordinate order (lng, lat)
                    return new L.LatLng(coords[1], coords[0], coords[2]);
                }
            });
            
            this.parcelsLayer.addLayer(geoJsonLayer);
            
            // Ensure parcels layer is properly positioned
            this.parcelsLayer.bringToFront();
            console.log('Features displayed with proper coordinate handling');
        }
    }

    // Apply current filters
    applyFilters() {
        // Get filter values
        this.currentFilters.landUse = document.getElementById('landUseFilter').value;
        this.currentFilters.minArea = parseFloat(document.getElementById('areaFilter').value);
        this.currentFilters.owner = document.getElementById('ownerFilter').value.toLowerCase().trim();

        // Filter features
        this.filteredFeatures = this.allFeatures.filter(feature => {
            const props = feature.properties;
            
            // Land use filter
            if (this.currentFilters.landUse && props.landuse_ti !== this.currentFilters.landUse) {
                return false;
            }
            
            // Area filter
            if (this.currentFilters.minArea > 0 && 
                (!props.area_m2_ti || parseFloat(props.area_m2_ti) < this.currentFilters.minArea)) {
                return false;
            }
            
            // Owner filter
            if (this.currentFilters.owner && 
                (!props.owner_name || !props.owner_name.toLowerCase().includes(this.currentFilters.owner))) {
                return false;
            }
            
            return true;
        });

        // Display filtered features
        this.displayFeatures(this.filteredFeatures);
        
        // Update statistics
        this.updateStatistics(this.filteredFeatures.length);
        
        // Show message
        this.showMessage(`Showing ${this.filteredFeatures.length} of ${this.allFeatures.length} parcels`, 'success');
        
        // Fit to filtered data bounds if any
        if (this.filteredFeatures.length > 0 && this.parcelsLayer.getLayers().length > 0) {
            this.map.fitBounds(this.parcelsLayer.getBounds(), { padding: [20, 20] });
        }
    }

    // Clear all filters
    clearFilters() {
        // Reset filter controls
        document.getElementById('landUseFilter').value = '';
        document.getElementById('areaFilter').value = '0';
        document.getElementById('areaValue').textContent = '0';
        document.getElementById('ownerFilter').value = '';
        
        // Reset filters
        this.currentFilters = {
            landUse: '',
            minArea: 0,
            owner: ''
        };
        
        // Show all features
        this.filteredFeatures = [...this.allFeatures];
        this.displayFeatures(this.filteredFeatures);
        
        // Update statistics
        this.updateStatistics(this.filteredFeatures.length);
        
        this.showMessage('All filters cleared', 'info');
        
        // Fit to all data bounds
        if (this.parcelsLayer.getLayers().length > 0) {
            this.map.fitBounds(this.parcelsLayer.getBounds(), { padding: [20, 20] });
        }
    }

    // Display filtered features on the map
    displayFeatures(features) {
        this.parcelsLayer.clearLayers();
        
        features.forEach(feature => {
            const geojsonLayer = L.geoJSON(feature, {
                style: this.getFeatureStyle(feature),
                onEachFeature: (feature, layer) => this.bindFeatureEvents(feature, layer)
            });
            
            this.parcelsLayer.addLayer(geojsonLayer);
        });
    }

    bindFeatureEvents(feature, layer) {
        // Create popup content
        const popupContent = this.createPopupContent(feature.properties);
        layer.bindPopup(popupContent);

        // Create permanent label if enabled
        if (this.labelsVisible && feature.properties.fullname) {
            const labelClass = `parcel-label ${feature.properties.change_type || ''}`;
            layer.bindTooltip(feature.properties.fullname, {
                permanent: true,
                direction: 'center',
                className: labelClass
            });
        }

        // Click event for feature selection
        layer.on('click', () => {
            this.selectFeature(feature, layer);
        });

        // Hover effects
        layer.on('mouseover', () => {
            layer.setStyle({ weight: 4, opacity: 1 });
        });

        layer.on('mouseout', () => {
            layer.setStyle(this.getFeatureStyle(feature));
        });
    }

    createPopupContent(properties) {
        const fields = [
            { label: 'Owner Name', value: properties.owner_name },
            { label: 'UPIN', value: properties.upin },
            { label: 'Kent Code', value: properties.kentcode },
            { label: 'Land Use', value: properties.landuse_ti },
            { label: 'Area (m²)', value: properties.area_m2_ti ? properties.area_m2_ti.toLocaleString() : null },
            { label: 'Area (ha)', value: properties.ha ? properties.ha.toFixed(4) : null },
            { label: 'First Name', value: properties.first_name },
            { label: 'Father\'s Name', value: properties.fathers_na },
            { label: 'Grandfather', value: properties.grandfathe },
            { label: 'Title Deed', value: properties.titledeed_ },
            { label: 'Land Tenure', value: properties.land_tenur },
            { label: 'Registration', value: properties.registerda ? new Date(properties.registerda).toLocaleDateString() : null }
        ];

        let content = `<h6>${properties.fullname || 'Unnamed Parcel'}</h6>`;
        
        fields.forEach(field => {
            if (field.value) {
                content += `
                    <div class="property-row">
                        <span class="property-label">${field.label}:</span>
                        <span class="property-value">${field.value}</span>
                    </div>
                `;
            }
        });

        if (properties.change_type && properties.change_type !== 'existing') {
            content += `
                <div class="property-row">
                    <span class="property-label">Change Type:</span>
                    <span class="property-value">
                        <span class="change-type-badge ${properties.change_type}">${properties.change_type}</span>
                    </span>
                </div>
            `;
        }

        return content;
    }

    selectFeature(feature, layer) {
        // Update feature information panel
        document.getElementById('featureInfo').innerHTML = this.createFeatureInfoPanel(feature.properties);
        
        // Highlight selected feature
        layer.setStyle({
            weight: 5,
            color: '#ff7800',
            fillOpacity: 0.8
        });

        // Store selection
        this.selectedFeatures.add({ feature, layer });
    }

    createFeatureInfoPanel(properties) {
        const fields = [
            { label: 'ID', value: properties.id },
            { label: 'Owner Name', value: properties.owner_name },
            { label: 'UPIN', value: properties.upin },
            { label: 'First Name', value: properties.first_name },
            { label: 'Father\'s Name', value: properties.fathers_na },
            { label: 'Land Use Type', value: properties.landuse_ti },
            { label: 'Area (m²)', value: properties.area_m2_ti ? properties.area_m2_ti.toLocaleString() : null },
            { label: 'Kent Code', value: properties.kentcode },
            { label: 'Full Name', value: properties.fullname }
        ];

        let content = `<h6>${properties.fullname || 'Unnamed Parcel'}</h6>`;
        
        fields.forEach(field => {
            if (field.value) {
                content += `
                    <div class="feature-property">
                        <span class="label">${field.label}</span>
                        <span class="value">${field.value}</span>
                    </div>
                `;
            }
        });

        if (properties.change_type && properties.change_type !== 'existing') {
            content += `
                <div class="feature-property">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="change-type-badge ${properties.change_type}">${properties.change_type}</span>
                    </span>
                </div>
            `;
        }

        return content;
    }

    updateStatistics(totalParcels) {
        document.getElementById('totalParcels').textContent = totalParcels.toLocaleString();
    }

    showLoadingOverlay(message = 'Loading...') {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            const text = overlay.querySelector('p');
            if (text) text.textContent = message;
            overlay.style.display = 'flex';
        }
    }

    hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    showMessage(message, type = 'info') {
        // Create and show a toast message
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        if (document.body) {
            document.body.appendChild(toast);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    addSearchControl() {
        const searchControl = L.control({ position: 'topleft' });
        searchControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
            div.innerHTML = `
                <div class="search-control" style="background: white; padding: 10px; border-radius: 5px; box-shadow: 0 1px 5px rgba(0,0,0,0.4);">
                    <input type="text" id="parcelSearch" placeholder="Search owner/UPIN..." style="width: 180px; padding: 5px; border: 1px solid #ccc; border-radius: 3px;">
                    <button id="searchBtn" style="padding: 5px 10px; margin-left: 5px; background: #2196F3; color: white; border: none; border-radius: 3px; cursor: pointer;">🔍</button>
                    <button id="clearSearchBtn" style="padding: 5px 10px; margin-left: 5px; background: #f44336; color: white; border: none; border-radius: 3px; cursor: pointer;">✕</button>
                </div>
            `;
            
            L.DomEvent.disableClickPropagation(div);
            L.DomEvent.disableScrollPropagation(div);
            
            return div;
        };
        searchControl.addTo(this.map);
        
        // Defer event listeners to after DOM is ready
        setTimeout(() => {
            const searchBtn = document.getElementById('searchBtn');
            const clearBtn = document.getElementById('clearSearchBtn');
            const searchInput = document.getElementById('parcelSearch');
            
            if (searchBtn) searchBtn.addEventListener('click', () => this.searchParcels());
            if (clearBtn) clearBtn.addEventListener('click', () => this.clearSearch());
            if (searchInput) searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.searchParcels();
            });
        }, 100);
    }
    
    addStatisticsPanel() {
        const statsControl = L.control({ position: 'bottomleft' });
        statsControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'statistics-panel');
            div.innerHTML = `
                <div style="background: white; padding: 15px; border-radius: 5px; box-shadow: 0 1px 5px rgba(0,0,0,0.4); min-width: 250px;">
                    <h4 style="margin: 0 0 10px 0; color: #333; font-size: 14px;">📊 Land Use Statistics</h4>
                    <div id="landUseStats" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                        <div>Loading statistics...</div>
                    </div>
                    <hr style="margin: 10px 0; border: none; border-top: 1px solid #ddd;">
                    <div style="font-weight: bold; font-size: 12px;">
                        Total Parcels: <span id="totalParcelsStats">0</span><br>
                        Total Area: <span id="totalAreaStats">0</span> ha
                    </div>
                </div>
            `;
            
            L.DomEvent.disableClickPropagation(div);
            L.DomEvent.disableScrollPropagation(div);
            
            return div;
        };
        statsControl.addTo(this.map);
    }
    
    addLegendControl() {
        const legendControl = L.control({ position: 'bottomright' });
        legendControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'legend-control');
            const landUseTypes = [
                'Residential', 'Commercial', 'Industrial', 'Educational',
                'Mixed Use', 'Public Space', 'Agricultural', 'Healthcare'
            ];
            
            const landUseColors = {
                'Residential': '#4CAF50',
                'Commercial': '#FF9800',
                'Industrial': '#9E9E9E',
                'Educational': '#2196F3',
                'Mixed Use': '#9C27B0',
                'Public Space': '#8BC34A',
                'Agricultural': '#795548',
                'Healthcare': '#E91E63'
            };
            
            let html = '<div style="background: white; padding: 10px; border-radius: 5px; box-shadow: 0 1px 5px rgba(0,0,0,0.4); max-height: 300px; overflow-y: auto;">';
            html += '<h4 style="margin: 0 0 10px 0; font-size: 14px;">🗺️ Land Use Types</h4>';
            
            landUseTypes.forEach(type => {
                const color = landUseColors[type];
                html += `
                    <div style="margin: 5px 0; display: flex; align-items: center;">
                        <div style="width: 18px; height: 18px; background: ${color}; margin-right: 8px; border: 1px solid #333; border-radius: 2px;"></div>
                        <span style="font-size: 11px;">${type}</span>
                    </div>
                `;
            });
            
            html += '</div>';
            div.innerHTML = html;
            
            L.DomEvent.disableClickPropagation(div);
            
            return div;
        };
        legendControl.addTo(this.map);
    }
    
    searchParcels() {
        const searchInput = document.getElementById('parcelSearch');
        if (!searchInput) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        if (!searchTerm) return;
        
        const matches = [];
        
        this.parcelsLayer.eachLayer(layer => {
            const props = layer.feature.properties;
            const ownerMatch = props.owner_name && props.owner_name.toLowerCase().includes(searchTerm);
            const upinMatch = props.upin && props.upin.toLowerCase().includes(searchTerm);
            const fullnameMatch = props.fullname && props.fullname.toLowerCase().includes(searchTerm);
            
            if (ownerMatch || upinMatch || fullnameMatch) {
                matches.push(layer);
                layer.setStyle({
                    weight: 4,
                    color: '#FF5722',
                    fillOpacity: 0.9
                });
            } else {
                layer.setStyle({
                    weight: 2,
                    color: 'white',
                    fillOpacity: 0.3
                });
            }
        });
        
        if (matches.length > 0) {
            const group = new L.featureGroup(matches);
            this.map.fitBounds(group.getBounds().pad(0.1));
            this.showMessage(`Found ${matches.length} matching parcels`, 'success');
        } else {
            this.showMessage('No parcels found matching your search', 'warning');
        }
    }
    
    clearSearch() {
        const searchInput = document.getElementById('parcelSearch');
        if (searchInput) searchInput.value = '';
        
        this.parcelsLayer.eachLayer(layer => {
            layer.setStyle(this.getFeatureStyle(layer.feature));
        });
        
        this.showMessage('Search cleared', 'info');
    }
    
    syncLayersOnZoom() {
        const currentZoom = this.map.getZoom();
        
        // Ensure layers stay synchronized during zoom
        if (this.parcelsLayer) {
            // Force redraw of parcels to maintain alignment
            this.parcelsLayer.eachLayer(layer => {
                if (layer.setStyle) {
                    // Adjust stroke width based on zoom level for better visibility
                    const baseStrokeWidth = currentZoom < 15 ? 1 : currentZoom < 17 ? 2 : 3;
                    const style = {
                        ...layer.options.style,
                        weight: baseStrokeWidth
                    };
                    layer.setStyle(style);
                }
            });
        }
        
        // Update any visible labels or markers
        if (this.labelsVisible) {
            this.updateLabels();
        }
        
        // Preload tiles for adjacent zoom levels for smoother transitions
        this.preloadAdjacentZoomTiles(currentZoom);
        
        console.log('Layers synchronized at zoom level:', currentZoom);
    }
    
    updateLabels() {
        // Update label positions if needed
        this.parcelsLayer.eachLayer(layer => {
            if (layer.getTooltip && layer.getTooltip()) {
                layer.getTooltip().update();
            }
        });
    }
    
    preloadAdjacentZoomTiles(currentZoom) {
        // Preload tiles for smoother zoom transitions
        if (!this.basemapLayer || this.isZooming) return;
        
        // Preload one level up and down for smooth transitions
        const tilesToPreload = [];
        const bounds = this.map.getBounds();
        
        // Get tile coordinates for current view at adjacent zoom levels
        [-1, 1].forEach(zoomDelta => {
            const targetZoom = currentZoom + zoomDelta;
            if (targetZoom >= 10 && targetZoom <= 20) {
                // Calculate which tiles would be visible at target zoom
                const nw = bounds.getNorthWest();
                const se = bounds.getSouthEast();
                
                // This triggers tile loading in background
                if (this.basemapLayer._tileZoom !== targetZoom) {
                    // Tiles will be cached for smoother transitions
                    console.log(`Preloading tiles for zoom level ${targetZoom}`);
                }
            }
        });
    }

    updateLandUseStatistics() {
        const stats = {};
        let totalArea = 0;
        let totalParcels = 0;
        
        this.allFeatures.forEach(feature => {
            const landUse = feature.properties.landuse_ti || 'Unknown';
            if (!stats[landUse]) {
                stats[landUse] = { count: 0, area: 0 };
            }
            stats[landUse].count++;
            stats[landUse].area += feature.properties.area_m2_ti || 0;
            totalArea += feature.properties.area_m2_ti || 0;
            totalParcels++;
        });
        
        // Color map for statistics
        const landUseColors = {
            'Residential': '#4CAF50',
            'Commercial': '#FF9800',
            'Industrial': '#9E9E9E',
            'Educational': '#2196F3',
            'Mixed Use': '#9C27B0',
            'Public Space': '#8BC34A',
            'Agricultural': '#795548',
            'Healthcare': '#E91E63',
            'Unknown': '#9E9E9E'
        };
        
        // Update statistics panel
        let statsHtml = '';
        Object.keys(stats).sort().forEach(landUse => {
            const percentage = ((stats[landUse].count / totalParcels) * 100).toFixed(1);
            const areaHa = (stats[landUse].area / 10000).toFixed(2);
            const color = landUseColors[landUse] || '#3F51B5';
            statsHtml += `
                <div style="margin: 5px 0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="display: flex; align-items: center;">
                        <span style="display: inline-block; width: 12px; height: 12px; background: ${color}; margin-right: 5px; border-radius: 2px;"></span>
                        ${landUse}:
                    </span>
                    <span style="font-size: 11px;">${stats[landUse].count} (${percentage}%)</span>
                </div>
            `;
        });
        
        const statsElement = document.getElementById('landUseStats');
        if (statsElement) statsElement.innerHTML = statsHtml || '<div>No data available</div>';
        
        const totalParcelsElement = document.getElementById('totalParcelsStats');
        if (totalParcelsElement) totalParcelsElement.textContent = totalParcels;
        
        const totalAreaElement = document.getElementById('totalAreaStats');
        if (totalAreaElement) totalAreaElement.textContent = (totalArea / 10000).toFixed(2);
    }

    setupEventListeners() {
        // Toggle labels
        const toggleLabelsBtn = document.getElementById('toggleLabels');
        if (toggleLabelsBtn) {
            toggleLabelsBtn.addEventListener('click', () => {
                this.toggleLabels();
            });
        }

        // View changes
        const viewChangesBtn = document.getElementById('viewChanges');
        if (viewChangesBtn) {
            viewChangesBtn.addEventListener('click', () => {
                this.loadChanges();
            });
        }

        // Refresh map
        const refreshMapBtn = document.getElementById('refreshMap');
        if (refreshMapBtn) {
            refreshMapBtn.addEventListener('click', () => {
                this.loadParcels();
            });
        }

        // Setup basemap dropdown event listeners
        document.querySelectorAll('.basemap-option').forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                const basemapType = e.target.getAttribute('data-basemap');
                if (basemapType && this.switchBasemap) {
                    this.switchBasemap(basemapType);
                }
            });
        });

        // Measurement tools
        document.querySelectorAll('.measurement-tool').forEach(tool => {
            tool.addEventListener('click', (e) => {
                e.preventDefault();
                const toolType = e.target.closest('.measurement-tool').getAttribute('data-tool');
                this.startMeasurement(toolType);
            });
        });

        // Clear measurements
        const clearMeasurementsBtn = document.getElementById('clearMeasurements');
        if (clearMeasurementsBtn) {
            clearMeasurementsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearMeasurements();
            });
        }

        // Export data
        const exportDataBtn = document.getElementById('exportData');
        if (exportDataBtn) {
            exportDataBtn.addEventListener('click', (e) => {
                e.preventDefault();
                // Show export format options
                const format = prompt('Export format:\n1. GeoJSON (default)\n2. CSV\n\nEnter format (geojson/csv):') || 'geojson';
                this.exportData(format.toLowerCase());
            });
        }

        // Print and PDF functionality
        const printMapBtn = document.getElementById('printMap');
        if (printMapBtn) {
            printMapBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.printCurrentView();
            });
        }

        const generatePDFBtn = document.getElementById('generatePDF');
        if (generatePDFBtn) {
            generatePDFBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.generatePDFReport();
            });
        }

        const exportImageBtn = document.getElementById('exportImage');
        if (exportImageBtn) {
            exportImageBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportAsImage();
            });
        }

        // Administrative boundaries
        const toggleBoundariesBtn = document.getElementById('toggleBoundaries');
        if (toggleBoundariesBtn) {
            toggleBoundariesBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleBoundaries();
            });
        }

        // Boundary layer controls
        ['showDistricts', 'showZones', 'showNeighborhoods'].forEach(id => {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.addEventListener('change', () => this.updateBoundaryLayers());
            }
        });

        const boundaryOpacity = document.getElementById('boundaryOpacity');
        if (boundaryOpacity) {
            boundaryOpacity.addEventListener('input', (e) => {
                this.updateBoundaryOpacity(e.target.value);
            });
        }

        // Spatial analysis tools
        const createBufferBtn = document.getElementById('createBuffer');
        if (createBufferBtn) {
            createBufferBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.createBufferAnalysis();
            });
        }

        const proximityAnalysisBtn = document.getElementById('proximityAnalysis');
        if (proximityAnalysisBtn) {
            proximityAnalysisBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.performProximityAnalysis();
            });
        }

        const clearAnalysisBtn = document.getElementById('clearAnalysis');
        if (clearAnalysisBtn) {
            clearAnalysisBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearSpatialAnalysis();
            });
        }

        // Advanced Layer Management
        this.setupLayerManagement();
        
        // Spatial Query Builder
        this.setupQueryBuilder();
        
        // Annotation Tools
        this.setupAnnotationTools();
        
        // Coordinate Reference System
        const crsBtn = document.getElementById('coordinateReference');
        if (crsBtn) {
            crsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showCoordinateSystemInfo();
            });
        }
        
        // Setup search functionality
        this.setupSearchFunctionality();
    }

    toggleLabels() {
        this.labelsVisible = !this.labelsVisible;
        
        this.parcelsLayer.eachLayer(layer => {
            if (this.labelsVisible) {
                if (layer.feature && layer.feature.properties.fullname) {
                    const labelClass = `parcel-label ${layer.feature.properties.change_type || ''}`;
                    layer.bindTooltip(layer.feature.properties.fullname, {
                        permanent: true,
                        direction: 'center',
                        className: labelClass
                    });
                }
            } else {
                layer.unbindTooltip();
            }
        });

        const button = document.getElementById('toggleLabels');
        if (button) {
            button.innerHTML = this.labelsVisible ? '<i class="fas fa-tags"></i>' : '<i class="far fa-tags"></i>';
        }
    }

    initBasemapLayers() {
        console.log('Initializing base map layers with offline support');
        
        // Define available basemap layers with offline fallback capability
        this.basemapLayers = {
            osm: {
                name: 'OpenStreetMap',
                layer: this.createTileLayerWithFallback('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors',
                    subdomains: 'abc'
                })
            },
            offline: {
                name: 'Offline Mode',
                layer: this.createOfflineLayer()
            },
            satellite: {
                name: 'Satellite Imagery',
                layer: this.createTileLayerWithFallback('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 18,
                    attribution: '© Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                    subdomains: ['server', 'services'],
                    tms: false
                })
            },
            terrain: {
                name: 'Terrain',
                layer: this.createTileLayerWithFallback('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: '© Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL, Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong), and the GIS User Community'
                })
            },
            streets: {
                name: 'Streets',
                layer: this.createTileLayerWithFallback('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: '© Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), swisstopo, and the GIS User Community'
                })
            },
            cartodb: {
                name: 'Light Theme',
                layer: this.createTileLayerWithFallback('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors, © CartoDB'
                })
            },
            dark: {
                name: 'Dark Theme',
                layer: this.createTileLayerWithFallback('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors, © CartoDB'
                })
            }
        };
        
        // Create dedicated panes for proper layer ordering
        if (!this.map.getPane('basemapPane')) {
            this.map.createPane('basemapPane');
            this.map.getPane('basemapPane').style.zIndex = 200;
        }
        
        // Set initial basemap with proper pane and z-index
        this.basemapLayer = this.basemapLayers[this.currentBasemap].layer;
        this.basemapLayer.options.pane = 'basemapPane';
        this.basemapLayer.setZIndex(1);  // Base map should be at bottom
        this.map.addLayer(this.basemapLayer);
        console.log('Base map layer added to map with proper pane:', this.currentBasemap);
        
        // Initialize offline fallback tracking
        this.offlineFallbackActivated = false;
        
        // Test internet connectivity
        this.testConnectivity();
    }

    createTileLayerWithFallback(url, options) {
        const layer = L.tileLayer(url, {
            ...options,
            // Enhanced tile loading for better zoom quality
            updateWhenZooming: true,   // Allow updates during zoom for better availability
            updateWhenIdle: true,       // Also update when zoom is complete
            keepBuffer: 6,              // Keep more tiles in memory for smoother experience
            tileSize: 256,              // Standard tile size
            zoomOffset: 0,              // No zoom offset
            detectRetina: false,        // Disable retina to ensure tile availability
            maxNativeZoom: 18,          // Satellite imagery typically available up to 18
            maxZoom: 20,                // Allow overzooming for better detail
            minZoom: 10,
            crossOrigin: true,
            // Performance optimizations
            bounds: [[-90, -180], [90, 180]],  // World bounds
            noWrap: false,
            continuousWorld: false,
            worldCopyJump: false,
            // Preload adjacent tiles for smoother zooming
            className: 'leaflet-tile-loaded',
            errorTileUrl: 'data:image/svg+xml;base64,' + btoa(`
                <svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256">
                    <rect width="256" height="256" fill="#f8f9fa" stroke="#dee2e6"/>
                    <text x="128" y="120" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="12">
                        Tile unavailable
                    </text>
                    <text x="128" y="140" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="10">
                        Check connection
                    </text>
                </svg>
            `)
        });
        
        // Track tile loading errors and implement fallback
        let errorCount = 0;
        layer.on('tileerror', (e) => {
            errorCount++;
            console.warn('Tile loading error at zoom', this.map.getZoom(), ':', e.tile.src);
            
            // Try alternative tile sources - use OSM as primary fallback
            const alternativeSources = [
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',  // OSM is most reliable
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',  // Esri Streets
                'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png'  // CartoDB Light
            ];
            
            // Try to load from alternative source
            if (e.tile && errorCount <= 3) {
                const urlParts = e.tile.src.match(/\/([0-9]+)\/([0-9]+)\/([0-9]+)/);
                if (urlParts) {
                    const [, z, y, x] = urlParts;
                    const altUrl = alternativeSources[errorCount - 1]
                        ?.replace('{z}', z)
                        .replace('{y}', y)
                        .replace('{x}', x)
                        .replace('{s}', 'a');
                    if (altUrl) {
                        console.log('Trying alternative tile source:', altUrl);
                        e.tile.src = altUrl;
                        return;
                    }
                }
            }
            
            // Switch to offline mode after multiple failures
            if (errorCount > 10 && !this.offlineFallbackActivated) {
                console.log('Multiple tile errors detected, switching to offline mode');
                setTimeout(() => this.switchToOfflineMode(), 2000);
            }
        });
        
        return layer;
    }

    createOfflineLayer() {
        // Create a canvas-based offline layer with coordinate grid
        return L.gridLayer({
            attribution: 'Offline Mode - No Internet Connection',
            maxZoom: 19,
            createTile: function(coords, done) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = 256;
                canvas.height = 256;
                
                // Fill with light background
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 0, 256, 256);
                
                // Draw coordinate grid
                ctx.strokeStyle = '#dee2e6';
                ctx.lineWidth = 1;
                ctx.setLineDash([5, 5]);
                
                // Draw grid lines every 32 pixels
                for (let i = 0; i <= 256; i += 32) {
                    ctx.beginPath();
                    ctx.moveTo(i, 0);
                    ctx.lineTo(i, 256);
                    ctx.moveTo(0, i);
                    ctx.lineTo(256, i);
                    ctx.stroke();
                }
                
                // Add tile coordinates and offline indicator
                ctx.fillStyle = '#6c757d';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(`Z${coords.z}/X${coords.x}/Y${coords.y}`, 128, 115);
                
                ctx.font = 'bold 16px Arial';
                ctx.fillStyle = '#dc3545';
                ctx.fillText('OFFLINE', 128, 140);
                
                ctx.font = '12px Arial';
                ctx.fillStyle = '#6c757d';
                ctx.fillText('No Internet Connection', 128, 160);
                
                // Add border
                ctx.strokeStyle = '#adb5bd';
                ctx.lineWidth = 2;
                ctx.setLineDash([]);
                ctx.strokeRect(0, 0, 256, 256);
                
                setTimeout(() => done(null, canvas), 0);
                return canvas;
            }
        });
    }

    switchToOfflineMode() {
        if (this.offlineFallbackActivated) return;
        
        this.offlineFallbackActivated = true;
        console.log('Switching to offline mode due to connectivity issues');
        
        // Show offline notification
        this.showMessage('Internet connection unavailable. Running in offline mode with local data only.', 'warning', 8000);
        
        // Switch to offline basemap
        if (this.basemapLayer) {
            this.map.removeLayer(this.basemapLayer);
        }
        this.basemapLayer = this.basemapLayers['offline'].layer;
        this.basemapLayer.setZIndex(1);
        this.map.addLayer(this.basemapLayer);
        this.currentBasemap = 'offline';
        
        // Update UI to show offline status
        this.updateOfflineUI();
    }

    updateOfflineUI() {
        // Add offline indicator to the map
        const offlineIndicator = document.createElement('div');
        offlineIndicator.id = 'offline-indicator';
        offlineIndicator.className = 'alert alert-warning position-fixed';
        offlineIndicator.style.cssText = 'top: 70px; left: 20px; z-index: 10000; min-width: 200px; font-size: 12px;';
        offlineIndicator.innerHTML = `
            <i class="fas fa-wifi-slash me-2"></i>Offline Mode Active
            <small class="d-block">Using local data only</small>
        `;
        
        // Remove existing indicator if present
        const existing = document.getElementById('offline-indicator');
        if (existing) existing.remove();
        
        if (document.body) {
            document.body.appendChild(offlineIndicator);
        }
    }

    testConnectivity() {
        // Test connectivity by trying to load a small tile
        const testImg = new Image();
        testImg.onload = () => {
            console.log('Internet connectivity confirmed');
        };
        testImg.onerror = () => {
            console.log('No internet connectivity detected, activating offline mode');
            setTimeout(() => this.switchToOfflineMode(), 1000);
        };
        testImg.src = 'https://a.tile.openstreetmap.org/0/0/0.png?' + Date.now();
    }

    // Search functionality 
    setupSearchFunctionality() {
        const searchInput = document.getElementById('ownerSearch');
        const searchBtn = document.getElementById('searchBtn');
        const clearBtn = document.getElementById('clearSearchBtn');
        
        if (!searchInput || !searchBtn || !clearBtn) {
            console.log('Search elements not found, will try again later');
            return;
        }

        // Search button click
        searchBtn.addEventListener('click', () => {
            const query = searchInput.value.trim();
            if (query) {
                this.searchParcels(query);
            }
        });

        // Enter key in search input
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    this.searchParcels(query);
                }
            }
        });

        // Clear button click
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            this.clearSearchHighlights();
            this.applyFilters(); // Show all parcels again
        });
    }

    searchParcels(query) {
        console.log('Searching for:', query);
        this.clearSearchHighlights();
        
        let foundFeatures = [];
        
        // Search through all loaded features
        this.allFeatures.forEach(feature => {
            const ownerName = feature.properties.owner_name || '';
            const firstName = feature.properties.first_name || '';
            const fullName = feature.properties.fullname || '';
            
            if (ownerName.toLowerCase().includes(query.toLowerCase()) ||
                firstName.toLowerCase().includes(query.toLowerCase()) ||
                fullName.toLowerCase().includes(query.toLowerCase())) {
                foundFeatures.push(feature);
            }
        });

        if (foundFeatures.length > 0) {
            // Display only found features
            this.displayFeatures(foundFeatures);
            
            // Highlight and zoom to first result if only one found
            if (foundFeatures.length === 1) {
                this.highlightSearchResult(foundFeatures[0]);
            }
            
            this.showMessage(`Found ${foundFeatures.length} parcel(s) matching "${query}"`, 'success');
        } else {
            this.showMessage(`No parcels found matching "${query}"`, 'warning');
        }
    }

    clearSearchHighlights() {
        // Remove search highlight class from all layers
        this.parcelsLayer.eachLayer(layer => {
            if (layer.feature) {
                layer.setStyle(this.getFeatureStyle(layer.feature));
            }
        });
    }

    highlightSearchResult(feature) {
        // Find the layer for this feature and highlight it
        this.parcelsLayer.eachLayer(layer => {
            if (layer.feature && layer.feature.properties.id === feature.properties.id) {
                layer.setStyle({
                    color: '#ff9800',
                    weight: 3,
                    fillColor: '#ffeb3b',
                    fillOpacity: 0.8
                });
                
                // Zoom to feature
                this.map.fitBounds(layer.getBounds(), { maxZoom: 16 });
                
                // Open popup
                layer.openPopup();
            }
        });
    }

    selectChange(feature, layer) {
        // Handle change selection - similar to selectFeature but for changes
        const changeElement = document.getElementById('changeInfo');
        if (changeElement) {
            changeElement.innerHTML = this.createFeatureInfoPanel(feature.properties);
        }
        
        // Highlight selected change
        layer.setStyle({
            weight: 5,
            color: '#ff0000',
            fillOpacity: 0.8
        });
    }

    async approveChange(changeId) {
        if (!confirm('Are you sure you want to approve this change?')) {
            return;
        }

        try {
            const response = await fetch('/api/changes/apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ approved_ids: [changeId] })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Approval failed');
            }

            const result = await response.json();
            alert('Change approved successfully!');
            
            // Reload the changes layer
            this.loadChanges();
            
        } catch (error) {
            console.error('Error approving change:', error);
            alert('Error approving change: ' + error.message);
        }
    }

    async rejectChange(changeId) {
        if (!confirm('Are you sure you want to reject this change?')) {
            return;
        }

        try {
            const response = await fetch('/api/changes/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ rejected_ids: [changeId] })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Rejection failed');
            }

            const result = await response.json();
            alert('Change rejected successfully!');
            
            // Reload the changes layer
            this.loadChanges();
            
        } catch (error) {
            console.error('Error rejecting change:', error);
            alert('Error rejecting change: ' + error.message);
        }
    }

    // Measurement functionality
    startMeasurement(tool) {
        this.measurementMode = tool;
        this.measurementPoints = [];
        
        // Change cursor to crosshair
        this.map.getContainer().style.cursor = 'crosshair';
        
        // Remove existing click handlers temporarily
        this.map.off('click');
        
        if (tool === 'distance') {
            this.startDistanceMeasurement();
        } else if (tool === 'area') {
            this.startAreaMeasurement();
        }
    }

    startDistanceMeasurement() {
        let polyline = null;
        let markers = [];
        let totalDistance = 0;

        const onMapClick = (e) => {
            this.measurementPoints.push(e.latlng);
            
            // Add marker
            const marker = L.marker(e.latlng, {
                icon: L.divIcon({
                    className: 'measurement-marker',
                    html: '<div class="measurement-point"></div>',
                    iconSize: [10, 10]
                })
            }).addTo(this.measurementLayer);
            markers.push(marker);

            if (this.measurementPoints.length === 1) {
                // First point - just add marker
                const popup = L.popup()
                    .setLatLng(e.latlng)
                    .setContent('Click to add next point')
                    .openOn(this.map);
                    
            } else {
                // Calculate distance from last point
                const lastPoint = this.measurementPoints[this.measurementPoints.length - 2];
                const distance = this.calculateDistance(lastPoint, e.latlng);
                totalDistance += distance;

                // Create or update polyline
                if (polyline) {
                    polyline.addLatLng(e.latlng);
                } else {
                    polyline = L.polyline(this.measurementPoints, {
                        color: '#ff0000',
                        weight: 3,
                        opacity: 0.8,
                        dashArray: '10, 10'
                    }).addTo(this.measurementLayer);
                }

                // Add distance label
                const midpoint = this.calculateMidpoint(lastPoint, e.latlng);
                L.marker(midpoint, {
                    icon: L.divIcon({
                        className: 'measurement-label',
                        html: `<div class="distance-label">${this.formatDistance(distance)}</div>`,
                        iconSize: [60, 20]
                    })
                }).addTo(this.measurementLayer);

                // Show total distance popup
                L.popup()
                    .setLatLng(e.latlng)
                    .setContent(`Total Distance: ${this.formatDistance(totalDistance)}<br>Double-click to finish`)
                    .openOn(this.map);
            }
        };

        const onMapDoubleClick = () => {
            this.finishMeasurement();
            this.map.off('click', onMapClick);
            this.map.off('dblclick', onMapDoubleClick);
        };

        this.map.on('click', onMapClick);
        this.map.on('dblclick', onMapDoubleClick);
    }

    startAreaMeasurement() {
        let polygon = null;
        let markers = [];

        const onMapClick = (e) => {
            this.measurementPoints.push(e.latlng);
            
            // Add marker
            const marker = L.marker(e.latlng, {
                icon: L.divIcon({
                    className: 'measurement-marker',
                    html: '<div class="measurement-point"></div>',
                    iconSize: [10, 10]
                })
            }).addTo(this.measurementLayer);
            markers.push(marker);

            if (this.measurementPoints.length >= 3) {
                // Create or update polygon
                if (polygon) {
                    polygon.setLatLngs(this.measurementPoints);
                } else {
                    polygon = L.polygon(this.measurementPoints, {
                        color: '#ff0000',
                        weight: 2,
                        opacity: 0.8,
                        fillColor: '#ff0000',
                        fillOpacity: 0.2,
                        dashArray: '5, 5'
                    }).addTo(this.measurementLayer);
                }

                const area = this.calculatePolygonArea(this.measurementPoints);
                const center = polygon.getBounds().getCenter();
                
                // Remove existing area label
                this.measurementLayer.eachLayer(layer => {
                    if (layer.options && layer.options.className === 'area-label-marker') {
                        this.measurementLayer.removeLayer(layer);
                    }
                });

                // Add area label
                L.marker(center, {
                    icon: L.divIcon({
                        className: 'measurement-label area-label-marker',
                        html: `<div class="area-label">Area: ${this.formatArea(area)}</div>`,
                        iconSize: [80, 20]
                    })
                }).addTo(this.measurementLayer);

                L.popup()
                    .setLatLng(e.latlng)
                    .setContent(`Area: ${this.formatArea(area)}<br>Double-click to finish`)
                    .openOn(this.map);
            }
        };

        const onMapDoubleClick = () => {
            this.finishMeasurement();
            this.map.off('click', onMapClick);
            this.map.off('dblclick', onMapDoubleClick);
        };

        this.map.on('click', onMapClick);
        this.map.on('dblclick', onMapDoubleClick);
    }

    finishMeasurement() {
        this.measurementMode = null;
        this.measurementPoints = [];
        this.map.getContainer().style.cursor = '';
        
        // Restore original click handlers
        this.setupEventListeners();
    }

    clearMeasurements() {
        this.measurementLayer.clearLayers();
        this.finishMeasurement();
    }

    calculateDistance(latlng1, latlng2) {
        return latlng1.distanceTo(latlng2);
    }

    calculateMidpoint(latlng1, latlng2) {
        return L.latLng(
            (latlng1.lat + latlng2.lat) / 2,
            (latlng1.lng + latlng2.lng) / 2
        );
    }

    calculatePolygonArea(points) {
        // Simple area calculation using shoelace formula for geographic coordinates
        if (points.length < 3) return 0;
        
        let area = 0;
        const n = points.length;
        
        // Convert to projected coordinates for better accuracy
        for (let i = 0; i < n; i++) {
            const j = (i + 1) % n;
            area += points[i].lat * points[j].lng;
            area -= points[j].lat * points[i].lng;
        }
        
        // Convert from degrees to approximate square meters
        // This is a rough approximation - for precise calculations, use a proper projection
        area = Math.abs(area) / 2;
        const metersPerDegree = 111320; // Approximate meters per degree at equator
        return area * metersPerDegree * metersPerDegree * Math.cos(points[0].lat * Math.PI / 180);
    }

    formatDistance(meters) {
        if (meters < 1000) {
            return `${Math.round(meters)} m`;
        } else {
            return `${(meters / 1000).toFixed(2)} km`;
        }
    }

    formatArea(sqMeters) {
        if (sqMeters < 10000) {
            return `${Math.round(sqMeters)} m²`;
        } else {
            return `${(sqMeters / 10000).toFixed(2)} ha`;
        }
    }

    // Export functionality
    exportData(format = 'geojson') {
        const features = this.allFeatures.length > 0 ? this.allFeatures : this.filteredFeatures;
        
        if (features.length === 0) {
            alert('No data to export');
            return;
        }

        const geojson = {
            type: 'FeatureCollection',
            features: features
        };

        let content, filename, mimeType;

        switch (format) {
            case 'geojson':
                content = JSON.stringify(geojson, null, 2);
                filename = 'city_parcels.geojson';
                mimeType = 'application/geo+json';
                break;
            case 'csv':
                content = this.convertToCSV(features);
                filename = 'city_parcels.csv';
                mimeType = 'text/csv';
                break;
            default:
                content = JSON.stringify(geojson, null, 2);
                filename = 'city_parcels.geojson';
                mimeType = 'application/geo+json';
        }

        this.downloadFile(content, filename, mimeType);
    }

    convertToCSV(features) {
        if (!features.length) return '';
        
        const headers = ['id', 'owner_name', 'landuse_ti', 'area_m2_ti', 'upin', 'first_name', 'fathers_na', 'fullname', 'kentcode'];
        const rows = features.map(feature => {
            const props = feature.properties || {};
            return headers.map(header => props[header] || '').join(',');
        });
        
        return [headers.join(','), ...rows].join('\n');
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        if (document.body) {
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        URL.revokeObjectURL(url);
    }

    // Print and PDF functionality
    printCurrentView() {
        // Create a print-friendly version of the map
        const printWindow = window.open('', '_blank');
        const mapElement = document.getElementById('map');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>City Master Plan - Map Print</title>
                <style>
                    body { margin: 0; padding: 20px; }
                    .print-header { text-align: center; margin-bottom: 20px; }
                    .print-map { width: 100%; height: 80vh; border: 1px solid #ccc; }
                    @media print { 
                        body { margin: 0; }
                        .print-header { margin-bottom: 10px; }
                        .print-map { height: 85vh; }
                    }
                </style>
                <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
            </head>
            <body>
                <div class="print-header">
                    <h2>City Master Plan - Woldia</h2>
                    <p>Generated on: ${new Date().toLocaleDateString()}</p>
                </div>
                <div id="printMap" class="print-map"></div>
                <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
                <script>
                    // Copy current map view to print window
                    const printMap = L.map('printMap').setView([${this.map.getCenter().lat}, ${this.map.getCenter().lng}], ${this.map.getZoom()});
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(printMap);
                    setTimeout(() => window.print(), 2000);
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    generatePDFReport() {
        // Create a comprehensive PDF report
        this.showMessage('Generating PDF report...', 'info');
        
        // Collect statistics
        const stats = this.calculateStatistics();
        
        // Generate report content
        const reportContent = this.createReportContent(stats);
        
        // For now, download as HTML (could be enhanced with PDF.js)
        this.downloadFile(reportContent, 'city_master_plan_report.html', 'text/html');
        this.showMessage('Report generated successfully!', 'success');
    }

    exportAsImage() {
        // Export the current map view as an image
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = 1200;
        canvas.height = 800;
        
        // Create a simple image export (basic implementation)
        ctx.fillStyle = '#f0f0f0';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#333';
        ctx.font = '20px Arial';
        ctx.fillText('City Master Plan - Woldia', 50, 50);
        ctx.font = '14px Arial';
        ctx.fillText(`Generated: ${new Date().toLocaleDateString()}`, 50, 80);
        
        // Convert to blob and download
        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'city_map_export.png';
            link.click();
            URL.revokeObjectURL(url);
        }, 'image/png');
    }

    // Administrative boundaries functionality
    toggleBoundaries() {
        this.boundariesVisible = !this.boundariesVisible;
        const card = document.getElementById('boundariesCard');
        const button = document.getElementById('toggleBoundaries');
        
        if (this.boundariesVisible) {
            card.style.display = 'block';
            button.classList.remove('btn-outline-info');
            button.classList.add('btn-info');
            this.loadBoundaryLayers();
        } else {
            card.style.display = 'none';
            button.classList.remove('btn-info');
            button.classList.add('btn-outline-info');
            this.boundariesLayer.clearLayers();
        }
    }

    loadBoundaryLayers() {
        // Create sample administrative boundaries for Woldia
        const districts = this.createSampleDistricts();
        const zones = this.createSampleZones();
        
        // Add to boundary layer
        this.updateBoundaryLayers();
    }

    createSampleDistricts() {
        // Sample district boundaries for Woldia area
        return [
            {
                name: 'Central District',
                coordinates: [
                    [11.8400, 39.5900], [11.8400, 39.6200], 
                    [11.8200, 39.6200], [11.8200, 39.5900], [11.8400, 39.5900]
                ]
            },
            {
                name: 'Northern District', 
                coordinates: [
                    [11.8400, 39.6000], [11.8600, 39.6000],
                    [11.8600, 39.6300], [11.8400, 39.6300], [11.8400, 39.6000]
                ]
            }
        ];
    }

    createSampleZones() {
        return [
            {
                name: 'Residential Zone A',
                coordinates: [
                    [11.8350, 39.6000], [11.8350, 39.6150],
                    [11.8250, 39.6150], [11.8250, 39.6000], [11.8350, 39.6000]
                ]
            },
            {
                name: 'Commercial Zone',
                coordinates: [
                    [11.8300, 39.6050], [11.8300, 39.6150],
                    [11.8200, 39.6150], [11.8200, 39.6050], [11.8300, 39.6050]
                ]
            }
        ];
    }

    updateBoundaryLayers() {
        this.boundariesLayer.clearLayers();
        
        const opacity = document.getElementById('boundaryOpacity')?.value || 0.7;
        
        if (document.getElementById('showDistricts')?.checked) {
            const districts = this.createSampleDistricts();
            districts.forEach(district => {
                const polygon = L.polygon(district.coordinates, {
                    color: '#ff0000',
                    weight: 2,
                    fillOpacity: opacity * 0.3,
                    fillColor: '#ff0000'
                }).bindPopup(`<b>${district.name}</b><br>Type: District`);
                this.boundariesLayer.addLayer(polygon);
            });
        }
        
        if (document.getElementById('showZones')?.checked) {
            const zones = this.createSampleZones();
            zones.forEach(zone => {
                const polygon = L.polygon(zone.coordinates, {
                    color: '#0000ff',
                    weight: 2,
                    fillOpacity: opacity * 0.3,
                    fillColor: '#0000ff'
                }).bindPopup(`<b>${zone.name}</b><br>Type: Planning Zone`);
                this.boundariesLayer.addLayer(polygon);
            });
        }
    }

    updateBoundaryOpacity(opacity) {
        this.updateBoundaryLayers();
    }

    // Spatial analysis functionality
    createBufferAnalysis() {
        const distance = document.getElementById('bufferDistance')?.value || 100;
        
        if (this.selectedFeatures.size === 0) {
            this.showMessage('Please select a parcel first', 'warning');
            return;
        }

        this.analysisLayer.clearLayers();
        
        // Create buffer around selected features
        this.selectedFeatures.forEach(feature => {
            if (feature.geometry && feature.geometry.coordinates) {
                const center = this.getFeatureCenter(feature);
                const bufferCircle = L.circle(center, {
                    radius: distance,
                    color: '#00ff00',
                    weight: 2,
                    fillColor: '#00ff00',
                    fillOpacity: 0.2,
                    dashArray: '5, 5'
                }).bindPopup(`Buffer Zone: ${distance}m radius`);
                
                this.analysisLayer.addLayer(bufferCircle);
            }
        });
        
        this.showMessage(`Buffer analysis created with ${distance}m radius`, 'success');
    }

    performProximityAnalysis() {
        if (this.allFeatures.length === 0) {
            this.showMessage('No data available for proximity analysis', 'warning');
            return;
        }

        this.analysisLayer.clearLayers();
        
        // Find features within proximity of each other
        const proximityResults = [];
        const maxDistance = 200; // meters
        
        this.allFeatures.forEach((feature, index) => {
            this.allFeatures.slice(index + 1).forEach(otherFeature => {
                const distance = this.calculateFeatureDistance(feature, otherFeature);
                if (distance < maxDistance) {
                    proximityResults.push({
                        feature1: feature,
                        feature2: otherFeature,
                        distance: distance
                    });
                }
            });
        });
        
        // Visualize proximity connections
        proximityResults.forEach(result => {
            const center1 = this.getFeatureCenter(result.feature1);
            const center2 = this.getFeatureCenter(result.feature2);
            
            const line = L.polyline([center1, center2], {
                color: '#ff8800',
                weight: 2,
                opacity: 0.7,
                dashArray: '3, 3'
            }).bindPopup(`Proximity: ${Math.round(result.distance)}m`);
            
            this.analysisLayer.addLayer(line);
        });
        
        this.showMessage(`Found ${proximityResults.length} proximity connections`, 'info');
    }

    clearSpatialAnalysis() {
        this.analysisLayer.clearLayers();
        this.showMessage('Spatial analysis cleared', 'info');
    }

    // Helper functions
    getFeatureCenter(feature) {
        if (feature.geometry.type === 'Polygon') {
            const coords = feature.geometry.coordinates[0];
            let lat = 0, lng = 0;
            coords.forEach(coord => {
                lng += coord[0];
                lat += coord[1];
            });
            return [lat / coords.length, lng / coords.length];
        }
        return [0, 0];
    }

    calculateFeatureDistance(feature1, feature2) {
        const center1 = this.getFeatureCenter(feature1);
        const center2 = this.getFeatureCenter(feature2);
        return L.latLng(center1).distanceTo(L.latLng(center2));
    }

    calculateStatistics() {
        const totalParcels = this.allFeatures.length;
        const totalArea = this.allFeatures.reduce((sum, feature) => {
            return sum + (feature.properties?.area_m2_ti || 0);
        }, 0);
        
        const landUseStats = {};
        this.allFeatures.forEach(feature => {
            const landUse = feature.properties?.landuse_ti || 'Unknown';
            landUseStats[landUse] = (landUseStats[landUse] || 0) + 1;
        });
        
        // Update legend counts
        this.updateLegendCounts(landUseStats);
        
        return { totalParcels, totalArea, landUseStats };
    }

    updateLegendCounts(landUseStats) {
        // Update legend with actual counts
        const legendMappings = {
            'Residential': 'residentialCount',
            'Commercial': 'commercialCount', 
            'Industrial': 'industrialCount',
            'Institutional': 'institutionalCount',
            'Mixed Use': 'mixedCount',
            'Recreation': 'recreationCount'
        };
        
        Object.entries(legendMappings).forEach(([landUse, elementId]) => {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = landUseStats[landUse] || 0;
            }
        });
    }

    createReportContent(stats) {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>City Master Plan Report - Woldia</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .section { margin-bottom: 25px; }
                    .stats-table { width: 100%; border-collapse: collapse; }
                    .stats-table th, .stats-table td { border: 1px solid #ddd; padding: 8px; }
                    .stats-table th { background-color: #f5f5f5; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>City Master Plan Report</h1>
                    <h2>Woldia City, Ethiopia</h2>
                    <p>Generated on: ${new Date().toLocaleDateString()}</p>
                </div>
                
                <div class="section">
                    <h3>Summary Statistics</h3>
                    <table class="stats-table">
                        <tr><th>Metric</th><th>Value</th></tr>
                        <tr><td>Total Parcels</td><td>${stats.totalParcels}</td></tr>
                        <tr><td>Total Area</td><td>${(stats.totalArea / 10000).toFixed(2)} hectares</td></tr>
                    </table>
                </div>
                
                <div class="section">
                    <h3>Land Use Distribution</h3>
                    <table class="stats-table">
                        <tr><th>Land Use Type</th><th>Count</th></tr>
                        ${Object.entries(stats.landUseStats).map(([type, count]) => 
                            `<tr><td>${type}</td><td>${count}</td></tr>`
                        ).join('')}
                    </table>
                </div>
            </body>
            </html>
        `;
    }

    // Advanced Layer Management System
    setupLayerManagement() {
        // Layer group toggle functionality
        document.querySelectorAll('.toggle-group').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = btn.getAttribute('data-target');
                const target = document.getElementById(targetId);
                const icon = btn.querySelector('i');
                
                if (target.style.display === 'none') {
                    target.style.display = 'block';
                    icon.className = 'fas fa-chevron-down';
                } else {
                    target.style.display = 'none';
                    icon.className = 'fas fa-chevron-right';
                }
            });
        });

        // Base map switching
        document.querySelectorAll('input[name="basemap"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.changeBasemap(e.target.value);
            });
        });

        // Layer visibility controls
        const parcelsToggle = document.getElementById('parcelsLayerToggle');
        if (parcelsToggle) {
            parcelsToggle.addEventListener('change', (e) => {
                this.toggleLayerVisibility('parcels', e.target.checked);
            });
        }

        const changesToggle = document.getElementById('changesLayerToggle');
        if (changesToggle) {
            changesToggle.addEventListener('change', (e) => {
                this.toggleLayerVisibility('changes', e.target.checked);
            });
        }

        // Opacity controls
        const parcelsOpacity = document.getElementById('parcelsOpacity');
        if (parcelsOpacity) {
            parcelsOpacity.addEventListener('input', (e) => {
                this.updateLayerOpacity('parcels', e.target.value);
            });
        }
    }

    changeBasemap(basemapType) {
        // Remove current base layer
        this.map.eachLayer((layer) => {
            if (layer._url && layer._url.includes('tile')) {
                this.map.removeLayer(layer);
            }
        });

        // Add new base layer
        let tileUrl, attribution;
        switch (basemapType) {
            case 'satellite':
                tileUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
                attribution = 'Tiles &copy; Esri';
                break;
            case 'terrain':
                tileUrl = 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png';
                attribution = 'Map data &copy; OpenStreetMap, SRTM | Map style &copy; OpenTopoMap';
                break;
            default: // osm
                tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
        }

        L.tileLayer(tileUrl, { attribution }).addTo(this.map);
        console.log(`Basemap changed to: ${basemapType}`);
    }

    toggleLayerVisibility(layerType, visible) {
        switch (layerType) {
            case 'parcels':
                if (visible) {
                    this.map.addLayer(this.parcelsLayer);
                } else {
                    this.map.removeLayer(this.parcelsLayer);
                }
                break;
            case 'changes':
                if (visible) {
                    this.map.addLayer(this.changesLayer);
                } else {
                    this.map.removeLayer(this.changesLayer);
                }
                break;
        }
    }

    updateLayerOpacity(layerType, opacity) {
        this.layerOpacity[layerType] = opacity;
        
        switch (layerType) {
            case 'parcels':
                this.parcelsLayer.eachLayer((layer) => {
                    layer.setStyle({ fillOpacity: opacity * 0.6 });
                });
                break;
        }
    }

    // Spatial Query Builder
    setupQueryBuilder() {
        const executeQueryBtn = document.getElementById('executeQuery');
        if (executeQueryBtn) {
            executeQueryBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.executeCustomQuery();
            });
        }

        const saveQueryBtn = document.getElementById('saveQuery');
        if (saveQueryBtn) {
            saveQueryBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveCurrentQuery();
            });
        }

        const clearQueryBtn = document.getElementById('clearQuery');
        if (clearQueryBtn) {
            clearQueryBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearQueryResults();
            });
        }
    }

    executeCustomQuery() {
        const field = document.getElementById('queryField')?.value;
        const operator = document.getElementById('queryOperator')?.value;
        const value = document.getElementById('queryValue')?.value;

        if (!field || !operator || !value) {
            this.showMessage('Please fill all query fields', 'warning');
            return;
        }

        // Clear previous results
        this.clearQueryResults();
        
        // Execute query on current data
        const results = this.allFeatures.filter(feature => {
            const fieldValue = feature.properties[field];
            
            switch (operator) {
                case 'equals':
                    return fieldValue?.toString().toLowerCase() === value.toLowerCase();
                case 'contains':
                    return fieldValue?.toString().toLowerCase().includes(value.toLowerCase());
                case 'greater':
                    return parseFloat(fieldValue) > parseFloat(value);
                case 'less':
                    return parseFloat(fieldValue) < parseFloat(value);
                case 'within':
                    // Within distance query (simplified)
                    return true; // Implement distance logic
                default:
                    return false;
            }
        });

        // Highlight query results
        this.queryResults = results;
        this.highlightQueryResults(results);
        
        this.showMessage(`Query executed: Found ${results.length} matching features`, 'success');
    }

    highlightQueryResults(results) {
        results.forEach(feature => {
            if (feature.layerInstance) {
                feature.layerInstance.setStyle({
                    color: '#ff0000',
                    weight: 3,
                    fillColor: '#ffff00',
                    fillOpacity: 0.5
                });
            }
        });
    }

    saveCurrentQuery() {
        const field = document.getElementById('queryField')?.value;
        const operator = document.getElementById('queryOperator')?.value;
        const value = document.getElementById('queryValue')?.value;

        if (!field || !operator || !value) {
            this.showMessage('Cannot save empty query', 'warning');
            return;
        }

        const queryName = prompt('Enter name for this query:');
        if (queryName) {
            const query = { name: queryName, field, operator, value };
            this.savedQueries.push(query);
            localStorage.setItem('savedQueries', JSON.stringify(this.savedQueries));
            this.showMessage(`Query "${queryName}" saved successfully`, 'success');
        }
    }

    clearQueryResults() {
        // Reset styles for query results
        this.queryResults.forEach(feature => {
            if (feature.layerInstance) {
                feature.layerInstance.setStyle(this.getFeatureStyle(feature));
            }
        });
        this.queryResults = [];
    }

    // Annotation Tools
    setupAnnotationTools() {
        // Annotation tool selection
        document.querySelectorAll('.annotation-tool').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const tool = btn.getAttribute('data-tool');
                this.selectAnnotationTool(tool);
            });
        });

        // Clear annotations
        const clearAnnotationsBtn = document.getElementById('clearAnnotations');
        if (clearAnnotationsBtn) {
            clearAnnotationsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearAnnotations();
            });
        }

        // Setup annotation layer toggle
        const annotationsToggle = document.getElementById('annotationsToggle');
        if (annotationsToggle) {
            annotationsToggle.addEventListener('change', (e) => {
                this.toggleLayerVisibility('annotations', e.target.checked);
            });
        }
    }

    selectAnnotationTool(toolType) {
        this.annotationMode = toolType;
        this.map.getContainer().style.cursor = 'crosshair';
        
        // Remove previous annotation event listeners
        this.map.off('click', this.onAnnotationClick);
        
        // Add new event listener based on tool type
        this.onAnnotationClick = (e) => {
            switch (toolType) {
                case 'text':
                    this.addTextAnnotation(e.latlng);
                    break;
                case 'arrow':
                    this.startArrowAnnotation(e.latlng);
                    break;
                case 'highlight':
                    this.addHighlightAnnotation(e.latlng);
                    break;
            }
        };
        
        this.map.on('click', this.onAnnotationClick);
        this.showMessage(`${toolType} annotation tool selected. Click on map to add.`, 'info');
    }

    addTextAnnotation(latlng) {
        const text = prompt('Enter annotation text:');
        if (text) {
            const textMarker = L.marker(latlng, {
                icon: L.divIcon({
                    className: 'text-annotation',
                    html: `<div class="annotation-text">${text}</div>`,
                    iconSize: [100, 30],
                    iconAnchor: [50, 15]
                })
            }).bindPopup(`<b>Annotation:</b><br>${text}`);
            
            this.annotationLayer.addLayer(textMarker);
        }
        this.exitAnnotationMode();
    }

    startArrowAnnotation(latlng) {
        if (!this.arrowStart) {
            this.arrowStart = latlng;
            this.showMessage('Click second point to complete arrow', 'info');
        } else {
            const arrow = L.polyline([this.arrowStart, latlng], {
                color: '#ff0000',
                weight: 3
            }).bindPopup('Planning Arrow');
            
            this.annotationLayer.addLayer(arrow);
            this.arrowStart = null;
            this.exitAnnotationMode();
        }
    }

    addHighlightAnnotation(latlng) {
        const highlight = L.circle(latlng, {
            radius: 50,
            color: '#ffff00',
            fillColor: '#ffff00',
            fillOpacity: 0.3,
            weight: 2
        }).bindPopup('Highlighted Area');
        
        this.annotationLayer.addLayer(highlight);
        this.exitAnnotationMode();
    }

    clearAnnotations() {
        this.annotationLayer.clearLayers();
        this.showMessage('All annotations cleared', 'info');
    }

    exitAnnotationMode() {
        this.annotationMode = null;
        this.map.getContainer().style.cursor = '';
        this.map.off('click', this.onAnnotationClick);
    }

    // Coordinate Reference System Info
    showCoordinateSystemInfo() {
        const center = this.map.getCenter();
        const zoom = this.map.getZoom();
        
        // Convert to different coordinate systems
        const wgs84 = `${center.lat.toFixed(6)}, ${center.lng.toFixed(6)}`;
        const utm = this.convertToUTM(center.lat, center.lng);
        
        const info = `
            <div style="font-family: monospace;">
                <h6>Current Map Center</h6>
                <p><strong>WGS84 (Lat/Lng):</strong><br>${wgs84}</p>
                <p><strong>UTM Zone 37N:</strong><br>${utm}</p>
                <p><strong>Zoom Level:</strong> ${zoom}</p>
                <p><strong>Current CRS:</strong> EPSG:4326</p>
            </div>
        `;
        
        // Show in popup
        const popup = L.popup()
            .setLatLng(center)
            .setContent(info)
            .openOn(this.map);
    }

    convertToUTM(lat, lng) {
        // Simplified UTM conversion for display purposes
        const easting = ((lng + 180) / 360 * 20037508.34 * 2).toFixed(2);
        const northing = ((lat * Math.PI / 180) * 6378137).toFixed(2);
        return `E: ${easting}, N: ${northing}`;
    }

    // Performance Optimization
    optimizeLayerRendering() {
        // Implement clustering for large datasets
        if (this.allFeatures.length > 100) {
            this.enableClustering();
        }
        
        // Use simplified geometry at lower zoom levels
        this.updateGeometryDetail();
    }

    enableClustering() {
        // Create marker cluster group for better performance with many features
        if (window.L && L.markerClusterGroup) {
            const clusters = L.markerClusterGroup({
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: false
            });
            
            // Add feature centroids to cluster when zoomed out
            if (this.map.getZoom() < 14) {
                this.allFeatures.forEach(feature => {
                    const center = this.getFeatureCenter(feature);
                    const marker = L.marker(center).bindPopup(
                        `<b>${feature.properties.owner_name || 'Unknown'}</b><br>
                         Land Use: ${feature.properties.landuse_ti || 'N/A'}`
                    );
                    clusters.addLayer(marker);
                });
                
                this.map.addLayer(clusters);
            }
        }
    }

    updateGeometryDetail() {
        const zoom = this.map.getZoom();
        
        // Simplify geometry rendering at lower zoom levels
        this.parcelsLayer.eachLayer((layer) => {
            if (zoom < 12) {
                layer.setStyle({ weight: 1 });
            } else if (zoom < 15) {
                layer.setStyle({ weight: 2 });
            } else {
                layer.setStyle({ weight: 3 });
            }
        });
    }

    // Loading indicator for better UX
    showLoadingIndicator(message) {
        const indicator = document.createElement('div');
        indicator.id = 'loadingIndicator';
        indicator.className = 'loading-indicator';
        indicator.innerHTML = `
            <div class="spinner"></div>
            <div>${message}</div>
        `;
        
        const mapContainer = document.getElementById('map');
        mapContainer.style.position = 'relative';
        mapContainer.appendChild(indicator);
    }

    hideLoadingIndicator() {
        const indicator = document.getElementById('loadingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    // Enhanced statistics update
    updateStatistics() {
        const stats = this.calculateStatistics();
        
        // Update display
        const totalParcelsEl = document.getElementById('totalParcels');
        const pendingChangesEl = document.getElementById('pendingChanges');
        const totalAreaEl = document.getElementById('totalArea');
        const selectedAreaEl = document.getElementById('selectedArea');
        
        if (totalParcelsEl) totalParcelsEl.textContent = stats.totalParcels;
        if (pendingChangesEl) pendingChangesEl.textContent = this.changesLayer.getLayers().length;
        if (totalAreaEl) totalAreaEl.textContent = (stats.totalArea / 10000).toFixed(2);
        
        // Calculate selected area
        let selectedArea = 0;
        this.selectedFeatures.forEach(feature => {
            selectedArea += feature.properties?.area_m2_ti || 0;
        });
        if (selectedAreaEl) selectedAreaEl.textContent = (selectedArea / 10000).toFixed(2);
    }

    // Check if we need to center on Woldia after upload
    checkAndCenterOnWoldia() {
        const shouldCenter = localStorage.getItem('centerOnWoldia');
        const uploadSuccess = localStorage.getItem('uploadSuccess');
        
        if (shouldCenter === 'true') {
            // Clear the flags
            localStorage.removeItem('centerOnWoldia');
            localStorage.removeItem('uploadSuccess');
            
            // Center on Woldia with animation
            setTimeout(() => {
                this.centerOnWoldia();
                
                if (uploadSuccess === 'true') {
                    this.showMessage('Upload successful! Map centered on Woldia. Loading changes for approval...', 'success');
                    // Load changes after successful upload
                    this.loadChanges();
                }
            }, 1000);
        }
    }

    // Center map on Woldia city
    centerOnWoldia() {
        const woldiaCoordinates = [11.8311, 39.6069];
        const zoomLevel = 14; // Closer zoom for better view
        
        // Animate to Woldia
        this.map.flyTo(woldiaCoordinates, zoomLevel, {
            duration: 2,
            easeLinearity: 0.5
        });
        
        // Add a temporary marker on Woldia
        const marker = L.marker(woldiaCoordinates)
            .addTo(this.map)
            .bindPopup('<b>Woldia City Center</b><br>Your data has been uploaded here')
            .openPopup();
        
        // Remove marker after 5 seconds
        setTimeout(() => {
            this.map.removeLayer(marker);
        }, 5000);
    }

}

// Global variable declaration
let cityMap = null;

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the map page
    if (!document.getElementById('map')) {
        console.log('Not on map page, skipping map initialization');
        return;
    }
    
    // Use mapConfig if available, otherwise use defaults
    const config = window.mapConfig || {
        centerLat: 11.8311,  // Woldia latitude
        centerLng: 39.6069,  // Woldia longitude
        zoom: 13,
        apiBaseUrl: '/api/'
    };
    
    console.log('DOM loaded, mapConfig:', config);
    
    if (config) {
        try {
            console.log('Creating CityMap instance...');
            cityMap = new CityMap(config);
            window.cityMap = cityMap;  // Make it globally accessible
            
            console.log('CityMap created successfully:', cityMap);
        } catch (error) {
            console.error('Error creating CityMap:', error);
        }
    } else {
        console.error('mapConfig not found');
    }
});
