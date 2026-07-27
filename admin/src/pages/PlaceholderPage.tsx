export function PlaceholderPage({ title }: { title: string }) {
  return (
    <div>
      <h1 className="text-xl font-semibold">{title}</h1>
      <p className="text-sm text-gray-500 mt-2">
        Module à implémenter dans son sprint dédié (voir docs/PLAN.md).
      </p>
    </div>
  )
}
