-- =====================================================
-- TABLES REFACTORISÉES - ACTIONS UNIFIÉES & OBJECTIFS = INDICATEURS
-- =====================================================

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
    category VARCHAR(100), -- stratégique, opérationnel, financier, etc.
    source VARCHAR(100), -- SWOT, PESTEL, audit, processus, etc.
    
    -- Évaluation du risque
    probability INTEGER CHECK (probability BETWEEN 1 AND 5), -- 1=très faible, 5=très élevée
    impact INTEGER CHECK (impact BETWEEN 1 AND 5), -- 1=négligeable, 5=catastrophique
    criticality INTEGER GENERATED ALWAYS AS (probability * impact) STORED,
    
    -- Traitement
    treatment_strategy VARCHAR(50), -- éviter, réduire, transférer, accepter (pour risques) / exploiter, améliorer, partager, accepter (pour opportunités)
    treatment_plan TEXT,
    residual_probability INTEGER CHECK (residual_probability BETWEEN 1 AND 5),
    residual_impact INTEGER CHECK (residual_impact BETWEEN 1 AND 5),
    residual_criticality INTEGER GENERATED ALWAYS AS (residual_probability * residual_impact) STORED,
    
    -- Suivi
    status VARCHAR(50) DEFAULT 'identified', -- identified, analyzed, treated, monitored, closed
    responsible_id BIGINT REFERENCES users(id),
    review_date DATE,
    review_frequency VARCHAR(50), -- mensuel, trimestriel, annuel
    
    -- Audit
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

-- Un objectif EST un indicateur avec une cible
CREATE TABLE objectives (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    
    -- Informations générales
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100), -- qualité, environnement, sécurité, énergie, stratégique
    strategic_axis VARCHAR(100), -- axe stratégique de l'entreprise
    
    -- Indicateur (KPI)
    indicator_name VARCHAR(255) NOT NULL, -- Nom de l'indicateur
    indicator_formula TEXT, -- Formule de calcul
    unit VARCHAR(50), -- %, €, jours, nombre, etc.
    measurement_frequency VARCHAR(50), -- mensuel, trimestriel, annuel
    data_source VARCHAR(255), -- Source des données
    
    -- Cible & Seuils
    baseline_value DECIMAL(10,2), -- Valeur de référence (point de départ)
    target_value DECIMAL(10,2) NOT NULL, -- Valeur cible à atteindre
    threshold_alert DECIMAL(10,2), -- Seuil d'alerte
    threshold_critical DECIMAL(10,2), -- Seuil critique
    
    -- Période
    start_date DATE,
    deadline DATE,
    
    -- Responsabilités
    responsible_id BIGINT REFERENCES users(id), -- Responsable de l'objectif
    data_collector_id BIGINT REFERENCES users(id), -- Responsable de la collecte des données
    
    -- Statut
    status VARCHAR(50) DEFAULT 'in_progress', -- in_progress, achieved, not_achieved, cancelled
    achievement_rate DECIMAL(5,2), -- Taux de réalisation en %
    
    -- Audit
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

-- Réalisations périodiques de l'indicateur
CREATE TABLE objective_realizations (
    id BIGSERIAL PRIMARY KEY,
    objective_id BIGINT NOT NULL REFERENCES objectives(id) ON DELETE CASCADE,
    period_date DATE NOT NULL, -- Date de la période (ex: 2025-01-01 pour janvier 2025)
    actual_value DECIMAL(10,2) NOT NULL, -- Valeur réelle mesurée
    target_value DECIMAL(10,2), -- Cible pour cette période (peut différer de la cible globale)
    gap DECIMAL(10,2), -- Écart = actual_value - target_value
    gap_percentage DECIMAL(5,2), -- Écart en %
    trend VARCHAR(20), -- up, down, stable
    comments TEXT,
    corrective_actions TEXT, -- Actions correctives si écart négatif
    recorded_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(objective_id, period_date)
);

CREATE INDEX idx_objective_realizations_objective ON objective_realizations(objective_id);
CREATE INDEX idx_objective_realizations_period ON objective_realizations(period_date);

-- =====================================================
-- 8. ACTIONS UNIFIÉES (REFACTORISÉ)
-- =====================================================

-- Table unique pour TOUTES les actions du système
CREATE TABLE actions (
    id BIGSERIAL PRIMARY KEY,
    ref VARCHAR(50) UNIQUE NOT NULL,
    site_id BIGINT NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    
    -- Origine de l'action (polymorphique)
    source_type VARCHAR(50) NOT NULL, -- 'objective', 'risk', 'opportunity', 'non_conformity', 'audit', 'complaint', 'improvement'
    source_id BIGINT, -- ID de l'entité source
    
    -- Relations spécifiques (pour faciliter les requêtes)
    objective_id BIGINT REFERENCES objectives(id) ON DELETE SET NULL,
    risk_opportunity_id BIGINT REFERENCES risks_opportunities(id) ON DELETE SET NULL,
    non_conformity_id BIGINT REFERENCES non_conformities(id) ON DELETE SET NULL,
    audit_id BIGINT REFERENCES audits(id) ON DELETE SET NULL,
    complaint_id BIGINT REFERENCES complaints(id) ON DELETE SET NULL,
    process_id BIGINT REFERENCES processes(id) ON DELETE SET NULL,
    
    -- Type d'action
    type VARCHAR(50) NOT NULL, -- corrective, preventive, improvement, treatment
    priority VARCHAR(20) DEFAULT 'medium', -- low, medium, high, critical
    
    -- Description de l'action
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL, -- Description détaillée de l'action
    expected_result TEXT, -- Résultat attendu
    
    -- Responsabilités
    responsible_id BIGINT NOT NULL REFERENCES users(id), -- Responsable principal
    
    -- Échéance
    start_date DATE,
    deadline DATE NOT NULL,
    completion_date DATE,
    
    -- Statut & Suivi
    status VARCHAR(50) DEFAULT 'pending', -- pending, in_progress, completed, cancelled, overdue
    progress_percentage INTEGER DEFAULT 0 CHECK (progress_percentage BETWEEN 0 AND 100),
    
    -- Efficacité
    effectiveness_verified BOOLEAN DEFAULT false,
    effectiveness_verification_date DATE,
    effectiveness_comments TEXT,
    verified_by BIGINT REFERENCES users(id),
    
    -- Coûts (optionnel)
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    
    -- Audit
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

-- Responsables impliqués dans l'action (équipe)
CREATE TABLE action_participants (
    id BIGSERIAL PRIMARY KEY,
    action_id BIGINT NOT NULL REFERENCES actions(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50), -- contributor, validator, informed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(action_id, user_id)
);

CREATE INDEX idx_action_participants_action ON action_participants(action_id);
CREATE INDEX idx_action_participants_user ON action_participants(user_id);

-- Suivi de l'avancement de l'action
CREATE TABLE action_progress_updates (
    id BIGSERIAL PRIMARY KEY,
    action_id BIGINT NOT NULL REFERENCES actions(id) ON DELETE CASCADE,
    update_date DATE NOT NULL,
    progress_percentage INTEGER CHECK (progress_percentage BETWEEN 0 AND 100),
    comments TEXT,
    difficulties TEXT, -- Difficultés rencontrées
    next_steps TEXT, -- Prochaines étapes
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_action_progress_action ON action_progress_updates(action_id);

-- Pièces jointes des actions
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
