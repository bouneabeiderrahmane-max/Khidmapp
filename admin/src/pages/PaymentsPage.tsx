import { Fragment, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '../lib/api'
import { Card, ErrorBanner, LoadingBlock, Pill, TableWrap, extractErrorMessage } from '../components/ui'

type Payment = {
  id: number
  order_id: number
  method_label: string
  status: string
  status_label: string
  amount_mru: string | number
  rejection_reason: string | null
  info_requested_note: string | null
  proof_download_url: string | null
  initiated_at: string
}

const STATUS_FILTERS = [
  { value: '', label: 'Tous' },
  { value: 'pending', label: 'En attente' },
  { value: 'validated', label: 'Validé' },
  { value: 'rejected', label: 'Refusé' },
  { value: 'info_requested', label: 'Complément demandé' },
]

function statusTone(status: string): 'ok' | 'warn' | 'crit' | 'neutral' {
  if (status === 'validated') return 'ok'
  if (status === 'pending' || status === 'info_requested') return 'warn'
  if (status === 'rejected') return 'crit'
  return 'neutral'
}

export function PaymentsPage() {
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('pending')
  const [openReasonFor, setOpenReasonFor] = useState<number | null>(null)
  const [openNoteFor, setOpenNoteFor] = useState<number | null>(null)
  const [reason, setReason] = useState('')
  const [note, setNote] = useState('')
  const [rowError, setRowError] = useState<string | null>(null)

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['admin-payments', status],
    queryFn: async () =>
      (
        await api.get<{ data: Payment[] }>('/admin/payments/manual', {
          params: { per_page: 50, ...(status ? { status } : {}) },
        })
      ).data.data,
  })

  function invalidate() {
    queryClient.invalidateQueries({ queryKey: ['admin-payments'] })
  }

  const validate = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/payments/${id}/validate`),
    onSuccess: invalidate,
    onError: (err) => setRowError(extractErrorMessage(err, 'Validation impossible.')),
  })

  const reject = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
      api.post(`/admin/payments/${id}/reject`, { reason }),
    onSuccess: () => {
      invalidate()
      setOpenReasonFor(null)
      setReason('')
    },
    onError: (err) => setRowError(extractErrorMessage(err, 'Refus impossible.')),
  })

  const requestInfo = useMutation({
    mutationFn: async ({ id, note }: { id: number; note: string }) =>
      api.post(`/admin/payments/${id}/request-info`, { note }),
    onSuccess: () => {
      invalidate()
      setOpenNoteFor(null)
      setNote('')
    },
    onError: (err) => setRowError(extractErrorMessage(err, 'Envoi impossible.')),
  })

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-xl font-semibold">Paiements manuels</h1>

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
      {isError && <ErrorBanner message={extractErrorMessage(error, 'Impossible de charger les paiements.')} />}

      {data && (
        <TableWrap>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-600 text-xs uppercase">
              <tr>
                <th className="text-start px-3 py-2">Commande</th>
                <th className="text-start px-3 py-2">Statut</th>
                <th className="text-end px-3 py-2">Montant</th>
                <th className="text-start px-3 py-2">Preuve</th>
                <th className="text-end px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.map((p) => (
                <Fragment key={p.id}>
                  <tr className="border-t">
                    <td className="px-3 py-2">#{p.order_id}</td>
                    <td className="px-3 py-2">
                      <Pill tone={statusTone(p.status)}>{p.status_label}</Pill>
                    </td>
                    <td className="px-3 py-2 text-end tabular-nums">{p.amount_mru} MRU</td>
                    <td className="px-3 py-2">
                      {p.proof_download_url ? (
                        <a href={p.proof_download_url} target="_blank" rel="noreferrer" className="underline">
                          Voir la preuve
                        </a>
                      ) : (
                        '—'
                      )}
                    </td>
                    <td className="px-3 py-2 text-end">
                      {p.status === 'pending' && (
                        <div className="flex justify-end gap-3 text-xs">
                          <button onClick={() => validate.mutate(p.id)} className="text-emerald-700 hover:underline">
                            Valider
                          </button>
                          <button
                            onClick={() => {
                              setOpenReasonFor(p.id)
                              setOpenNoteFor(null)
                            }}
                            className="text-red-600 hover:underline"
                          >
                            Refuser
                          </button>
                          <button
                            onClick={() => {
                              setOpenNoteFor(p.id)
                              setOpenReasonFor(null)
                            }}
                            className="text-gray-600 hover:underline"
                          >
                            Demander un complément
                          </button>
                        </div>
                      )}
                    </td>
                  </tr>
                  {openReasonFor === p.id && (
                    <tr className="border-t bg-gray-50">
                      <td colSpan={5} className="px-3 py-3">
                        <form
                          onSubmit={(e) => {
                            e.preventDefault()
                            reject.mutate({ id: p.id, reason })
                          }}
                          className="flex gap-2 items-center"
                        >
                          <input
                            required
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Motif du refus"
                            className="border rounded px-2 py-1 text-sm flex-1"
                          />
                          <button type="submit" className="text-sm bg-gray-900 text-white rounded px-3 py-1">
                            Confirmer le refus
                          </button>
                        </form>
                      </td>
                    </tr>
                  )}
                  {openNoteFor === p.id && (
                    <tr className="border-t bg-gray-50">
                      <td colSpan={5} className="px-3 py-3">
                        <form
                          onSubmit={(e) => {
                            e.preventDefault()
                            requestInfo.mutate({ id: p.id, note })
                          }}
                          className="flex gap-2 items-center"
                        >
                          <input
                            required
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            placeholder="Complément demandé au client"
                            className="border rounded px-2 py-1 text-sm flex-1"
                          />
                          <button type="submit" className="text-sm bg-gray-900 text-white rounded px-3 py-1">
                            Envoyer
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
                    Aucun paiement
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
