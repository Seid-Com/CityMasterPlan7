-- Enable PostGIS extension if not already enabled
CREATE EXTENSION IF NOT EXISTS postgis;

-- Create spatial parcels table
CREATE TABLE IF NOT EXISTS spatial_parcels (
    id SERIAL PRIMARY KEY,
    upin VARCHAR(50) UNIQUE,
    owner_name VARCHAR(255),
    fullname VARCHAR(255),
    landuse_ti VARCHAR(100),
    area_m2_ti NUMERIC(10,2),
    status VARCHAR(50) DEFAULT 'active',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    geometry GEOMETRY(Polygon, 4326),
    properties JSONB
);

-- Create spatial index
CREATE INDEX IF NOT EXISTS idx_spatial_parcels_geometry ON spatial_parcels USING GIST (geometry);

-- Create index for commonly queried fields
CREATE INDEX IF NOT EXISTS idx_spatial_parcels_landuse ON spatial_parcels(landuse_ti);
CREATE INDEX IF NOT EXISTS idx_spatial_parcels_status ON spatial_parcels(status);
CREATE INDEX IF NOT EXISTS idx_spatial_parcels_upload_date ON spatial_parcels(upload_date);

-- Create change tracking table
CREATE TABLE IF NOT EXISTS spatial_changes (
    id SERIAL PRIMARY KEY,
    parcel_id INTEGER REFERENCES spatial_parcels(id),
    change_type VARCHAR(50), -- 'new', 'modified', 'deleted'
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved BOOLEAN DEFAULT FALSE,
    approved_by VARCHAR(255),
    approved_date TIMESTAMP,
    old_data JSONB,
    new_data JSONB
);

-- Create index for change tracking
CREATE INDEX IF NOT EXISTS idx_spatial_changes_parcel ON spatial_changes(parcel_id);
CREATE INDEX IF NOT EXISTS idx_spatial_changes_approved ON spatial_changes(approved);
CREATE INDEX IF NOT EXISTS idx_spatial_changes_date ON spatial_changes(change_date);

-- Create uploads tracking table
CREATE TABLE IF NOT EXISTS shapefile_uploads (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    features_count INTEGER,
    status VARCHAR(50) DEFAULT 'pending',
    error_message TEXT,
    metadata JSONB
);