import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Card, ErrorBanner, LoadingBlock, TableWrap, extractErrorMessage } from '../components/ui'

type AuditLog = {
  id: number
  action: string
  actor: { id: number; name: string } | null
  subject_type: string | null
  subject_id: number | null
  changes: Record<string, unknown> | null
  created_at: string
}

const ACTIONS = [
  { value: '', label: 'Toutes' },
  { value: 'margin_rule.created', label: 'Marge créée' },
  { value: 'exchange_rate.created', label: 'Taux de change créé' },
  { value: 'payment.validated', label: 'Paiement validé' },
  { value: 'payment.rejected', label: 'Paiement refusé' },
  { value: 'payment.info_requested', label: 'Complément demandé (paiement)' },
  { value: 'user.role_assigned', label: 'Rôle attribué' },
  { value: 'user.blocked', label: 'Compte bloqué' },
  { value: 'user.unblocked', label: 'Compte débloqué' },
  { value: 'user.created', label: 'Compte interne créé' },
]

function subjectLabel(type: string | null): string {
  if (!type) return '—'
  return type.replace('App\\Models\\', '')
}

export function ReportsPage() {
  const [action, setAction] = useState('')

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['audit-logs', action],
    queryFn: async () =>
      (
        await api.get<{ data: AuditLog[] }>('/admin/audit-logs', {
          params: { per_page: 50, ...(action ? { action } : {}) },
        })
      ).data.data,
  })

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-xl font-semibold">Rapports — Journal d'audit</h1>
      <p className="text-sm text-gray-500 -mt-2">
        Actions sensibles tracées : validation de paiement, modification de marge/taux, changement de rôle, blocage de
        compte. Pour le chiffre d'affaires et l'export CSV, voir le Tableau de bord.
      </p>

      <Card className="p-4">
        <label className="flex items-center gap-2 text-sm">
          <span className="text-gray-600">Action</span>
          <select value={action} onChange={(e) => setAction(e.target.value)} className="border rounded px-2 py-1 min-w-64">
            {ACTIONS.map((a) => (
              <option key={a.value} value={a.value}>
                {a.label}
              </option>
            ))}
          </select>
        </label>
      </Card>

      {isLoading && <LoadingBlock />}
      {isError && <ErrorBanner message={extractErrorMessage(error, "Impossible de charger le journal d'audit.")} />}

      {data && (
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Action</th>
                <th className="text-start px-3 py-2">Acteur</th>
                <th className="text-start px-3 py-2">Sujet</th>
                <th className="text-start px-3 py-2">Détails</th>
                <th className="text-start px-3 py-2">Date</th>
              </tr>
            </thead>
            <tbody>
              {data.map((log) => (
                <tr key={log.id} className="border-t align-top">
                  <td className="px-3 py-2 font-medium">{ACTIONS.find((a) => a.value === log.action)?.label ?? log.action}</td>
                  <td className="px-3 py-2 text-gray-500">{log.actor?.name ?? '—'}</td>
                  <td className="px-3 py-2 text-gray-500">
                    {subjectLabel(log.subject_type)}
                    {log.subject_id ? ` #${log.subject_id}` : ''}
                  </td>
                  <td className="px-3 py-2 text-gray-500 font-mono text-xs whitespace-pre-wrap">
                    {log.changes ? JSON.stringify(log.changes) : '—'}
                  </td>
                  <td className="px-3 py-2 whitespace-nowrap">{new Date(log.created_at).toLocaleString('fr-FR')}</td>
                </tr>
              ))}
              {data.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-3 py-4 text-center text-gray-400">
                    Aucune entrée
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
