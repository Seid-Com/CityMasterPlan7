// Simple Leaflet map with OpenStreetMap base layer
document.addEventListener('DOMContentLoaded', function() {
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
});