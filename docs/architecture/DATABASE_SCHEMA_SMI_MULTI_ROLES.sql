-- =====================================================
-- SCHÉMA DE BASE DE DONNÉES COMPLET - SYSTÈME DE MANAGEMENT INTÉGRÉ (SMI)
-- BestQHSE - Architecture Multi-Sites & Multi-Rôles
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
    country VARCHAR(100) DEFAULT 'Benin',
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
    manager_id BIGINT, -- Référence vers users (ajouté après création de users)
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Utilisateurs (MODIFIÉ: ajout site_id pour association)
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    enterprise_id BIGINT REFERENCES enterprises(id) ON DELETE CASCADE,
    site_id BIGINT REFERENCES sites(id) ON DELETE SET NULL, -- NOUVEAU: Association au site
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

-- Ajouter la contrainte FK pour manager_id après création de users
ALTER TABLE sites ADD CONSTRAINT fk_sites_manager 
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL;

-- Table pivot: Utilisateurs multi-sites (si un utilisateur peut accéder à plusieurs sites)
CREATE TABLE user_site_access (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    is_primary BOOLEAN DEFAULT false, -- Site principal de l'utilisateur
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, site_id)
);

-- Rôles & Permissions (Spatie)
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(name, guard_name)
);

CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    description TEXT,
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
-- 2. GESTION DES NORMES (ISO 9001, 14001, etc.)
-- =====================================================

CREATE TABLE norms (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL, -- ISO 9001, ISO 14001
    name VARCHAR(255) NOT NULL,
    description TEXT,
    domain VARCHAR(50) DEFAULT 'quality', -- quality, environment, security, food_safety, integrated, other
    current_version_id BIGINT,
    status VARCHAR(20) DEFAULT 'draft', -- draft, published, archived
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    deleted_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 3. GESTION DES ABONNEMENTS & OFFRES
-- =====================================================

CREATE TABLE offers (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_months INTEGER NOT NULL, -- Durée en mois
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    deleted_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Table pivot: Offres et Normes (une offre peut inclure plusieurs normes)
CREATE TABLE norm_offer (
    id BIGSERIAL PRIMARY KEY,
    norm_id BIGINT NOT NULL REFERENCES norms(id) ON DELETE CASCADE,
    offer_id BIGINT NOT NULL REFERENCES offers(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(norm_id, offer_id)
);

CREATE TABLE enterprise_subscriptions (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    offer_id BIGINT NOT NULL REFERENCES offers(id) ON DELETE CASCADE,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    start_date TIMESTAMP NOT NULL,
    expiration_date TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_trial BOOLEAN DEFAULT false,
    trial_ends_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'trial', -- trial, active, expired, cancelled
    payment_status VARCHAR(20), -- pending, completed, failed
    subscription_type VARCHAR(20) DEFAULT 'primary', -- primary, addon
    payment_method VARCHAR(50),
    amount_paid DECIMAL(10,2) DEFAULT 0,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    deleted_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Historique des paiements d'abonnements
CREATE TABLE subscription_payments (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    subscription_id BIGINT NOT NULL REFERENCES enterprise_subscriptions(id) ON DELETE CASCADE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    payment_method VARCHAR(50), -- card, bank_transfer, paypal, stripe, etc.
    payment_date TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL, -- pending, completed, failed, refunded
    transaction_id VARCHAR(255), -- ID de transaction externe (Stripe, PayPal, etc.)
    invoice_number VARCHAR(100),
    invoice_url VARCHAR(500),
    notes TEXT,
    processed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_subscription_payments_subscription ON subscription_payments(subscription_id);
CREATE INDEX idx_subscription_payments_status ON subscription_payments(status);
CREATE INDEX idx_subscription_payments_date ON subscription_payments(payment_date);

-- =====================================================
-- 4. SYSTÈME DE NOTIFICATIONS & ALERTES
-- =====================================================

CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT NOT NULL,
    data JSONB NOT NULL,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_notifiable ON notifications(notifiable_type, notifiable_id);
CREATE INDEX idx_notifications_read ON notifications(read_at);

CREATE TABLE notification_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    channel VARCHAR(50) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, channel, event_type)
);

CREATE TABLE system_alerts (
    id BIGSERIAL PRIMARY KEY,
    enterprise_id BIGINT NOT NULL REFERENCES enterprises(id) ON DELETE CASCADE,
    site_id BIGINT REFERENCES sites(id) ON DELETE CASCADE,
    alert_type VARCHAR(100) NOT NULL,
    severity VARCHAR(20) DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_entity_type VARCHAR(255),
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
-- 5. CONTEXTE DE L'ORGANISME (ISO §4)
-- =====================================================

CREATE TABLE swot_analyses (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    analysis_date DATE NOT NULL,
    strengths JSONB,
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

CREATE TABLE stakeholders (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    needs TEXT,
    expectations TEXT,
    requirements TEXT,
    influence_level VARCHAR(20),
    satisfaction_level VARCHAR(20),
    contact_person VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE application_scopes (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    included_activities TEXT,
    excluded_activities TEXT,
    justification_exclusions TEXT,
    applicable_standards JSONB,
    version VARCHAR(20),
    effective_date DATE,
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 5. PROCESSUS (MODIFIÉ: ajout processus fournisseurs/clients)
-- =====================================================

CREATE TABLE processes (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50),
    category VARCHAR(100),
    description TEXT,
    objectives TEXT,
    owner_id BIGINT REFERENCES users(id), -- Pilote du processus
    status VARCHAR(50) DEFAULT 'draft',
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

-- NOUVEAU: Activités du processus avec entrées/sorties
CREATE TABLE process_activities (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sequence_order INTEGER,
    responsible_id BIGINT REFERENCES users(id),
    duration_estimated INTEGER, -- en minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- NOUVEAU: Processus fournisseurs (entrées d'une activité)
CREATE TABLE activity_supplier_processes (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT NOT NULL REFERENCES process_activities(id) ON DELETE CASCADE,
    supplier_process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    input_description TEXT, -- Description de l'entrée fournie
    input_type VARCHAR(100), -- document, matière, information, etc.
    is_critical BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(activity_id, supplier_process_id)
);

-- NOUVEAU: Processus clients (sorties d'une activité)
CREATE TABLE activity_client_processes (
    id BIGSERIAL PRIMARY KEY,
    activity_id BIGINT NOT NULL REFERENCES process_activities(id) ON DELETE CASCADE,
    client_process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    output_description TEXT, -- Description de la sortie fournie
    output_type VARCHAR(100), -- document, produit, service, information, etc.
    is_critical BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(activity_id, client_process_id)
);

-- Ressources du processus
CREATE TABLE process_resources (
    id BIGSERIAL PRIMARY KEY,
    process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    resource_type VARCHAR(50),
    resource_name VARCHAR(255),
    quantity INTEGER,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indicateurs du processus
CREATE TABLE process_indicators (
    id BIGSERIAL PRIMARY KEY,
    process_id BIGINT NOT NULL REFERENCES processes(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit VARCHAR(50),
    target_value DECIMAL(10,2),
    frequency VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 6. RISQUES & OPPORTUNITÉS (ENRICHI)
-- =====================================================

CREATE TABLE risks_opportunities (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    type VARCHAR(50) NOT NULL, -- 'risk' ou 'opportunity'
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    source VARCHAR(100), -- SWOT, PESTEL, audit, processus, etc.
    
    -- Évaluation
    probability INTEGER CHECK (probability BETWEEN 1 AND 5),
    impact INTEGER CHECK (impact BETWEEN 1 AND 5),
    criticality INTEGER GENERATED ALWAYS AS (probability * impact) STORED,
    
    -- Traitement
    treatment_strategy VARCHAR(50),
    treatment_plan TEXT,
    residual_probability INTEGER CHECK (residual_probability BETWEEN 1 AND 5),
    residual_impact INTEGER CHECK (residual_impact BETWEEN 1 AND 5),
    residual_criticality INTEGER GENERATED ALWAYS AS (residual_probability * residual_impact) STORED,
    
    -- Suivi
    status VARCHAR(50) DEFAULT 'identified',
    responsible_id BIGINT REFERENCES users(id),
    review_date DATE,
    review_frequency VARCHAR(50),
    
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_risks_opportunities_site ON risks_opportunities(site_id);
CREATE INDEX idx_risks_opportunities_type ON risks_opportunities(type);
CREATE INDEX idx_risks_opportunities_criticality ON risks_opportunities(criticality DESC);

-- =====================================================
-- 7. OBJECTIFS = INDICATEURS (REFACTORISÉ)
-- =====================================================

CREATE TABLE objectives (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    
    -- Informations générales
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    strategic_axis VARCHAR(100),
    
    -- Indicateur (KPI)
    indicator_name VARCHAR(255) NOT NULL,
    indicator_formula TEXT,
    unit VARCHAR(50),
    measurement_frequency VARCHAR(50),
    data_source VARCHAR(255),
    
    -- Cible & Seuils
    baseline_value DECIMAL(10,2),
    target_value DECIMAL(10,2) NOT NULL,
    threshold_alert DECIMAL(10,2),
    threshold_critical DECIMAL(10,2),
    
    -- Période
    start_date DATE,
    deadline DATE,
    
    -- Responsabilités
    responsible_id BIGINT REFERENCES users(id),
    data_collector_id BIGINT REFERENCES users(id),
    
    -- Statut
    status VARCHAR(50) DEFAULT 'in_progress',
    achievement_rate DECIMAL(5,2),
    
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_objectives_site ON objectives(site_id);
CREATE INDEX idx_objectives_process ON objectives(process_id);
CREATE INDEX idx_objectives_status ON objectives(status);

CREATE TABLE objective_realizations (
    id BIGSERIAL PRIMARY KEY,
    objective_id BIGINT NOT NULL REFERENCES objectives(id) ON DELETE CASCADE,
    period_date DATE NOT NULL,
    actual_value DECIMAL(10,2) NOT NULL,
    target_value DECIMAL(10,2),
    gap DECIMAL(10,2),
    gap_percentage DECIMAL(5,2),
    trend VARCHAR(20),
    comments TEXT,
    corrective_actions TEXT,
    recorded_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(objective_id, period_date)
);

CREATE INDEX idx_objective_realizations_objective ON objective_realizations(objective_id);
CREATE INDEX idx_objective_realizations_period ON objective_realizations(period_date);

-- =====================================================
-- 8. DOCUMENTS
-- =====================================================

CREATE TABLE documents (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    category VARCHAR(100),
    description TEXT,
    version VARCHAR(20),
    status VARCHAR(50) DEFAULT 'draft',
    file_path VARCHAR(500),
    file_size BIGINT,
    effective_date DATE,
    review_date DATE,
    expiration_date DATE,
    created_by BIGINT REFERENCES users(id),
    validated_by BIGINT REFERENCES users(id),
    validated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

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
-- 10. AUDITS
-- =====================================================

CREATE TABLE audits (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(50),
    scope TEXT,
    audit_date DATE,
    status VARCHAR(50) DEFAULT 'planned',
    lead_auditor_id BIGINT REFERENCES users(id),
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE audit_team (
    id BIGSERIAL PRIMARY KEY,
    audit_id BIGINT NOT NULL REFERENCES audits(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(audit_id, user_id)
);

CREATE TABLE audit_findings (
    id BIGSERIAL PRIMARY KEY,
    audit_id BIGINT NOT NULL REFERENCES audits(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    type VARCHAR(50),
    description TEXT,
    severity VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 8. ACTIONS UNIFIÉES (REFACTORISÉ)
-- =====================================================

CREATE TABLE actions (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    
    -- Origine polymorphique
    source_type VARCHAR(50) NOT NULL,
    source_id BIGINT,
    
    -- Relations spécifiques
    objective_id BIGINT REFERENCES objectives(id) ON DELETE SET NULL,
    risk_opportunity_id BIGINT REFERENCES risks_opportunities(id) ON DELETE SET NULL,
    non_conformity_id BIGINT REFERENCES non_conformities(id) ON DELETE SET NULL,
    audit_id BIGINT REFERENCES audits(id) ON DELETE SET NULL,
    complaint_id BIGINT REFERENCES complaints(id) ON DELETE SET NULL,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    
    -- Type & Priorité
    type VARCHAR(50) NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    
    -- Description
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    expected_result TEXT,
    
    -- Responsabilités
    responsible_id BIGINT NOT NULL REFERENCES users(id),
    
    -- Échéance
    start_date DATE,
    deadline DATE NOT NULL,
    completion_date DATE,
    
    -- Statut & Suivi
    status VARCHAR(50) DEFAULT 'pending',
    progress_percentage INTEGER DEFAULT 0 CHECK (progress_percentage BETWEEN 0 AND 100),
    
    -- Efficacité
    effectiveness_verified BOOLEAN DEFAULT false,
    effectiveness_verification_date DATE,
    effectiveness_comments TEXT,
    verified_by BIGINT REFERENCES users(id),
    
    -- Coûts
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_actions_site ON actions(site_id);
CREATE INDEX idx_actions_source ON actions(source_type, source_id);
CREATE INDEX idx_actions_responsible ON actions(responsible_id);
CREATE INDEX idx_actions_status ON actions(status);
CREATE INDEX idx_actions_deadline ON actions(deadline);

CREATE TABLE action_participants (
    id BIGSERIAL PRIMARY KEY,
    action_id BIGINT NOT NULL REFERENCES actions(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(action_id, user_id)
);

CREATE INDEX idx_action_participants_action ON action_participants(action_id);
CREATE INDEX idx_action_participants_user ON action_participants(user_id);

CREATE TABLE action_progress_updates (
    id BIGSERIAL PRIMARY KEY,
    action_id BIGINT NOT NULL REFERENCES actions(id) ON DELETE CASCADE,
    update_date DATE NOT NULL,
    progress_percentage INTEGER CHECK (progress_percentage BETWEEN 0 AND 100),
    comments TEXT,
    difficulties TEXT,
    next_steps TEXT,
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_action_progress_action ON action_progress_updates(action_id);

CREATE TABLE action_attachments (
    id BIGSERIAL PRIMARY KEY,
    action_id BIGINT NOT NULL REFERENCES actions(id) ON DELETE CASCADE,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    file_type VARCHAR(100),
    uploaded_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_action_attachments_action ON action_attachments(action_id);

-- =====================================================
-- 9. NON-CONFORMITÉS
-- =====================================================

CREATE TABLE non_conformities (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    audit_id BIGINT REFERENCES audits(id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50),
    severity VARCHAR(20),
    status VARCHAR(50) DEFAULT 'open',
    detected_date DATE,
    detected_by BIGINT REFERENCES users(id),
    responsible_id BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 11. RÉCLAMATIONS
-- =====================================================

CREATE TABLE complaints (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    complaint_date DATE NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    severity VARCHAR(20),
    status VARCHAR(50) DEFAULT 'new',
    assigned_to BIGINT REFERENCES users(id),
    resolution TEXT,
    resolution_date DATE,
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =====================================================
-- 12. FORMATIONS & COMPÉTENCES
-- =====================================================

CREATE TABLE trainings (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50),
    duration INTEGER,
    trainer VARCHAR(255),
    training_date DATE,
    location VARCHAR(255),
    status VARCHAR(50) DEFAULT 'planned',
    created_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE training_participants (
    id BIGSERIAL PRIMARY KEY,
    training_id BIGINT NOT NULL REFERENCES trainings(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    attendance_status VARCHAR(50) DEFAULT 'registered',
    evaluation_score DECIMAL(5,2),
    certificate_issued BOOLEAN DEFAULT false,
    certificate_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(training_id, user_id)
);

-- =====================================================
-- 13. ÉQUIPEMENTS & MAINTENANCE
-- =====================================================

CREATE TABLE equipment (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    manufacturer VARCHAR(255),
    model VARCHAR(255),
    serial_number VARCHAR(255),
    purchase_date DATE,
    warranty_expiration DATE,
    status VARCHAR(50) DEFAULT 'operational',
    location VARCHAR(255),
    responsible_id BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE maintenance_records (
    id BIGSERIAL PRIMARY KEY,
    equipment_id BIGINT NOT NULL REFERENCES equipment(id) ON DELETE CASCADE,
    type VARCHAR(50),
    description TEXT,
    maintenance_date DATE NOT NULL,
    next_maintenance_date DATE,
    performed_by BIGINT REFERENCES users(id),
    cost DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 14. LOGS & TRAÇABILITÉ
-- =====================================================

CREATE TABLE activity_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    site_id BIGINT REFERENCES sites(id) ON DELETE CASCADE,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(255),
    entity_id BIGINT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);

-- =====================================================
-- 15. WEBHOOKS & INTÉGRATIONS
-- =====================================================

CREATE TABLE webhooks (
    id BIGSERIAL PRIMARY KEY,
    enterprise_id BIGINT NOT NULL REFERENCES enterprises(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    events JSONB NOT NULL,
    secret VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE webhook_logs (
    id BIGSERIAL PRIMARY KEY,
    webhook_id BIGINT NOT NULL REFERENCES webhooks(id) ON DELETE CASCADE,
    event VARCHAR(100) NOT NULL,
    payload JSONB,
    response_status INTEGER,
    response_body TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 16. INDICES DE PERFORMANCE
-- =====================================================

CREATE TABLE performance_indices (
    id BIGSERIAL PRIMARY KEY,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit VARCHAR(50),
    target_value DECIMAL(10,2),
    current_value DECIMAL(10,2),
    measurement_date DATE,
    frequency VARCHAR(50),
    responsible_id BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TRIGGERS & FONCTIONS
-- =====================================================

-- Fonction pour mettre à jour updated_at automatiquement
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Appliquer le trigger sur toutes les tables avec updated_at
CREATE TRIGGER update_enterprises_updated_at BEFORE UPDATE ON enterprises FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_sites_updated_at BEFORE UPDATE ON sites FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_processes_updated_at BEFORE UPDATE ON processes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_documents_updated_at BEFORE UPDATE ON documents FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_audits_updated_at BEFORE UPDATE ON audits FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_non_conformities_updated_at BEFORE UPDATE ON non_conformities FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_actions_updated_at BEFORE UPDATE ON actions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- DONNÉES INITIALES - RÔLES
-- =====================================================

INSERT INTO roles (name, guard_name, description) VALUES
('super_admin', 'web', 'Administrateur système - Accès complet'),
('enterprise_admin', 'web', 'Administrateur entreprise - Gestion multi-sites'),
('site_manager', 'web', 'Responsable de site - Gestion complète du site'),
('quality_manager', 'web', 'Responsable qualité - Gestion SMI du site'),
('process_owner', 'web', 'Pilote de processus - Gestion de processus spécifiques'),
('auditor', 'web', 'Auditeur - Réalisation et suivi des audits'),
('employee', 'web', 'Collaborateur - Consultation et saisie limitée'),
('viewer', 'web', 'Lecteur - Consultation uniquement');
