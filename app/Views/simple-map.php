<?php 
echo view('templates/header', ['title' => 'OpenStreetMap Base Layer']); 
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Simple Map Container -->
        <div class="col-12">
            <div id="map" style="height: calc(100vh - 56px); width: 100%;"></div>
        </div>
    </div>
</div>

<script>
// Simple OpenStreetMap base layer
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing OpenStreetMap...');
    
    // Initialize the map
    const map = L.map('map', {
        center: [11.8311, 39.6069], // Woldia city coordinates
        zoom: 13
    });

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    console.log('OpenStreetMap base layer loaded successfully');
    
    // Load spatial data from API
    fetch('/api/parcels')
        .then(response => response.json())
        .then(data => {
            console.log('Loaded spatial data:', data);
            
            if (data.features && data.features.length > 0) {
                // Add spatial data as GeoJSON layer
                L.geoJSON(data, {
                    style: function(feature) {
                        // Color based on land use type
                        const landUse = feature.properties.landuse_ti;
                        let color = '#007bff'; // default blue
                        
                        switch(landUse) {
                            case 'Residential': color = '#28a745'; break;
                            case 'Commercial': color = '#ffc107'; break;
                            case 'Industrial': color = '#6c757d'; break;
                            case 'Educational': color = '#dc3545'; break;
                            case 'Mixed Use': color = '#17a2b8'; break;
                            case 'Public Space': color = '#6f42c1'; break;
                        }
                        
                        return {
                            fillColor: color,
                            weight: 2,
                            opacity: 1,
                            color: 'white',
                            fillOpacity: 0.7
                        };
                    },
                    onEachFeature: function(feature, layer) {
                        // Create popup with property information
                        const props = feature.properties;
                        const popupContent = `
                            <h6>${props.fullname || 'Unnamed Parcel'}</h6>
                            <p><strong>Owner:</strong> ${props.owner_name}<br>
                            <strong>Land Use:</strong> ${props.landuse_ti}<br>
                            <strong>Area:</strong> ${props.area_m2_ti ? props.area_m2_ti.toLocaleString() + ' m²' : 'N/A'}<br>
                            <strong>UPIN:</strong> ${props.upin}</p>
                        `;
                        layer.bindPopup(popupContent);
                        
                        // Add permanent label
                        layer.bindTooltip(props.fullname || props.owner_name, {
                            permanent: true,
                            direction: 'center',
                            className: 'parcel-label'
                        });
                    }
                }).addTo(map);
                
                console.log(`Loaded ${data.features.length} spatial features`);
            } else {
                console.log('No spatial data found');
            }
        })
        .catch(error => {
            console.error('Error loading spatial data:', error);
        });
});
</script>

<?php echo view('templates/footer'); ?>