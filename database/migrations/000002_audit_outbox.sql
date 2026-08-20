CREATE TABLE audit_logs (
    id UUID PRIMARY KEY,
    group_id UUID,
    company_id UUID,
    branch_id UUID,
    actor_id UUID,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ip_address INET,
    entity VARCHAR(120) NOT NULL,
    entity_id UUID,
    action VARCHAR(80) NOT NULL,
    before_value JSONB,
    after_value JSONB,
    origin VARCHAR(120) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX audit_logs_entity_idx ON audit_logs(entity, entity_id);
CREATE INDEX audit_logs_actor_occurred_idx ON audit_logs(actor_id, occurred_at);
CREATE INDEX audit_logs_scope_occurred_idx ON audit_logs(group_id, company_id, branch_id, occurred_at);

CREATE TABLE outbox (
    id UUID PRIMARY KEY,
    event_name VARCHAR(160) NOT NULL,
    aggregate_type VARCHAR(120) NOT NULL,
    aggregate_id UUID NOT NULL,
    payload JSONB NOT NULL,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    attempts INTEGER NOT NULL DEFAULT 0,
    available_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    published_at TIMESTAMPTZ,
    last_error TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX outbox_status_available_idx ON outbox(status, available_at);
CREATE INDEX outbox_event_name_idx ON outbox(event_name);
