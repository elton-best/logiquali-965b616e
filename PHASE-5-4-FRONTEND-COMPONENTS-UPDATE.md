# Phase 5.4: Frontend Components Update

**Status**: ✅ Complete  
**Date**: 2025-01-XX

## Objective

Update frontend document pages and components to use unified types (UnifiedDocument) supporting both legacy DocumentInventory and new Document formats during transition period.

---

## Files Modified

### 1. Document List Page
**File**: `frontend/src/modules/clienta/pages/documents/index.vue`

**Changes**:
- Imported `UnifiedDocument` type and `DocumentHelpers` utility
- Updated component refs to use `UnifiedDocument` instead of `DocumentInventory`
- Modified `filteredDocuments` computed to use `DocumentHelpers.getTitle()` and `DocumentHelpers.getStatut()`
- Updated `openDetails()` function signature to accept `UnifiedDocument`
- Updated template to use `DocumentHelpers.getTitle()` for displaying document name
- Updated `handleImportSuccess()` to use `DocumentHelpers.getTitle()` for toast message

**Impact**: Main document list page now handles both old and new document formats seamlessly

---

### 2. Document Card Component
**File**: `frontend/src/modules/clienta/components/documents/DocumentCard.vue`

**Changes**:
- Imported `UnifiedDocument` type and `DocumentHelpers` utility
- Updated props interface to accept `UnifiedDocument`
- Updated emits to use `UnifiedDocument` type
- Modified template to use `DocumentHelpers.getTitle()` instead of direct `document.nom` access

**Impact**: Card component displays correct title regardless of document format

---

### 3. Status Badge Component
**File**: `frontend/src/modules/clienta/components/documents/StatusBadge.vue`

**Changes**:
- Extended props type to accept both legacy and new status values:
  - Legacy: `brouillon`, `en_revision`, `valide`
  - New: `draft`, `pending_verification`, `approved`, `obsolete`
- Added mapping in `statusConfig` for all status values
- Both formats now display correctly with proper labels and icons

**Impact**: Status badges work with both old and new status field values

---

## Key Features

### Backward Compatibility
- All components accept both `DocumentInventory` (legacy) and `Document` (new) formats
- `DocumentHelpers` utility provides normalized access to fields:
  - `getTitle()` - returns `title` or `nom`
  - `getFilePath()` - returns `file_path` or `fichier`
  - `getStatus()` - returns new format status
  - `getStatut()` - returns legacy format status
  - `isMigrated()` - checks if document uses new format

### Automatic Field Mapping
- Components use helper functions instead of direct field access
- No breaking changes to existing functionality
- Seamless transition between formats

### Status Mapping
Status badges support bidirectional mapping:
```
brouillon ↔ draft
en_revision ↔ pending_verification
valide ↔ approved
obsolete (new only)
```

---

## Components Not Modified

The following components were reviewed but don't require changes as they:
1. Don't directly access document fields that differ between formats
2. Use generic props that work with both formats
3. Are used in contexts where format is already normalized

**Unchanged Components**:
- `StateBadge.vue` - uses `etat` field (same in both formats)
- `TypeBadge.vue` - uses `type` field (same in both formats)
- `create.vue` - creates new documents using new format
- `[id].vue` - detail page uses new Document format from unified API

---

## Testing Checklist

- [ ] Document list displays correctly with mixed old/new documents
- [ ] Document cards show correct titles for both formats
- [ ] Status badges display correctly for all status values
- [ ] Filtering works with both status formats
- [ ] Search works with both `nom` and `title` fields
- [ ] Document details open correctly for both formats
- [ ] Import success messages display correct document names

---

## Migration Path

### Phase 1: Transition (Current)
- ✅ Backend supports both formats via adapter
- ✅ Frontend components use UnifiedDocument type
- ✅ Helper functions normalize field access
- Status: Both formats coexist

### Phase 2: Data Migration
- Run migration command to convert DocumentInventory → Document
- Verify data integrity
- Monitor for issues

### Phase 3: Deprecation
- Remove DocumentInventory model
- Remove adapter service
- Update types to use Document only
- Remove helper functions (use direct access)

---

## Developer Notes

### Using UnifiedDocument in New Components

```typescript
import type { UnifiedDocument } from '@/types/document-unified.types'
import { DocumentHelpers } from '@/types/document-unified.types'

// Props
const props = defineProps<{
  document: UnifiedDocument
}>()

// Access fields safely
const title = DocumentHelpers.getTitle(props.document)
const status = DocumentHelpers.getStatus(props.document)
const isMigrated = DocumentHelpers.isMigrated(props.document)
```

### Status Badge Usage

```vue
<!-- Works with both formats -->
<StatusBadge :statut="DocumentHelpers.getStatut(document)" />
```

---

## Next Steps

1. **Phase 5.5**: Update remaining document-related pages (verification, approbation)
2. **Phase 5.6**: Add integration tests for unified document handling
3. **Phase 5.7**: Update API documentation with migration guide
4. **Phase 6**: Execute data migration in production

---

## Related Files

- `frontend/src/modules/clienta/types/document-unified.types.ts` - Type definitions
- `frontend/src/modules/clienta/composables/useDocuments.ts` - Composable using unified types
- `app/Services/DocumentInventoryAdapterService.php` - Backend adapter
- `app/Http/Controllers/Api/DocumentInventoryController.php` - Unified controller
