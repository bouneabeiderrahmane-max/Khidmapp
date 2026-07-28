import { useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Card, ErrorBanner, LoadingBlock, Pill, TableWrap, extractErrorMessage } from '../components/ui'

type Boutique = {
  id: number
  name: string
  slug: string
  base_url: string
  country_code: string
  currency_code: string
  status: string
  status_label: string
}

const STATUSES = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'en_pause', label: 'En pause' },
  { value: 'en_test', label: 'En test' },
]

function statusTone(status: string): 'ok' | 'warn' | 'neutral' {
  if (status === 'active') return 'ok'
  if (status === 'en_test' || status === 'en_pause') return 'warn'
  return 'neutral'
}

export function BoutiquesPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [rowError, setRowError] = useState<string | null>(null)

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-boutiques'],
    queryFn: async () => (await api.get<{ data: Boutique[] }>('/admin/boutiques', { params: { per_page: 50 } })).data.data,
  })

  const updateStatus = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) =>
      api.patch(`/admin/boutiques/${id}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-boutiques'] }),
    onError: (err) => setRowError(extractErrorMessage(err, 'Changement de statut impossible.')),
  })

  const createBoutique = useMutation({
    mutationFn: async (payload: Record<string, string>) => api.post('/admin/boutiques', payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-boutiques'] })
      setShowForm(false)
      setFormError(null)
    },
    onError: (err) => setFormError(extractErrorMessage(err, "Création impossible.")),
  })

  const deleteBoutique = useMutation({
    mutationFn: async (id: number) => api.delete(`/admin/boutiques/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-boutiques'] }),
    onError: (err) => setRowError(extractErrorMessage(err, 'Suppression impossible.')),
  })

  function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    createBoutique.mutate({
      name: String(form.get('name')),
      base_url: String(form.get('base_url')),
      country_code: String(form.get('country_code')),
      currency_code: String(form.get('currency_code')),
    })
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">Boutiques</h1>
        <button
          onClick={() => setShowForm((v) => !v)}
          className="text-sm border rounded px-3 py-1.5 hover:bg-gray-50"
        >
          {showForm ? 'Annuler' : 'Nouvelle boutique'}
        </button>
      </div>

      {showForm && (
        <Card className="p-4">
          <form onSubmit={handleCreate} className="grid grid-cols-2 gap-3 text-sm">
            <label className="flex flex-col gap-1">
              <span className="text-gray-600">Nom</span>
              <input name="name" required className="border rounded px-2 py-1" />
            </label>
            <label className="flex flex-col gap-1">
              <span className="text-gray-600">URL de la boutique</span>
              <input name="base_url" type="url" required placeholder="https://…" className="border rounded px-2 py-1" />
            </label>
            <label className="flex flex-col gap-1">
              <span className="text-gray-600">Code pays (ex. ES)</span>
              <input name="country_code" required maxLength={2} className="border rounded px-2 py-1 uppercase" />
            </label>
            <label className="flex flex-col gap-1">
              <span className="text-gray-600">Devise (ex. EUR)</span>
              <input name="currency_code" required maxLength={3} className="border rounded px-2 py-1 uppercase" />
            </label>
            {formError && (
              <div className="col-span-2">
                <ErrorBanner message={formError} />
              </div>
            )}
            <div className="col-span-2">
              <button
                type="submit"
                disabled={createBoutique.isPending}
                className="bg-gray-900 text-white rounded px-3 py-1.5 text-sm disabled:opacity-50"
              >
                Créer
              </button>
            </div>
          </form>
        </Card>
      )}

      {rowError && <ErrorBanner message={rowError} />}
      {isLoading && <LoadingBlock />}
      {isError && <ErrorBanner message={extractErrorMessage(error, 'Impossible de charger les boutiques.')} />}

      {data && (
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Boutique</th>
                <th className="text-start px-3 py-2">Pays</th>
                <th className="text-start px-3 py-2">Statut</th>
                <th className="text-start px-3 py-2">Changer le statut</th>
                <th className="text-end px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.map((b) => (
                <tr key={b.id} className="border-t">
                  <td className="px-3 py-2">{b.name}</td>
                  <td className="px-3 py-2">{b.country_code}</td>
                  <td className="px-3 py-2">
                    <Pill tone={statusTone(b.status)}>{b.status_label}</Pill>
                  </td>
                  <td className="px-3 py-2">
                    <select
                      value={b.status}
                      onChange={(e) => updateStatus.mutate({ id: b.id, status: e.target.value })}
                      className="border rounded px-2 py-1 text-sm"
                    >
                      {STATUSES.map((s) => (
                        <option key={s.value} value={s.value}>
                          {s.label}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="px-3 py-2 text-end">
                    <button
                      onClick={() => deleteBoutique.mutate(b.id)}
                      className="text-red-600 hover:underline text-xs"
                    >
                      Supprimer
                    </button>
                  </td>
                </tr>
              ))}
              {data.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-3 py-4 text-center text-gray-400">
                    Aucune boutique
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
