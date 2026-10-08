/**
 * useUsers Composable
 * Manages users (collaborateurs) state and operations
 */

import type { User } from '@/types/models/users'
import { computed, ref } from 'vue'
import { type CreateUserDTO, type UpdateUserDTO, type UsersListParams, usersService } from '@/api/services/users.service'

export function useUsers () {
  const users = ref<User[]>([])
  const currentUser = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  const activeUsers = computed(() => users.value.filter(u => u.is_active !== false))
  const inactiveUsers = computed(() => users.value.filter(u => u.is_active === false))

  function buildUserDisplayName (user: Partial<User> | null | undefined): string {
    if (!user) {
      return 'Utilisateur'
    }

    const explicitName = String(user.name || '').trim()
    const firstName = String(user.first_name || '').trim()
    const lastName = String(user.last_name || '').trim()
    const fullName = String(user.full_name || '').trim()
    const email = String(user.email || '').trim()

    return explicitName
      || fullName
      || [firstName, lastName].filter(Boolean).join(' ')
      || email
      || 'Utilisateur'
  }

  function normalizeUser (user: User): User {
    const displayName = buildUserDisplayName(user)

    return {
      ...user,
      name: displayName,
      full_name: String(user.full_name || '').trim() || displayName,
      display_name: displayName,
    }
  }

  /**
   * Fetch users list
   */
  async function fetchUsers (params: UsersListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await usersService.getUsers(params)
      users.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch users'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single user
   */
  async function fetchUser (id: number) {
    loading.value = true
    error.value = null
    try {
      currentUser.value = normalizeUser(await usersService.getUser(id))
      return currentUser.value
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch user'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new user
   */
  async function createUser (data: CreateUserDTO) {
    loading.value = true
    error.value = null
    try {
      const newUser = normalizeUser(await usersService.createUser(data))
      users.value.unshift(newUser)
      return newUser
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create user'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update user
   */
  async function updateUser (id: number, data: UpdateUserDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedUser = normalizeUser(await usersService.updateUser(id, data))
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = updatedUser
      }
      if (currentUser.value?.id === id) {
        currentUser.value = updatedUser
      }
      return updatedUser
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update user'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete user
   */
  async function deleteUser (id: number) {
    loading.value = true
    error.value = null
    try {
      await usersService.deleteUser(id)
      users.value = users.value.filter(u => u.id !== id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to delete user'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Toggle user status
   */
  async function toggleUserStatus (id: number) {
    loading.value = true
    error.value = null
    try {
      const updatedUser = normalizeUser(await usersService.toggleUserStatus(id))
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = updatedUser
      }
      return updatedUser
    } catch (error_: any) {
      error.value = error_.message || 'Failed to toggle user status'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update user permissions
   */
  async function updatePermissions (id: number, permissions: string[]) {
    loading.value = true
    error.value = null
    try {
      const updatedUser = normalizeUser(await usersService.updatePermissions(id, permissions))
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = updatedUser
      }
      if (currentUser.value?.id === id) {
        currentUser.value = updatedUser
      }
      return updatedUser
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update permissions'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Assign user to site
   */
  async function assignToSite (id: number, site_id: number | null) {
    loading.value = true
    error.value = null
    try {
      const updatedUser = normalizeUser(await usersService.assignToSite(id, site_id))
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = updatedUser
      }
      return updatedUser
    } catch (error_: any) {
      error.value = error_.message || 'Failed to assign user to site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    currentUser,
    loading,
    error,
    pagination,
    activeUsers,
    inactiveUsers,
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    toggleUserStatus,
    updatePermissions,
    assignToSite,
  }
}
