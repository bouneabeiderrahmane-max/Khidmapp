# Faci-lity — Cahier des Charges v2.0

> **Version texte générée automatiquement** à partir de `Faci-lity_Cahier_des_Charges_v2.docx` (source de vérité).
> Elle existe pour rendre les exigences `RF-Mx-0xx` consultables et *greppables* depuis le dépôt.
> En cas de divergence, le fichier `.docx` fait foi. Ne pas éditer ce fichier à la main.

---
F A C I - L I T Y

Plateforme Logistique, Marketplace & FinTech Intelligente — Tout-en-Un

CAHIER DES CHARGES

Spécifications Techniques & Fonctionnelles Complètes

Version  2.0 — Édition Étendue (Plateforme Complète)

Date  Août 2026

Secteur  Logistique, FinTech & Location d'Équipements Lourds — Mauritanie

STATUT : CONFIDENTIEL

© 2026 Faci-lity — Tous droits réservés — Toute reproduction interdite sans autorisation écrite

## Historique des Versions & Évolutions

Ce document est la version 2.0 du cahier des charges Faci-lity. Il remplace intégralement la version 1.0 (juin 2026) et constitue la référence unique pour les équipes de développement, les partenaires technologiques et les investisseurs.

| Version | Date | Auteur | Description des changements |
|---|---|---|---|
| 1.0 | Juin 2026 | Équipe Produit Faci-lity | Version initiale — 4 modules (Cœur Logistique/IA, Dispatch, FinTech, Éco-IoT). |
| 2.0 | Août 2026 | Équipe Produit Faci-lity | Édition étendue : ajout du Marketplace de fret élargi (FCL/booking navire), du Module Audit & Inspection, du Marketplace de location d'équipements lourds, des exigences non-fonctionnelles, du modèle de rôles/permissions et du modèle de données consolidé. |

#### Synthèse des principales évolutions de la v2.0

- Vision élargie : Faci-lity devient une plateforme unique couvrant tout le cycle logistique — du groupage collaboratif (LCL) à la réservation d'espace sur navire complet (FCL), en passant par le transport, le paiement, l'audit qualité et la location de matériel lourd.
- Nouveau Module 4 — Audit & Inspection : traçabilité et certification numérique de l'état des marchandises à chaque étape (avant expédition, mise en conteneur, réception, livraison).
- Nouveau Module 5 — Location d'Équipements Lourds : marketplace intégrée permettant de louer bennes, reach stackers, tracteurs, chariots élévateurs, bulldozers, compacteurs et chargeuses.
- Module 1 enrichi : ajout de la réservation directe d'espace navire (FCL) en complément du groupage (LCL) existant.
- Nouvelles sections transverses : cartographie des utilisateurs et matrice de permissions, exigences non-fonctionnelles, modèle de données global, indicateurs de succès (KPIs).

## Table des Matières

1.  Introduction & Présentation du Projet	5

1.1  Contexte et Vision	5

1.2  Proposition de Valeur : une Seule Application pour Toute la Logistique	5

1.3  Objectifs Stratégiques	5

1.4  Périmètre du Document	6

1.5  Comment Utiliser ce Document (Public Développeurs)	6

2.  Utilisateurs, Rôles & Personas	7

2.1  Cartographie des Acteurs	7

2.2  Matrice des Permissions (RBAC)	8

2.3  Parcours Utilisateur Illustratif — L'Importateur « Tout-en-Un »	8

3.  Architecture Système Globale	9

3.1  Modèle Architectural	9

3.2  Vue d'Ensemble des Modules	9

3.3  Flux de Données par Couche	10

3.4  Communication Inter-Modules (Événements Clés)	10

4.  Spécifications Fonctionnelles Détaillées	12

4.1  Module 1 — Cœur Logistique, IA Documentaire & Marketplace de Fret	13

4.2  Module 2 — Géolocalisation & Dispatch Intelligent	18

4.3  Module 3 — Passerelle FinTech & Paiements	21

4.4  Module 4 — Audit, Inspection & Certification des Marchandises	24

4.5  Module 5 — Marketplace de Location d'Équipements Lourds	27

4.6  Module 6 — Éco-Logistique, IoT & SaaS B2B Flottes	31

5.  Exigences Non-Fonctionnelles	33

5.1  Performance & Scalabilité	33

5.2  Disponibilité & Fiabilité	33

5.3  Internationalisation & Accessibilité	33

5.4  Compatibilité	33

5.5  Observabilité	33

5.6  Environnements & CI/CD	34

6.  Modèle de Données Global & Relations Inter-Modules	35

6.1  Entité Utilisateur Unifiée	35

6.2  Relations Inter-Modules Clés	35

7.  Stack Technologique Recommandée	37

8.  Sécurité, Traçabilité & Immuabilité	38

8.1  Preuve de Livraison Cryptographique (PoD)	38

8.2  Gestion des Accès aux Documents Sensibles	38

8.3  Conformité & Isolation des Données Financières	38

8.4  Sécurité du Module Audit	38

8.5  Sécurité du Module Location d'Équipements	39

8.6  Politique Générale de Sécurité	39

9.  Roadmap de Développement	40

9.1 · 0 à 6 mois  Phase 1 — MVP	40

9.2 · 6 à 12 mois  Phase 2 — Extension	40

9.3 · 12 à 18 mois  Phase 3 — Marketplace Élargi & Location d'Équipements	40

9.4 · 18 à 24+ mois  Phase 4 — Scale, IoT & Innovation	41

10.  Indicateurs de Succès (KPIs)	42

11.  Annexe A — Glossaire Technique	43

## 1.  Introduction & Présentation du Projet

### 1.1  Contexte et Vision

Faci-lity est une plateforme digitale de nouvelle génération dédiée à la gestion end-to-end de la chaîne logistique en Mauritanie. Elle répond à une problématique critique : la fragmentation des acteurs du commerce international — importateurs, transitaires, transporteurs, loueurs de matériel et institutions financières — qui opèrent aujourd'hui sans infrastructure numérique commune, avec des outils déconnectés (WhatsApp, Excel, papier) générant délais, litiges et pertes de traçabilité.

La plateforme vise à devenir le système d'exploitation de la logistique mauritanienne : une application unique qui interconnecte le port de Nouakchott, les entrepôts, les transporteurs routiers, les prestataires d'équipements lourds, les auditeurs qualité et les systèmes de paiement locaux au sein d'un écosystème unique, intelligent, traçable et auditable.

### 1.2  Proposition de Valeur : une Seule Application pour Toute la Logistique

Faci-lity est conçue pour accompagner un client tout au long de son besoin logistique, quelle qu'en soit la nature, sans qu'il ait besoin de changer d'outil ou d'interlocuteur. Le parcours ci-dessous illustre cette ambition « tout-en-un » :

| Parcours illustratif —  / Aujourd'hui : un importateur cherche une place dans un conteneur groupé (LCL) pour un petit volume de marchandise. / Demain : ce même importateur réserve directement un espace sur un navire complet (FCL) pour un plus gros volume, en comparant les tarifs de plusieurs transporteurs. / Après-demain : il fait auditer sa marchandise à réception, règle son fournisseur en devises via la passerelle FinTech, puis loue directement dans l'application un chariot élévateur et un camion-benne pour décharger et acheminer sa marchandise vers son entrepôt. |
|---|

Cette continuité de bout en bout — réservation de fret, transport, paiement, audit qualité et location de matériel — est le fil conducteur de l'ensemble des spécifications de ce document et la raison d'être des Modules 4 et 5, nouveaux dans cette version 2.0.

### 1.3  Objectifs Stratégiques

- Digitaliser et automatiser le traitement documentaire douanier (IA) pour réduire les délais de dédouanement.
- Créer une place de marché du fret complète — du groupage collaboratif (LCL) à la réservation d'espace sur navire complet (FCL) — pour mutualiser les coûts et donner accès à des tarifs comparés en temps réel.
- Offrir un système de dispatch géolocalisé pour les tracteurs portuaires, inspiré du modèle Uber.
- Garantir la confiance et réduire les litiges grâce à un module d'audit et d'inspection numérique des marchandises, à chaque étape critique du transport.
- Optimiser l'utilisation du matériel lourd via une marketplace de location d'équipements (bennes, reach stackers, tracteurs, forklifts, bulldozers, compacteurs, chargeuses), créant une nouvelle source de revenus pour les propriétaires de flotte et un accès à la demande pour les clients.
- Intégrer une passerelle FinTech permettant le paiement en Ouguiyas (MRU) et le règlement automatique en devises étrangères.
- Préparer une évolution vers l'éco-logistique, la chaîne du froid connectée (IoT) et le SaaS B2B pour la gestion de flottes (transport et équipements).

### 1.4  Périmètre du Document

Le présent cahier des charges couvre l'intégralité des spécifications fonctionnelles et techniques nécessaires au développement de la plateforme Faci-lity, organisée en six modules principaux, une couche transverse de rôles et permissions, un socle d'exigences non-fonctionnelles et une roadmap de développement en quatre phases. Il est destiné aux équipes de développement, aux partenaires technologiques et aux investisseurs techniques.

### 1.5  Comment Utiliser ce Document (Public Développeurs)

Chaque module de la section 4 est rédigé pour être exploitable de façon autonome par une équipe de développement dédiée, en microservice indépendant. Chaque sous-section de module suit systématiquement la même structure :

- Vue d'ensemble — objectif métier du sous-module.
- User stories — besoins exprimés du point de vue des utilisateurs.
- Exigences fonctionnelles (RF-xxx) — liste numérotée, priorisée par phase de la roadmap (§9), directement transformable en tickets de développement.
- Modèle de données — entités clés et champs principaux, base de départ pour le schéma de base de données.
- Endpoints API — routes REST principales à exposer par le microservice.
Les identifiants d'exigences (ex. RF-M5-004) sont uniques dans tout le document et peuvent être repris tels quels comme identifiants de tickets Jira/Linear afin d'assurer la traçabilité entre le cahier des charges et le backlog de développement.

## 2.  Utilisateurs, Rôles & Personas

Cette section est nouvelle dans la v2.0. Elle formalise le modèle d'acteurs et de permissions (RBAC) nécessaire à l'implémentation de l'authentification et des autorisations, transverse à l'ensemble des six modules.

### 2.1  Cartographie des Acteurs

Un même compte utilisateur peut cumuler plusieurs rôles (ex. un chauffeur propriétaire de son tracteur est à la fois « Transporteur » sur le Module 2 et « Loueur d'Équipement » sur le Module 5). Le modèle de données Utilisateur (§6.2) doit donc supporter une relation multi-rôles et non un simple champ unique.

Importateur / Exportateur (Client)

Entreprise ou particulier réalisant des opérations d'import/export. Acteur central : initie les expéditions, recherche du fret (LCL ou FCL), demande des transports, paie via la passerelle FinTech, commande des audits et loue du matériel.

Modules concernés : M1, M2, M3, M4, M5, M6 (lecture)

Transitaire / Commissionnaire en Douane Agréé

Professionnel du dédouanement pouvant agir pour le compte d'un importateur. Valide et complète les déclarations générées par l'IA, peut gérer plusieurs dossiers clients simultanément.

Modules concernés : M1 (gestion documentaire), M4 (lecture)

Transporteur / Chauffeur (Tracteur Portuaire)

Prestataire de transport routier. Reçoit et exécute les courses via le dispatch géolocalisé, peut aussi être propriétaire de son véhicule et donc « Loueur » sur le Module 5.

Modules concernés : M2, M4 (livraison), M5 (si propriétaire)

Loueur d'Équipement (Bailleur)

Entreprise de location, opérateur portuaire ou particulier propriétaire d'engins lourds (bennes, reach stackers, forklifts, bulldozers, compacteurs, chargeuses). Publie des annonces et gère son parc.

Modules concernés : M5, M6 (tableau de bord flotte), M3 (paiements)

Auditeur / Inspecteur

Collaborateur interne Faci-lity ou tiers certifié (société de surveillance indépendante type SGS/Bureau Veritas/Cotecna) réalisant les inspections physiques et générant les rapports numériques.

Modules concernés : M4 (cœur de métier), M1 (lecture)

Institution Financière Partenaire

Banque locale ou opérateur de mobile money (Bankily, Masrivi, Click, Sadad) connecté via API pour le traitement des flux de paiement et de change.

Modules concernés : M3 (intégration API)

Partenaire Maritime / Transitaire International

Compagnie maritime, agent maritime ou freight forwarder international publiant les disponibilités et tarifs d'espace navire (FCL) sur la marketplace de fret élargie.

Modules concernés : M1 (marketplace fret)

Administrateur Plateforme

Équipe interne Faci-lity : Super Administrateur (configuration système), Support Client (assistance, résolution litiges), Modérateur (validation des annonces équipements, contrôle qualité des audits).

Modules concernés : Accès transverse à tous les modules (back-office)

### 2.2  Matrice des Permissions (RBAC)

Niveaux d'accès de référence pour la conception du contrôle d'accès (à affiner en granularité « propriétaire de la ressource » lors de l'implémentation, ex. un client ne voit que ses propres expéditions).

- A = Accès complet / Administration
- E = Écriture sur ses propres données (créer, modifier, suivre)
- L = Lecture seule
- — = Aucun accès

| Rôle | M1 Fret/IA | M2 Dispatch | M3 FinTech | M4 Audit | M5 Location | M6 IoT/SaaS |
|---|---|---|---|---|---|---|
| Importateur / Exportateur | E | E | E | E | E | L |
| Transitaire agréé | E | L | L | L | — | — |
| Transporteur / Chauffeur | L | E | L | E | E | L |
| Loueur d'Équipement | — | — | L | E | E | E |
| Auditeur / Inspecteur | L | — | — | E | E | — |
| Institution Financière | — | — | E | — | — | — |
| Partenaire Maritime | E | — | L | — | — | — |
| Administrateur Plateforme | A | A | A | A | A | A |

### 2.3  Parcours Utilisateur Illustratif — L'Importateur « Tout-en-Un »

Le parcours ci-dessous illustre comment un même utilisateur traverse les six modules au sein d'une seule session applicative, et sert de fil rouge pour les tests d'intégration bout-en-bout (E2E).

- Inscription et vérification KYC de l'entreprise importatrice (document d'identité, registre de commerce).
- Upload de la facture commerciale fournisseur → extraction automatique par l'IA documentaire (M1).
- Choix : rejoindre un groupage LCL pour un petit volume, OU comparer et réserver un espace de conteneur complet (FCL) sur un navire (M1).
- Paiement du fret et des droits de douane estimés via wallet mobile local (M3).
- À l'arrivée du navire, demande de dispatch d'un tracteur portuaire pour l'acheminement vers l'entrepôt (M2), suivi en temps réel.
- Déclenchement automatique d'une inspection de réception à l'entrepôt : photos géolocalisées, checklist, détection d'éventuelles avaries (M4).
- Besoin ponctuel de matériel : location d'un chariot élévateur pour décharger et d'un camion-benne pour évacuer les emballages, directement dans l'app (M5).
- Retour à vide du tracteur proposé automatiquement à un autre client sur le trajet retour, réduisant les coûts globaux (M6).
- Consultation du tableau de bord unifié : historique des expéditions, factures, rapports d'audit et locations, avec export comptable (M3 + M6).

## 3.  Architecture Système Globale

### 3.1  Modèle Architectural

Faci-lity repose sur une architecture découplée orientée services (SOA/Microservices). Ce choix garantit que les fonctionnalités à haute intensité de calcul — telles que le tracking GPS temps réel, le traitement IA documentaire ou la recherche dans la marketplace d'équipements — n'affectent pas la stabilité et la sécurité de la passerelle de paiement FinTech, dont la base de données reste isolée (cf. §8.3).

La v2.0 fait passer l'architecture de quatre à six piliers fonctionnels, chacun conçu comme un microservice indépendant, déployable et scalable séparément.

### 3.2  Vue d'Ensemble des Modules

| Code | Module | Rôle en une phrase |
|---|---|---|
| M1 | Cœur Logistique, IA Documentaire & Marketplace de Fret | Traitement documentaire par IA, groupage LCL, réservation d'espace navire FCL, calcul du Landed Cost. |
| M2 | Géolocalisation & Dispatch Intelligent | Mise en relation temps réel type Uber entre demandes de transport et tracteurs portuaires disponibles. |
| M3 | Passerelle FinTech & Paiements | Agrégation des wallets locaux, règlement international SWIFT/FX, escrow, facturation. |
| M4 | Audit, Inspection & Certification des Marchandises | Inspections digitales horodatées et géolocalisées à chaque étape critique, gestion des litiges. |
| M5 | Marketplace de Location d'Équipements Lourds | Mise en relation loueurs/locataires d'engins (bennes, reach stackers, forklifts, bulldozers…). |
| M6 | Éco-Logistique, IoT & SaaS B2B Flottes | Optimisation des retours à vide, télémétrie chaîne du froid, tableau de bord multi-actifs. |

Figure 1 — Vue d'ensemble de l'architecture système (couches client, gateway, microservices, message queue, données)

### 3.3  Flux de Données par Couche

- Couche Client : Web App (Next.js) pour les importateurs, transitaires, loueurs d'équipement et SaaS flottes ; application mobile (Flutter) pour les chauffeurs, auditeurs et clients finaux.
- API Gateway / Reverse Proxy : point d'entrée unique, routage des requêtes vers les microservices concernés, authentification JWT centralisée, rate limiting.
- Couche Services : six microservices indépendants (M1 à M6) communiquant via une message queue (RabbitMQ / Kafka).
- Couche Recherche & Découverte (NOUVEAU) : moteur de recherche/indexation (Elasticsearch ou Algolia) pour la recherche full-text et géo-filtrée des annonces de la marketplace de fret (M1) et de la marketplace d'équipements (M5) — critique pour la performance perçue par l'utilisateur.
- Couche Notification (NOUVEAU) : service dédié consommant les événements de la message queue pour l'envoi de SMS, push notifications (Firebase Cloud Messaging) et emails transactionnels, partagé par tous les modules.
- Couche Données : isolation stricte des bases de données par module (PostgreSQL + PostGIS, Redis, MongoDB, TimescaleDB/InfluxDB, AWS S3).

### 3.4  Communication Inter-Modules (Événements Clés)

L'architecture événementielle est essentielle à la cohérence du parcours « tout-en-un » (§2.3). Le tableau ci-dessous liste les principaux événements publiés sur la message queue et leurs abonnés, base de la conception des contrats d'événements (event schemas).

| Événement | Émetteur | Abonné(s) | Action déclenchée |
|---|---|---|---|
| shipment.documents.validated | M1 | M3 | Calcul et affichage du montant de droits/taxes à régler. |
| payment.confirmed | M3 | M1, M2 | Déblocage du conteneur / activation de la demande de transport. |
| dispatch.trip.completed | M2 | M4, M3 | Proposition d'inspection de livraison (PoD) ; calcul de la course. |
| audit.discrepancy.detected | M4 | M3, Notification | Gel des fonds escrow concernés ; alerte client + transitaire. |
| rental.booking.confirmed | M5 | M3, M4 | Prélèvement de la caution (escrow) ; planification de l'état des lieux de départ. |
| rental.equipment.returned | M5 | M4, M3, M6 | Déclenchement état des lieux de retour ; libération de caution ; mise à jour du journal de maintenance. |
| geofence.entry.detected | M2 | M6 | Démarrage du compteur d'immobilisation ; horodatage immuable. |
| iot.telemetry.threshold_exceeded | M6 | Notification | Alerte temps réel (chaîne du froid) vers client et transporteur. |

## 4.  Spécifications Fonctionnelles Détaillées

Cette section constitue le cœur opérationnel du cahier des charges. Les six modules sont détaillés selon une structure homogène (vue d'ensemble, user stories, exigences fonctionnelles, modèle de données, endpoints API) afin d'être directement exploitables par les équipes de développement respectives. Les identifiants d'exigences (RF-Mx-0xx) sont uniques dans tout le document.

### 4.1  Module 1 — Cœur Logistique, IA Documentaire & Marketplace de Fret

Ce module constitue le cerveau opérationnel de la plateforme. Il automatise les tâches les plus chronophages du transit maritime grâce à l'intelligence artificielle, et porte désormais la marketplace de fret complète : du groupage collaboratif (LCL) à la réservation directe d'espace sur navire complet (FCL).

#### Pipeline de Traitement Documentaire par IA

Types de documents supportés

| Type de document | Champs extraits | Obligatoire |
|---|---|---|
| Facture commerciale | Fournisseur, n° facture, valeur FOB/CIF, devise, lignes produits | Oui |
| Bill of Lading (BL) / LTA | N° BL, navire, port origine/destination, poids brut, conteneur | Oui |
| Packing List | Dimensions, poids par colis, nombre de colis | Oui |
| Certificat d'origine | Pays d'origine, autorité émettrice | Selon accord commercial |
| Certificat phytosanitaire / sanitaire | Numéro, autorité, validité | Selon nature marchandise |

Étapes du pipeline

- Ingestion & OCR — extraction des métadonnées via l'API Google Cloud Document AI (ou modèles LayoutLM open-source). Reconnaissance des codes SH, pays d'origine, valeur FOB/CIF, poids et dimensions.
- Moteur de Règles & RAG — un agent LLM (GPT-4o / Claude) analyse les données extraites, interroge la base de données réglementaire douanière locale et génère les taux de droits et taxes applicables.
- Validation humaine (Human-in-the-loop) — tout champ extrait avec un score de confiance inférieur à 85 % est signalé et doit être revu/corrigé par le transitaire avant soumission officielle.
- Génération & Compliance — production automatique de la liasse douanière complète au format PDF, stockée de manière chiffrée sur AWS S3. Détection des anomalies croisant les données du BL avec la facture commerciale.
- Calcul du Landed Cost — agrégation automatique prix d'achat + fret + assurance + droits de douane + taxes locales, affiché au client avant confirmation.
Gestion des erreurs et cas limites

- Document illisible ou de mauvaise qualité : rejet automatique avec message explicite et demande de re-upload.
- Incohérence critique détectée (ex. poids BL ≠ poids facture au-delà d'un seuil de tolérance) : blocage du flux et alerte au transitaire, déclaration non soumise tant que non résolue.
- Document manquant obligatoire : impossibilité de générer la déclaration douanière, statut « Incomplet » affiché au client avec liste des pièces manquantes.
User stories

- En tant qu'importateur, je veux uploader ma facture commerciale et obtenir automatiquement les droits de douane estimés, afin de budgétiser mon opération sans expertise douanière.
- En tant que transitaire, je veux visualiser les champs à faible confiance signalés par l'IA, afin de concentrer mon temps de vérification sur les données réellement incertaines.
- En tant qu'administrateur, je veux consulter l'historique des corrections manuelles, afin d'améliorer le modèle IA dans le temps.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M1-001 | Le système DOIT permettre l'upload de documents commerciaux (PDF/JPEG/PNG, 20 Mo max) et leur classification automatique par type. | P1 |
| RF-M1-002 | Le système DOIT extraire automatiquement via OCR/IA : code SH, pays d'origine, valeur FOB/CIF, poids, dimensions, fournisseur, n° facture. | P1 |
| RF-M1-003 | Le système DOIT afficher un score de confiance par champ extrait et imposer une validation humaine sous le seuil de 85 %. | P1 |
| RF-M1-004 | Le système DOIT permettre la correction manuelle de tout champ extrait avant validation finale, avec journalisation de l'auteur. | P1 |
| RF-M1-005 | L'agent LLM DOIT proposer les taux de droits et taxes applicables avec la référence réglementaire correspondante. | P1 |
| RF-M1-006 | Le système DOIT détecter les incohérences BL/facture (poids, quantités, références) et générer une alerte bloquante. | P2 |
| RF-M1-007 | Le système DOIT générer la liasse douanière au format PDF, stockée chiffrée sur AWS S3 avec URL signée à validité limitée. | P1 |
| RF-M1-008 | Le système DOIT conserver un historique versionné de chaque document et de ses corrections (piste d'audit). | P2 |
| RF-M1-020 | Le système DOIT calculer et afficher le Landed Cost complet de chaque expédition avant confirmation de paiement. | P1 |

#### Moteur de Groupage Collaboratif (LCL)

Le système de groupage permet à plusieurs importateurs de partager un même conteneur, réduisant ainsi les coûts de fret pour les petits volumes.

- Algorithme de packing 3D de type Bin Packing Problem (heuristique gloutonne) calculant le volume occupé en m³ à partir des dimensions lues par l'IA.
- Mise à jour en temps réel de la capacité restante : Capacité_Restante = Capacité_Totale − Σ Volume_Client(i).
- Interface de consultation transparente : chaque importateur visualise le taux de remplissage du conteneur et le coût proportionnel à son volume.
- Système de clôture automatique : le conteneur est verrouillé dès l'atteinte du seuil de capacité configuré ou à la date limite de booking.
- Suggestion intelligente : si le volume d'un client dépasse un seuil paramétrable (ex. 70 % d'un conteneur 20 pieds), le système lui propose une réservation FCL directe, potentiellement plus économique.
User stories

- En tant qu'importateur avec un petit volume, je veux rejoindre un conteneur partagé, afin de réduire mes coûts de fret sans attendre d'avoir un conteneur complet.
- En tant qu'importateur, je veux voir en temps réel le taux de remplissage du conteneur que j'ai rejoint, afin d'anticiper la date de départ probable.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M1-009 | Le système DOIT calculer le volume en m³ de chaque envoi via l'algorithme de bin packing à partir des dimensions extraites. | P2 |
| RF-M1-010 | Le système DOIT afficher en temps réel la capacité restante de chaque conteneur ouvert selon la formule définie. | P2 |
| RF-M1-011 | Chaque participant DOIT pouvoir visualiser le taux de remplissage et le coût proportionnel à son volume. | P2 |
| RF-M1-012 | Le système DOIT verrouiller automatiquement un conteneur au seuil de capacité ou à la date limite, selon la première condition atteinte. | P2 |
| RF-M1-013 | Le système DOIT permettre l'annulation d'une participation avant clôture, avec recalcul de capacité et pénalité configurable. | P2 |

#### Marketplace de Fret Élargie — Réservation d'Espace Navire (FCL & Booking Direct)

| Extension majeure —  / Nouveauté v2.0 : ce sous-module généralise la logique de marketplace du groupage (LCL) à la réservation d'un conteneur complet (FCL), voire à terme d'espace vraquier/breakbulk. Objectif produit : « aujourd'hui une place dans un conteneur, demain une place sur un bateau », dans la même application. |
|---|

Les fonctionnalités suivantes transforment Faci-lity en véritable comparateur et guichet de réservation de fret maritime, à la manière d'un Freightos ou Flexport adapté au marché mauritanien.

- Recherche par route : port d'origine, port de destination, date souhaitée, type de conteneur (20', 40', 40' HC, reefer).
- Calendrier des départs (Sailing Schedule) : agrégation des disponibilités publiées par les compagnies maritimes et agents/transitaires partenaires.
- Comparateur de tarifs : classement des offres par prix, durée de transit et note de fiabilité du transporteur.
- Réservation & Booking Note numérique : confirmation instantanée et génération du document de réservation.
- Suivi de statut : Confirmée → En attente de chargement → Embarquée → En transit → Arrivée, avec notifications automatiques en cas de changement d'horaire.
Modèle d'intégration progressif : en Phase 3, les partenaires maritimes/transitaires saisissent manuellement leurs disponibilités et tarifs via une interface dédiée (back-office partenaire). Une intégration API directe avec les compagnies maritimes et plateformes de fret internationales est envisagée en évolution ultérieure, une fois le volume de transactions justifiant l'investissement d'intégration.

User stories

- En tant qu'importateur avec un volume important, je veux comparer les tarifs de plusieurs transporteurs pour une route donnée, afin de choisir l'offre la plus avantageuse.
- En tant que partenaire maritime, je veux publier mes disponibilités et tarifs, afin d'accéder à la demande de fret des importateurs mauritaniens.
- En tant qu'importateur, je veux être notifié automatiquement en cas de retard de mon navire, afin d'ajuster la logistique de réception.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M1-014 | Le système DOIT permettre la recherche d'espace de fret par port origine/destination et date, en distinguant offres LCL et FCL. | P3 |
| RF-M1-015 | Le système DOIT agréger et afficher le calendrier des départs (sailing schedule) par route. | P3 |
| RF-M1-016 | Le système DOIT permettre la comparaison des offres sur les critères prix / durée de transit / fiabilité. | P3 |
| RF-M1-017 | Le système DOIT générer un Booking Note numérique dès confirmation de la réservation d'espace navire. | P3 |
| RF-M1-018 | Le système DOIT notifier automatiquement le client de tout changement de statut ou retard de sa réservation. | P3 |
| RF-M1-019 | Le système DOIT fournir une interface partenaire permettant la saisie manuelle des disponibilités et tarifs (Phase 3). | P3 |

#### Modèle de Données du Module 1

| Entité | Champs clés | Description |
|---|---|---|
| Shipment | id, importer_id, type (LCL/FCL), status, origin_port, destination_port, incoterm | Expédition d'un client, pivot central du module. |
| ShipmentDocument | id, shipment_id, type, file_url, ocr_status, confidence_avg | Document uploadé et son statut de traitement IA. |
| ExtractedField | id, document_id, field_name, value, confidence_score, corrected_by | Champ individuel extrait par l'IA, avec traçabilité des corrections. |
| HSClassification | id, shipment_item_id, hs_code, description, duty_rate | Classification douanière d'une ligne produit. |
| CustomsDeclaration | id, shipment_id, status, pdf_url, generated_at | Liasse douanière générée. |
| GroupageContainer | id, route, capacity_total_m3, capacity_remaining_m3, status, closing_date | Conteneur ouvert au groupage collaboratif. |
| GroupageBooking | id, container_id, shipment_id, volume_m3, cost, status | Participation d'un client à un groupage. |
| VesselSailing | id, carrier_id, origin_port, destination_port, etd, eta, vessel_name | Départ navire publié par un partenaire. |
| FreightRateOffer | id, sailing_id, forwarder_id, price, currency, container_type, valid_until | Offre tarifaire pour un départ donné. |
| VesselBooking | id, sailing_id, rate_offer_id, shipment_id, status, booking_note_url | Réservation FCL confirmée. |

#### Endpoints API Principaux

| Méthode | Endpoint | Description |
|---|---|---|
| POST | /api/v1/shipments | Créer une nouvelle expédition. |
| POST | /api/v1/shipments/{id}/documents | Uploader et lancer le traitement IA d'un document. |
| GET | /api/v1/documents/{id}/extraction | Récupérer les champs extraits et leurs scores de confiance. |
| PATCH | /api/v1/documents/{id}/fields/{fieldId} | Corriger manuellement un champ extrait. |
| POST | /api/v1/shipments/{id}/customs-declaration | Générer la déclaration douanière PDF. |
| GET | /api/v1/shipments/{id}/landed-cost | Calculer le coût de revient total (Landed Cost). |
| GET | /api/v1/groupage/containers | Rechercher les conteneurs de groupage disponibles (par route/date). |
| POST | /api/v1/groupage/containers/{id}/bookings | Réserver un volume dans un conteneur groupé. |
| GET | /api/v1/freight/sailings | Rechercher les départs navires (origine, destination, date). |
| GET | /api/v1/freight/sailings/{id}/rates | Comparer les tarifs disponibles pour un départ. |
| POST | /api/v1/freight/bookings | Réserver un espace FCL et générer le Booking Note. |
| GET | /api/v1/freight/bookings/{id} | Suivre le statut d'une réservation navire. |

#### Règles de Gestion Clés

- Un conteneur de groupage ne peut jamais dépasser 100 % de sa capacité déclarée ; toute tentative de réservation dépassant la capacité restante est refusée avec proposition d'alternative (autre conteneur ou FCL).
- Le seuil de confiance IA (85 % par défaut) est configurable par l'administrateur, par type de champ.
- Une déclaration douanière ne peut être générée que si tous les documents obligatoires (facture, BL, packing list) sont validés (statut « Vérifié »).
- Un Booking Note FCL est juridiquement engageant dès sa génération : son annulation suit une politique de pénalité définie contractuellement avec le partenaire maritime.

### 4.2  Module 2 — Géolocalisation & Dispatch Intelligent

Ce module reproduit le modèle de mise en relation à la demande (type Uber) pour les demandes de traction entre le port de Nouakchott et les entrepôts/clients finaux.

#### Indexation Spatiale & Matchmaking

- Utilisation de la bibliothèque Uber H3 pour discrétiser la carte en mailles hexagonales. Les requêtes de proximité sont effectuées au sein de ces index, évitant les calculs de distance coûteux en base de données.
- Calcul de l'itinéraire réel (matrice de distance) via l'API OSRM, intégrant les contraintes de gabarit routier pour les poids lourds.
- Algorithme de matchmaking prenant en compte : la distance GPS, la disponibilité du chauffeur, le type de remorque requis et l'historique de notation.
- Diffusion de la course aux chauffeurs les plus pertinents avec fenêtre d'acceptation limitée ; élargissement automatique du rayon de recherche en l'absence de réponse.

#### Télémétrie & Communication Temps Réel

- Protocole MQTT (broker Mosquitto) pour la transmission des positions GPS des téléphones des chauffeurs — réduit la consommation de données de 90 % par rapport au HTTP, crucial pour les zones à faible couverture réseau.
- Protocole WebSockets pour le push des mises à jour de statut vers les interfaces clients (« Chauffeur en route », « Au port », « Livraison effectuée »).
- Stockage en cache Redis des dernières coordonnées GPS connues pour un rafraîchissement ultra-rapide de la carte en temps réel.
- Mode dégradé Offline-First côté application chauffeur (file d'attente locale SQLite) avec synchronisation différée en cas de perte réseau.

#### Geofencing & Frais d'Immobilisation

- Définition de polygones virtuels (PostGIS) autour des terminaux portuaires et des entrepôts clients.
- Déclenchement automatique du compteur de frais d'immobilisation dès le franchissement de la barrière virtuelle, horodaté et consigné de manière immuable (cf. §8.1 — Preuve cryptographique).
- Génération automatique des justificatifs de facturation des frais de stationnement, éliminant les litiges contractuels.

#### Tarification & Politique d'Annulation

Sous-module nouveau, indispensable à la mise en production : la tarification doit être transparente et prévisible pour le client comme pour le chauffeur.

- Formule de prix transparente : tarif de base + (distance × tarif/km) + majoration selon type de remorque + majoration horaire éventuelle (nuit/heures de pointe).
- Affichage du prix estimé au client avant confirmation de la demande.
- Frais d'annulation configurables, appliqués si l'annulation intervient après acceptation par un chauffeur, afin de dédommager le temps de mobilisation.

#### Système de Notation & Réputation

- Notation bidirectionnelle (client ↔ chauffeur) sur 5 étoiles après chaque course, avec commentaire optionnel.
- La note moyenne du chauffeur influence sa priorité dans l'algorithme de matchmaking.
- Tout chauffeur dont la note moyenne descend sous un seuil configurable est signalé pour revue par un administrateur (formation, avertissement, suspension).
User stories

- En tant que client, je veux voir le prix estimé de ma course avant de la confirmer, afin de budgétiser mon transport.
- En tant que chauffeur, je veux recevoir les demandes de courses pertinentes par rapport à ma position et mon type de remorque, afin de maximiser mon temps utile.
- En tant que client, je veux suivre en temps réel la position du tracteur envoyé, afin d'anticiper l'heure d'arrivée.
- En tant qu'administrateur, je veux être alerté quand un chauffeur a une note moyenne basse, afin d'agir avant que cela n'affecte l'expérience client.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M2-001 | Le système DOIT indexer la position de chaque tracteur disponible en maille H3 (résolution 8 recommandée). | P1 |
| RF-M2-002 | Le système DOIT calculer l'itinéraire réel via OSRM en tenant compte des contraintes de gabarit poids lourd. | P1 |
| RF-M2-003 | L'algorithme de matchmaking DOIT pondérer distance, disponibilité, type de remorque et note moyenne. | P1 |
| RF-M2-004 | Le système DOIT diffuser la course aux chauffeurs pertinents avec fenêtre d'acceptation de 60 s, puis élargir la recherche. | P2 |
| RF-M2-005 | Le système DOIT recevoir la position GPS des chauffeurs via MQTT à fréquence configurable (10-30 s). | P1 |
| RF-M2-006 | Le système DOIT pousser les mises à jour de statut via WebSockets en moins de 2 secondes. | P1 |
| RF-M2-007 | Le système DOIT mettre en cache Redis les dernières coordonnées connues de chaque chauffeur. | P1 |
| RF-M2-008 | L'application chauffeur DOIT fonctionner en mode dégradé hors-ligne avec synchronisation différée. | P2 |
| RF-M2-009 | Le système DOIT permettre la définition de zones geofence (PostGIS) autour des sites portuaires/entrepôts. | P2 |
| RF-M2-010 | Le système DOIT déclencher automatiquement le compteur d'immobilisation à l'entrée en zone, horodatage immuable. | P2 |
| RF-M2-011 | Le système DOIT générer automatiquement les justificatifs de facturation des frais de stationnement. | P2 |
| RF-M2-012 | Le système DOIT calculer et afficher le prix estimé d'une course avant confirmation, selon une formule transparente. | P1 |
| RF-M2-013 | Le système DOIT appliquer des frais d'annulation configurables après acceptation par un chauffeur. | P2 |
| RF-M2-014 | Le système DOIT permettre une notation bidirectionnelle sur 5 étoiles après chaque course. | P2 |
| RF-M2-015 | Le système DOIT signaler pour revue tout chauffeur dont la note moyenne passe sous un seuil configurable. | P2 |

#### Modèle de Données du Module 2

| Entité | Champs clés | Description |
|---|---|---|
| Tractor | id, owner_id, plate_number, trailer_type, capacity_t, status | Véhicule tracteur enregistré sur la plateforme. |
| Driver | id, user_id, license_number, rating_avg, current_h3_index | Profil chauffeur et sa position indexée. |
| DispatchRequest | id, client_id, pickup_point, dropoff_point, trailer_type_required, status | Demande de transport émise par un client. |
| Trip | id, request_id, driver_id, tractor_id, distance_km, price, status, started_at, completed_at | Course réalisée ou en cours. |
| GeofenceZone | id, name, polygon, type (port/entrepôt) | Zone géographique surveillée. |
| DemurrageCharge | id, trip_id, zone_id, entry_time, exit_time, amount | Frais d'immobilisation calculés. |
| Rating | id, trip_id, rated_by, rated_user, score, comment | Évaluation bidirectionnelle post-course. |

#### Endpoints API Principaux

| Méthode | Endpoint | Description |
|---|---|---|
| POST | /api/v1/dispatch/requests | Créer une demande de transport et lancer le matchmaking. |
| GET | /api/v1/dispatch/requests/{id}/matches | Obtenir la liste des chauffeurs proposés. |
| POST | /api/v1/trips/{id}/accept | Le chauffeur accepte la course. |
| PATCH | /api/v1/trips/{id}/status | Mettre à jour le statut d'une course. |
| WS | /ws/trips/{id}/location | Flux temps réel de la position du tracteur (WebSocket). |
| POST | /api/v1/geofences | Créer une zone geofence (rôle Administrateur). |
| GET | /api/v1/trips/{id}/demurrage | Consulter les frais d'immobilisation d'une course. |
| POST | /api/v1/trips/{id}/rating | Soumettre une notation post-course. |

### 4.3  Module 3 — Passerelle FinTech & Paiements

Ce module est le pilier financier de la plateforme. Il transforme les paiements en monnaie locale (MRU) en règlements internationaux en devises étrangères, de manière sécurisée et quasi instantanée, et centralise désormais tous les flux financiers des six modules dans un portefeuille unifié.

#### Agrégateur de Wallets Mobiles Locaux

- Intégration API des quatre principales solutions de mobile banking mauritaniennes : Bankily, Masrivi, Click et Sadad.
- Couche d'abstraction de routage intelligent : l'utilisateur sélectionne son wallet préféré, le système route automatiquement la requête vers l'API correspondante.
- Mécanisme de réconciliation automatique des paiements entrants pour mise à jour des soldes des comptes escrow de la plateforme.

#### Règlement Fournisseur International (FX & SWIFT)

- Connexion sécurisée (standards ISO 20022) avec une banque partenaire locale pour l'accès aux flux de change en temps réel.
- Calcul automatique du taux de change appliqué : cours spot + marge commerciale (spread) transparente pour l'importateur.
- Génération automatique d'un ordre de virement international (réseau SWIFT) vers le compte du fournisseur étranger dès confirmation du dépôt en MRU.

#### Sécurité des Transactions Financières

Le détail des exigences de sécurité est consolidé au §8.3 (Conformité & Isolation des Données Financières). Synthèse applicable à ce module :

- Authentification double facteur obligatoire pour toute transaction (OTP par SMS ou push notification).
- Chiffrement des clés d'API bancaires via module HSM ou coffre-fort numérique HashiCorp Vault.
- Isolation complète de la base de données FinTech, chiffrement au repos (AES-256).
- Système d'escrow : les fonds sont bloqués à la confirmation de la commande et libérés uniquement à la validation de la livraison ou du service rendu.

#### Facturation, Comptabilité & Portefeuille Unifié

| Extension majeure —  / Nouveauté v2.0 : avec l'ajout des Modules 4 (Audit) et 5 (Location d'équipements), Faci-lity gère désormais des flux financiers hétérogènes (fret, courses, frais d'audit, locations et cautions). Un portefeuille unifié par utilisateur devient indispensable à l'expérience « tout-en-un ». |
|---|

- Portefeuille (wallet) unique par utilisateur consolidant tous les mouvements, quel que soit le module d'origine (fret, dispatch, audit, location d'équipement).
- Génération automatique d'une facture PDF pour chaque transaction, conforme aux mentions légales mauritaniennes.
- Export comptable (CSV/Excel) de l'historique des transactions sur une période donnée, pour les entreprises et leur comptabilité.
- Tableau de bord consolidé filtrable par module, statut et période.
User stories

- En tant qu'utilisateur, je veux payer indifféremment via Bankily, Masrivi, Click ou Sadad, afin d'utiliser le service auquel je suis déjà habitué.
- En tant qu'entreprise cliente, je veux exporter l'ensemble de mes transactions Faci-lity du mois, afin de simplifier ma comptabilité.
- En tant que loueur d'équipement, je veux que ma caution reçue soit bloquée en escrow jusqu'au retour du matériel, afin de me prémunir contre les dommages.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M3-001 | Le système DOIT intégrer les API des wallets Bankily, Masrivi, Click et Sadad via une couche d'abstraction commune. | P1→P2 |
| RF-M3-002 | Le système DOIT router automatiquement la requête de paiement vers l'API du wallet sélectionné. | P1 |
| RF-M3-003 | Le système DOIT réconcilier automatiquement les paiements entrants avec les soldes escrow. | P1 |
| RF-M3-004 | Le système DOIT se connecter en ISO 20022 à une banque partenaire pour les flux de change temps réel. | P2 |
| RF-M3-005 | Le système DOIT calculer et afficher le taux de change (spot + spread) avant confirmation. | P2 |
| RF-M3-006 | Le système DOIT générer automatiquement un ordre SWIFT dès confirmation du dépôt en MRU. | P2 |
| RF-M3-007 | Toute transaction DOIT être protégée par authentification à deux facteurs. | P1 |
| RF-M3-008 | Les fonds DOIVENT être bloqués en escrow à la commande et libérés à validation de livraison/service. | P1 |
| RF-M3-009 | Le système DOIT proposer un portefeuille unifié par utilisateur regroupant tous les flux multi-modules. | P2 |
| RF-M3-010 | Le système DOIT générer automatiquement une facture PDF pour chaque transaction. | P2 |
| RF-M3-011 | Le système DOIT permettre l'export comptable CSV/Excel de l'historique de transactions. | P3 |
| RF-M3-012 | Le système DOIT afficher un tableau de bord consolidé filtrable par module/statut/période. | P2 |

#### Modèle de Données du Module 3

| Entité | Champs clés | Description |
|---|---|---|
| Wallet | id, user_id, balance_mru, currency, status | Portefeuille unifié d'un utilisateur. |
| Transaction | id, wallet_id, type, amount, currency, module_origin, status, created_at | Mouvement financier, quel que soit le module source. |
| EscrowHold | id, transaction_id, related_entity_type, related_entity_id, status, held_at, released_at | Fonds bloqués jusqu'à réalisation d'une condition. |
| Invoice | id, transaction_id, pdf_url, issued_at | Facture générée automatiquement. |
| FXRate | id, currency_pair, spot_rate, spread, effective_at | Cours de change appliqué à un instant donné. |
| PaymentMethod | id, user_id, wallet_provider, external_account_ref | Moyen de paiement enregistré (wallet mobile). |
| SwiftOrder | id, transaction_id, beneficiary_bank, iban_swift, amount, currency, status | Ordre de virement international. |

#### Endpoints API Principaux

| Méthode | Endpoint | Description |
|---|---|---|
| POST | /api/v1/wallet/topup | Recharger le portefeuille via un wallet mobile local. |
| POST | /api/v1/payments | Initier un paiement pour un module donné (module_origin). |
| GET | /api/v1/wallet/balance | Consulter le solde du portefeuille. |
| GET | /api/v1/wallet/transactions | Historique des transactions, filtrable par module/période. |
| POST | /api/v1/payments/{id}/escrow/release | Libérer des fonds bloqués en escrow. |
| POST | /api/v1/fx/quote | Obtenir un taux de change en temps réel. |
| POST | /api/v1/fx/swift-orders | Générer un ordre de virement SWIFT. |
| GET | /api/v1/invoices/{id} | Télécharger une facture PDF. |
| GET | /api/v1/wallet/export | Exporter l'historique de transactions (CSV/Excel). |

### 4.4  Module 4 — Audit, Inspection & Certification des Marchandises

| Nouveau module —  / Module entièrement nouveau dans la v2.0, demandé pour fiabiliser l'ensemble du parcours logistique : chaque partie prenante (importateur, transporteur, loueur d'équipement, assureur) doit pouvoir prouver l'état exact d'une marchandise ou d'un équipement à un instant donné. |
|---|

#### Vue d'Ensemble & Enjeux

L'absence d'un mécanisme d'audit standardisé est l'une des premières causes de litiges commerciaux dans la chaîne logistique : marchandise endommagée non documentée, écarts de quantité non prouvés, équipement loué rendu détérioré sans preuve de l'état de départ. Le Module 4 répond à ce besoin en digitalisant l'inspection physique et en la rendant opposable grâce à l'horodatage, la géolocalisation et le hachage cryptographique (cf. §8.1 — Preuve de Livraison Cryptographique, dont ce module est une généralisation).

#### Types d'Audits Supportés

| Type d'audit | Déclencheur | Réalisé par |
|---|---|---|
| Inspection avant expédition (PSI) | Demande volontaire de l'importateur, ou exigence contractuelle du fournisseur/assureur | Auditeur tiers certifié |
| Mise en / hors conteneur (Stuffing / Destuffing) | Chargement ou déchargement physique du conteneur | Auditeur Faci-lity ou tiers, sur site |
| Réception entrepôt | Arrivée de la marchandise chez le client | Auditeur ou auto-inspection guidée par le client |
| Livraison finale (PoD enrichi) | Fin de course de dispatch (Module 2) | Chauffeur + réceptionnaire (signature croisée) |
| État des lieux de location (M5) | Début et fin d'une location d'équipement | Loueur + locataire, ou auditeur tiers si litige |
| Conformité qualité | Sur demande, avant règlement final du fournisseur | Auditeur tiers certifié |

#### Processus d'Audit Digital

- Création de la demande — manuelle par un utilisateur, ou déclenchée automatiquement par un événement d'un autre module (ex. fin de course M2, fin de location M5) ; sélection du type d'audit et de la checklist associée.
- Assignation de l'auditeur — auto-assignation au chauffeur/réceptionnaire pour les inspections simples, ou attribution à un auditeur tiers disponible par proximité géographique (index H3, même logique que le Module 2).
- Réalisation sur site — parcours d'une checklist configurable par catégorie de marchandise ou d'équipement, avec photo géolocalisée et horodatée obligatoire à chaque point de contrôle.
- Signature électronique — de toutes les parties présentes (auditeur, réceptionnaire, chauffeur selon le contexte).
- Détection automatique d'anomalies — écart entre quantité/état déclaré et quantité/état constaté.
- Génération du rapport — rapport d'inspection PDF horodaté, avec hash cryptographique SHA-256 inscrit au registre immuable.
- Notification & clôture — toutes les parties prenantes sont notifiées ; le dossier est clos ou un litige est ouvert automatiquement si une anomalie a été détectée.

#### Gestion des Anomalies & Litiges

Workflow de résolution des litiges, conçu pour être équitable et traçable pour toutes les parties :

- Détection d'un écart → statut « Anomalie détectée », preuve (photos, rapport) automatiquement jointe au dossier.
- Notification immédiate des parties concernées (client, transitaire, transporteur ou loueur selon le contexte).
- Fenêtre de réponse contradictoire (48 heures par défaut, configurable) : la partie mise en cause peut apporter des preuves complémentaires.
- Escalade vers un administrateur/médiateur Faci-lity si aucun accord n'est trouvé.
- Décision consignée et statut « Litige résolu », avec répercussion financière automatique si applicable (libération partielle d'escrow, remboursement, pénalité — cf. Module 3).

#### Réseau d'Auditeurs Tiers Certifiés

- Processus d'accréditation : upload de certifications professionnelles, vérification manuelle par un administrateur avant activation du compte.
- Déclaration d'une zone de couverture géographique (mailles H3), utilisée pour l'assignation automatique des missions.
- Notation de l'auditeur après chaque mission par le donneur d'ordre, visible dans son profil.
- Modèle de rémunération : frais de mission facturés au donneur d'ordre via le Module 3, avec commission plateforme.
User stories

- En tant qu'importateur, je veux commander une inspection avant expédition, afin de vérifier la conformité de ma marchandise avant de payer le solde à mon fournisseur.
- En tant que loueur d'équipement, je veux un état des lieux photographié et horodaté au départ de la location, afin de me protéger en cas de dommage constaté au retour.
- En tant que client, je veux être notifié immédiatement si un audit détecte une anomalie sur ma marchandise, afin de réagir rapidement.
- En tant qu'auditeur tiers certifié, je veux recevoir les missions proches de ma localisation, afin d'optimiser mes déplacements.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M4-001 | Le système DOIT permettre la création d'une demande d'audit en spécifiant son type parmi les six supportés. | P2→P3 |
| RF-M4-002 | Chaque type d'audit DOIT être associé à une checklist configurable par catégorie de marchandise ou d'équipement. | P3 |
| RF-M4-003 | Le système DOIT assigner automatiquement un auditeur disponible par proximité géographique (index H3). | P3 |
| RF-M4-004 | L'application d'audit mobile DOIT imposer une photo géolocalisée et horodatée à chaque point de contrôle. | P2 |
| RF-M4-005 | Le système DOIT permettre la signature électronique de toutes les parties présentes lors de l'inspection. | P2 |
| RF-M4-006 | Le système DOIT détecter automatiquement les écarts entre état/quantité déclaré et constaté. | P3 |
| RF-M4-007 | Le système DOIT générer un rapport PDF horodaté avec hash SHA-256 inscrit au registre immuable. | P2 |
| RF-M4-008 | Le système DOIT notifier automatiquement toutes les parties prenantes à la clôture d'un audit. | P2 |
| RF-M4-009 | En cas d'anomalie, le système DOIT ouvrir automatiquement un dossier de litige et geler l'escrow concerné. | P3 |
| RF-M4-010 | Le système DOIT offrir une fenêtre de réponse contradictoire configurable (48h par défaut). | P3 |
| RF-M4-011 | Le système DOIT permettre l'accréditation d'auditeurs tiers avec validation manuelle par un administrateur. | P3 |
| RF-M4-012 | Le système DOIT permettre la notation d'un auditeur après chaque mission. | P3 |

#### Modèle de Données du Module 4

| Entité | Champs clés | Description |
|---|---|---|
| AuditRequest | id, type, related_entity_type, related_entity_id, requested_by, status | Demande d'audit, liée à une expédition, une course ou une location. |
| ChecklistTemplate | id, name, category, applicable_to (marchandise/équipement) | Modèle de checklist réutilisable. |
| ChecklistItem | id, template_id, label, requires_photo, order | Point de contrôle individuel d'une checklist. |
| AuditReport | id, request_id, auditor_id, status, pdf_url, hash_sha256, completed_at | Rapport d'inspection finalisé. |
| AuditEvidence | id, report_id, checklist_item_id, photo_url, gps_lat, gps_lng, timestamp | Preuve photographique géolocalisée d'un point de contrôle. |
| Discrepancy | id, report_id, description, severity, declared_value, observed_value | Écart constaté entre déclaré et observé. |
| Dispute | id, discrepancy_id, status, opened_at, resolved_at, resolution_notes | Litige ouvert suite à une anomalie. |
| Auditor | id, user_id, certification_docs, coverage_zone_h3, rating_avg | Profil d'un auditeur tiers accrédité. |

#### Endpoints API Principaux

| Méthode | Endpoint | Description |
|---|---|---|
| POST | /api/v1/audits/requests | Créer une demande d'audit. |
| GET | /api/v1/audits/requests/{id} | Consulter le statut d'une demande d'audit. |
| POST | /api/v1/audits/{id}/evidence | Uploader une preuve (photo géolocalisée) pour un point de contrôle. |
| POST | /api/v1/audits/{id}/sign | Apposer la signature électronique d'une partie. |
| POST | /api/v1/audits/{id}/complete | Clôturer l'audit et générer le rapport PDF + hash. |
| GET | /api/v1/audits/{id}/report | Télécharger le rapport d'inspection. |
| POST | /api/v1/disputes | Ouvrir un litige suite à une anomalie détectée. |
| PATCH | /api/v1/disputes/{id}/resolve | Enregistrer la résolution d'un litige. |
| GET | /api/v1/auditors/nearby | Rechercher les auditeurs disponibles à proximité. |

### 4.5  Module 5 — Marketplace de Location d'Équipements Lourds

| Nouveau module —  / Module entièrement nouveau dans la v2.0. Positionnement produit : un « Airbnb / Uber de l'équipement lourd » intégré à Faci-lity — les clients louent à la demande le matériel nécessaire au chargement, déchargement et aux travaux, et les propriétaires de flotte monétisent leurs actifs inutilisés. |
|---|

#### Vue d'Ensemble & Positionnement

En Mauritanie comme ailleurs, une part importante du parc d'engins lourds (BTP, portuaire, manutention) reste sous-utilisée en dehors des chantiers ou opérations de leurs propriétaires. Le Module 5 crée une marketplace bifaces : d'un côté les Loueurs (entreprises de location, opérateurs portuaires, particuliers propriétaires) publient leurs équipements disponibles ; de l'autre, les clients (importateurs, entreprises de BTP, transitaires) réservent le matériel nécessaire directement dans l'application, avec ou sans opérateur.

| Distinction avec le Module 2 —  / Le Module 2 gère des courses ponctuelles de transport (couple chauffeur + tracteur, facturées au trajet, matching temps réel type Uber). Le Module 5 gère la location de l'ÉQUIPEMENT lui-même, sur des durées plus longues (heure, jour, semaine, mois), pour des besoins de manutention, de BTP ou de logistique portuaire élargie. Un même utilisateur peut être à la fois Transporteur (M2) et Loueur (M5) — voir §2.1. Ces deux modules partagent l'infrastructure de géolocalisation (H3, MQTT) mais restent fonctionnellement distincts. |
|---|

#### Catalogue d'Équipements Supportés

Catalogue de lancement couvrant les demandes explicitement identifiées ; conçu comme extensible (§ RF-M5-006) pour accueillir de nouvelles catégories sans développement additionnel.

| Catégorie | Sous-types / Exemples | Usage typique | Unité habituelle |
|---|---|---|---|
| Bennes (Camions-bennes) | Benne simple, benne semi-remorque, benne TP | Évacuation de gravats, transport de matériaux en vrac (sable, gravier) | Jour / Chantier |
| Reach Stacker | Reach stacker 40-45t, version heavy-duty | Empilage et manutention de conteneurs en cour ou terminal | Heure / Jour |
| Tracteurs Routiers & Châssis Porte-conteneurs | Tracteur routier 4x2/6x4, châssis 20'/40' | Transport routier de conteneurs entre port, entrepôts et chantiers | Jour / Trajet |
| Chariots Élévateurs (Forklifts) | Forklift thermique 3-5t, électrique, mât rétractable | Chargement/déchargement et manutention en entrepôt | Heure / Jour |
| Bulldozers | Bulldozer sur chenilles, petit/moyen/grand gabarit | Terrassement, nivellement de terrain | Jour / Semaine |
| Compacteurs | Rouleau compresseur, plaque vibrante | Compactage de sol, travaux routiers | Jour / Semaine |
| Chargeuses (Loaders) | Chargeuse frontale sur pneus, mini-chargeuse | Chargement de matériaux en vrac, déblaiement | Jour / Semaine |

Chaque catégorie supporte une option « avec opérateur » ou « sans opérateur » (location sèche), configurable par le loueur au niveau de chaque annonce.

#### Cycle de Vie d'une Annonce et d'une Réservation

- Inscription & vérification du loueur (KYC équipement) — soumission des documents obligatoires par engin (carte grise, assurance, contrôle technique/conformité) ; validation par un modérateur avant activation.
- Publication de l'annonce — photos, caractéristiques techniques, tarifs (horaire/jour/semaine/mois), zone et calendrier de disponibilité, option avec/sans opérateur.
- Recherche & filtre côté client — par catégorie, localisation/rayon, dates, capacité et prix.
- Réservation & contrat digital — génération automatique du contrat de location (conditions générales, montant de la franchise/caution).
- Paiement sécurisé — règlement via la passerelle FinTech (Module 3) ; la caution est bloquée en escrow.
- État des lieux de départ — déclenchement automatique d'un audit de type « état des lieux » (Module 4) avant remise de l'équipement.
- Suivi pendant la location — géolocalisation optionnelle pour les engins motorisés (réutilise l'infrastructure MQTT du Module 2).
- État des lieux de retour — nouvel audit (Module 4) à restitution, comparé automatiquement à l'état de départ.
- Libération de la caution — totale, partielle ou ouverture d'un litige selon les écarts constatés entre les deux états des lieux.
- Notation bidirectionnelle — loueur et locataire s'évaluent mutuellement, alimentant la réputation de chacun.

#### Vérification & Conformité des Loueurs

- Documents obligatoires par équipement : carte grise/certificat d'immatriculation, attestation d'assurance en cours de validité, certificat de contrôle technique ou de conformité.
- Statuts de vérification : En attente, Vérifié, Rejeté, Expiré — un équipement au statut différent de « Vérifié » ne peut pas être publié en annonce.
- Alertes automatiques de renouvellement envoyées au loueur avant l'expiration de l'assurance ou du contrôle technique.
- Si la location inclut un opérateur, le permis/habilitation de l'opérateur est également vérifié.

#### Modèle Économique & Tarification

- Commission plateforme prélevée automatiquement sur chaque transaction de location (taux configurable par l'administrateur, ex. 10-15 %).
- Structure tarifaire flexible définie par le loueur : taux horaire, journalier, hebdomadaire et/ou mensuel.
- Majoration automatique du tarif si l'option « avec opérateur » est sélectionnée.
- Montant de caution minimum recommandé par catégorie d'équipement (paramétrable par l'administrateur), ajustable à la hausse par le loueur.
User stories

- En tant qu'entreprise de BTP, je veux louer un bulldozer pour 3 jours directement dans l'application, afin de démarrer mon chantier sans passer par un intermédiaire.
- En tant que loueur possédant un reach stacker sous-utilisé en semaine, je veux le proposer à la location, afin de générer un revenu complémentaire sur un actif dormant.
- En tant que locataire, je veux un état des lieux photographié au départ et au retour, afin d'éviter tout litige sur l'état de l'équipement restitué.
- En tant qu'administrateur, je veux valider les documents d'un loueur avant qu'il ne puisse publier une annonce, afin de garantir la sécurité et la conformité de la marketplace.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M5-001 | Le système DOIT permettre à un loueur de s'inscrire et de soumettre les documents KYC de chaque équipement. | P3 |
| RF-M5-002 | Un équipement DOIT être au statut « Vérifié » par un modérateur avant de pouvoir être publié en annonce. | P3 |
| RF-M5-003 | Le système DOIT alerter automatiquement le loueur avant l'expiration de l'assurance ou du contrôle technique. | P3 |
| RF-M5-004 | Le système DOIT permettre la publication d'une annonce avec photos, caractéristiques, tarifs et calendrier. | P3 |
| RF-M5-005 | Le système DOIT permettre la recherche d'équipements par catégorie, localisation/rayon, dates et option opérateur. | P3 |
| RF-M5-006 | Le catalogue de catégories d'équipements DOIT être extensible par un administrateur, sans développement. | P3 |
| RF-M5-007 | Le système DOIT générer automatiquement un contrat de location numérique à la confirmation d'une réservation. | P3 |
| RF-M5-008 | Le système DOIT bloquer la caution en escrow (Module 3) à la confirmation du paiement. | P3 |
| RF-M5-009 | Le système DOIT déclencher automatiquement un état des lieux de départ (Module 4) avant remise de l'équipement. | P3 |
| RF-M5-010 | Le système DOIT déclencher automatiquement un état des lieux de retour (Module 4) en fin de location. | P3 |
| RF-M5-011 | Le système DOIT comparer les états des lieux de départ/retour et proposer une libération de caution totale, partielle, ou un litige. | P3 |
| RF-M5-012 | Le système DOIT permettre le suivi géolocalisé optionnel des équipements motorisés pendant la location. | P4 |
| RF-M5-013 | Le système DOIT permettre une notation bidirectionnelle loueur ↔ locataire après chaque location. | P3 |
| RF-M5-014 | Le système DOIT calculer et prélever automatiquement la commission plateforme sur chaque transaction. | P3 |

#### Modèle de Données du Module 5

| Entité | Champs clés | Description |
|---|---|---|
| EquipmentCategory | id, name, unit_types, icon | Catégorie du catalogue (bennes, reach stacker, etc.), extensible. |
| Equipment | id, owner_id, category_id, brand, model, year, capacity, with_operator_option, status | Engin physique enregistré par un loueur. |
| EquipmentDocument | id, equipment_id, type, file_url, expiry_date, status | Document KYC (carte grise, assurance, contrôle technique). |
| RentalListing | id, equipment_id, hourly_rate, daily_rate, weekly_rate, monthly_rate, deposit_amount, location | Annonce publiée avec tarification et disponibilité. |
| RentalBooking | id, listing_id, renter_id, start_date, end_date, with_operator, status, total_price | Réservation d'un équipement par un client. |
| RentalContract | id, booking_id, pdf_url, terms_version, signed_at | Contrat de location généré et signé. |
| Deposit | id, booking_id, amount, status, released_at | Caution bloquée en escrow puis libérée. |
| MaintenanceLog | id, equipment_id, date, description, performed_by | Historique de maintenance d'un équipement. |
| Review | id, booking_id, reviewer_id, reviewed_user_id, score, comment | Avis bidirectionnel post-location. |

#### Endpoints API Principaux

| Méthode | Endpoint | Description |
|---|---|---|
| POST | /api/v1/equipment | Enregistrer un nouvel équipement (loueur). |
| POST | /api/v1/equipment/{id}/documents | Uploader un document KYC pour un équipement. |
| GET | /api/v1/equipment/search | Rechercher des équipements disponibles (catégorie, zone, dates). |
| POST | /api/v1/rental-listings | Publier une annonce de location. |
| POST | /api/v1/rental-bookings | Réserver un équipement. |
| GET | /api/v1/rental-bookings/{id}/contract | Télécharger le contrat de location. |
| POST | /api/v1/rental-bookings/{id}/checkin | Déclencher l'état des lieux de départ (lien Module 4). |
| POST | /api/v1/rental-bookings/{id}/checkout | Déclencher l'état des lieux de retour (lien Module 4). |
| POST | /api/v1/rental-bookings/{id}/deposit/release | Libérer la caution après validation. |
| POST | /api/v1/rental-bookings/{id}/review | Soumettre un avis post-location. |

#### Considérations Légales & Assurance

| Point d'attention —  / Ce document est une spécification technique et non un avis juridique. Il est recommandé de faire valider par un conseil juridique local : le cadre contractuel type de location (avec/sans opérateur), la répartition de responsabilité en cas d'accident ou de dommage pendant la location, les niveaux d'assurance minimum exigés des loueurs, et la conformité au droit du travail mauritanien lorsque la location inclut la mise à disposition d'un opérateur. |
|---|

### 4.6  Module 6 — Éco-Logistique, IoT & SaaS B2B Flottes

Ce module prépare la plateforme à l'échelle industrielle et aux standards internationaux de durabilité et de connectivité des objets. Sa portée est élargie en v2.0 pour couvrir non seulement les flottes de transport, mais aussi le parc d'équipements loués/possédés du Module 5.

#### Optimisation des Retours à Vide

- Système de graphes connectant les points de livraison aux points de collecte d'exportation disponibles.
- Proposition automatique au chauffeur d'un trajet retour générant du revenu (marchandise à collecter sur le chemin du retour au port).
- Réduction estimée des trajets à vide de 30 à 50 %, avec impact direct sur la réduction des émissions de CO₂ et les coûts opérationnels.

#### Télémétrie IoT — Chaîne du Froid & Sécurité

- Base de données orientée séries temporelles (TimescaleDB ou InfluxDB) pour stocker les logs de température, d'humidité et de luminosité issus des capteurs physiques.
- Alertes en temps réel (push/SMS) en cas de dépassement des seuils critiques pour les conteneurs réfrigérés (pharmaceutique, agroalimentaire).
- Détection de choc/vibration anormale, utile pour la surveillance d'équipements du Module 5 en transit (ex. reach stacker transporté sur porte-char).

#### SaaS B2B pour la Gestion de Flottes Multi-Actifs

| Extension v2.0 —  / Élargi en v2.0 : le tableau de bord ne couvre plus seulement les flottes de transport, mais l'ensemble des actifs qu'un même propriétaire opère sur Faci-lity — tracteurs (M2) ET équipements loués (M5) — dans une vue consolidée unique. |
|---|

- Vue consolidée de l'ensemble des actifs d'une entreprise (tracteurs, bennes, reach stackers, forklifts, etc.), avec statut en temps réel.
- KPIs par actif : taux d'utilisation, revenus générés, coûts de maintenance, disponibilité.
- Alertes de maintenance préventive configurables (basées sur les heures d'utilisation ou le kilométrage).
- Rapports d'émissions CO₂ consolidés par client, période et type d'actif.

#### Empreinte Carbone & Reporting ESG

Positionnement à moyen terme : permettre aux grandes entreprises clientes (import/export, BTP) d'intégrer leurs indicateurs Faci-lity dans leur reporting RSE/ESG, argument différenciant pour l'expansion régionale (§9).

User stories

- En tant que chauffeur, je veux recevoir une proposition de trajet retour rémunéré, afin de ne pas rentrer à vide au port.
- En tant qu'importateur de produits pharmaceutiques, je veux être alerté immédiatement si la température de mon conteneur sort de la plage autorisée, afin de préserver ma marchandise.
- En tant qu'entreprise possédant plusieurs tracteurs et engins loués, je veux un tableau de bord unique de tous mes actifs, afin de piloter mon activité sans changer d'outil.
Exigences fonctionnelles

| ID | Exigence | Phase |
|---|---|---|
| RF-M6-001 | Le système DOIT proposer automatiquement au chauffeur un trajet retour générateur de revenu. | P4 |
| RF-M6-002 | Le système DOIT stocker les logs de température/humidité/luminosité IoT dans une base séries temporelles. | P4 |
| RF-M6-003 | Le système DOIT déclencher une alerte temps réel en cas de dépassement de seuil critique pour conteneurs réfrigérés. | P4 |
| RF-M6-004 | Le système DOIT fournir un tableau de bord SaaS B2B consolidé couvrant flottes de transport ET équipements loués. | P4 |
| RF-M6-005 | Le tableau de bord DOIT permettre le suivi des KPIs par actif (utilisation, revenus, maintenance). | P4 |
| RF-M6-006 | Le système DOIT générer un rapport d'émissions CO₂ consolidé par client/période/type d'actif. | P4 |
| RF-M6-007 | Le système DOIT permettre la configuration d'alertes de maintenance préventive. | P4 |

#### Modèle de Données du Module 6

| Entité | Champs clés | Description |
|---|---|---|
| ReturnTripOffer | id, trip_id, proposed_route, potential_revenue, status | Proposition de trajet retour rémunéré. |
| IoTSensor | id, equipment_or_container_id, type, last_reading_at | Capteur physique rattaché à un conteneur ou équipement. |
| TelemetryReading | id, sensor_id, timestamp, temperature, humidity, luminosity, shock_detected | Mesure horodatée issue d'un capteur. |
| AlertThreshold | id, sensor_id, metric, min_value, max_value | Seuil critique déclenchant une alerte. |
| FleetDashboardAsset | id, owner_id, asset_type, asset_id, utilization_rate, revenue_total | Vue agrégée d'un actif pour le tableau de bord. |
| CarbonReport | id, owner_id, period, co2_kg, generated_at | Rapport d'émissions CO₂ généré. |

#### Endpoints API Principaux

| Méthode | Endpoint | Description |
|---|---|---|
| GET | /api/v1/eco/return-trips/suggestions | Obtenir les propositions de trajet retour pour un chauffeur. |
| POST | /api/v1/iot/sensors/{id}/readings | Ingérer une mesure de télémétrie IoT. |
| GET | /api/v1/iot/sensors/{id}/alerts | Consulter l'historique d'alertes d'un capteur. |
| GET | /api/v1/fleet/dashboard | Obtenir la vue consolidée des actifs d'une entreprise. |
| GET | /api/v1/fleet/carbon-report | Générer le rapport d'émissions CO₂. |

## 5.  Exigences Non-Fonctionnelles

Section nouvelle dans la v2.0. Ces exigences s'appliquent transversalement aux six modules et doivent être intégrées dès la conception (« non-functional by design »), pas ajoutées a posteriori.

### 5.1  Performance & Scalabilité

- Temps de réponse API cible : < 300 ms (p95) pour les endpoints de lecture, < 800 ms (p95) pour les traitements complexes (ex. génération de déclaration douanière).
- Latence position GPS temps réel : < 2 secondes entre émission MQTT côté chauffeur et affichage sur la carte client.
- Recherche marketplace (fret M1 / équipements M5) : résultats affichés en < 500 ms pour un catalogue de 10 000 annonces actives.
- Chaque microservice DOIT être stateless et horizontalement scalable (sessions et caches externalisés dans Redis, aucun état en mémoire locale).
- Capacité cible : 5 000 utilisateurs actifs simultanés en Phase 2, 20 000 à partir de la Phase 4, via scalabilité horizontale derrière un load balancer.

### 5.2  Disponibilité & Fiabilité

- SLA cible : 99,5 % de disponibilité en Phases 1-2, 99,9 % à partir de la Phase 3 (hors maintenance planifiée annoncée).
- Sauvegardes automatiques quotidiennes de toutes les bases de données, rétention minimale de 30 jours, tests de restauration trimestriels.
- Réplication multi-zone de disponibilité (multi-AZ) pour PostgreSQL et Redis en production.
- Plan de reprise d'activité (PRA) : objectif de temps de reprise (RTO) < 4h, objectif de perte de données maximale (RPO) < 1h, exigence renforcée pour le Module 3 (FinTech).

### 5.3  Internationalisation & Accessibilité

- Langues supportées : Français (langue principale dès le MVP), Arabe (langue officielle de Mauritanie, support RTL complet dès la Phase 2), Anglais (Phase 3, pour les partenaires maritimes internationaux).
- Le support de l'arabe DOIT inclure un layout miroir complet (RTL), pas seulement la traduction des textes.
- Conformité visée : WCAG 2.1 niveau AA pour la Web App (contrastes, navigation clavier, compatibilité lecteurs d'écran).

### 5.4  Compatibilité

- Navigateurs Web supportés : deux dernières versions majeures de Chrome, Firefox, Safari et Edge.
- Mobile : Android 9+ (API 28+) et iOS 14+.
- Mode hors-ligne (Offline-First) obligatoire pour les applications Chauffeur et Auditeur, avec file d'attente locale SQLite et synchronisation différée.
- Web responsive (desktop et tablette) ; applications mobiles natives optimisées smartphone.

### 5.5  Observabilité

- Logging centralisé de tous les microservices (stack type ELK ou solution managée équivalente).
- Monitoring et alerting temps réel (Prometheus + Grafana ou solution managée équivalente).
- Traçage distribué (distributed tracing, ex. OpenTelemetry) pour le débogage des flux inter-microservices.
- Tableau de bord de santé système accessible aux administrateurs : statut, latence et taux d'erreur par microservice.

### 5.6  Environnements & CI/CD

- Trois environnements minimum : Développement, Staging/Recette, Production.
- Pipeline CI/CD automatisé : tests unitaires et d'intégration à chaque commit, déploiement automatique en staging, déploiement en production validé manuellement.
- Stratégie de déploiement sans interruption (blue-green ou rolling deployment) pour les microservices critiques (M2 Dispatch, M3 FinTech).
- Tests de charge obligatoires avant toute mise en production majeure.

#### Synthèse des Cibles Mesurables

| Métrique | Cible | Applicable dès |
|---|---|---|
| Temps de réponse API (lecture, p95) | < 300 ms | Phase 1 |
| Latence position GPS temps réel | < 2 s | Phase 1 |
| Disponibilité (SLA) | 99,5 % → 99,9 % | Phase 1 → Phase 3 |
| RTO / RPO (Module FinTech) | < 4h / < 1h | Phase 2 |
| Utilisateurs actifs simultanés | 5 000 → 20 000 | Phase 2 → Phase 4 |
| Support RTL (Arabe) | Complet | Phase 2 |

## 6.  Modèle de Données Global & Relations Inter-Modules

Section nouvelle dans la v2.0. Le modèle de données de Faci-lity reste distribué (une base par microservice, cf. §3.1) mais certaines entités sont transverses et doivent être conçues en cohérence dès le départ pour éviter duplication et incohérences entre équipes.

### 6.1  Entité Utilisateur Unifiée

L'entité Utilisateur est la seule véritablement partagée par tous les modules (généralement via un service d'identité central exposant un jeton JWT contenant l'identifiant utilisateur et ses rôles). Chaque microservice ne stocke localement que les données spécifiques à son domaine, référencées par cet identifiant.

| Champ | Type | Description |
|---|---|---|
| id | UUID | Identifiant unique, utilisé comme clé étrangère dans tous les microservices. |
| full_name / company_name | string | Nom de la personne ou raison sociale. |
| phone_number | string | Identifiant de connexion principal (OTP par SMS). |
| email | string | Optionnel, utilisé pour les notifications et factures. |
| national_id_or_rc | string | Pièce d'identité ou registre de commerce (KYC). |
| roles | array&lt;enum&gt; | Un ou plusieurs rôles simultanés — cf. §2.1 (Importateur, Transporteur, Loueur, Auditeur…). |
| preferred_language | enum [fr, ar, en] | Langue d'affichage préférée (cf. §5.3). |
| kyc_status | enum [pending, verified, rejected] | Statut de vérification d'identité, prérequis à certaines actions sensibles. |
| created_at | timestamp | Date de création du compte. |

### 6.2  Relations Inter-Modules Clés

Ce tableau récapitule les relations qui traversent les frontières de microservices, base de la conception des contrats d'API et des événements asynchrones (cf. §3.4).

| Entité source | Entité source | Entité cible | Nature de la relation |
|---|---|---|---|
| Shipment (M1) | Shipment (M1) | AuditRequest (M4) | Une expédition peut déclencher un ou plusieurs audits (PSI, réception). |
| Trip (M2) | Trip (M2) | AuditRequest (M4) | Une course terminée déclenche un audit de livraison (PoD enrichi). |
| RentalBooking (M5) | RentalBooking (M5) | AuditRequest (M4) | Chaque réservation déclenche deux états des lieux (départ et retour). |
| VesselBooking / GroupageBooking (M1) | VesselBooking / GroupageBooking (M1) | Transaction (M3) | Le paiement d'une réservation de fret crée une transaction référencée. |
| RentalBooking (M5) | RentalBooking (M5) | Transaction / Deposit (M3) | Paiement de la location et blocage de la caution en escrow. |
| Equipment (M5) | Equipment (M5) | FleetDashboardAsset (M6) | Chaque équipement loué apparaît dans le tableau de bord flotte de son propriétaire. |
| Tractor (M2) | Tractor (M2) | FleetDashboardAsset (M6) | Chaque tracteur apparaît dans le tableau de bord flotte de son propriétaire. |
| User (transverse) | User (transverse) | Toutes les entités « owner »/« actor » | Référence centrale multi-rôles utilisée par tous les modules. |
| Bonne pratique microservices —  / Recommandation d'implémentation : chaque référence inter-modules (ex. shipment_id dans AuditRequest) doit être stockée comme un identifiant opaque (UUID), sans jointure SQL directe entre bases — la cohérence est assurée par les événements de la message queue (§3.4), pas par des clés étrangères physiques entre microservices. | Bonne pratique microservices —  / Recommandation d'implémentation : chaque référence inter-modules (ex. shipment_id dans AuditRequest) doit être stockée comme un identifiant opaque (UUID), sans jointure SQL directe entre bases — la cohérence est assurée par les événements de la message queue (§3.4), pas par des clés étrangères physiques entre microservices. | Bonne pratique microservices —  / Recommandation d'implémentation : chaque référence inter-modules (ex. shipment_id dans AuditRequest) doit être stockée comme un identifiant opaque (UUID), sans jointure SQL directe entre bases — la cohérence est assurée par les événements de la message queue (§3.4), pas par des clés étrangères physiques entre microservices. | Bonne pratique microservices —  / Recommandation d'implémentation : chaque référence inter-modules (ex. shipment_id dans AuditRequest) doit être stockée comme un identifiant opaque (UUID), sans jointure SQL directe entre bases — la cohérence est assurée par les événements de la message queue (§3.4), pas par des clés étrangères physiques entre microservices. |

## 7.  Stack Technologique Recommandée

Le tableau suivant présente la stack technologique retenue pour le développement de Faci-lity, avec la justification de chaque choix. Les lignes marquées « NOUVEAU » répondent aux besoins des Modules 4 et 5 introduits en v2.0.

| Couche Applicative | Technologie | Rôle & Justification |
|---|---|---|
| Mobile (Chauffeurs / Clients / Loueurs / Auditeurs) | Flutter (Dart) | Codebase unique pour iOS et Android. Cache SQLite local pour le mode Offline-First, essentiel dans les zones à couverture limitée. |
| Web (Admin / SaaS Flottes / Back-office Loueurs) | Next.js + TailwindCSS | Rendu côté serveur (SSR) pour une vitesse d'affichage maximale des tableaux de bord et de la carte live. Déploiement optimisé sur Vercel ou AWS. |
| Back-End API | NestJS (TypeScript) | Framework structuré, modulaire et fortement typé. Idéal pour maintenir un code propre au sein d'une équipe et imposer les standards de sécurité. |
| Base de Données Principale | PostgreSQL + PostGIS | Base relationnelle robuste pour comptes, factures et contrats. PostGIS permet les requêtes géospatiales natives (positions, geofencing). |
| Cache & Temps Réel | Redis | Stockage en RAM des dernières coordonnées GPS. Gestion des sessions et des locks de transactions FinTech. |
| Message Queue | RabbitMQ / Kafka | Gestion asynchrone des files : notifications, traitements IA documentaire, événements inter-modules (§3.4), sans bloquer l'API principale. |
| Stockage Cloud | AWS S3 | Stockage sécurisé des documents douaniers, preuves d'audit et contrats de location. Accès uniquement via URLs signées temporaires (15 min max). |
| IoT & Séries Temporelles | TimescaleDB / InfluxDB | Ingestion haute fréquence des données de télémétrie (température, GPS, chocs) depuis les capteurs physiques. |
| Recherche & Indexation (NOUVEAU) | Elasticsearch ou Algolia | Recherche full-text et géo-filtrée performante pour les marketplaces de fret (M1) et d'équipements (M5) — cible &lt; 500 ms (§5.1). |
| Notifications Push (NOUVEAU) | Firebase Cloud Messaging | Notifications push mobiles multiplateformes, consommées depuis la message queue par le service de notification (§3.3). |
| Catalogues & Avis (NOUVEAU) | MongoDB | Stockage schema-less adapté aux attributs variables des annonces (équipements M5) et aux avis/notations (reviews). |
| Signature Électronique (NOUVEAU) | Solution tierce (ex. API de signature électronique) ou implémentation interne | Signature des contrats de location (M5) et rapports d'audit (M4) par toutes les parties. |
| Observabilité (NOUVEAU) | Prometheus + Grafana, stack ELK | Monitoring, logs centralisés et alerting transverses à tous les microservices (§5.5). |

## 8.  Sécurité, Traçabilité & Immuabilité

La confiance entre les transporteurs, les importateurs, les loueurs d'équipement, les auditeurs et les institutions financières repose sur des mécanismes de sécurité irréfutables. Les exigences suivantes sont non négociables dans l'implémentation.

### 8.1  Preuve de Livraison Cryptographique (PoD)

À chaque changement de responsabilité (Sortie Port, Livraison Client, remise ou restitution d'un équipement loué, etc.), le système génère un condensat cryptographique (Hash SHA-256) contenant :

- Les coordonnées GPS précises au moment de l'événement.
- L'horodatage exact (timestamp UTC).
- La signature numérique du réceptionnaire (collectée via l'interface mobile).
Ce hash est stocké dans un registre immuable (journal d'audit infalsifiable), accessible à toutes les parties prenantes mais non modifiable. Ce mécanisme, initialement conçu pour la livraison (Module 2), est généralisé par le Module 4 à tous les points de contrôle : inspection, état des lieux de location, réception entrepôt.

### 8.2  Gestion des Accès aux Documents Sensibles

- Les documents douaniers, rapports d'audit et contrats de location hébergés sur AWS S3 ne sont jamais publics.
- Le système génère exclusivement des URLs signées temporaires (Presigned URLs) avec une validité maximale de 15 minutes.
- Chaque accès à un document est journalisé avec l'identité de l'utilisateur, l'horodatage et l'adresse IP.

### 8.3  Conformité & Isolation des Données Financières

- Isolation physique de la base de données FinTech sur un serveur dédié, non accessible depuis les autres microservices.
- Chiffrement au repos (AES-256) de toutes les tables contenant des données bancaires et des transactions financières.
- Chiffrement des communications (TLS 1.3) sur toutes les routes de l'API FinTech.
- Politique de rétention des données conforme aux réglementations bancaires mauritaniennes et aux standards PCI-DSS.

### 8.4  Sécurité du Module Audit

| Nouveau —  / Nouveau en v2.0 — la valeur probante du Module 4 dépend entièrement de l'intégrité technique de ses rapports. |
|---|

- Intégrité des rapports : un rapport d'audit finalisé et signé est immuable ; toute correction ultérieure crée une nouvelle version horodatée, jamais une édition en place.
- Non-répudiation : chaque signature électronique est associée à un identifiant utilisateur authentifié et horodatée, empêchant tout désaveu ultérieur.
- Vérification des preuves photographiques : contrôle de cohérence des métadonnées (géolocalisation, horodatage) avant acceptation, avec détection des tentatives de réutilisation d'une photo existante.

### 8.5  Sécurité du Module Location d'Équipements

| Nouveau —  / Nouveau en v2.0 — la marketplace d'équipements manipule à la fois des documents d'identité/KYC et des flux financiers de caution. |
|---|

- KYC obligatoire de tout loueur et de chaque équipement avant activation d'une annonce (cf. §4.5.5).
- Cautions strictement séparées dans un sous-compte escrow dédié par réservation, jamais mélangées à la trésorerie opérationnelle de la plateforme.
- Vérification périodique automatisée de la validité des documents d'assurance et de contrôle technique, avec suspension automatique de l'annonce en cas d'expiration non renouvelée.

### 8.6  Politique Générale de Sécurité

- Mots de passe hashés (bcrypt/argon2), politique de complexité minimale imposée ; authentification à deux facteurs obligatoire pour toute transaction financière (M3) et recommandée pour tous les comptes professionnels.
- Journal d'audit global : toute action sensible (connexion, changement de rôle, accès à un document confidentiel, validation KYC) est journalisée avec horodatage, utilisateur et adresse IP.
- Tests de pénétration (pentest) recommandés avant chaque mise en production majeure, en particulier pour le Module 3.
- Protection des données personnelles alignée sur les principes de minimisation, de finalité et de durée de conservation limitée, applicable dès lors que la plateforme traite des données d'identité (KYC) et de localisation en continu.

## 9.  Roadmap de Développement

La roadmap passe de trois à quatre phases en v2.0 afin d'accueillir la marketplace de fret élargie et le Module 5. Chaque exigence fonctionnelle du document (§4) porte une étiquette de phase (P1 à P4) directement exploitable pour le découpage en sprints.

Figure 2 — Feuille de route de développement sur 24 mois

### 9.1 · 0 à 6 mois  Phase 1 — MVP

- Module 1 (base) : pipeline IA documentaire, calcul du Landed Cost, interface importateur.
- Module 2 (dispatch basique) : tracking GPS chauffeurs, interface mobile Flutter.
- Module 3 : intégration initiale d'un wallet mobile (Bankily) pour les paiements locaux.
- Infrastructure cloud de base : PostgreSQL, Redis, AWS S3.

### 9.2 · 6 à 12 mois  Phase 2 — Extension

- Déploiement du groupage collaboratif (LCL) complet avec algorithme de packing 3D.
- Intégration des quatre wallets mobiles (Bankily, Masrivi, Click, Sadad) et activation de la passerelle FX internationale (SWIFT).
- Geofencing des terminaux portuaires et facturation automatique des frais d'immobilisation ; système de notation M2.
- Lancement initial du Module 4 : inspection de livraison (PoD enrichi), preuve cryptographique généralisée.

### 9.3 · 12 à 18 mois  Phase 3 — Marketplace Élargi & Location d'Équipements

| Nouvelle phase —  / Nouvelle phase en v2.0, portant les deux principales extensions demandées : la réservation d'espace navire et la marketplace de location d'équipements. |
|---|

- Marketplace de fret élargie (Module 1) : booking direct d'espace navire (FCL), calendrier des départs, comparateur de tarifs.
- Module 4 complet : tous types d'audits, réseau d'auditeurs tiers certifiés, gestion des litiges.
- Lancement complet du Module 5 : catalogue d'équipements, KYC loueurs, réservation, cautions escrow, contrats digitaux, états des lieux liés au Module 4.
- Portefeuille unifié et facturation consolidée (Module 3).

### 9.4 · 18 à 24+ mois  Phase 4 — Scale, IoT & Innovation

- Lancement du Module IoT : chaîne du froid connectée, alertes temps réel.
- Déploiement du SaaS B2B multi-actifs (Module 6) : tableau de bord consolidé flottes de transport et équipements loués.
- Algorithme d'optimisation des retours à vide et reporting CO₂/ESG.
- Suivi géolocalisé optionnel des équipements motorisés en location (Module 5).
- Expansion potentielle vers les marchés du Sénégal, du Mali et des pays du Sahel.

## 10.  Indicateurs de Succès (KPIs)

Section nouvelle dans la v2.0. Ces indicateurs, à instrumenter dès le lancement de chaque module, permettent de mesurer l'adoption produit indépendamment de l'avancement technique.

| Module | Indicateur | Cible indicative |
|---|---|---|
| M1 — Fret & IA | Taux de champs extraits validés sans correction manuelle | > 80 % d'ici Phase 2 |
| M1 — Fret & IA | Volume de fret transitant par la marketplace (LCL + FCL) | Croissance mensuelle continue |
| M2 — Dispatch | Temps moyen d'attribution d'une course (demande → acceptation) | < 5 minutes |
| M2 — Dispatch | Taux d'acceptation des courses proposées | > 70 % |
| M3 — FinTech | Taux de succès des transactions de paiement | > 99 % |
| M3 — FinTech | Délai moyen de traitement d'un virement SWIFT | < 48h ouvrées |
| M4 — Audit | Taux de litiges résolus dans la fenêtre contradictoire | > 90 % |
| M4 — Audit | Délai moyen de génération d'un rapport après clôture de mission | < 15 minutes |
| M5 — Location | Taux d'utilisation moyen des équipements listés | > 40 % après 6 mois |
| M5 — Location | Taux de cautions restituées sans litige | > 85 % |
| M6 — Éco/IoT | Réduction des trajets à vide | 30 à 50 % |
| Transverse | Taux de rétention utilisateurs à 90 jours | > 60 % |
| Transverse | Net Promoter Score (NPS) | > 40 |

## 11.  Annexe A — Glossaire Technique

| Terme | Définition |
|---|---|
| LCL | Less than Container Load — groupage de marchandises de plusieurs expéditeurs dans un même conteneur. |
| FCL | Full Container Load — location d'un conteneur maritime complet par un seul expéditeur, par opposition au LCL. |
| Bin Packing Problem | Problème d'optimisation combinatoire consistant à ranger des objets de tailles variables dans un nombre minimal de contenants de capacité fixe ; utilisé pour le calcul de remplissage des conteneurs groupés. |
| OSRM | Open Source Routing Machine — moteur open-source de calcul d'itinéraires routiers optimisés. |
| Uber H3 | Bibliothèque d'indexation géospatiale développée par Uber, divisant la surface terrestre en cellules hexagonales hiérarchiques. |
| MQTT | Message Queuing Telemetry Transport — protocole de messagerie léger idéal pour les objets connectés et les connexions instables. |
| Geofencing | Technique de définition de zones géographiques virtuelles déclenchant des actions automatiques lors de leur franchissement. |
| PoD | Proof of Delivery — preuve de livraison (ou d'inspection) cryptographiquement signée et horodatée. |
| SH / HS Code | Système Harmonisé — nomenclature internationale standardisée pour la classification des marchandises en douane. |
| Landed Cost | Coût total d'une importation incluant prix d'achat, fret, assurance, droits de douane et taxes locales. |
| RAG | Retrieval-Augmented Generation — technique IA combinant une base de connaissances avec un modèle de langage pour des réponses précises et actualisées. |
| HSM | Hardware Security Module — composant physique dédié à la gestion sécurisée des clés cryptographiques. |
| ISO 20022 | Standard international pour les messages de paiement électronique entre institutions financières. |
| Escrow | Compte séquestre — mécanisme de blocage temporaire de fonds jusqu'à la réalisation d'une condition contractuelle (livraison, retour d'équipement en bon état). |
| KYC | Know Your Customer — processus de vérification de l'identité d'un utilisateur ou de la conformité documentaire d'un équipement avant activation. |
| RBAC | Role-Based Access Control — modèle de contrôle d'accès basé sur les rôles attribués à chaque utilisateur (cf. §2.2). |
| Reach Stacker | Engin de manutention portuaire permettant d'empiler et de déplacer des conteneurs sans portique fixe. |
| Booking Note | Document de réservation confirmant l'engagement d'un espace de fret sur un navire. |
| Sailing Schedule | Calendrier des départs et arrivées de navires publié par les compagnies maritimes ou leurs agents. |
| SLA | Service Level Agreement — engagement contractuel de niveau de service (ex. taux de disponibilité). |
| RTO / RPO | Recovery Time Objective / Recovery Point Objective — délai cible de reprise d'activité et perte de données maximale tolérée après un incident. |
| WCAG | Web Content Accessibility Guidelines — référentiel international de bonnes pratiques d'accessibilité numérique. |

— Fin du document —

© 2026 Faci-lity — Document confidentiel, toute reproduction interdite sans autorisation écrite.

