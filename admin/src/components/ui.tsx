import type { ReactNode } from 'react'

export function Card({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`bg-white border rounded-lg shadow-sm ${className}`}>{children}</div>
}

export function StatTile({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="bg-white border rounded-lg p-4">
      <div className="text-2xl font-semibold tabular-nums">{value}</div>
      <div className="text-xs text-gray-500 mt-1">{label}</div>
    </div>
  )
}

export function Pill({ tone, children }: { tone: 'ok' | 'warn' | 'neutral' | 'crit'; children: ReactNode }) {
  const tones: Record<string, string> = {
    ok: 'bg-emerald-50 text-emerald-700',
    warn: 'bg-amber-50 text-amber-700',
    crit: 'bg-red-50 text-red-700',
    neutral: 'bg-gray-100 text-gray-700',
  }
  return (
    <span className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full ${tones[tone]}`}>
      {children}
    </span>
  )
}

export function TableWrap({ children }: { children: ReactNode }) {
  return <div className="overflow-x-auto border rounded-lg bg-white">{children}</div>
}

export function ErrorBanner({ message }: { message: string }) {
  return (
    <div role="alert" className="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">
      {message}
    </div>
  )
}

export function LoadingBlock() {
  return <div className="text-sm text-gray-500 py-8 text-center">Chargement…</div>
}

export function extractErrorMessage(error: unknown, fallback: string): string {
  const response = (error as { response?: { data?: { message?: string } } })?.response
  return response?.data?.message ?? fallback
}
