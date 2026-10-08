<template>
  <div class="document-editor">
    <!-- Active Users Bar -->
    <div v-if="activeUsers.length > 0" class="active-users-bar">
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">Collaborateurs actifs:</span>
        <div class="flex gap-1">
          <div
            v-for="user in activeUsers"
            :key="user.id"
            class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold"
            :style="{ backgroundColor: user.color }"
            :title="user.name"
          >
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
        </div>
      </div>
      <div v-if="isSyncing" class="text-sm text-blue-600">
        <svg class="animate-spin h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor" />
        </svg>
        Synchronisation...
      </div>
    </div>

    <!-- Quill Editor -->
    <QuillEditor
      ref="quillEditor"
      v-model:content="content"
      content-type="delta"
      :options="editorOptions"
      theme="snow"
      @ready="handleEditorReady"
      @text-change="handleTextChange"
    />
  </div>
</template>

<script setup lang="ts">
  import { QuillEditor } from '@vueup/vue-quill'
  import { debounce } from 'lodash'
  import { onMounted, onUnmounted, ref } from 'vue'
  import api from '@/api/client'
  import echo from '@/plugins/echo'
  import '@vueup/vue-quill/dist/vue-quill.snow.css'

  interface Props {
    documentId: number
  }

  interface ActiveUser {
    id: number
    name: string
    username: string
    color: string
  }

  interface TextChangeEvent {
    delta: any
    oldDelta: any
    source: string
  }

  const props = defineProps<Props>()

  const quillEditor = ref<any>(null)
  const content = ref<any>(null)
  const activeUsers = ref<ActiveUser[]>([])
  const isSyncing = ref(false)
  const version = ref(1)
  const channel = ref<any>(null)
  const isRemoteChange = ref(false)

  const editorOptions = {
    modules: {
      toolbar: [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ header: 1 }, { header: 2 }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ script: 'sub' }, { script: 'super' }],
        [{ indent: '-1' }, { indent: '+1' }],
        [{ direction: 'rtl' }],
        [{ size: ['small', false, 'large', 'huge'] }],
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        [{ color: [] }, { background: [] }],
        [{ font: [] }],
        [{ align: [] }],
        ['clean'],
        ['link', 'image', 'video'],
      ],
    },
    placeholder: 'Commencez à écrire...',
  }

  // Debounced sync to avoid flooding
  const syncToServer = debounce(async (delta: any) => {
    if (isRemoteChange.value) {
      isRemoteChange.value = false
      return
    }

    isSyncing.value = true

    try {
      const response = await api.post(`/documents/${props.documentId}/collaboration/sync`, {
        delta: delta.ops,
        version: version.value,
      })

      if (response.data.version) {
        version.value = response.data.version
      }
    } catch (error: any) {
      if (error.response?.status === 409) {
        // Version conflict - reload
        console.warn('Version conflict detected, reloading document...')
        await loadDocumentState()
      } else {
        console.error('Failed to sync content:', error)
      }
    } finally {
      isSyncing.value = false
    }
  }, 500)

  function handleTextChange (event: TextChangeEvent) {
    if (event.source === 'user') {
      syncToServer(event.delta)
    }
  }

  function handleEditorReady () {
    console.log('Quill editor ready')
  }

  async function loadDocumentState () {
    try {
      const response = await api.get(`/documents/${props.documentId}/collaboration/state`)
      content.value = response.data.content
      version.value = response.data.version
    } catch (error) {
      console.error('Failed to load document state:', error)
    }
  }

  function joinChannel () {
    channel.value = echo.join(`document.${props.documentId}`)

    // Listen for presence events
    channel.value
      .here((users: ActiveUser[]) => {
        activeUsers.value = users
        console.log('Users currently in document:', users)
      })
      .joining((user: ActiveUser) => {
        activeUsers.value.push(user)
        console.log('User joined:', user.name)
      })
      .leaving((user: ActiveUser) => {
        activeUsers.value = activeUsers.value.filter(u => u.id !== user.id)
        console.log('User left:', user.name)
      })
      .listen('.content.changed', (event: any) => {
        console.log('Content changed by user:', event.userId)

        // Apply remote delta to local editor
        isRemoteChange.value = true

        const quill = quillEditor.value?.getQuill()
        if (quill && event.delta) {
          quill.updateContents(event.delta, 'api')
        }

        version.value = event.version
      })
  }

  function leaveChannel () {
    if (channel.value) {
      echo.leave(`document.${props.documentId}`)
      channel.value = null
    }
  }

  onMounted(async () => {
    await loadDocumentState()
    joinChannel()
  })

  onUnmounted(() => {
    leaveChannel()
  })
</script>

<style scoped>
.document-editor {
  @apply border rounded-lg overflow-hidden;
}

.active-users-bar {
  @apply bg-gray-50 border-b px-4 py-2 flex justify-between items-center;
}

:deep(.ql-container) {
  @apply min-h-[400px];
}

:deep(.ql-editor) {
  @apply text-base;
}
</style>
