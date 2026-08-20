CREATE TABLE groups (
    id UUID PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE companies (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    legal_name VARCHAR(180) NOT NULL,
    trade_name VARCHAR(180),
    tax_id VARCHAR(32),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX companies_group_id_idx ON companies(group_id);

CREATE TABLE branches (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    name VARCHAR(160) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX branches_scope_idx ON branches(group_id, company_id);

CREATE TABLE users (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    name VARCHAR(160) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX users_scope_idx ON users(group_id, company_id, branch_id);

CREATE TABLE roles (
    id UUID PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE permissions (
    id UUID PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE role_user (
    user_id UUID NOT NULL REFERENCES users(id),
    role_id UUID NOT NULL REFERENCES roles(id),
    PRIMARY KEY (user_id, role_id)
);

CREATE TABLE permission_role (
    permission_id UUID NOT NULL REFERENCES permissions(id),
    role_id UUID NOT NULL REFERENCES roles(id),
    PRIMARY KEY (permission_id, role_id)
);

CREATE TABLE vehicles (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    plate VARCHAR(16) NOT NULL,
    fleet_number VARCHAR(40),
    manufacturer VARCHAR(120),
    model VARCHAR(120),
    model_year SMALLINT,
    current_odometer_km INTEGER NOT NULL DEFAULT 0,
    current_hourmeter INTEGER,
    status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (company_id, plate)
);

CREATE INDEX vehicles_scope_status_idx ON vehicles(group_id, company_id, branch_id, status);
CREATE INDEX vehicles_plate_idx ON vehicles(plate);

CREATE TABLE drivers (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    name VARCHAR(160) NOT NULL,
    document_number VARCHAR(32),
    license_number VARCHAR(60),
    license_category VARCHAR(12),
    license_expires_at DATE,
    status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX drivers_scope_status_idx ON drivers(group_id, company_id, branch_id, status);
CREATE INDEX drivers_license_expires_idx ON drivers(license_expires_at);

CREATE TABLE vehicle_documents (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    vehicle_id UUID NOT NULL REFERENCES vehicles(id),
    document_type VARCHAR(80) NOT NULL,
    issued_at DATE,
    expires_at DATE,
    status VARCHAR(32) NOT NULL DEFAULT 'VALID',
    responsible_user_id UUID REFERENCES users(id),
    file_path TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX vehicle_documents_scope_expires_idx ON vehicle_documents(group_id, company_id, branch_id, expires_at);

CREATE TABLE driver_documents (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    driver_id UUID NOT NULL REFERENCES drivers(id),
    document_type VARCHAR(80) NOT NULL,
    issued_at DATE,
    expires_at DATE,
    status VARCHAR(32) NOT NULL DEFAULT 'VALID',
    responsible_user_id UUID REFERENCES users(id),
    file_path TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX driver_documents_scope_expires_idx ON driver_documents(group_id, company_id, branch_id, expires_at);
