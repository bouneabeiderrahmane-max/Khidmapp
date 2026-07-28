import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { api, TOKEN_KEY } from '../lib/api'
import { ROLES, useAuth } from '../lib/auth'
import { Card, ErrorBanner, LoadingBlock, StatTile, TableWrap, extractErrorMessage } from '../components/ui'

type SalesRow = { boutique_id?: number; boutique_name?: string; zone?: string; revenue_mru?: number; order_count: number }
type ProductRow = { product_variant_id: number | null; product_name: Record<string, string> | string | null; quantity_sold: number; revenue_mru?: number }

type DashboardReport = {
  period: { from: string | null; to: string | null }
  filters: { boutique_id: number | null; zone: string | null }
  ca_mru?: number
  order_count: number
  average_basket_mru?: number
  margin_realized_mru?: number
  sales_by_boutique: SalesRow[]
  sales_by_zone: SalesRow[]
  top_products: ProductRow[]
  orders_in_progress: number
  orders_delivered: number
}

function productLabel(name: ProductRow['product_name']): string {
  if (!name) return '—'
  if (typeof name === 'string') return name
  return name.fr ?? Object.values(name)[0] ?? '—'
}

function mru(value: number | undefined): string {
  if (value === undefined) return '—'
  return `${value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MRU`
}

export function DashboardPage() {
  const { t } = useTranslation()
  const { hasRole } = useAuth()
  const isAdministrateur = hasRole(ROLES.ADMINISTRATEUR)

  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [boutiqueId, setBoutiqueId] = useState('')
  const [zone, setZone] = useState('')

  const params = {
    ...(from ? { from } : {}),
    ...(to ? { to } : {}),
    ...(boutiqueId ? { boutique_id: boutiqueId } : {}),
    ...(zone ? { zone } : {}),
  }

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['dashboard-report', params],
    queryFn: async () => (await api.get<{ data: DashboardReport }>('/admin/dashboard/report', { params })).data.data,
  })

  const { data: boutiques } = useQuery({
    queryKey: ['boutiques-for-filter'],
    queryFn: async () => (await api.get<{ data: { id: number; name: string }[] }>('/boutiques')).data.data,
  })

  function exportCsv() {
    const token = localStorage.getItem(TOKEN_KEY)
    const query = new URLSearchParams(params).toString()
    const url = `${api.defaults.baseURL}/admin/dashboard/report.csv${query ? `?${query}` : ''}`
    fetch(url, { headers: { Authorization: `Bearer ${token}` } })
      .then((res) => res.blob())
      .then((blob) => {
        const link = document.createElement('a')
        link.href = URL.createObjectURL(blob)
        link.download = `khidmapp-rapport-${new Date().toISOString().slice(0, 10)}.csv`
        link.click()
        URL.revokeObjectURL(link.href)
      })
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{t('nav.dashboard')}</h1>
        {isAdministrateur && (
          <button onClick={exportCsv} className="text-sm border rounded px-3 py-1.5 hover:bg-gray-50">
            Exporter en CSV
          </button>
        )}
      </div>

      <Card className="p-4">
        <div className="flex flex-wrap gap-3 items-end text-sm">
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Du</span>
            <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="border rounded px-2 py-1" />
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Au</span>
            <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="border rounded px-2 py-1" />
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Boutique</span>
            <select value={boutiqueId} onChange={(e) => setBoutiqueId(e.target.value)} className="border rounded px-2 py-1 min-w-40">
              <option value="">Toutes</option>
              {boutiques?.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </select>
          </label>
          <label className="flex flex-col gap-1">
            <span className="text-gray-600">Zone</span>
            <input
              type="text"
              placeholder="ex. nouakchott"
              value={zone}
              onChange={(e) => setZone(e.target.value)}
              className="border rounded px-2 py-1"
            />
          </label>
        </div>
      </Card>

      {isLoading && <LoadingBlock />}
      {isError && <ErrorBanner message={extractErrorMessage(error, 'Impossible de charger le tableau de bord.')} />}

      {data && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {data.ca_mru !== undefined && <StatTile label="Chiffre d'affaires" value={mru(data.ca_mru)} />}
            <StatTile label="Commandes" value={data.order_count} />
            {data.average_basket_mru !== undefined && <StatTile label="Panier moyen" value={mru(data.average_basket_mru)} />}
            {data.margin_realized_mru !== undefined && <StatTile label="Marge réalisée (brute)" value={mru(data.margin_realized_mru)} />}
            <StatTile label="En cours" value={data.orders_in_progress} />
            <StatTile label="Livrées" value={data.orders_delivered} />
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <h2 className="text-sm font-semibold text-gray-700 mb-2">Ventes par boutique</h2>
              <TableWrap>
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                      <th className="text-start px-3 py-2">Boutique</th>
                      {data.sales_by_boutique.some((r) => r.revenue_mru !== undefined) && (
                        <th className="text-end px-3 py-2">CA</th>
                      )}
                      <th className="text-end px-3 py-2">Commandes</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.sales_by_boutique.length === 0 && (
                      <tr>
                        <td colSpan={3} className="px-3 py-4 text-center text-gray-400">
                          Aucune donnée
                        </td>
                      </tr>
                    )}
                    {data.sales_by_boutique.map((row) => (
                      <tr key={row.boutique_id ?? 'inconnue'} className="border-t">
                        <td className="px-3 py-2">{row.boutique_name ?? '—'}</td>
                        {row.revenue_mru !== undefined && <td className="px-3 py-2 text-end tabular-nums">{mru(row.revenue_mru)}</td>}
                        <td className="px-3 py-2 text-end tabular-nums">{row.order_count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </TableWrap>
            </div>

            <div>
              <h2 className="text-sm font-semibold text-gray-700 mb-2">Ventes par zone</h2>
              <TableWrap>
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                      <th className="text-start px-3 py-2">Zone</th>
                      {data.sales_by_zone.some((r) => r.revenue_mru !== undefined) && <th className="text-end px-3 py-2">CA</th>}
                      <th className="text-end px-3 py-2">Commandes</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.sales_by_zone.length === 0 && (
                      <tr>
                        <td colSpan={3} className="px-3 py-4 text-center text-gray-400">
                          Aucune donnée
                        </td>
                      </tr>
                    )}
                    {data.sales_by_zone.map((row) => (
                      <tr key={row.zone ?? 'inconnue'} className="border-t">
                        <td className="px-3 py-2">{row.zone ?? '—'}</td>
                        {row.revenue_mru !== undefined && <td className="px-3 py-2 text-end tabular-nums">{mru(row.revenue_mru)}</td>}
                        <td className="px-3 py-2 text-end tabular-nums">{row.order_count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </TableWrap>
            </div>
          </div>

          <div>
            <h2 className="text-sm font-semibold text-gray-700 mb-2">Produits les plus vendus</h2>
            <TableWrap>
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
                  <tr>
                    <th className="text-start px-3 py-2">Produit</th>
                    <th className="text-end px-3 py-2">Quantité</th>
                    {data.top_products.some((r) => r.revenue_mru !== undefined) && <th className="text-end px-3 py-2">CA</th>}
                  </tr>
                </thead>
                <tbody>
                  {data.top_products.length === 0 && (
                    <tr>
                      <td colSpan={3} className="px-3 py-4 text-center text-gray-400">
                        Aucune donnée
                      </td>
                    </tr>
                  )}
                  {data.top_products.map((row) => (
                    <tr key={row.product_variant_id ?? 'inconnu'} className="border-t">
                      <td className="px-3 py-2">{productLabel(row.product_name)}</td>
                      <td className="px-3 py-2 text-end tabular-nums">{row.quantity_sold}</td>
                      {row.revenue_mru !== undefined && <td className="px-3 py-2 text-end tabular-nums">{mru(row.revenue_mru)}</td>}
                    </tr>
                  ))}
                </tbody>
              </table>
            </TableWrap>
          </div>
        </>
      )}
    </div>
  )
}
