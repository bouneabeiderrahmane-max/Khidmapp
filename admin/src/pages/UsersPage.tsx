import { Fragment, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Card, ErrorBanner, LoadingBlock, Pill, TableWrap, extractErrorMessage } from '../components/ui'

type AdminUser = {
  id: number
  name: string
  phone: string | null
  email: string | null
  roles: string[]
  is_blocked: boolean
  blocked_reason: string | null
}

export function UsersPage() {
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [blockingId, setBlockingId] = useState<number | null>(null)
  const [reason, setReason] = useState('')
  const [rowError, setRowError] = useState<string | null>(null)

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-users', search, role],
    queryFn: async () =>
      (
        await api.get<{ data: AdminUser[] }>('/admin/users', {
          params: { per_page: 50, ...(search ? { search } : {}), ...(role ? { role } : {}) },
        })
      ).data.data,
  })

  function invalidate() {
    queryClient.invalidateQueries({ queryKey: ['admin-users'] })
  }

  const block = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
      api.post(`/admin/users/${id}/block`, { reason }),
    onSuccess: () => {
      invalidate()
      setBlockingId(null)
      setReason('')
    },
    onError: (err) => setRowError(extractErrorMessage(err, 'Blocage impossible.')),
  })

  const unblock = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/users/${id}/unblock`),
    onSuccess: invalidate,
    onError: (err) => setRowError(extractErrorMessage(err, 'Déblocage impossible.')),
  })

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-xl font-semibold">Utilisateurs</h1>

      <Card className="p-4">
        <div className="flex flex-wrap gap-3 items-end text-sm">
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Recherche</span>
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Nom, e-mail…"
              className="border rounded px-2 py-1"
            />
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Rôle</span>
            <select value={role} onChange={(e) => setRole(e.target.value)} className="border rounded px-2 py-1">
              <option value="">Tous</option>
              <option value="client">Client</option>
              <option value="service_client">Service client</option>
              <option value="administrateur">Administrateur</option>
            </select>
          </label>
        </div>
      </Card>

      {rowError && <ErrorBanner message={rowError} />}
      {isLoading && <LoadingBlock />}
      {isError && <ErrorBanner message={extractErrorMessage(error, 'Impossible de charger les utilisateurs.')} />}

      {data && (
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Nom</th>
                <th className="text-start px-3 py-2">Contact</th>
                <th className="text-start px-3 py-2">Rôles</th>
                <th className="text-start px-3 py-2">Statut</th>
                <th className="text-end px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.map((u) => (
                <Fragment key={u.id}>
                  <tr className="border-t">
                    <td className="px-3 py-2">{u.name}</td>
                    <td className="px-3 py-2 text-gray-500">{u.email ?? u.phone ?? '—'}</td>
                    <td className="px-3 py-2">{u.roles.join(', ')}</td>
                    <td className="px-3 py-2">
                      {u.is_blocked ? <Pill tone="crit">Bloqué</Pill> : <Pill tone="ok">Actif</Pill>}
                    </td>
                    <td className="px-3 py-2 text-end">
                      {u.is_blocked ? (
                        <button onClick={() => unblock.mutate(u.id)} className="text-emerald-700 hover:underline text-xs">
                          Débloquer
                        </button>
                      ) : (
                        <button
                          onClick={() => setBlockingId(u.id)}
                          className="text-red-600 hover:underline text-xs"
                        >
                          Bloquer
                        </button>
                      )}
                    </td>
                  </tr>
                  {blockingId === u.id && (
                    <tr className="border-t bg-gray-50">
                      <td colSpan={5} className="px-3 py-3">
                        <form
                          onSubmit={(e) => {
                            e.preventDefault()
                            block.mutate({ id: u.id, reason })
                          }}
                          className="flex gap-2 items-center"
                        >
                          <input
                            required
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Motif du blocage"
                            className="border rounded px-2 py-1 text-sm flex-1"
                          />
                          <button type="submit" className="text-sm bg-gray-900 text-white rounded px-3 py-1">
                            Confirmer le blocage
                          </button>
                        </form>
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
              {data.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-3 py-4 text-center text-gray-400">
                    Aucun utilisateur
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </TableWrap>
      )}
    </div>
  )
}
