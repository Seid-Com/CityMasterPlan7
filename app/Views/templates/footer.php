    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Draw JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/js/map.js"></script>
    
    <?php if (isset($includeUploadJS) && $includeUploadJS): ?>
        <script src="/js/upload.js"></script>
    <?php endif; ?>
</body>
</html>
