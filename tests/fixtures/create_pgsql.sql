CREATE USER "php-crud-api" WITH PASSWORD 'php-crud-api';
CREATE DATABASE "php-crud-api";
GRANT ALL PRIVILEGES ON DATABASE "php-crud-api" to "php-crud-api";
\c "php-crud-api";
CREATE EXTENSION IF NOT EXISTS postgis;