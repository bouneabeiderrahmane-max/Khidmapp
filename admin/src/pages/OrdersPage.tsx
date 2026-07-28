import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { api } from '../lib/api'
import { ORDER_STATUSES, statusTone } from '../lib/orderStatus'
import { Card, ErrorBanner, LoadingBlock, Pill, TableWrap, extractErrorMessage } from '../components/ui'

type OrderRow = {
  id: number
  status: string
  status_label: string
  total_mru: string | number
  payment_method: string | null
  is_manual_order: boolean
  created_at: string
}

export function OrdersPage() {
  const [status, setStatus] = useState('')

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-orders', status],
    queryFn: async () =>
      (
        await api.get<{ data: OrderRow[] }>('/admin/orders', {
          params: { per_page: 50, ...(status ? { status } : {}) },
        })
      ).data.data,
  })

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-xl font-semibold">Commandes</h1>

      <Card className="p-4">
        <label className="flex items-center gap-2 text-sm">
          <span className="text-gray-600">Statut</span>
          <select value={status} onChange={(e) => setStatus(e.target.value)} className="border rounded px-2 py-1">
            <option value="">Tous</option>
            {ORDER_STATUSES.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
        </label>
      </Card>

      {isLoading && <LoadingBlock />}
      {isError && <ErrorBanner message={extractErrorMessage(error, 'Impossible de charger les commandes.')} />}

      {data && (
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Commande</th>
                <th className="text-start px-3 py-2">Statut</th>
                <th className="text-start px-3 py-2">Paiement</th>
                <th className="text-end px-3 py-2">Total</th>
                <th className="text-start px-3 py-2">Créée le</th>
              </tr>
            </thead>
            <tbody>
              {data.map((order) => (
                <tr key={order.id} className="border-t hover:bg-gray-50">
                  <td className="px-3 py-2">
                    <Link to={`/orders/${order.id}`} className="text-gray-900 underline">
                      #{order.id}
                    </Link>
                    {order.is_manual_order && <span className="ms-2 text-xs text-gray-400">(manuelle)</span>}
                  </td>
                  <td className="px-3 py-2">
                    <Pill tone={statusTone(order.status)}>{order.status_label}</Pill>
                  </td>
                  <td className="px-3 py-2">{order.payment_method ?? '—'}</td>
                  <td className="px-3 py-2 text-end tabular-nums">{order.total_mru} MRU</td>
                  <td className="px-3 py-2">{new Date(order.created_at).toLocaleString('fr-FR')}</td>
                </tr>
              ))}
              {data.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-3 py-4 text-center text-gray-400">
                    Aucune commande
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
