# City Master Plan GIS Application

## Project Overview
A CodeIgniter 4-based web GIS application for city master planning with PostGIS integration. The application provides interactive mapping functionality for managing city parcels, land use planning, and urban development.

## Recent Changes (Migration to Replit)
- **2025-08-29**: 
  - Major GIS enhancements - Added measurement tools, print/PDF capabilities, administrative boundaries, spatial analysis, and mobile optimization
  - Fixed shapefile upload JSON parsing errors with improved error handling
  - Added automatic map centering to Woldia (11.8311°N, 39.6069°E) after successful shapefile uploads
  - Enhanced upload feedback with visual indicators and automatic map redirection
  - Resolved PHP Shapefile library dependency issue with simplified validation-only processing
  - Fixed JavaScript "appendChild" error by adding document.body existence checks to prevent DOM access before page load
  - Resolved upload JSON parsing errors with enhanced error handling, CORS support, and improved response processing
  - Implemented database integration for spatial data storage with PostGIS-enabled tables
  - Created spatial_parcels table for storing uploaded shapefile data dynamically
  - Updated upload system to save spatial data directly to PostgreSQL database
  - Modified API endpoints to fetch spatial data from database for dynamic display on Woldia map
  - **Added professional-grade urban planning features:**
    - Search Control: Interactive parcel search by owner name, UPIN, or fullname with highlighting
    - Land Use Statistics Panel: Real-time statistics showing parcel counts and area calculations by land use type
    - Legend Control: Visual legend displaying all land use types with enhanced color coding
    - Enhanced Color Scheme: Professional color palette for 13+ land use types (Residential, Commercial, Industrial, Educational, etc.)
  - **Integrated main database table (geostore.spartialdata):**
    - Configured API to fetch from main database table with full attribute schema
    - Added support for all 30+ parcel attributes including region codes, kebele codes, title deeds, land tenure, etc.
    - Enhanced popup displays to show comprehensive parcel information
    - Implemented SRID transformation from EPSG:20137 to EPSG:4326 for web display
- **2025-08-28**: Changed map center from Addis Ababa to Woldia city coordinates
- **2025-01-25**: Successfully migrated from Replit Agent to standard Replit environment
- Updated base URL configuration for Replit (0.0.0.0:5000)
- Set up PostgreSQL database with environment variables (DATABASE_URL, PGHOST, PGUSER, etc.)
- Fixed file permissions for writable directories (cache, session, uploads)
- Created missing cache and session directories
- Verified application functionality with interactive Leaflet map and GeoJSON data loading

## Project Architecture
- **Backend**: PHP 8.2+ with CodeIgniter 4.5 framework
- **Database**: PostgreSQL with PostGIS extension for spatial data
- **Frontend**: HTML5, CSS3, JavaScript with Leaflet.js for interactive mapping
- **Security**: Content Security Policy configured, CSRF protection enabled, environment variables for sensitive data

## User Preferences
- Uses CodeIgniter MVC architecture patterns
- PostgreSQL preferred for spatial data capabilities
- Modern web standards with responsive design
- Security-first approach with proper client/server separation

## Key Features
- Interactive mapping with Leaflet.js and multiple basemap layers
- Professional measurement tools (distance and area calculations)
- Print and PDF report generation capabilities
- Administrative boundaries layer (districts, zones, neighborhoods)
- Spatial analysis tools (buffer zones, proximity analysis)
- Data export functionality (GeoJSON, CSV formats)
- Mobile-responsive design with touch interface optimization
- GeoJSON data visualization for city parcels
- Land use classification and area calculations
- Shapefile upload and change management workflow
- RESTful API endpoints for spatial data
- Offline map layer support with connectivity fallback

## Environment Configuration
- Production-ready security configurations
- Environment variables for database connections
- Writable directories properly configured for caching and sessions
- Content Security Policy with approved CDN sources