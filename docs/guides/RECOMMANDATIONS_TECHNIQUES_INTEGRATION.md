# 🔧 RECOMMANDATIONS TECHNIQUES - Intégration Logi → Client A

**Date:** 9 février 2026  
**Complément de:** ANALYSE_INTEGRATION_LOGI_CLIENTA.md

---

## 🎯 SYNTHÈSE DES RECOMMANDATIONS

### ✅ Décision Stratégique Recommandée

**ADOPTER LA MIGRATION PROGRESSIVE VERS REACT**

**Raison principale:** Le design Logi basé sur Radix UI offre une accessibilité et une UX nettement supérieures, avec un écosystème de composants modernes parfaitement adaptés aux besoins QHSE.

---

## 🏗️ ARCHITECTURE TECHNIQUE RECOMMANDÉE

### Stack Technologique Final

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND REACT                        │
│  ┌──────────────────────────────────────────────────┐   │
│  │  UI Layer (Radix UI + shadcn/ui + Tailwind)     │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Pages (Dashboard, Modules ISO 4-10, etc.)      │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  State Management (Zustand / Pinia)             │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Services (API Client - Axios)                  │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Hooks (useAuth, usePermissions, useAPI)        │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Utils (Formatters, Validators, Helpers)        │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                            ⬍
                   API REST (JSON)
                            ⬍
┌─────────────────────────────────────────────────────────┐
│                   BACKEND LARAVEL 11                     │
│  ┌──────────────────────────────────────────────────┐   │
│  │  API Controllers (Sanctum Auth)                  │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Services (Business Logic)                       │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Repositories (Data Access)                      │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Models Eloquent (30+ nouveaux)                  │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Policies (Spatie Permission)                    │   │
│  ├──────────────────────────────────────────────────┤   │
│  │  Events & Listeners (Notifications)              │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                            ⬍
                      PostgreSQL 14+
                     (50+ tables)
```

---

## 📦 ORGANISATION DU CODE

### Structure Frontend React Recommandée

```
frontend-react/
├── public/
│   ├── favicon.ico
│   └── assets/
├── src/
│   ├── main.tsx                    # Entry point
│   ├── App.tsx                     # Root component
│   ├── router.tsx                  # React Router config
│   │
│   ├── components/
│   │   ├── ui/                     # shadcn/ui (Radix UI)
│   │   │   ├── button.tsx
│   │   │   ├── card.tsx
│   │   │   ├── dialog.tsx
│   │   │   ├── dropdown-menu.tsx
│   │   │   ├── form.tsx
│   │   │   ├── input.tsx
│   │   │   ├── select.tsx
│   │   │   ├── table.tsx
│   │   │   ├── tabs.tsx
│   │   │   └── ... (40+ composants)
│   │   │
│   │   ├── layout/
│   │   │   ├── MainLayout.tsx      # Layout principal
│   │   │   ├── Sidebar.tsx         # Navigation latérale
│   │   │   ├── Header.tsx          # En-tête
│   │   │   ├── Breadcrumbs.tsx
│   │   │   └── Footer.tsx
│   │   │
│   │   ├── onboarding/
│   │   │   ├── FirstLogin.tsx      # Workflow onboarding
│   │   │   ├── PasswordChange.tsx
│   │   │   ├── SignatureUpload.tsx
│   │   │   └── ActivityDomain.tsx
│   │   │
│   │   ├── shared/
│   │   │   ├── AiAssistant.tsx     # Assistant IA Grok
│   │   │   ├── DataTable.tsx       # Table réutilisable
│   │   │   ├── FileUpload.tsx
│   │   │   ├── DateRangePicker.tsx
│   │   │   ├── SignaturePad.tsx
│   │   │   ├── ExcelImport.tsx
│   │   │   ├── PDFGenerator.tsx
│   │   │   └── ...
│   │   │
│   │   └── charts/
│   │       ├── RiskHeatmap.tsx
│   │       ├── ActionProgress.tsx
│   │       ├── AuditTimeline.tsx
│   │       └── KPIDashboard.tsx
│   │
│   ├── pages/
│   │   ├── auth/
│   │   │   ├── Login.tsx
│   │   │   ├── ForgotPassword.tsx
│   │   │   └── LockScreen.tsx
│   │   │
│   │   ├── dashboard/
│   │   │   └── Dashboard.tsx       # Dashboard moderne
│   │   │
│   │   ├── context/                # Point 4 - Contexte Organisme
│   │   │   ├── index.tsx
│   │   │   ├── SWOT.tsx
│   │   │   ├── PESTEL.tsx
│   │   │   ├── Stakeholders.tsx
│   │   │   └── ApplicationScope.tsx
│   │   │
│   │   ├── leadership/             # Point 5
│   │   │   ├── index.tsx
│   │   │   ├── QHSEPolicy.tsx
│   │   │   ├── JobDescriptions.tsx
│   │   │   └── Responsibilities.tsx
│   │   │
│   │   ├── planning/               # Point 6
│   │   │   ├── index.tsx
│   │   │   ├── RiskMatrix.tsx
│   │   │   ├── DUERP.tsx
│   │   │   ├── EnvironmentalAspects.tsx
│   │   │   ├── Objectives.tsx
│   │   │   └── Compliance.tsx
│   │   │
│   │   ├── support/                # Point 7
│   │   │   ├── index.tsx
│   │   │   ├── Equipment.tsx
│   │   │   ├── Maintenance.tsx
│   │   │   ├── Training.tsx
│   │   │   └── Communication.tsx
│   │   │
│   │   ├── operations/             # Point 8
│   │   │   ├── index.tsx
│   │   │   ├── OperationalControls.tsx
│   │   │   └── EmergencyProcedures.tsx
│   │   │
│   │   ├── evaluation/             # Point 9
│   │   │   ├── index.tsx
│   │   │   ├── Audits.tsx
│   │   │   └── ManagementReview.tsx
│   │   │
│   │   ├── improvement/            # Point 10
│   │   │   ├── index.tsx
│   │   │   ├── NonConformities.tsx
│   │   │   ├── Actions.tsx
│   │   │   ├── FiveWhy.tsx
│   │   │   └── Ishikawa.tsx
│   │   │
│   │   ├── documents/
│   │   │   └── Documents.tsx       # Migré
│   │   │
│   │   ├── processes/
│   │   │   ├── Processes.tsx
│   │   │   └── TurtleDiagram.tsx
│   │   │
│   │   └── NotFound.tsx
│   │
│   ├── stores/                     # State Management
│   │   ├── authStore.ts
│   │   ├── contextStore.ts
│   │   ├── stakeholderStore.ts
│   │   ├── riskStore.ts
│   │   ├── auditStore.ts
│   │   ├── actionStore.ts
│   │   └── ...
│   │
│   ├── services/                   # API Services
│   │   ├── api.ts                  # Axios instance
│   │   ├── authService.ts
│   │   ├── contextService.ts
│   │   ├── stakeholderService.ts
│   │   ├── riskService.ts
│   │   ├── aiService.ts            # Grok API
│   │   └── ...
│   │
│   ├── hooks/                      # Custom Hooks
│   │   ├── useAuth.ts
│   │   ├── usePermissions.ts
│   │   ├── useAPI.ts
│   │   ├── useDebounce.ts
│   │   ├── useLocalStorage.ts
│   │   └── ...
│   │
│   ├── utils/                      # Utilities
│   │   ├── formatters.ts
│   │   ├── validators.ts
│   │   ├── constants.ts
│   │   ├── helpers.ts
│   │   └── cn.ts                   # classnames helper
│   │
│   ├── types/                      # TypeScript Types
│   │   ├── index.d.ts
│   │   ├── api.d.ts
│   │   ├── models.d.ts
│   │   ├── context.d.ts
│   │   ├── risk.d.ts
│   │   └── ...
│   │
│   ├── styles/
│   │   ├── globals.css
│   │   └── tailwind.css
│   │
│   └── config/
│       ├── routes.ts
│       ├── permissions.ts
│       └── env.ts
│
├── .env.example
├── .env.local
├── package.json
├── tsconfig.json
├── tailwind.config.js
├── vite.config.ts
└── README.md
```

### Structure Backend Laravel (Nouvelles Additions)

```
backend/
├── app/
│   ├── Models/
│   │   ├── Context/                # Point 4
│   │   │   ├── AnalysisCategory.php
│   │   │   ├── OrganizationContext.php
│   │   │   ├── ContextIssue.php
│   │   │   ├── Stakeholder.php
│   │   │   └── ApplicationScope.php
│   │   │
│   │   ├── Leadership/             # Point 5
│   │   │   ├── QHSEPolicy.php
│   │   │   ├── JobDescription.php
│   │   │   └── Responsibility.php
│   │   │
│   │   ├── Planning/               # Point 6
│   │   │   ├── Opportunity.php
│   │   │   ├── RiskTreatmentPlan.php
│   │   │   ├── ComplianceRequirement.php
│   │   │   ├── EnvironmentalAspect.php
│   │   │   ├── Duerp.php
│   │   │   └── DuerpDanger.php
│   │   │
│   │   ├── Support/                # Point 7
│   │   │   ├── Equipment.php
│   │   │   ├── MaintenancePlan.php
│   │   │   ├── TrainingPlan.php
│   │   │   ├── TrainingSession.php
│   │   │   ├── TrainingEvaluation.php
│   │   │   └── CommunicationAction.php
│   │   │
│   │   ├── Operations/             # Point 8
│   │   │   ├── OperationalControl.php
│   │   │   └── EmergencyProcedure.php
│   │   │
│   │   ├── Evaluation/             # Point 9
│   │   │   └── ManagementReview.php
│   │   │
│   │   ├── Improvement/            # Point 10
│   │   │   └── CollaboratorActionConfirmation.php
│   │   │
│   │   └── Transversal/
│   │       ├── AiSuggestion.php
│   │       └── OnboardingStep.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       └── ClientA/
│   │   │           ├── ContextController.php
│   │   │           ├── StakeholderController.php
│   │   │           ├── QHSEPolicyController.php
│   │   │           ├── JobDescriptionController.php
│   │   │           ├── DUERPController.php
│   │   │           ├── EnvironmentalAspectController.php
│   │   │           ├── EquipmentController.php
│   │   │           ├── MaintenanceController.php
│   │   │           ├── TrainingController.php
│   │   │           ├── ManagementReviewController.php
│   │   │           ├── OnboardingController.php
│   │   │           └── AIAssistantController.php
│   │   │
│   │   ├── Requests/
│   │   │   └── ClientA/
│   │   │       ├── StoreContextIssueRequest.php
│   │   │       ├── UpdateStakeholderRequest.php
│   │   │       ├── StoreDUERPRequest.php
│   │   │       └── ...
│   │   │
│   │   └── Resources/
│   │       └── ClientA/
│   │           ├── ContextIssueResource.php
│   │           ├── StakeholderResource.php
│   │           ├── DUERPResource.php
│   │           └── ...
│   │
│   ├── Services/
│   │   ├── ContextService.php
│   │   ├── RiskCalculationService.php
│   │   ├── DUERPGenerationService.php
│   │   ├── PDFGenerationService.php
│   │   ├── AIAssistantService.php
│   │   └── ...
│   │
│   ├── Repositories/
│   │   ├── ContextRepository.php
│   │   ├── StakeholderRepository.php
│   │   └── ...
│   │
│   ├── Events/
│   │   ├── ContextIssueCreated.php
│   │   ├── RiskCriticalityChanged.php
│   │   ├── ActionOverdue.php
│   │   └── ...
│   │
│   ├── Listeners/
│   │   ├── NotifyStakeholdersOfContextChange.php
│   │   ├── AlertOnCriticalRisk.php
│   │   └── ...
│   │
│   └── Policies/
│       ├── ContextIssuePolicy.php
│       ├── StakeholderPolicy.php
│       └── ...
│
├── database/
│   ├── migrations/
│   │   ├── 2026_02_10_000001_create_analysis_categories_table.php
│   │   ├── 2026_02_10_000002_create_organization_contexts_table.php
│   │   ├── ... (30+ migrations)
│   │   └── 2026_02_10_000028_create_onboarding_steps_table.php
│   │
│   ├── seeders/
│   │   ├── AnalysisCategorySeeder.php
│   │   ├── DemoContextSeeder.php
│   │   └── ...
│   │
│   └── factories/
│       ├── ContextIssueFactory.php
│       └── ...
│
└── routes/
    └── api.php                     # Routes API étendues
```

---

## 🔌 CONNEXION FRONTEND-BACKEND

### Configuration Axios (services/api.ts)

```typescript
import axios from 'axios';
import type { AxiosInstance, InternalAxiosRequestConfig, AxiosResponse } from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Instance Axios configurée
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Pour cookies Sanctum
});

// Intercepteur requête - Ajouter token
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token');
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Intercepteur réponse - Gérer erreurs
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expiré - Redirection login
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### Service Type (services/contextService.ts)

```typescript
import api from './api';
import type { 
  OrganizationContext, 
  ContextIssue, 
  Stakeholder,
  PaginatedResponse 
} from '@/types';

export const contextService = {
  // Contexts
  async getContexts(enterpriseId: number): Promise<OrganizationContext[]> {
    const { data } = await api.get(`/client-a/contexts`, {
      params: { enterprise_id: enterpriseId }
    });
    return data.data;
  },

  async createContext(payload: Partial<OrganizationContext>): Promise<OrganizationContext> {
    const { data } = await api.post('/client-a/contexts', payload);
    return data.data;
  },

  async updateContext(id: number, payload: Partial<OrganizationContext>): Promise<OrganizationContext> {
    const { data } = await api.put(`/client-a/contexts/${id}`, payload);
    return data.data;
  },

  async deleteContext(id: number): Promise<void> {
    await api.delete(`/client-a/contexts/${id}`);
  },

  // Context Issues (SWOT/PESTEL)
  async getContextIssues(contextId: number): Promise<ContextIssue[]> {
    const { data } = await api.get(`/client-a/contexts/${contextId}/issues`);
    return data.data;
  },

  async createIssue(contextId: number, payload: Partial<ContextIssue>): Promise<ContextIssue> {
    const { data } = await api.post(`/client-a/contexts/${contextId}/issues`, payload);
    return data.data;
  },

  // Stakeholders
  async getStakeholders(params?: any): Promise<PaginatedResponse<Stakeholder>> {
    const { data } = await api.get('/client-a/stakeholders', { params });
    return data;
  },

  async createStakeholder(payload: Partial<Stakeholder>): Promise<Stakeholder> {
    const { data } = await api.post('/client-a/stakeholders', payload);
    return data.data;
  },

  // IA Suggestions
  async getAISuggestion(context: string, prompt: string): Promise<string> {
    const { data } = await api.post('/client-a/ai/suggest', {
      context,
      prompt
    });
    return data.suggestion;
  }
};
```

### Store Zustand (stores/contextStore.ts)

```typescript
import { create } from 'zustand';
import { contextService } from '@/services/contextService';
import type { OrganizationContext, ContextIssue } from '@/types';

interface ContextStore {
  contexts: OrganizationContext[];
  currentContext: OrganizationContext | null;
  issues: ContextIssue[];
  loading: boolean;
  error: string | null;

  // Actions
  fetchContexts: (enterpriseId: number) => Promise<void>;
  setCurrentContext: (context: OrganizationContext | null) => void;
  createContext: (payload: Partial<OrganizationContext>) => Promise<void>;
  updateContext: (id: number, payload: Partial<OrganizationContext>) => Promise<void>;
  deleteContext: (id: number) => Promise<void>;
  
  fetchIssues: (contextId: number) => Promise<void>;
  createIssue: (contextId: number, payload: Partial<ContextIssue>) => Promise<void>;
}

export const useContextStore = create<ContextStore>((set, get) => ({
  contexts: [],
  currentContext: null,
  issues: [],
  loading: false,
  error: null,

  fetchContexts: async (enterpriseId) => {
    set({ loading: true, error: null });
    try {
      const contexts = await contextService.getContexts(enterpriseId);
      set({ contexts, loading: false });
    } catch (error: any) {
      set({ error: error.message, loading: false });
    }
  },

  setCurrentContext: (context) => {
    set({ currentContext: context });
  },

  createContext: async (payload) => {
    set({ loading: true, error: null });
    try {
      const newContext = await contextService.createContext(payload);
      set((state) => ({
        contexts: [...state.contexts, newContext],
        loading: false
      }));
    } catch (error: any) {
      set({ error: error.message, loading: false });
      throw error;
    }
  },

  updateContext: async (id, payload) => {
    set({ loading: true, error: null });
    try {
      const updated = await contextService.updateContext(id, payload);
      set((state) => ({
        contexts: state.contexts.map(c => c.id === id ? updated : c),
        currentContext: state.currentContext?.id === id ? updated : state.currentContext,
        loading: false
      }));
    } catch (error: any) {
      set({ error: error.message, loading: false });
      throw error;
    }
  },

  deleteContext: async (id) => {
    set({ loading: true, error: null });
    try {
      await contextService.deleteContext(id);
      set((state) => ({
        contexts: state.contexts.filter(c => c.id !== id),
        currentContext: state.currentContext?.id === id ? null : state.currentContext,
        loading: false
      }));
    } catch (error: any) {
      set({ error: error.message, loading: false });
      throw error;
    }
  },

  fetchIssues: async (contextId) => {
    set({ loading: true, error: null });
    try {
      const issues = await contextService.getContextIssues(contextId);
      set({ issues, loading: false });
    } catch (error: any) {
      set({ error: error.message, loading: false });
    }
  },

  createIssue: async (contextId, payload) => {
    set({ loading: true, error: null });
    try {
      const newIssue = await contextService.createIssue(contextId, payload);
      set((state) => ({
        issues: [...state.issues, newIssue],
        loading: false
      }));
    } catch (error: any) {
      set({ error: error.message, loading: false });
      throw error;
    }
  },
}));
```

---

## 🔐 GESTION PERMISSIONS (Frontend)

### Hook usePermissions

```typescript
import { useAuthStore } from '@/stores/authStore';

export function usePermissions() {
  const { user } = useAuthStore();

  const hasPermission = (permission: string): boolean => {
    if (!user) return false;
    return user.permissions?.includes(permission) || false;
  };

  const hasRole = (role: string): boolean => {
    if (!user) return false;
    return user.roles?.includes(role) || false;
  };

  const hasAnyPermission = (permissions: string[]): boolean => {
    return permissions.some(permission => hasPermission(permission));
  };

  const hasAllPermissions = (permissions: string[]): boolean => {
    return permissions.every(permission => hasPermission(permission));
  };

  return {
    hasPermission,
    hasRole,
    hasAnyPermission,
    hasAllPermissions,
    isSuperAdmin: hasRole('super_admin'),
    isAdmin: hasRole('admin'),
    isRQ: hasRole('responsable_qualite'),
  };
}
```

### Composant ProtectedRoute

```typescript
import { Navigate, Outlet } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { usePermissions } from '@/hooks/usePermissions';

interface ProtectedRouteProps {
  permissions?: string[];
  roles?: string[];
  requireAll?: boolean;
}

export function ProtectedRoute({ 
  permissions = [], 
  roles = [],
  requireAll = false 
}: ProtectedRouteProps) {
  const { isAuthenticated, loading } = useAuthStore();
  const { hasPermission, hasRole } = usePermissions();

  if (loading) {
    return <div>Chargement...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  // Vérifier permissions
  if (permissions.length > 0) {
    const hasAccess = requireAll
      ? permissions.every(p => hasPermission(p))
      : permissions.some(p => hasPermission(p));
    
    if (!hasAccess) {
      return <Navigate to="/403" replace />;
    }
  }

  // Vérifier rôles
  if (roles.length > 0) {
    const hasAccess = requireAll
      ? roles.every(r => hasRole(r))
      : roles.some(r => hasRole(r));
    
    if (!hasAccess) {
      return <Navigate to="/403" replace />;
    }
  }

  return <Outlet />;
}
```

### Utilisation dans Router

```typescript
import { createBrowserRouter } from 'react-router-dom';
import { ProtectedRoute } from '@/components/ProtectedRoute';

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <Login />,
  },
  {
    element: <ProtectedRoute />,
    children: [
      {
        path: '/',
        element: <MainLayout />,
        children: [
          {
            path: 'dashboard',
            element: <Dashboard />,
          },
          {
            path: 'context',
            element: <ProtectedRoute permissions={['view_context']} />,
            children: [
              { index: true, element: <ContextList /> },
              { path: 'swot', element: <SWOT /> },
              { path: 'stakeholders', element: <Stakeholders /> },
            ],
          },
          {
            path: 'audits',
            element: <ProtectedRoute permissions={['view_audits']} />,
            children: [
              { index: true, element: <AuditsList /> },
              { path: ':id', element: <AuditDetail /> },
            ],
          },
          // ... autres routes
        ],
      },
    ],
  },
]);
```

---

## 📊 GÉNÉRATION DE DOCUMENTS PDF (Backend)

### Service PDFGenerationService

```php
<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\QHSEPolicy;
use App\Models\Duerp;
use Illuminate\Support\Facades\Storage;

class PDFGenerationService
{
    public function generateQHSEPolicy(QHSEPolicy $policy): string
    {
        $pdf = Pdf::loadView('pdf.qhse-policy', [
            'policy' => $policy,
            'enterprise' => $policy->enterprise,
        ]);

        $filename = "politique-qhse-v{$policy->version}-" . time() . ".pdf";
        $path = "documents/policies/{$policy->enterprise_id}/{$filename}";

        Storage::put($path, $pdf->output());

        $policy->update([
            'document_path' => $path,
            'generated_at' => now(),
        ]);

        return $path;
    }

    public function generateDUERP(Duerp $duerp): string
    {
        $dangers = $duerp->dangers()
            ->with(['process', 'createdBy'])
            ->orderBy('criticality_score', 'desc')
            ->get();

        $pdf = Pdf::loadView('pdf.duerp', [
            'duerp' => $duerp,
            'enterprise' => $duerp->enterprise,
            'site' => $duerp->site,
            'dangers' => $dangers,
        ])->setPaper('a4', 'landscape');

        $filename = "duerp-v{$duerp->version}-" . time() . ".pdf";
        $path = "documents/duerp/{$duerp->enterprise_id}/{$filename}";

        Storage::put($path, $pdf->output());

        $duerp->update([
            'document_path' => $path,
            'generated_at' => now(),
        ]);

        return $path;
    }

    public function generateManagementReviewReport($reviewId): string
    {
        $review = ManagementReview::with(['enterprise', 'participants', 'actions'])
            ->findOrFail($reviewId);

        $pdf = Pdf::loadView('pdf.management-review', [
            'review' => $review,
        ]);

        $filename = "revue-direction-" . $review->review_date->format('Y-m-d') . ".pdf";
        $path = "documents/reviews/{$review->enterprise_id}/{$filename}";

        Storage::put($path, $pdf->output());

        $review->update([
            'report_path' => $path,
            'generated_at' => now(),
        ]);

        return $path;
    }
}
```

### Template Blade (resources/views/pdf/qhse-policy.blade.php)

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Politique QHSE - {{ $enterprise->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
        }
        h1 { color: #1a365d; border-bottom: 3px solid #3182ce; padding-bottom: 10px; }
        h2 { color: #2d3748; margin-top: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { max-height: 80px; }
        .section { margin-bottom: 20px; }
        .commitment { margin-left: 20px; padding: 10px; background: #f7fafc; }
    </style>
</head>
<body>
    <div class="header">
        @if($enterprise->logo)
            <img src="{{ storage_path('app/public/' . $enterprise->logo) }}" class="logo" alt="Logo">
        @endif
        <h1>Politique Qualité, Hygiène, Sécurité, Environnement</h1>
        <p><strong>{{ $enterprise->name }}</strong></p>
        <p>Version {{ $policy->version }} - {{ $policy->effective_date->format('d/m/Y') }}</p>
    </div>

    <div class="section">
        <h2>Mission</h2>
        <p>{{ $policy->mission }}</p>
    </div>

    @if($policy->vision)
    <div class="section">
        <h2>Vision</h2>
        <p>{{ $policy->vision }}</p>
    </div>
    @endif

    @if($policy->values)
    <div class="section">
        <h2>Nos Valeurs</h2>
        <ul>
            @foreach(json_decode($policy->values) as $value)
                <li>{{ $value }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="section">
        <h2>Engagements QHSE</h2>
        @foreach(json_decode($policy->commitments) as $commitment)
            <div class="commitment">{{ $commitment }}</div>
        @endforeach
    </div>

    @if($policy->quality_policy)
    <div class="section">
        <h2>Politique Qualité (ISO 9001)</h2>
        <p>{{ $policy->quality_policy }}</p>
    </div>
    @endif

    <div class="section" style="margin-top: 50px;">
        <p><strong>Date d'approbation :</strong> {{ $policy->validated_by_direction_at?->format('d/m/Y') }}</p>
        <p><strong>Approuvé par :</strong> {{ $policy->validatedBy?->name }}</p>
    </div>
</body>
</html>
```

---

## 🤖 INTÉGRATION IA GROK

### Service AIAssistantService (Backend)

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Transversal\AiSuggestion;

class AIAssistantService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.x.ai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.grok.api_key');
    }

    public function getSuggestion(
        string $context,
        string $prompt,
        int $enterpriseId,
        int $userId,
        ?string $relatedEntityType = null,
        ?int $relatedEntityId = null
    ): array {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl, [
            'model' => 'grok-2-latest',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt($context),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erreur API Grok: ' . $response->body());
        }

        $data = $response->json();
        $suggestion = $data['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;

        // Sauvegarder historique
        AiSuggestion::create([
            'enterprise_id' => $enterpriseId,
            'suggestion_context' => $context,
            'related_entity_type' => $relatedEntityType,
            'related_entity_id' => $relatedEntityId,
            'user_prompt' => $prompt,
            'ai_response' => $suggestion,
            'confidence_score' => 0.85, // À calculer si possible
            'api_model' => 'grok-2',
            'api_tokens_used' => $tokensUsed,
            'api_cost' => $this->calculateCost($tokensUsed),
            'user_id' => $userId,
        ]);

        return [
            'suggestion' => $suggestion,
            'tokens_used' => $tokensUsed,
        ];
    }

    protected function getSystemPrompt(string $context): string
    {
        $prompts = [
            'swot' => "Tu es un expert en analyse stratégique SWOT pour entreprises ISO 9001. Fournis des suggestions pertinentes, concrètes et adaptées au secteur QHSE.",
            'pestel' => "Tu es un expert en analyse PESTEL. Identifie les facteurs macro-environnementaux pertinents pour l'entreprise.",
            'risk' => "Tu es un expert en gestion des risques ISO 31000. Aide à identifier, évaluer et traiter les risques.",
            'action' => "Tu es un consultant en amélioration continue. Suggère des actions correctives et préventives efficaces.",
            'duerp' => "Tu es un expert en prévention des risques professionnels et DUERP. Aide à identifier les dangers et mesures de prévention.",
        ];

        return $prompts[$context] ?? "Tu es un assistant QHSE expert ISO 9001, 14001, 45001.";
    }

    protected function calculateCost(int $tokens): float
    {
        // Prix Grok estimé: $5 / 1M tokens
        return ($tokens / 1000000) * 5;
    }
}
```

### Composant Frontend (components/shared/AiAssistant.tsx)

```typescript
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card } from '@/components/ui/card';
import { Sparkles, Loader2 } from 'lucide-react';
import { aiService } from '@/services/aiService';
import { toast } from 'sonner';

interface AiAssistantProps {
  context: 'swot' | 'pestel' | 'risk' | 'action' | 'duerp';
  placeholder?: string;
  onSuggestion?: (suggestion: string) => void;
}

export function AiAssistant({ context, placeholder, onSuggestion }: AiAssistantProps) {
  const [prompt, setPrompt] = useState('');
  const [suggestion, setSuggestion] = useState('');
  const [loading, setLoading] = useState(false);

  const handleGetSuggestion = async () => {
    if (!prompt.trim()) {
      toast.error('Veuillez saisir une question');
      return;
    }

    setLoading(true);
    try {
      const result = await aiService.getSuggestion(context, prompt);
      setSuggestion(result.suggestion);
      toast.success('Suggestion générée avec succès');
    } catch (error) {
      toast.error('Erreur lors de la génération de la suggestion');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleApply = () => {
    if (onSuggestion && suggestion) {
      onSuggestion(suggestion);
      toast.success('Suggestion appliquée');
      setPrompt('');
      setSuggestion('');
    }
  };

  return (
    <Card className="p-4 border-dashed border-2 border-blue-300 bg-blue-50/50">
      <div className="flex items-center gap-2 mb-3">
        <Sparkles className="w-5 h-5 text-blue-600" />
        <h3 className="font-semibold text-blue-900">Assistant IA Grok</h3>
      </div>

      <Textarea
        value={prompt}
        onChange={(e) => setPrompt(e.target.value)}
        placeholder={placeholder || "Décrivez votre besoin..."}
        className="mb-3"
        rows={3}
      />

      <Button
        onClick={handleGetSuggestion}
        disabled={loading || !prompt.trim()}
        className="w-full mb-3"
      >
        {loading ? (
          <>
            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
            Génération en cours...
          </>
        ) : (
          <>
            <Sparkles className="w-4 h-4 mr-2" />
            Obtenir une suggestion
          </>
        )}
      </Button>

      {suggestion && (
        <div className="mt-3 p-3 bg-white rounded-lg border">
          <p className="text-sm text-gray-700 whitespace-pre-wrap">{suggestion}</p>
          {onSuggestion && (
            <Button
              onClick={handleApply}
              variant="outline"
              size="sm"
              className="mt-2"
            >
              Appliquer cette suggestion
            </Button>
          )}
        </div>
      )}
    </Card>
  );
}
```

---

## ✅ CHECKLIST AVANT DÉPLOIEMENT

### Backend

- [ ] Toutes les migrations créées et testées
- [ ] Seeders de données de démo
- [ ] Tous les modèles Eloquent avec relations
- [ ] Controllers API avec validation (FormRequest)
- [ ] Resources API (formatage réponses)
- [ ] Policies Spatie pour chaque module
- [ ] Tests unitaires (PHPUnit) > 80% couverture
- [ ] Tests API (Postman/Insomnia collections)
- [ ] Documentation API (Swagger/OpenAPI)
- [ ] Optimisation queries (eager loading)
- [ ] Index BDD pour performance
- [ ] Jobs asynchrones pour tâches lourdes
- [ ] Queues configurées (Redis/Database)
- [ ] Cache activé (Redis recommended)
- [ ] Logs configurés (Laravel Log)
- [ ] Variables .env documentées

### Frontend

- [ ] Tous composants UI testés
- [ ] Pages principales fonctionnelles
- [ ] Routing complet avec guards
- [ ] State management opérationnel
- [ ] Services API tous connectés
- [ ] Gestion erreurs API
- [ ] Loading states partout
- [ ] Formulaires avec validation
- [ ] Accessibilité (WCAG AA)
- [ ] Responsive mobile/tablet/desktop
- [ ] Tests E2E (Cypress/Playwright)
- [ ] Performance optimisée (Lighthouse > 90)
- [ ] PWA configuré (si besoin)
- [ ] i18n (si multi-langue)
- [ ] Build production testé
- [ ] Variables .env documentées

### Sécurité

- [ ] CORS configuré correctement
- [ ] Sanctum tokens sécurisés
- [ ] CSRF protection activé
- [ ] XSS protection
- [ ] SQL Injection protection (Eloquent)
- [ ] Rate limiting API
- [ ] Input validation partout
- [ ] File upload sécurisé
- [ ] HTTPS en production
- [ ] Secrets jamais en code
- [ ] Audit logs activés

### DevOps

- [ ] Git workflow défini
- [ ] CI/CD pipeline configuré
- [ ] Tests automatisés dans CI
- [ ] Staging environment
- [ ] Production environment
- [ ] Backups automatiques BDD
- [ ] Monitoring (Sentry/Bugsnag)
- [ ] Analytics (optionnel)
- [ ] Documentation déploiement
- [ ] Rollback strategy

---

## 📚 RESSOURCES UTILES

### Documentation Officielle

- **React:** https://react.dev
- **Radix UI:** https://www.radix-ui.com
- **shadcn/ui:** https://ui.shadcn.com
- **Tailwind CSS:** https://tailwindcss.com
- **Zustand:** https://github.com/pmndrs/zustand
- **React Router:** https://reactrouter.com
- **Laravel 11:** https://laravel.com/docs/11.x
- **Spatie Permission:** https://spatie.be/docs/laravel-permission

### Packages Recommandés

**Frontend:**
```bash
npm install @radix-ui/react-* tailwindcss
npm install react-router-dom zustand axios
npm install react-hook-form zod
npm install recharts lucide-react
npm install sonner # Notifications
npm install date-fns # Dates
```

**Backend:**
```bash
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

---

**Document créé le:** 9 février 2026  
**Version:** 1.0  
**Complément de:** ANALYSE_INTEGRATION_LOGI_CLIENTA.md
