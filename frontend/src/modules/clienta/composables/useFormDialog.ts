import { computed, ref } from 'vue'

export interface FormDialogOptions<T> {
  initialData?: Partial<T>
  onSubmit?: (data: T) => Promise<void> | void
  onCancel?: () => void
  resetOnClose?: boolean
}

export function useFormDialog<T extends Record<string, any>> (
  options: FormDialogOptions<T> = {},
) {
  const dialog = ref(false)
  const editMode = ref(false)
  const loading = ref(false)
  const formData = ref<Partial<T>>({ ...options.initialData } as Partial<T>)
  const errors = ref<Record<string, string>>({})

  // Computed title
  const title = computed(() =>
    editMode.value ? 'Modifier' : 'Créer',
  )

  // Open dialog for create
  function openCreate (initialData?: Partial<T>) {
    editMode.value = false
    formData.value = { ...options.initialData, ...initialData } as Partial<T>
    errors.value = {}
    dialog.value = true
  }

  // Open dialog for edit
  function openEdit (data: T) {
    editMode.value = true
    formData.value = { ...data }
    errors.value = {}
    dialog.value = true
  }

  // Close dialog
  function close () {
    dialog.value = false
    if (options.resetOnClose !== false) {
      setTimeout(() => {
        formData.value = { ...options.initialData } as Partial<T>
        errors.value = {}
        editMode.value = false
      }, 300) // Wait for dialog close animation
    }
    options.onCancel?.()
  }

  // Submit form
  async function submit () {
    try {
      loading.value = true
      errors.value = {}

      if (options.onSubmit) {
        await options.onSubmit(formData.value as T)
      }

      close()
    } catch (error: any) {
      // Handle validation errors
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      } else if (error.response?.data?.message) {
        errors.value = { general: error.response.data.message }
      } else {
        errors.value = { general: 'Une erreur est survenue' }
      }
    } finally {
      loading.value = false
    }
  }

  // Update form field
  function updateField (key: keyof T, value: any) {
    formData.value = { ...formData.value, [key]: value }
    // Clear field error on change
    if (errors.value[key as string]) {
      delete errors.value[key as string]
    }
  }

  // Set errors (useful for server-side validation)
  function setErrors (newErrors: Record<string, string>) {
    errors.value = newErrors
  }

  // Clear errors
  function clearErrors () {
    errors.value = {}
  }

  return {
    // State
    dialog,
    editMode,
    loading,
    formData,
    errors,

    // Computed
    title,

    // Methods
    openCreate,
    openEdit,
    close,
    submit,
    updateField,
    setErrors,
    clearErrors,
  }
}
