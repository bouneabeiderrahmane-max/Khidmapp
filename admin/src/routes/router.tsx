import { createBrowserRouter } from 'react-router-dom'
import { AppLayout } from '../layouts/AppLayout'
import { DashboardPage } from '../pages/DashboardPage'
import { PlaceholderPage } from '../pages/PlaceholderPage'

export const router = createBrowserRouter([
  {
    path: '/',
    element: <AppLayout />,
    children: [
      { index: true, element: <DashboardPage /> },
      { path: 'boutiques', element: <PlaceholderPage title="Boutiques" /> },
      { path: 'orders', element: <PlaceholderPage title="Commandes" /> },
      { path: 'payments', element: <PlaceholderPage title="Paiements" /> },
      { path: 'users', element: <PlaceholderPage title="Utilisateurs" /> },
      { path: 'roles', element: <PlaceholderPage title="Rôles et permissions" /> },
      { path: 'reports', element: <PlaceholderPage title="Rapports" /> },
    ],
  },
])
