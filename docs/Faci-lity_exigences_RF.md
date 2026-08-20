# Faci-lity — Index de traçabilité des exigences (RF-Mx-0xx)

Index généré depuis `Faci-lity_Cahier_des_Charges_v2.docx` (§4). Il sert de backlog de
référence : **tout commit ou PR doit citer l'identifiant `RF-Mx-0xx` correspondant**
(règle de traçabilité du projet).

- Source de vérité : `docs/Faci-lity_Cahier_des_Charges_v2.docx`
- Version texte consultable : `docs/Faci-lity_Cahier_des_Charges_v2.md`
- Phases : **P1** = MVP (0-6 mois) · **P2** = Extension (6-12 mois) · **P3** = Marketplace élargi (12-18 mois) · **P4** = Scale & IoT (18-24+ mois), cf. §9

> Ne pas éditer à la main : régénérer depuis le `.docx` si le cahier des charges évolue.

## Répartition

| Module | Périmètre | Section | P1 | P2 | P3 | P4 | Total |
|---|---|---|---|---|---|---|---|
| **M1** | Cœur Logistique, IA Documentaire & Marketplace de Fret | §4.1 | 7 | 7 | 6 | 0 | 20 |
| **M2** | Géolocalisation & Dispatch Intelligent | §4.2 | 7 | 8 | 0 | 0 | 15 |
| **M3** | Passerelle FinTech & Paiements | §4.3 | 5 | 6 | 1 | 0 | 12 |
| **M4** | Audit, Inspection & Certification des Marchandises | §4.4 | 0 | 5 | 7 | 0 | 12 |
| **M5** | Marketplace de Location d'Équipements Lourds | §4.5 | 0 | 0 | 13 | 1 | 14 |
| **M6** | Éco-Logistique, IoT & SaaS B2B Flottes | §4.6 | 0 | 0 | 0 | 7 | 7 |
| | **Total** | | **19** | **26** | **27** | **8** | **80** |

## M1 — Cœur Logistique, IA Documentaire & Marketplace de Fret (§4.1)

| Exigence | Phase | Description |
|---|---|---|
| `RF-M1-001` | P1 | Le système DOIT permettre l'upload de documents commerciaux (PDF/JPEG/PNG, 20 Mo max) et leur classification automatique par type. |
| `RF-M1-002` | P1 | Le système DOIT extraire automatiquement via OCR/IA : code SH, pays d'origine, valeur FOB/CIF, poids, dimensions, fournisseur, n° facture. |
| `RF-M1-003` | P1 | Le système DOIT afficher un score de confiance par champ extrait et imposer une validation humaine sous le seuil de 85 %. |
| `RF-M1-004` | P1 | Le système DOIT permettre la correction manuelle de tout champ extrait avant validation finale, avec journalisation de l'auteur. |
| `RF-M1-005` | P1 | L'agent LLM DOIT proposer les taux de droits et taxes applicables avec la référence réglementaire correspondante. |
| `RF-M1-006` | P2 | Le système DOIT détecter les incohérences BL/facture (poids, quantités, références) et générer une alerte bloquante. |
| `RF-M1-007` | P1 | Le système DOIT générer la liasse douanière au format PDF, stockée chiffrée sur AWS S3 avec URL signée à validité limitée. |
| `RF-M1-008` | P2 | Le système DOIT conserver un historique versionné de chaque document et de ses corrections (piste d'audit). |
| `RF-M1-009` | P2 | Le système DOIT calculer le volume en m³ de chaque envoi via l'algorithme de bin packing à partir des dimensions extraites. |
| `RF-M1-010` | P2 | Le système DOIT afficher en temps réel la capacité restante de chaque conteneur ouvert selon la formule définie. |
| `RF-M1-011` | P2 | Chaque participant DOIT pouvoir visualiser le taux de remplissage et le coût proportionnel à son volume. |
| `RF-M1-012` | P2 | Le système DOIT verrouiller automatiquement un conteneur au seuil de capacité ou à la date limite, selon la première condition atteinte. |
| `RF-M1-013` | P2 | Le système DOIT permettre l'annulation d'une participation avant clôture, avec recalcul de capacité et pénalité configurable. |
| `RF-M1-014` | P3 | Le système DOIT permettre la recherche d'espace de fret par port origine/destination et date, en distinguant offres LCL et FCL. |
| `RF-M1-015` | P3 | Le système DOIT agréger et afficher le calendrier des départs (sailing schedule) par route. |
| `RF-M1-016` | P3 | Le système DOIT permettre la comparaison des offres sur les critères prix / durée de transit / fiabilité. |
| `RF-M1-017` | P3 | Le système DOIT générer un Booking Note numérique dès confirmation de la réservation d'espace navire. |
| `RF-M1-018` | P3 | Le système DOIT notifier automatiquement le client de tout changement de statut ou retard de sa réservation. |
| `RF-M1-019` | P3 | Le système DOIT fournir une interface partenaire permettant la saisie manuelle des disponibilités et tarifs (Phase 3). |
| `RF-M1-020` | P1 | Le système DOIT calculer et afficher le Landed Cost complet de chaque expédition avant confirmation de paiement. |

## M2 — Géolocalisation & Dispatch Intelligent (§4.2)

| Exigence | Phase | Description |
|---|---|---|
| `RF-M2-001` | P1 | Le système DOIT indexer la position de chaque tracteur disponible en maille H3 (résolution 8 recommandée). |
| `RF-M2-002` | P1 | Le système DOIT calculer l'itinéraire réel via OSRM en tenant compte des contraintes de gabarit poids lourd. |
| `RF-M2-003` | P1 | L'algorithme de matchmaking DOIT pondérer distance, disponibilité, type de remorque et note moyenne. |
| `RF-M2-004` | P2 | Le système DOIT diffuser la course aux chauffeurs pertinents avec fenêtre d'acceptation de 60 s, puis élargir la recherche. |
| `RF-M2-005` | P1 | Le système DOIT recevoir la position GPS des chauffeurs via MQTT à fréquence configurable (10-30 s). |
| `RF-M2-006` | P1 | Le système DOIT pousser les mises à jour de statut via WebSockets en moins de 2 secondes. |
| `RF-M2-007` | P1 | Le système DOIT mettre en cache Redis les dernières coordonnées connues de chaque chauffeur. |
| `RF-M2-008` | P2 | L'application chauffeur DOIT fonctionner en mode dégradé hors-ligne avec synchronisation différée. |
| `RF-M2-009` | P2 | Le système DOIT permettre la définition de zones geofence (PostGIS) autour des sites portuaires/entrepôts. |
| `RF-M2-010` | P2 | Le système DOIT déclencher automatiquement le compteur d'immobilisation à l'entrée en zone, horodatage immuable. |
| `RF-M2-011` | P2 | Le système DOIT générer automatiquement les justificatifs de facturation des frais de stationnement. |
| `RF-M2-012` | P1 | Le système DOIT calculer et afficher le prix estimé d'une course avant confirmation, selon une formule transparente. |
| `RF-M2-013` | P2 | Le système DOIT appliquer des frais d'annulation configurables après acceptation par un chauffeur. |
| `RF-M2-014` | P2 | Le système DOIT permettre une notation bidirectionnelle sur 5 étoiles après chaque course. |
| `RF-M2-015` | P2 | Le système DOIT signaler pour revue tout chauffeur dont la note moyenne passe sous un seuil configurable. |

## M3 — Passerelle FinTech & Paiements (§4.3)

| Exigence | Phase | Description |
|---|---|---|
| `RF-M3-001` | P1→P2 | Le système DOIT intégrer les API des wallets Bankily, Masrivi, Click et Sadad via une couche d'abstraction commune. |
| `RF-M3-002` | P1 | Le système DOIT router automatiquement la requête de paiement vers l'API du wallet sélectionné. |
| `RF-M3-003` | P1 | Le système DOIT réconcilier automatiquement les paiements entrants avec les soldes escrow. |
| `RF-M3-004` | P2 | Le système DOIT se connecter en ISO 20022 à une banque partenaire pour les flux de change temps réel. |
| `RF-M3-005` | P2 | Le système DOIT calculer et afficher le taux de change (spot + spread) avant confirmation. |
| `RF-M3-006` | P2 | Le système DOIT générer automatiquement un ordre SWIFT dès confirmation du dépôt en MRU. |
| `RF-M3-007` | P1 | Toute transaction DOIT être protégée par authentification à deux facteurs. |
| `RF-M3-008` | P1 | Les fonds DOIVENT être bloqués en escrow à la commande et libérés à validation de livraison/service. |
| `RF-M3-009` | P2 | Le système DOIT proposer un portefeuille unifié par utilisateur regroupant tous les flux multi-modules. |
| `RF-M3-010` | P2 | Le système DOIT générer automatiquement une facture PDF pour chaque transaction. |
| `RF-M3-011` | P3 | Le système DOIT permettre l'export comptable CSV/Excel de l'historique de transactions. |
| `RF-M3-012` | P2 | Le système DOIT afficher un tableau de bord consolidé filtrable par module/statut/période. |

## M4 — Audit, Inspection & Certification des Marchandises (§4.4)

| Exigence | Phase | Description |
|---|---|---|
| `RF-M4-001` | P2→P3 | Le système DOIT permettre la création d'une demande d'audit en spécifiant son type parmi les six supportés. |
| `RF-M4-002` | P3 | Chaque type d'audit DOIT être associé à une checklist configurable par catégorie de marchandise ou d'équipement. |
| `RF-M4-003` | P3 | Le système DOIT assigner automatiquement un auditeur disponible par proximité géographique (index H3). |
| `RF-M4-004` | P2 | L'application d'audit mobile DOIT imposer une photo géolocalisée et horodatée à chaque point de contrôle. |
| `RF-M4-005` | P2 | Le système DOIT permettre la signature électronique de toutes les parties présentes lors de l'inspection. |
| `RF-M4-006` | P3 | Le système DOIT détecter automatiquement les écarts entre état/quantité déclaré et constaté. |
| `RF-M4-007` | P2 | Le système DOIT générer un rapport PDF horodaté avec hash SHA-256 inscrit au registre immuable. |
| `RF-M4-008` | P2 | Le système DOIT notifier automatiquement toutes les parties prenantes à la clôture d'un audit. |
| `RF-M4-009` | P3 | En cas d'anomalie, le système DOIT ouvrir automatiquement un dossier de litige et geler l'escrow concerné. |
| `RF-M4-010` | P3 | Le système DOIT offrir une fenêtre de réponse contradictoire configurable (48h par défaut). |
| `RF-M4-011` | P3 | Le système DOIT permettre l'accréditation d'auditeurs tiers avec validation manuelle par un administrateur. |
| `RF-M4-012` | P3 | Le système DOIT permettre la notation d'un auditeur après chaque mission. |

## M5 — Marketplace de Location d'Équipements Lourds (§4.5)

| Exigence | Phase | Description |
|---|---|---|
| `RF-M5-001` | P3 | Le système DOIT permettre à un loueur de s'inscrire et de soumettre les documents KYC de chaque équipement. |
| `RF-M5-002` | P3 | Un équipement DOIT être au statut « Vérifié » par un modérateur avant de pouvoir être publié en annonce. |
| `RF-M5-003` | P3 | Le système DOIT alerter automatiquement le loueur avant l'expiration de l'assurance ou du contrôle technique. |
| `RF-M5-004` | P3 | Le système DOIT permettre la publication d'une annonce avec photos, caractéristiques, tarifs et calendrier. |
| `RF-M5-005` | P3 | Le système DOIT permettre la recherche d'équipements par catégorie, localisation/rayon, dates et option opérateur. |
| `RF-M5-006` | P3 | Le catalogue de catégories d'équipements DOIT être extensible par un administrateur, sans développement. |
| `RF-M5-007` | P3 | Le système DOIT générer automatiquement un contrat de location numérique à la confirmation d'une réservation. |
| `RF-M5-008` | P3 | Le système DOIT bloquer la caution en escrow (Module 3) à la confirmation du paiement. |
| `RF-M5-009` | P3 | Le système DOIT déclencher automatiquement un état des lieux de départ (Module 4) avant remise de l'équipement. |
| `RF-M5-010` | P3 | Le système DOIT déclencher automatiquement un état des lieux de retour (Module 4) en fin de location. |
| `RF-M5-011` | P3 | Le système DOIT comparer les états des lieux de départ/retour et proposer une libération de caution totale, partielle, ou un litige. |
| `RF-M5-012` | P4 | Le système DOIT permettre le suivi géolocalisé optionnel des équipements motorisés pendant la location. |
| `RF-M5-013` | P3 | Le système DOIT permettre une notation bidirectionnelle loueur ↔ locataire après chaque location. |
| `RF-M5-014` | P3 | Le système DOIT calculer et prélever automatiquement la commission plateforme sur chaque transaction. |

## M6 — Éco-Logistique, IoT & SaaS B2B Flottes (§4.6)

| Exigence | Phase | Description |
|---|---|---|
| `RF-M6-001` | P4 | Le système DOIT proposer automatiquement au chauffeur un trajet retour générateur de revenu. |
| `RF-M6-002` | P4 | Le système DOIT stocker les logs de température/humidité/luminosité IoT dans une base séries temporelles. |
| `RF-M6-003` | P4 | Le système DOIT déclencher une alerte temps réel en cas de dépassement de seuil critique pour conteneurs réfrigérés. |
| `RF-M6-004` | P4 | Le système DOIT fournir un tableau de bord SaaS B2B consolidé couvrant flottes de transport ET équipements loués. |
| `RF-M6-005` | P4 | Le tableau de bord DOIT permettre le suivi des KPIs par actif (utilisation, revenus, maintenance). |
| `RF-M6-006` | P4 | Le système DOIT générer un rapport d'émissions CO₂ consolidé par client/période/type d'actif. |
| `RF-M6-007` | P4 | Le système DOIT permettre la configuration d'alertes de maintenance préventive. |
