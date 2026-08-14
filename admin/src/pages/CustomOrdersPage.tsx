import { Fragment, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Card, ErrorBanner, LoadingBlock, Pill, TableWrap, extractErrorMessage } from '../components/ui'

type Boutique = {
  id: number
  name: string
} | null

type CustomOrderItem = {
  id: number
  boutique: Boutique
  product_url: string
  quantity: number
  estimated_price_eur: string | number
  notes: string | null
}

type CustomOrderRequest = {
  id: number
  status: string
  status_label: string
  stage: string | null
  stage_label: string | null
  address: { label: string; city: string; area: string | null; phone: string } | null
  payment_method: string | null
  admin_note: string | null
  reviewed_at: string | null
  order?: { id: number; status: string; total_mru: string | number } | null
  items: CustomOrderItem[]
  created_at: string
}

const STATUS_FILTERS = [
  { value: 'en_attente', label: 'En attente' },
  { value: '', label: 'Toutes' },
  { value: 'confirmee', label: 'Confirmée' },
  { value: 'rejetee', label: 'Rejetée' },
]

function statusTone(status: string): 'ok' | 'warn' | 'crit' | 'neutral' {
  if (status === 'confirmee') return 'ok'
  if (status === 'rejetee') return 'crit'
  return 'warn'
}

export function CustomOrdersPage() {
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('en_attente')
  const [openRejectFor, setOpenRejectFor] = useState<number | null>(null)
  const [reason, setReason] = useState('')
  const [rowError, setRowError] = useState<string | null>(null)

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-custom-orders', status],
    queryFn: async () =>
      (
        await api.get<{ data: CustomOrderRequest[] }>('/admin/custom-order-requests', {
          params: { per_page: 50, ...(status ? { status } : {}) },
        })
      ).data.data,
  })

  function invalidate() {
    queryClient.invalidateQueries({ queryKey: ['admin-custom-orders'] })
  }

  const approve = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/custom-order-requests/${id}/approve`),
    onSuccess: invalidate,
    onError: (err) => setRowError(extractErrorMessage(err, 'Confirmation impossible.')),
  })

  const reject = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
      api.post(`/admin/custom-order-requests/${id}/reject`, { reason }),
    onSuccess: () => {
      invalidate()
      setOpenRejectFor(null)
      setReason('')
    },
    onError: (err) => setRowError(extractErrorMessage(err, 'Rejet impossible.')),
  })

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-xl font-semibold">Demandes de produits personnalisés</h1>

      <Card className="p-4">
        <label className="flex items-center gap-2 text-sm">
          <span className="text-gray-600">Statut</span>
          <select value={status} onChange={(e) => setStatus(e.target.value)} className="border rounded px-2 py-1">
            {STATUS_FILTERS.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
        </label>
      </Card>

      {rowError && <ErrorBanner message={rowError} />}
      {isLoading && <LoadingBlock />}
      {isError && <ErrorBanner message={extractErrorMessage(error, 'Impossible de charger les demandes.')} />}

      {data && (
        <div className="flex flex-col gap-3">
          {data.length === 0 && (
            <Card className="p-6 text-center text-sm text-gray-400">Aucune demande</Card>
          )}

          {data.map((r) => (
            <Card key={r.id} className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span className="font-medium">Demande #{r.id}</span>
                  <Pill tone={statusTone(r.status)}>{r.status_label}</Pill>
                  {r.stage_label && <span className="text-xs text-gray-500">Étape : {r.stage_label}</span>}
                  {r.order && (
                    <span className="text-xs text-gray-500">→ Commande #{r.order.id}</span>
                  )}
                </div>
                <span className="text-xs text-gray-400">{new Date(r.created_at).toLocaleString('fr-FR')}</span>
              </div>

              {r.address && (
                <div className="mt-2 text-xs text-gray-500">
                  Livraison : {r.address.label}, {r.address.city}
                  {r.address.area ? `, ${r.address.area}` : ''} · Paiement : {r.payment_method ?? '—'}
                </div>
              )}

              <div className="mt-3">
                <TableWrap>
                  <table className="w-full text-sm">
                    <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
                      <tr>
                        <th className="text-start px-3 py-2">Boutique</th>
                        <th className="text-start px-3 py-2">Lien produit</th>
                        <th className="text-end px-3 py-2">Qté</th>
                        <th className="text-end px-3 py-2">Prix estimé</th>
                        <th className="text-start px-3 py-2">Notes</th>
                      </tr>
                    </thead>
                    <tbody>
                      {r.items.map((item) => (
                        <tr key={item.id} className="border-t">
                          <td className="px-3 py-2">{item.boutique?.name ?? '—'}</td>
                          <td className="px-3 py-2 max-w-xs truncate">
                            <a href={item.product_url} target="_blank" rel="noreferrer" className="underline">
                              {item.product_url}
                            </a>
                          </td>
                          <td className="px-3 py-2 text-end tabular-nums">{item.quantity}</td>
                          <td className="px-3 py-2 text-end tabular-nums">{item.estimated_price_eur} €</td>
                          <td className="px-3 py-2 text-gray-500">{item.notes ?? '—'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </TableWrap>
              </div>

              {r.admin_note && (
                <div className="mt-2 text-xs text-gray-500">Note admin : {r.admin_note}</div>
              )}

              {r.status === 'en_attente' && (
                <Fragment>
                  <div className="mt-3 flex gap-3 text-sm">
                    <button
                      onClick={() => approve.mutate(r.id)}
                      disabled={approve.isPending}
                      className="text-emerald-700 hover:underline disabled:opacity-50"
                    >
                      Confirmer et créer la commande
                    </button>
                    <button
                      onClick={() => setOpenRejectFor(r.id === openRejectFor ? null : r.id)}
                      className="text-red-600 hover:underline"
                    >
                      Rejeter
                    </button>
                  </div>
                  {openRejectFor === r.id && (
                    <form
                      onSubmit={(e) => {
                        e.preventDefault()
                        reject.mutate({ id: r.id, reason })
                      }}
                      className="mt-2 flex gap-2 items-center"
                    >
                      <input
                        required
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder="Motif du rejet"
                        className="border rounded px-2 py-1 text-sm flex-1"
                      />
                      <button type="submit" className="text-sm bg-gray-900 text-white rounded px-3 py-1">
                        Confirmer le rejet
                      </button>
                    </form>
                  )}
                </Fragment>
              )}
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
