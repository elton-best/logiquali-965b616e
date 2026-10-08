-- =====================================================
-- SCHÉMA DE BASE DE DONNÉES COMPLET - SYSTÈME DE MANAGEMENT INTÉGRÉ (SMI)
-- BestQHSE - Architecture Scalable & Moderne
-- =====================================================

-- =====================================================
-- 1. GESTION DES ENTREPRISES & UTILISATEURS
-- =====================================================

-- Entreprises (multi-tenant)
CREATE TABLE enterprises (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'France',
    siret VARCHAR(50),
    tva_number VARCHAR(50),
    logo_url VARCHAR(500),
    domaine_activite VARCHAR(100),
    field TEXT,
    is_active BOOLEAN DEFAULT true,
    trial_ends_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Sites (multi-sites par entreprise)
CREATE TABLE sites (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    enterprise_id BIGINT NOT NULL REFERENCES enterprises(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(255),
    manager_name VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Utilisateurs
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    enterprise_id BIGINT REFERENCES enterprises(id) ON DELETE CASCADE,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(50),
    position VARCHAR(100),
    avatar_url VARCHAR(500),
    language VARCHAR(10) DEFAULT 'fr',
    timezone VARCHAR(50) DEFAULT 'Europe/Paris',
    is_active BOOLEAN DEFAULT true,
    email_verified_at TIMESTAMP,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Rôles & Permissions (Spatie)
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(name, guard_name)
);

CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(name, guard_name)
);

CREATE TABLE model_has_roles (
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    PRIMARY KEY(role_id, model_id, model_type)
);

CREATE TABLE model_has_permissions (
    permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    PRIMARY KEY(permission_id, model_id, model_type)
);

CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY(permission_id, role_id)
);

-- =====================================================
-- 2. GESTION DES ABONNEMENTS & OFFRES
-- =====================================================

-- Offres (plans d'abonnement)
CREATE TABLE offers (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    billing_period VARCHAR(50) DEFAULT 'monthly', -- monthly, quarterly, yearly
    max_users INTEGER,
    max_sites INTEGER,
    features JSONB, -- Liste des fonctionnalités incluses
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Abonnements entreprise
CREATE TABLE enterprise_subscriptions (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    offer_id BIGINT NOT NULL REFERENCES offers(id),
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    start_date TIMESTAMP NOT NULL,
    expiration_date TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_trial BOOLEAN DEFAULT false,
    payment_method VARCHAR(50),
    amount_paid DECIMAL(10,2),
    auto_renew BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 3. SYSTÈME DE NOTIFICATIONS & ALERTES
-- =====================================================

-- Notifications utilisateur
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    type VARCHAR(255) NOT NULL, -- App\Notifications\...
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT NOT NULL,
    data JSONB NOT NULL,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_notifiable ON notifications(notifiable_type, notifiable_id);
CREATE INDEX idx_notifications_read ON notifications(read_at);

-- Préférences de notification par utilisateur
CREATE TABLE notification_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    channel VARCHAR(50) NOT NULL, -- email, sms, push, in_app
    event_type VARCHAR(100) NOT NULL, -- document_created, nc_assigned, audit_reminder, etc.
    is_enabled BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, channel, event_type)
);

-- Alertes système (pour les échéances, expirations, etc.)
CREATE TABLE system_alerts (
    id BIGSERIAL PRIMARY KEY,
    enterprise_id BIGINT NOT NULL REFERENCES enterprises(id) ON DELETE CASCADE,
    site_id BIGINT REFERENCES sites(id) ON DELETE CASCADE,
    alert_type VARCHAR(100) NOT NULL, -- subscription_expiring, document_expiring, audit_due, etc.
    severity VARCHAR(20) DEFAULT 'info', -- info, warning, error, critical
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_entity_type VARCHAR(255), -- Document, Audit, NonConformity, etc.
    related_entity_id BIGINT,
    is_resolved BOOLEAN DEFAULT false,
    resolved_at TIMESTAMP,
    resolved_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_alerts_enterprise ON system_alerts(enterprise_id, is_resolved);
CREATE INDEX idx_alerts_severity ON system_alerts(severity, is_resolved);

-- =====================================================
-- 4. CONTEXTE DE L'ORGANISME (ISO §4)
-- =====================================================

-- Analyse SWOT
CREATE TABLE swot_analyses (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    analysis_date DATE NOT NULL,
    strengths JSONB, -- [{description, impact, actions}]
    weaknesses JSONB,
    opportunities JSONB,
    threats JSONB,
    conclusions TEXT,
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Analyse PESTEL
CREATE TABLE pestel_analyses (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    analysis_date DATE NOT NULL,
    political JSONB,
    economic JSONB,
    social JSONB,
    technological JSONB,
    environmental JSONB,
    legal JSONB,
    conclusions TEXT,
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Parties intéressées
CREATE TABLE stakeholders (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100), -- client, fournisseur, actionnaire, employé, etc.
    needs TEXT,
    expectations TEXT,
    requirements TEXT,
    influence_level VARCHAR(20), -- low, medium, high
    satisfaction_level VARCHAR(20),
    contact_person VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Domaine d'application
CREATE TABLE application_scopes (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    scope_description TEXT NOT NULL,
    included_activities JSONB,
    excluded_activities JSONB,
    justification_exclusions TEXT,
    applicable_standards JSONB, -- [ISO 9001, ISO 14001, ISO 45001, etc.]
    version VARCHAR(20),
    effective_date DATE,
    review_date DATE,
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 5. PROCESSUS (ISO §4.4)
-- =====================================================

-- Processus
CREATE TABLE processes (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50), -- management, realization, support
    description TEXT,
    owner_id BIGINT REFERENCES users(id),
    inputs JSONB,
    outputs JSONB,
    resources JSONB,
    risks JSONB,
    indicators JSONB,
    interactions JSONB, -- Liens avec autres processus
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Interactions entre processus
CREATE TABLE process_interactions (
    id BIGSERIAL PRIMARY KEY,
    source_process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    target_process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    interaction_type VARCHAR(50), -- input, output, resource, information
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 6. RISQUES & OPPORTUNITÉS (ISO §6.1)
-- =====================================================

-- Risques et opportunités
CREATE TABLE risks_opportunities (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id),
    type VARCHAR(20) NOT NULL, -- risk, opportunity
    category VARCHAR(100), -- strategic, operational, financial, compliance, etc.
    description TEXT NOT NULL,
    causes TEXT,
    consequences TEXT,
    probability INTEGER CHECK (probability BETWEEN 1 AND 5),
    severity INTEGER CHECK (severity BETWEEN 1 AND 5),
    initial_criticality INTEGER GENERATED ALWAYS AS (probability * severity) STORED,
    treatment_strategy VARCHAR(50), -- avoid, reduce, transfer, accept, exploit
    treatment_actions TEXT,
    responsible_id BIGINT REFERENCES users(id),
    deadline DATE,
    residual_probability INTEGER CHECK (residual_probability BETWEEN 1 AND 5),
    residual_severity INTEGER CHECK (residual_severity BETWEEN 1 AND 5),
    residual_criticality INTEGER GENERATED ALWAYS AS (residual_probability * residual_severity) STORED,
    status VARCHAR(50) DEFAULT 'identified', -- identified, in_treatment, treated, closed
    review_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 7. OBJECTIFS & INDICATEURS (ISO §6.2)
-- =====================================================

-- Objectifs système
CREATE TABLE system_objectives (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id),
    strategic_axis VARCHAR(100), -- Axe 1, Axe 2, Axe 3
    title VARCHAR(255) NOT NULL,
    description TEXT,
    indicator_name VARCHAR(255),
    calculation_method TEXT,
    frequency VARCHAR(50), -- monthly, quarterly, semi-annual, annual
    target_value DECIMAL(10,2),
    resources TEXT,
    deadline DATE,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Réalisations des objectifs (par période)
CREATE TABLE objective_realizations (
    id BIGSERIAL PRIMARY KEY,
    objective_id BIGINT NOT NULL REFERENCES system_objectives(id) ON DELETE CASCADE,
    period VARCHAR(20) NOT NULL, -- M1, M2, T1, T2, S1, S2, A1
    period_date DATE NOT NULL,
    realized_value DECIMAL(10,2),
    achievement_rate DECIMAL(5,2), -- Pourcentage
    observation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(objective_id, period)
);

-- Actions liées aux objectifs
CREATE TABLE objective_actions (
    id BIGSERIAL PRIMARY KEY,
    objective_id BIGINT NOT NULL REFERENCES system_objectives(id) ON DELETE CASCADE,
    description TEXT NOT NULL,
    responsible_id BIGINT REFERENCES users(id),
    involved_users JSONB, -- [{user_id, role}]
    deadline DATE,
    status VARCHAR(50) DEFAULT 'todo', -- todo, in_progress, done, late
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 8. DOCUMENTS & GED
-- =====================================================

-- Documents
CREATE TABLE documents (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id),
    title VARCHAR(255) NOT NULL,
    type VARCHAR(100), -- procedure, instruction, form, record, etc.
    category VARCHAR(100),
    description TEXT,
    version VARCHAR(20),
    file_path VARCHAR(500),
    file_size BIGINT,
    mime_type VARCHAR(100),
    status VARCHAR(50) DEFAULT 'draft', -- draft, review, approved, obsolete
    effective_date DATE,
    review_date DATE,
    expiration_date DATE,
    created_by BIGINT REFERENCES users(id),
    reviewed_by BIGINT REFERENCES users(id),
    approved_by BIGINT REFERENCES users(id),
    approved_at TIMESTAMP,
    is_confidential BOOLEAN DEFAULT false,
    access_level VARCHAR(50) DEFAULT 'public', -- public, restricted, confidential
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Versions de documents
CREATE TABLE document_versions (
    id BIGSERIAL PRIMARY KEY,
    document_id BIGINT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    version VARCHAR(20) NOT NULL,
    file_path VARCHAR(500),
    changes_description TEXT,
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 9. AUDITS (ISO §9.2)
-- =====================================================

-- Audits
CREATE TABLE audits (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL, -- internal, external, certification
    scope TEXT NOT NULL,
    standard VARCHAR(100), -- ISO 9001, ISO 14001, etc.
    planned_date DATE NOT NULL,
    actual_date DATE,
    duration_hours DECIMAL(5,2),
    lead_auditor_id BIGINT REFERENCES users(id),
    audit_team JSONB, -- [{user_id, role}]
    auditees JSONB,
    status VARCHAR(50) DEFAULT 'planned', -- planned, in_progress, completed, cancelled
    conclusion TEXT,
    report_file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Constats d'audit
CREATE TABLE audit_findings (
    id BIGSERIAL PRIMARY KEY,
    audit_id BIGINT NOT NULL REFERENCES audits(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id),
    type VARCHAR(50) NOT NULL, -- conformity, minor_nc, major_nc, opportunity
    clause_reference VARCHAR(100),
    description TEXT NOT NULL,
    evidence TEXT,
    recommendation TEXT,
    responsible_id BIGINT REFERENCES users(id),
    deadline DATE,
    status VARCHAR(50) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 10. NON-CONFORMITÉS & ACTIONS CORRECTIVES (ISO §10.2)
-- =====================================================

-- Non-conformités
CREATE TABLE non_conformities (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id),
    audit_id BIGINT REFERENCES audits(id),
    type VARCHAR(50), -- product, process, system
    severity VARCHAR(20), -- minor, major, critical
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    detected_date DATE NOT NULL,
    detected_by BIGINT REFERENCES users(id),
    responsible_id BIGINT REFERENCES users(id),
    root_cause TEXT,
    immediate_action TEXT,
    corrective_action TEXT,
    preventive_action TEXT,
    deadline DATE,
    status VARCHAR(50) DEFAULT 'open', -- open, in_analysis, in_treatment, closed
    closure_date DATE,
    effectiveness_verified BOOLEAN DEFAULT false,
    verification_date DATE,
    verified_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Actions (génériques - liées à NC, audits, risques, etc.)
CREATE TABLE actions (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    source_type VARCHAR(100), -- NonConformity, Audit, Risk, Objective, etc.
    source_id BIGINT,
    type VARCHAR(50), -- corrective, preventive, improvement
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    responsible_id BIGINT REFERENCES users(id),
    involved_users JSONB,
    deadline DATE,
    priority VARCHAR(20) DEFAULT 'medium', -- low, medium, high, urgent
    status VARCHAR(50) DEFAULT 'todo', -- todo, in_progress, done, cancelled, late
    completion_date DATE,
    effectiveness_evaluation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 11. RÉCLAMATIONS CLIENTS
-- =====================================================

-- Réclamations
CREATE TABLE complaints (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    complaint_date DATE NOT NULL,
    channel VARCHAR(50), -- email, phone, letter, website
    product_service VARCHAR(255),
    description TEXT NOT NULL,
    severity VARCHAR(20), -- low, medium, high
    assigned_to BIGINT REFERENCES users(id),
    root_cause TEXT,
    immediate_response TEXT,
    corrective_action TEXT,
    customer_feedback TEXT,
    status VARCHAR(50) DEFAULT 'received', -- received, in_analysis, in_treatment, resolved, closed
    resolution_date DATE,
    satisfaction_level INTEGER CHECK (satisfaction_level BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 12. FORMATION & COMPÉTENCES
-- =====================================================

-- Plan de formation
CREATE TABLE training_plans (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    year INTEGER NOT NULL,
    budget DECIMAL(10,2),
    status VARCHAR(50) DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Formations
CREATE TABLE trainings (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    training_plan_id BIGINT REFERENCES training_plans(id),
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(100), -- internal, external, elearning
    provider VARCHAR(255),
    duration_hours DECIMAL(5,2),
    cost DECIMAL(10,2),
    planned_date DATE,
    actual_date DATE,
    trainer VARCHAR(255),
    location VARCHAR(255),
    objectives TEXT,
    content TEXT,
    status VARCHAR(50) DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Participants aux formations
CREATE TABLE training_participants (
    id BIGSERIAL PRIMARY KEY,
    training_id BIGINT NOT NULL REFERENCES trainings(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    attendance_status VARCHAR(50) DEFAULT 'registered', -- registered, attended, absent, cancelled
    evaluation_score DECIMAL(5,2),
    feedback TEXT,
    certificate_issued BOOLEAN DEFAULT false,
    certificate_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(training_id, user_id)
);

-- =====================================================
-- 13. ÉQUIPEMENTS & MAINTENANCE
-- =====================================================

-- Équipements
CREATE TABLE equipment (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    purchase_date DATE,
    warranty_expiration DATE,
    location VARCHAR(255),
    responsible_id BIGINT REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'operational', -- operational, maintenance, out_of_service
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    maintenance_frequency_days INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Maintenances
CREATE TABLE maintenances (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    equipment_id BIGINT NOT NULL REFERENCES equipment(id) ON DELETE CASCADE,
    type VARCHAR(50), -- preventive, corrective, calibration
    planned_date DATE,
    actual_date DATE,
    duration_hours DECIMAL(5,2),
    technician VARCHAR(255),
    description TEXT,
    observations TEXT,
    cost DECIMAL(10,2),
    status VARCHAR(50) DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 14. ACTIVITÉ & LOGS
-- =====================================================

-- Journal d'activité (Spatie Activity Log)
CREATE TABLE activity_log (
    id BIGSERIAL PRIMARY KEY,
    log_name VARCHAR(255),
    description TEXT NOT NULL,
    subject_type VARCHAR(255),
    subject_id BIGINT,
    causer_type VARCHAR(255),
    causer_id BIGINT,
    properties JSONB,
    event VARCHAR(255),
    batch_uuid UUID,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_activity_subject ON activity_log(subject_type, subject_id);
CREATE INDEX idx_activity_causer ON activity_log(causer_type, causer_id);
CREATE INDEX idx_activity_log_name ON activity_log(log_name);

-- =====================================================
-- 15. WEBHOOKS & INTÉGRATIONS
-- =====================================================

-- Webhooks pour intégrations externes
CREATE TABLE webhooks (
    id BIGSERIAL PRIMARY KEY,
    enterprise_id BIGINT NOT NULL REFERENCES enterprises(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    events JSONB NOT NULL, -- Liste des événements à écouter
    secret VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    last_triggered_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Logs des webhooks
CREATE TABLE webhook_logs (
    id BIGSERIAL PRIMARY KEY,
    webhook_id BIGINT NOT NULL REFERENCES webhooks(id) ON DELETE CASCADE,
    event_type VARCHAR(100) NOT NULL,
    payload JSONB NOT NULL,
    response_status INTEGER,
    response_body TEXT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 16. INDICES POUR PERFORMANCE
-- =====================================================

-- Indices généraux
CREATE INDEX idx_sites_enterprise ON sites(enterprise_id);
CREATE INDEX idx_users_enterprise ON users(enterprise_id);
CREATE INDEX idx_subscriptions_site ON enterprise_subscriptions(site_id);
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);
CREATE INDEX idx_documents_site ON documents(site_id);
CREATE INDEX idx_documents_status ON documents(status);
CREATE INDEX idx_audits_site ON audits(site_id);
CREATE INDEX idx_nc_site ON non_conformities(site_id);
CREATE INDEX idx_nc_status ON non_conformities(status);
CREATE INDEX idx_actions_responsible ON actions(responsible_id);
CREATE INDEX idx_actions_status ON actions(status);
CREATE INDEX idx_actions_deadline ON actions(deadline);

-- =====================================================
-- 17. TRIGGERS POUR NOTIFICATIONS AUTOMATIQUES
-- =====================================================

-- Fonction pour créer une alerte système
CREATE OR REPLACE FUNCTION create_system_alert()
RETURNS TRIGGER AS $$
BEGIN
    -- Exemple: Alerte quand un document expire dans 30 jours
    IF TG_TABLE_NAME = 'documents' AND NEW.expiration_date IS NOT NULL THEN
        IF NEW.expiration_date - CURRENT_DATE <= 30 AND NEW.expiration_date > CURRENT_DATE THEN
            INSERT INTO system_alerts (
                enterprise_id, site_id, alert_type, severity, title, message,
                related_entity_type, related_entity_id
            )
            SELECT 
                s.enterprise_id, NEW.site_id, 'document_expiring', 'warning',
                'Document expire bientôt',
                'Le document ' || NEW.title || ' expire le ' || NEW.expiration_date,
                'Document', NEW.id
            FROM sites s WHERE s.id = NEW.site_id;
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- FIN DU SCHÉMA
-- =====================================================
