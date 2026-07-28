import { createContext, useContext, useState, type ReactNode } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api, TOKEN_KEY } from './api'

export const ROLES = {
  CLIENT: 'client',
  SERVICE_CLIENT: 'service_client',
  ADMINISTRATEUR: 'administrateur',
} as const

export type CurrentUser = {
  id: number
  name: string
  email: string | null
  locale: string
  roles: string[]
}

type AuthContextValue = {
  user: CurrentUser | undefined
  isLoading: boolean
  isAuthenticated: boolean
  hasRole: (role: string) => boolean
  login: (email: string, password: string) => Promise<void>
  loginError: string | null
  isLoggingIn: boolean
  logout: () => void
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()
  const [hasToken, setHasToken] = useState(() => Boolean(localStorage.getItem(TOKEN_KEY)))
  const [loginError, setLoginError] = useState<string | null>(null)

  const { data: user, isLoading } = useQuery({
    queryKey: ['me'],
    queryFn: async () => (await api.get<{ data: CurrentUser }>('/me')).data.data,
    enabled: hasToken,
    retry: false,
  })

  const loginMutation = useMutation({
    mutationFn: async ({ email, password }: { email: string; password: string }) => {
      const response = await api.post<{ access_token: string }>('/auth/login', { email, password })
      return response.data.access_token
    },
    onSuccess: (token) => {
      localStorage.setItem(TOKEN_KEY, token)
      setHasToken(true)
      setLoginError(null)
      queryClient.invalidateQueries({ queryKey: ['me'] })
    },
    onError: (error: unknown) => {
      const message =
        (error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Connexion impossible.'
      setLoginError(message)
    },
  })

  async function login(email: string, password: string) {
    await loginMutation.mutateAsync({ email, password })
  }

  function logout() {
    localStorage.removeItem(TOKEN_KEY)
    setHasToken(false)
    queryClient.clear()
  }

  function hasRole(role: string) {
    return user?.roles.includes(role) ?? false
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading: hasToken && isLoading,
        isAuthenticated: hasToken && Boolean(user),
        hasRole,
        login,
        loginError,
        isLoggingIn: loginMutation.isPending,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
