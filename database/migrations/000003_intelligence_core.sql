CREATE TABLE alerts (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    type VARCHAR(80) NOT NULL,
    category VARCHAR(80) NOT NULL,
    entity VARCHAR(120) NOT NULL,
    entity_id UUID NOT NULL,
    severity VARCHAR(20) NOT NULL,
    alert_date TIMESTAMPTZ NOT NULL,
    predicted_date TIMESTAMPTZ,
    deadline TIMESTAMPTZ,
    origin VARCHAR(120) NOT NULL,
    rule VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'ABERTO',
    assignee_id UUID REFERENCES users(id),
    evidence JSONB NOT NULL DEFAULT '{}'::JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX alerts_scope_status_idx ON alerts(group_id, company_id, branch_id, status);
CREATE INDEX alerts_entity_idx ON alerts(entity, entity_id);
CREATE INDEX alerts_severity_deadline_idx ON alerts(severity, deadline);

CREATE TABLE insights (
    id UUID PRIMARY KEY,
    group_id UUID NOT NULL REFERENCES groups(id),
    company_id UUID NOT NULL REFERENCES companies(id),
    branch_id UUID REFERENCES branches(id),
    type VARCHAR(60) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    evidence JSONB NOT NULL DEFAULT '{}'::JSONB,
    impact VARCHAR(80),
    probability NUMERIC(5,2),
    confidence NUMERIC(5,2),
    priority INTEGER NOT NULL DEFAULT 0,
    recommendation TEXT,
    entity VARCHAR(120) NOT NULL,
    entity_id UUID NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'OPEN',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX insights_scope_status_idx ON insights(group_id, company_id, branch_id, status);
CREATE INDEX insights_entity_idx ON insights(entity, entity_id);
