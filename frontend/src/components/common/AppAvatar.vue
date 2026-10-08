<template>
  <div :class="avatarClasses" :style="avatarStyle">
    <img
      v-if="src && !imageError"
      :alt="alt || name"
      class="avatar-image"
      :src="src"
      @error="handleImageError"
    >
    <div v-else-if="name" class="avatar-initials">
      {{ initials }}
    </div>
    <component :is="icon" v-else class="avatar-icon" />

    <div v-if="status" :class="statusClasses" :title="statusLabel" />
  </div>
</template>

<script setup lang="ts">
  import { User } from 'lucide-vue-next'
  import { computed, ref } from 'vue'

  interface Props {
    src?: string
    name?: string
    alt?: string
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    status?: 'online' | 'offline' | 'busy' | 'away'
    icon?: any
    color?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    icon: User,
  })

  const imageError = ref(false)

  const avatarClasses = computed(() => [
    'app-avatar',
    `app-avatar--${props.size}`,
  ])

  const avatarStyle = computed(() => {
    if (props.color) {
      return {
        backgroundColor: props.color,
      }
    }
    if (props.name) {
      return {
        backgroundColor: generateColor(props.name),
      }
    }
    return {}
  })

  const initials = computed(() => {
    if (!props.name) return ''

    const parts = props.name.trim().split(/\s+/)
    if (parts.length === 1) {
      return parts[0].slice(0, 2).toUpperCase()
    }
    return (parts[0][0] + parts.at(-1)[0]).toUpperCase()
  })

  const statusClasses = computed(() => [
    'avatar-status',
    `avatar-status--${props.status}`,
    `avatar-status--${props.size}`,
  ])

  const statusLabel = computed(() => {
    const labels = {
      online: 'En ligne',
      offline: 'Hors ligne',
      busy: 'Occupé',
      away: 'Absent',
    }
    return props.status ? labels[props.status] : ''
  })

  function handleImageError () {
    imageError.value = true
  }

  function generateColor (name: string): string {
    const colors = [
      '#4471c4', // primary
      '#10b981', // success
      '#f59e0b', // warning
      '#8b5cf6', // audit
      '#3b82f6', // info
      '#f97316', // risk
      '#ec4899', // pink
      '#14b8a6', // teal
      '#8b5cf6', // purple
      '#f59e0b', // amber
    ]

    let hash = 0
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }

    return colors[Math.abs(hash) % colors.length]
  }
</script>

<style scoped>
.app-avatar {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  overflow: hidden;
  background: #e2e8f0;
  color: #ffffff;
  font-weight: 600;
  flex-shrink: 0;
}

/* Sizes */
.app-avatar--xs {
  width: 24px;
  height: 24px;
  font-size: 0.625rem;
}

.app-avatar--sm {
  width: 32px;
  height: 32px;
  font-size: 0.75rem;
}

.app-avatar--md {
  width: 40px;
  height: 40px;
  font-size: 0.875rem;
}

.app-avatar--lg {
  width: 48px;
  height: 48px;
  font-size: 1rem;
}

.app-avatar--xl {
  width: 64px;
  height: 64px;
  font-size: 1.25rem;
}

.app-avatar--2xl {
  width: 96px;
  height: 96px;
  font-size: 1.875rem;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-initials {
  user-select: none;
}

.avatar-icon {
  width: 60%;
  height: 60%;
  color: #94a3b8;
}

/* Status indicator */
.avatar-status {
  position: absolute;
  border-radius: 9999px;
  border: 2px solid #ffffff;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.avatar-status--xs {
  width: 8px;
  height: 8px;
  bottom: 0;
  right: 0;
}

.avatar-status--sm {
  width: 10px;
  height: 10px;
  bottom: 0;
  right: 0;
}

.avatar-status--md {
  width: 12px;
  height: 12px;
  bottom: 0;
  right: 0;
}

.avatar-status--lg {
  width: 14px;
  height: 14px;
  bottom: 1px;
  right: 1px;
}

.avatar-status--xl {
  width: 16px;
  height: 16px;
  bottom: 2px;
  right: 2px;
}

.avatar-status--2xl {
  width: 20px;
  height: 20px;
  bottom: 4px;
  right: 4px;
}

.avatar-status--online {
  background: #10b981;
}

.avatar-status--offline {
  background: #94a3b8;
}

.avatar-status--busy {
  background: #ef4444;
}

.avatar-status--away {
  background: #f59e0b;
}
</style>
