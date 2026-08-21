import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { api } from '../lib/api'
import { ORDER_STATUSES, statusTone } from '../lib/orderStatus'
import { Card, ErrorBanner, LoadingBlock, Pill, TableWrap, extractErrorMessage } from '../components/ui'

type OrderItem = {
  id: number
  product_name: Record<string, string> | string
  size: string | null
  color: string | null
  quantity: number
  unit_price_mru: string | number
  line_subtotal_mru: number
}

type StatusHistoryEntry = {
  from_status: string | null
  to_status: string
  to_status_label: string
  actor_type: string
  note: string | null
  created_at: string
}

type Payment = {
  id: number
  method_label: string
  status_label: string
  amount_mru: string | number
  proof_download_url: string | null
}

type OrderDetail = {
  id: number
  status: string
  status_label: string
  shipping: { label: string; city: string; area: string | null; phone: string }
  payment_method: string | null
  subtotal_mru: string | number
  delivery_fee_mru: string | number
  management_fee_mru: string | number
  delivery_zone: string | null
  total_mru: string | number
  cancellation_reason: string | null
  refund_reason: string | null
  items: OrderItem[]
  status_history: StatusHistoryEntry[]
  payments: Payment[]
  created_at: string
}

function itemName(name: OrderItem['product_name']): string {
  if (typeof name === 'string') return name
  return name.fr ?? Object.values(name)[0] ?? '—'
}

export function OrderDetailPage() {
  const { id } = useParams<{ id: string }>()
  const queryClient = useQueryClient()
  const [nextStatus, setNextStatus] = useState('')
  const [note, setNote] = useState('')
  const [transitionError, setTransitionError] = useState<string | null>(null)

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-order', id],
    queryFn: async () => (await api.get<{ data: OrderDetail }>(`/admin/orders/${id}`)).data.data,
  })

  const transition = useMutation({
    mutationFn: async () => api.patch(`/admin/orders/${id}/status`, { status: nextStatus, ...(note ? { note } : {}) }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-order', id] })
      setTransitionError(null)
      setNote('')
    },
    onError: (err) => setTransitionError(extractErrorMessage(err, 'Transition refusée.')),
  })

  if (isLoading) return <LoadingBlock />
  if (isError || !data) return <ErrorBanner message={extractErrorMessage(error, 'Commande introuvable.')} />

  return (
    <div className="flex flex-col gap-6">
      <div>
        <Link to="/orders" className="text-sm text-gray-500 hover:underline">
          ← Commandes
        </Link>
        <div className="flex items-center gap-3 mt-1">
          <h1 className="text-xl font-semibold">Commande #{data.id}</h1>
          <Pill tone={statusTone(data.status)}>{data.status_label}</Pill>
        </div>
      </div>

      <div className="grid md:grid-cols-2 gap-4">
        <Card className="p-4">
          <h2 className="text-sm font-semibold text-gray-700 mb-3">Livraison</h2>
          <dl className="text-sm grid grid-cols-2 gap-y-1">
            <dt className="text-gray-500">Adresse</dt>
            <dd>{data.shipping.label}</dd>
            <dt className="text-gray-500">Ville</dt>
            <dd>
              {data.shipping.city}
              {data.shipping.area ? `, ${data.shipping.area}` : ''}
            </dd>
            <dt className="text-gray-500">Téléphone</dt>
            <dd>{data.shipping.phone}</dd>
            <dt className="text-gray-500">Zone tarifaire</dt>
            <dd>{data.delivery_zone ?? '—'}</dd>
          </dl>
        </Card>

        <Card className="p-4">
          <h2 className="text-sm font-semibold text-gray-700 mb-3">Montants</h2>
          <dl className="text-sm grid grid-cols-2 gap-y-1">
            <dt className="text-gray-500">Sous-total</dt>
            <dd className="tabular-nums">{data.subtotal_mru} MRU</dd>
            <dt className="text-gray-500">Livraison</dt>
            <dd className="tabular-nums">{data.delivery_fee_mru} MRU</dd>
            <dt className="text-gray-500">Coût de gestion</dt>
            <dd className="tabular-nums">{data.management_fee_mru} MRU</dd>
            <dt className="text-gray-500 font-medium">Total</dt>
            <dd className="tabular-nums font-medium">{data.total_mru} MRU</dd>
            <dt className="text-gray-500">Mode de paiement</dt>
            <dd>{data.payment_method ?? '—'}</dd>
          </dl>
        </Card>
      </div>

      <div>
        <h2 className="text-sm font-semibold text-gray-700 mb-2">Articles</h2>
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Produit</th>
                <th className="text-start px-3 py-2">Taille / Couleur</th>
                <th className="text-end px-3 py-2">Qté</th>
                <th className="text-end px-3 py-2">Sous-total</th>
              </tr>
            </thead>
            <tbody>
              {data.items.map((item) => (
                <tr key={item.id} className="border-t">
                  <td className="px-3 py-2">{itemName(item.product_name)}</td>
                  <td className="px-3 py-2 text-gray-500">
                    {[item.size, item.color].filter(Boolean).join(' / ') || '—'}
                  </td>
                  <td className="px-3 py-2 text-end tabular-nums">{item.quantity}</td>
                  <td className="px-3 py-2 text-end tabular-nums">{item.line_subtotal_mru} MRU</td>
                </tr>
              ))}
            </tbody>
          </table>
        </TableWrap>
      </div>

      {data.payments.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-gray-700 mb-2">Paiements</h2>
          <TableWrap>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                  <th className="text-start px-3 py-2">Méthode</th>
                  <th className="text-start px-3 py-2">Statut</th>
                  <th className="text-end px-3 py-2">Montant</th>
                  <th className="text-end px-3 py-2">Preuve</th>
                </tr>
              </thead>
              <tbody>
                {data.payments.map((p) => (
                  <tr key={p.id} className="border-t">
                    <td className="px-3 py-2">{p.method_label}</td>
                    <td className="px-3 py-2">{p.status_label}</td>
                    <td className="px-3 py-2 text-end tabular-nums">{p.amount_mru} MRU</td>
                    <td className="px-3 py-2 text-end">
                      {p.proof_download_url ? (
                        <a href={p.proof_download_url} target="_blank" rel="noreferrer" className="underline">
                          Voir
                        </a>
                      ) : (
                        '—'
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </TableWrap>
        </div>
      )}

      <div>
        <h2 className="text-sm font-semibold text-gray-700 mb-2">Historique des statuts</h2>
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Statut</th>
                <th className="text-start px-3 py-2">Acteur</th>
                <th className="text-start px-3 py-2">Note</th>
                <th className="text-start px-3 py-2">Date</th>
              </tr>
            </thead>
            <tbody>
              {data.status_history.map((h, i) => (
                <tr key={i} className="border-t">
                  <td className="px-3 py-2">{h.to_status_label}</td>
                  <td className="px-3 py-2 text-gray-500">{h.actor_type}</td>
                  <td className="px-3 py-2 text-gray-500">{h.note ?? '—'}</td>
                  <td className="px-3 py-2">{new Date(h.created_at).toLocaleString('fr-FR')}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </TableWrap>
      </div>

      <Card className="p-4">
        <h2 className="text-sm font-semibold text-gray-700 mb-3">Changer le statut</h2>
        <form
          onSubmit={(e) => {
            e.preventDefault()
            transition.mutate()
          }}
          className="flex flex-wrap items-end gap-3 text-sm"
        >
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Nouveau statut</span>
            <select
              required
              value={nextStatus}
              onChange={(e) => setNextStatus(e.target.value)}
              className="border rounded px-2 py-1 min-w-56"
            >
              <option value="">Choisir…</option>
              {ORDER_STATUSES.map((s) => (
                <option key={s.value} value={s.value}>
                  {s.label}
                </option>
              ))}
            </select>
          </label>
          <label className="flex flex-col gap-1 flex-1 min-w-56">
            <span className="text-gray-600">Motif (requis pour un remboursement ou une annulation tardive)</span>
            <input value={note} onChange={(e) => setNote(e.target.value)} className="border rounded px-2 py-1" />
          </label>
          <button
            type="submit"
            disabled={transition.isPending}
            className="bg-gray-900 text-white rounded px-3 py-1.5 disabled:opacity-50"
          >
            Appliquer
          </button>
        </form>
        {transitionError && (
          <div className="mt-3">
            <ErrorBanner message={transitionError} />
          </div>
        )}
      </Card>
    </div>
  )
}
