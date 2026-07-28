import { useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Card, ErrorBanner, LoadingBlock, TableWrap, extractErrorMessage } from '../components/ui'

type AdminUser = { id: number; name: string; email: string | null; roles: string[] }

export function RolesPage() {
  const queryClient = useQueryClient()
  const [formError, setFormError] = useState<string | null>(null)
  const [rowError, setRowError] = useState<string | null>(null)

  const { data: serviceClients, isLoading: loadingSc } = useQuery({
    queryKey: ['internal-users', 'service_client'],
    queryFn: async () =>
      (await api.get<{ data: AdminUser[] }>('/admin/users', { params: { role: 'service_client', per_page: 50 } })).data
        .data,
  })
  const { data: administrateurs, isLoading: loadingAdmin } = useQuery({
    queryKey: ['internal-users', 'administrateur'],
    queryFn: async () =>
      (await api.get<{ data: AdminUser[] }>('/admin/users', { params: { role: 'administrateur', per_page: 50 } })).data
        .data,
  })

  const internalUsers = [...(serviceClients ?? []), ...(administrateurs ?? [])]

  function invalidate() {
    queryClient.invalidateQueries({ queryKey: ['internal-users'] })
  }

  const createUser = useMutation({
    mutationFn: async (payload: Record<string, string>) => api.post('/admin/users', payload),
    onSuccess: () => {
      invalidate()
      setFormError(null)
    },
    onError: (err) => setFormError(extractErrorMessage(err, 'Création impossible.')),
  })

  const updateRoles = useMutation({
    mutationFn: async ({ id, role }: { id: number; role: string }) => api.put(`/admin/users/${id}/roles`, { roles: [role] }),
    onSuccess: invalidate,
    onError: (err) => setRowError(extractErrorMessage(err, 'Changement de rôle impossible.')),
  })

  function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    createUser.mutate({
      name: String(form.get('name')),
      email: String(form.get('email')),
      password: String(form.get('password')),
      role: String(form.get('role')),
    })
    event.currentTarget.reset()
  }

  return (
    <div className="flex flex-col gap-6">
      <h1 className="text-xl font-semibold">Rôles et permissions</h1>

      <Card className="p-4">
        <h2 className="text-sm font-semibold text-gray-700 mb-3">Créer un compte interne</h2>
        <form onSubmit={handleCreate} className="grid grid-cols-2 gap-3 text-sm">
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Nom</span>
            <input name="name" required className="border rounded px-2 py-1" />
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">E-mail</span>
            <input name="email" type="email" required className="border rounded px-2 py-1" />
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Mot de passe</span>
            <input name="password" type="password" required minLength={8} className="border rounded px-2 py-1" />
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Rôle</span>
            <select name="role" required className="border rounded px-2 py-1">
              <option value="service_client">Service client</option>
              <option value="administrateur">Administrateur</option>
            </select>
          </label>
          {formError && (
            <div className="col-span-2">
              <ErrorBanner message={formError} />
            </div>
          )}
          <div className="col-span-2">
            <button
              type="submit"
              disabled={createUser.isPending}
              className="bg-gray-900 text-white rounded px-3 py-1.5 disabled:opacity-50"
            >
              Créer
            </button>
          </div>
        </form>
      </Card>

      {rowError && <ErrorBanner message={rowError} />}
      {(loadingSc || loadingAdmin) && <LoadingBlock />}

      {internalUsers.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-gray-700 mb-2">Comptes internes</h2>
          <TableWrap>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                  <th className="text-start px-3 py-2">Nom</th>
                  <th className="text-start px-3 py-2">E-mail</th>
                  <th className="text-start px-3 py-2">Rôle actuel</th>
                  <th className="text-start px-3 py-2">Changer le rôle</th>
                </tr>
              </thead>
              <tbody>
                {internalUsers.map((u) => (
                  <tr key={u.id} className="border-t">
                    <td className="px-3 py-2">{u.name}</td>
                    <td className="px-3 py-2 text-gray-500">{u.email ?? '—'}</td>
                    <td className="px-3 py-2">{u.roles.join(', ')}</td>
                    <td className="px-3 py-2">
                      <select
                        defaultValue={u.roles[0]}
                        onChange={(e) => updateRoles.mutate({ id: u.id, role: e.target.value })}
                        className="border rounded px-2 py-1"
                      >
                        <option value="service_client">Service client</option>
                        <option value="administrateur">Administrateur</option>
                      </select>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </TableWrap>
        </div>
      )}
    </div>
  )
}
