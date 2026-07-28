// Libellés d'affichage uniquement — la validation des transitions reste
// entièrement côté API (OrderStatus::allowedNextStatuses côté backend) ;
// le formulaire propose les 15 statuts et laisse le backend accepter ou
// rejeter (422) la transition demandée, pour ne pas dupliquer la règle
// métier ici.
export const ORDER_STATUSES = [
  { value: 'brouillon', label: 'Brouillon' },
  { value: 'paiement_en_attente', label: 'Paiement en attente' },
  { value: 'paiement_valide', label: 'Paiement validé' },
  { value: 'achat_en_cours', label: 'Achat en cours' },
  { value: 'commande_boutique', label: 'Commandé auprès de la boutique' },
  { value: 'expedie_boutique', label: 'Expédié par la boutique' },
  { value: 'recu_madrid', label: 'Reçu à Madrid' },
  { value: 'controle_qualite', label: 'Contrôle qualité' },
  { value: 'consolidation', label: 'Consolidation' },
  { value: 'expedie_nouakchott', label: 'Expédié vers Nouakchott' },
  { value: 'arrive_nouakchott', label: 'Arrivé à Nouakchott' },
  { value: 'livraison_en_cours', label: 'En cours de livraison' },
  { value: 'livre', label: 'Livré' },
  { value: 'annule', label: 'Annulé' },
  { value: 'rembourse', label: 'Remboursé' },
]

export function statusTone(status: string): 'ok' | 'warn' | 'crit' | 'neutral' {
  if (status === 'livre') return 'ok'
  if (status === 'annule' || status === 'rembourse') return 'crit'
  if (status === 'paiement_en_attente' || status === 'brouillon') return 'warn'
  return 'neutral'
}
