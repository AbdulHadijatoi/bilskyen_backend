-- DMR filtered dataset: normalized star/snowflake schema (lookup tables + fact + bridges).
-- MySQL 8.0+ / MariaDB 10.5+ (InnoDB, utf8mb4). No data — structure only.
-- Table names use the dmr_ prefix; lookup/dimension tables use plural English names; PK column is id.
--
-- Run in your database, e.g.:
--   CREATE DATABASE IF NOT EXISTS dmr_normalized_data CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE dmr_normalized_data;
--   SOURCE dmr_normalized_schema.sql;
--
-- dmr_etl_loads is created before dmr_fact_vehicles so the fact table can reference it without ALTER TABLE.
-- MySQL: UNIQUE on TEXT/BLOB requires a prefix or VARCHAR; this file uses VARCHAR for keyed strings.
--
-- Production notes:
--   - Emission/consumption/motor scalars use DECIMAL(15,6) to avoid float drift and allow large values without overflow.
--   - No UNIQUE on stel_nummer: same VIN can appear in multiple Statistik rows (snapshots); non-unique index only.
--   - dmr_equipment_types.type_nummer is nullable so odd XML rows without a type number do not block loads.
--   - Equipment bridge uses (vehicle_id, line_order) like drivmiddel so duplicate equipment types on one vehicle are kept.
--   - Applying this to an existing populated database requires ALTER TABLE (and index builds can be slow at scale).
--   - Optional UNIQUE(registrering_nummer): only if exactly one fact row per plate is guaranteed; otherwise
--     duplicate plates across snapshots will violate the constraint. See optional DDL after dmr_fact_vehicles indexes.
--   - oevrigt_udstyr: kept on dmr_fact_vehicles here. To normalize (1:1 child table + FULLTEXT), add a table and
--     update ETL/loaders; extend dmr_v_fact_vehicles_with_hierarchy with LEFT JOIN if the column moves off fact.
--   - teknisk_total_vaegt is BIGINT: source values can exceed signed INT range (e.g. unusual units or bad data).
--   - type_nummer on drive_energy / measurement_norm / equipment_type is BIGINT: DMR codes can exceed int32.

-- ---------------------------------------------------------------------------
-- Optional: load batch / source file (for traceability) — no dependencies
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_etl_loads (
    id          BIGINT NOT NULL AUTO_INCREMENT,
    loaded_at   TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    source_path TEXT,
    note        TEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Vehicle uses / purpose (KoeretoejAnvendelseNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_vehicle_uses (
    id   BIGINT NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_vehicle_uses_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Registration statuses (KoeretoejRegistreringStatus)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_registration_statuses (
    id   BIGINT NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_registration_statuses_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Brands / makes (KoeretoejMaerkeTypeNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_brands (
    id   BIGINT NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_brands_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Models (KoeretoejModelTypeNavn), child of brand
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_models (
    id       BIGINT NOT NULL AUTO_INCREMENT,
    brand_id BIGINT NOT NULL,
    name     VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_models_brand_name UNIQUE (brand_id, name),
    CONSTRAINT fk_dmr_models_brand FOREIGN KEY (brand_id) REFERENCES dmr_brands (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_models_brand_id ON dmr_models (brand_id);

-- ---------------------------------------------------------------------------
-- Variants (KoeretoejVariantTypeNavn), child of model
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_variants (
    id       BIGINT NOT NULL AUTO_INCREMENT,
    model_id BIGINT NOT NULL,
    name     VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_variants_model_name UNIQUE (model_id, name),
    CONSTRAINT fk_dmr_variants_model FOREIGN KEY (model_id) REFERENCES dmr_models (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_variants_model_id ON dmr_variants (model_id);

-- ---------------------------------------------------------------------------
-- Body types (KarrosseriTypeNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_body_types (
    id   BIGINT NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_body_types_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Colours (FarveTypeNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_colours (
    id   BIGINT NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_colours_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Emission / type-approval norm labels (NormTypeNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_emission_norms (
    id   BIGINT NOT NULL AUTO_INCREMENT,
    name VARCHAR(512) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_emission_norms_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Drive-energy / fuel types (DrivkraftTypeNummer + DrivkraftTypeNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_drive_energies (
    id          BIGINT NOT NULL AUTO_INCREMENT,
    type_nummer BIGINT,
    name        VARCHAR(512),
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_drive_energies_nummer UNIQUE (type_nummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_drive_energies_name ON dmr_drive_energies (name);

-- ---------------------------------------------------------------------------
-- Consumption measurement norms (KoeretoejMotorMaaleNormTypeNummer + Navn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_measurement_norms (
    id          BIGINT NOT NULL AUTO_INCREMENT,
    type_nummer BIGINT,
    name        VARCHAR(512),
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_measurement_norms_nummer UNIQUE (type_nummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Equipment types (KoeretoejUdstyrTypeNummer + KoeretoejUdstyrTypeNavn)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_equipment_types (
    id          BIGINT NOT NULL AUTO_INCREMENT,
    type_nummer BIGINT NULL,
    name        VARCHAR(512),
    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_equipment_types_nummer UNIQUE (type_nummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_equipment_types_name ON dmr_equipment_types (name);

-- ---------------------------------------------------------------------------
-- Fact: one row per vehicle record (one Statistik element / snapshot row)
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_fact_vehicles (
    id BIGINT NOT NULL AUTO_INCREMENT,

    stel_nummer              VARCHAR(64),
    registrering_nummer      VARCHAR(32),

    variant_id               BIGINT,
    vehicle_use_id           BIGINT,
    body_type_id             BIGINT,
    colour_id                BIGINT,
    emission_norm_id         BIGINT,
    registration_status_id   BIGINT,

    foerste_registrering_dato   DATETIME(6),
    registrering_status_dato    DATETIME(6),

    emission_co                 DECIMAL(15,6),
    emission_nox                DECIMAL(15,6),
    partikel_filter             TINYINT(1),

    motor_stoerste_effekt       DECIMAL(15,6),
    motor_slag_volumen          DECIMAL(15,6),

    aksel_antal                 INT,
    antal_doere                 INT,
    antal_gear                  INT,
    maksimum_hastighed          INT,
    model_aar                   INT,
    ncap_test                   TINYINT(1),
    siddepladser_maksimum       INT,
    siddepladser_minimum        INT,
    teknisk_total_vaegt         BIGINT,

    oevrigt_udstyr              TEXT,

    created_at                  TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    etl_load_id                 BIGINT,

    PRIMARY KEY (id),

    CONSTRAINT fk_dmr_fact_vehicles_variant FOREIGN KEY (variant_id) REFERENCES dmr_variants (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_fact_vehicles_vehicle_use FOREIGN KEY (vehicle_use_id) REFERENCES dmr_vehicle_uses (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_fact_vehicles_body_type FOREIGN KEY (body_type_id) REFERENCES dmr_body_types (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_fact_vehicles_colour FOREIGN KEY (colour_id) REFERENCES dmr_colours (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_fact_vehicles_emission_norm FOREIGN KEY (emission_norm_id) REFERENCES dmr_emission_norms (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_fact_vehicles_registration_status FOREIGN KEY (registration_status_id) REFERENCES dmr_registration_statuses (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_fact_vehicles_etl_load FOREIGN KEY (etl_load_id) REFERENCES dmr_etl_loads (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_fact_vehicles_stel ON dmr_fact_vehicles (stel_nummer);
CREATE INDEX idx_dmr_fact_vehicles_registrering ON dmr_fact_vehicles (registrering_nummer);
CREATE INDEX idx_dmr_fact_vehicles_model_aar ON dmr_fact_vehicles (model_aar);
CREATE INDEX idx_dmr_fact_vehicles_body_type_id ON dmr_fact_vehicles (body_type_id);
CREATE INDEX idx_dmr_fact_vehicles_colour_id ON dmr_fact_vehicles (colour_id);
CREATE INDEX idx_dmr_fact_vehicles_registration_status_id ON dmr_fact_vehicles (registration_status_id);
CREATE INDEX idx_dmr_fact_vehicles_variant ON dmr_fact_vehicles (variant_id);
CREATE INDEX idx_dmr_fact_vehicles_etl_load ON dmr_fact_vehicles (etl_load_id);

-- Existing DB migrated from INT: ALTER TABLE dmr_fact_vehicles MODIFY teknisk_total_vaegt BIGINT NULL;
-- Parquet int64 type codes: ALTER TABLE dmr_drive_energies MODIFY type_nummer BIGINT NULL;
--   ALTER TABLE dmr_measurement_norms MODIFY type_nummer BIGINT NULL;
--   ALTER TABLE dmr_equipment_types MODIFY type_nummer BIGINT NULL;

-- Optional: one row per registration number (drops redundant non-unique index on same column):
-- ALTER TABLE dmr_fact_vehicles
--   ADD CONSTRAINT uq_dmr_fact_vehicles_registrering_nummer UNIQUE (registrering_nummer);
-- DROP INDEX idx_dmr_fact_vehicles_registrering ON dmr_fact_vehicles;

-- Optional: normalize oevrigt_udstyr to a 1:1 child (requires ETL to populate; remove column from dmr_fact_vehicles if used):
-- CREATE TABLE dmr_fact_vehicle_oevrigt_udstyr (
--     vehicle_id BIGINT NOT NULL PRIMARY KEY,
--     oevrigt_udstyr TEXT,
--     CONSTRAINT fk_dmr_oevrigt_vehicle FOREIGN KEY (vehicle_id) REFERENCES dmr_fact_vehicles (id) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Bridge: equipment lines per vehicle
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_bridge_vehicle_equipment (
    id                BIGINT NOT NULL AUTO_INCREMENT,
    vehicle_id        BIGINT NOT NULL,
    line_order        INT NOT NULL DEFAULT 1,
    equipment_type_id BIGINT NOT NULL,
    antal             INT,
    vises_ved_syn                     TINYINT(1),
    vises_ved_forespoergsel           TINYINT(1),
    vises_ved_standard_oprettelse     TINYINT(1),

    created_at        TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_bridge_vehicle_equipment_line UNIQUE (vehicle_id, line_order),
    CONSTRAINT fk_dmr_bridge_equipment_vehicle FOREIGN KEY (vehicle_id) REFERENCES dmr_fact_vehicles (id) ON DELETE CASCADE,
    CONSTRAINT fk_dmr_bridge_equipment_type FOREIGN KEY (equipment_type_id) REFERENCES dmr_equipment_types (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_bridge_vehicle_equipment_vehicle ON dmr_bridge_vehicle_equipment (vehicle_id);

-- ---------------------------------------------------------------------------
-- Bridge: drivmiddel / propellant lines per vehicle
-- ---------------------------------------------------------------------------
CREATE TABLE dmr_bridge_vehicle_drivmiddel (
    id                   BIGINT NOT NULL AUTO_INCREMENT,
    vehicle_id           BIGINT NOT NULL,
    line_order           INT NOT NULL DEFAULT 1,

    drive_energy_id      BIGINT,
    measurement_norm_id  BIGINT,

    drivmiddel_primaer   TINYINT(1),

    motor_km_per_liter     DECIMAL(15,6),
    miljoe_co2_udslip      DECIMAL(15,6),

    motor_elektrisk_forbrug DECIMAL(15,6),
    motor_braendselscelle   TINYINT(1),

    created_at           TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    PRIMARY KEY (id),
    CONSTRAINT uq_dmr_bridge_vehicle_drivmiddel_line UNIQUE (vehicle_id, line_order),
    CONSTRAINT fk_dmr_bridge_drivmiddel_vehicle FOREIGN KEY (vehicle_id) REFERENCES dmr_fact_vehicles (id) ON DELETE CASCADE,
    CONSTRAINT fk_dmr_bridge_drivmiddel_drive_energy FOREIGN KEY (drive_energy_id) REFERENCES dmr_drive_energies (id) ON DELETE RESTRICT,
    CONSTRAINT fk_dmr_bridge_drivmiddel_measurement_norm FOREIGN KEY (measurement_norm_id) REFERENCES dmr_measurement_norms (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_dmr_bridge_vehicle_drivmiddel_vehicle ON dmr_bridge_vehicle_drivmiddel (vehicle_id);

-- ---------------------------------------------------------------------------
-- Convenience view: fact row with brand / model / variant names
-- If oevrigt_udstyr is moved to dmr_fact_vehicle_oevrigt_udstyr, add LEFT JOIN here and select that column.
-- ---------------------------------------------------------------------------
CREATE VIEW dmr_v_fact_vehicles_with_hierarchy AS
SELECT
    fv.*,
    b.name AS brand_name,
    m.name AS model_name,
    v.name AS variant_name
FROM dmr_fact_vehicles fv
LEFT JOIN dmr_variants v ON fv.variant_id = v.id
LEFT JOIN dmr_models m ON v.model_id = m.id
LEFT JOIN dmr_brands b ON m.brand_id = b.id;
