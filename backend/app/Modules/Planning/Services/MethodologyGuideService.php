<?php

namespace App\Modules\Planning\Services;

class MethodologyGuideService
{
    /**
     * Retourne l'ensemble des guides méthodologiques et matrices de cotation.
     */
    public function getAllGuides(): array
    {
        return [
            'risks_opportunities' => $this->getRisksOpportunitiesGuide(),
            'duerp' => $this->getDuerpGuide(),
            'aes' => $this->getAesGuide(),
            'process_reviews' => $this->getProcessReviewGuide(),
            'management_reviews' => $this->getManagementReviewGuide(),
        ];
    }

    /**
     * Guide méthodologique et matrice 4x4 des Risques et Opportunités (ISO 9001 §6.1).
     */
    public function getRisksOpportunitiesGuide(): array
    {
        return [
            'id' => 'risks_opportunities',
            'title' => 'Matrice d’évaluation et de cotation des Risques et Opportunités',
            'norme' => 'ISO 9001:2015 §6.1 / ISO 14001 §6.1 / ISO 45001 §6.1',
            'formula' => 'Criticité (C) = Probabilité (P) × Gravité (G)',
            'description' => 'Grille méthodologique permettant d’identifier, d’évaluer et de hiérarchiser les risques et opportunités associés au contexte et aux processus de l’organisme.',
            'probability_scale' => [
                [
                    'level' => 1,
                    'label' => 'Improbable',
                    'frequency' => 'Fréquence > 5 ans',
                    'description' => 'Événement tout à fait exceptionnel ou sans précédent connu.',
                ],
                [
                    'level' => 2,
                    'label' => 'Peu probable',
                    'frequency' => 'Fréquence de 1 à 5 ans',
                    'description' => 'Événement rare mais qui s’est déjà produit ou peut survenir.',
                ],
                [
                    'level' => 3,
                    'label' => 'Probable',
                    'frequency' => 'Fréquence d’environ 1 fois par an',
                    'description' => 'Événement envisageable sur l’exercice en cours.',
                ],
                [
                    'level' => 4,
                    'label' => 'Très probable',
                    'frequency' => 'Fréquence récurrente (mensuelle ou trimestrielle)',
                    'description' => 'Événement presque certain sans intervention préventive.',
                ],
            ],
            'gravity_scale' => [
                [
                    'level' => 1,
                    'label' => 'Mineur',
                    'impact' => 'Négligeable',
                    'description' => 'Aucune conséquence financière, légale ou réputationnelle notable. Prise en charge immédiate.',
                ],
                [
                    'level' => 2,
                    'label' => 'Modéré',
                    'impact' => 'Moyen',
                    'description' => 'Conséquences limitées, réversibles à court terme sans compromettre les objectifs globaux.',
                ],
                [
                    'level' => 3,
                    'label' => 'Majeur',
                    'impact' => 'Élevé',
                    'description' => 'Impact sérieux sur les performances, surcoûts importants ou réclamation client significative.',
                ],
                [
                    'level' => 4,
                    'label' => 'Critique',
                    'impact' => 'Inacceptable',
                    'description' => 'Menace vitale pour l’activité, mise en cause réglementaire, sinistre lourd ou perte de certification.',
                ],
            ],
            'criticality_levels' => [
                [
                    'score_range' => '1 - 4',
                    'code' => 'low',
                    'label' => 'Faible (Acceptable)',
                    'color' => '#4caf50',
                    'action_rule' => 'Risque acceptable. Surveillance périodique standard. Aucune action corrective obligatoire.',
                ],
                [
                    'score_range' => '5 - 9',
                    'code' => 'medium',
                    'label' => 'Modéré (Tolérable sous contrôle)',
                    'color' => '#ff9800',
                    'action_rule' => 'Risque à maîtriser. Définition d’actions préventives et désignation d’un responsable de suivi.',
                ],
                [
                    'score_range' => '10 - 12',
                    'code' => 'high',
                    'label' => 'Élevé (Indésirable)',
                    'color' => '#f44336',
                    'action_rule' => 'Risque significatif. Plan d’action prioritaire obligatoire avec délai précis et réévaluation à échéance.',
                ],
                [
                    'score_range' => '15 - 16',
                    'code' => 'critical',
                    'label' => 'Critique (Inacceptable)',
                    'color' => '#b71c1c',
                    'action_rule' => 'Risque intolérable. Plan d’action immédiat d’urgence et escalade obligatoire en revue de direction.',
                ],
            ],
            'strategies' => [
                'risk' => [
                    ['code' => 'accepter', 'label' => 'Accepter', 'description' => 'Tolérer le risque résiduel sans action supplémentaire.'],
                    ['code' => 'reduire', 'label' => 'Réduire / Atténuer', 'description' => 'Mettre en place des barrières pour réduire la probabilité ou la gravité.'],
                    ['code' => 'transferer', 'label' => 'Transférer / Partager', 'description' => 'Transférer le risque à un tiers (assurance, sous-traitant spécialisé).'],
                    ['code' => 'eviter', 'label' => 'Éviter / Supprimer', 'description' => 'Modifier le procédé ou renoncer à l’activité pour éliminer le risque.'],
                ],
                'opportunity' => [
                    ['code' => 'exploiter', 'label' => 'Exploiter', 'description' => 'Mobiliser les ressources pour garantir la réalisation de l’opportunité.'],
                    ['code' => 'partager', 'label' => 'Partager', 'description' => 'S’associer avec des tiers pour décupler les gains de l’opportunité.'],
                    ['code' => 'ameliorer', 'label' => 'Améliorer', 'description' => 'Augmenter la probabilité ou l’impact positif de l’opportunité.'],
                    ['code' => 'ignorer', 'label' => 'Ignorer', 'description' => 'Ne pas affecter de ressources spécifiques.'],
                ],
            ],
        ];
    }

    /**
     * Guide méthodologique et Notice d'évaluation du DUERP (ISO 45001 / SST).
     * Issu de la notice de référence "Document Unique d'Évaluation des Risques Professionnels".
     */
    public function getDuerpGuide(): array
    {
        return [
            'id' => 'duerp',
            'title' => 'Notice et Guide d’évaluation des Risques Professionnels (DUERP)',
            'norme' => 'ISO 45001:2018 §6.1.2 / Code du Travail (L.4121-1 & R.4121-1)',
            'formula' => 'Risque Brut (R) = Gravité (G) × Fréquence (F) ; Risque Résiduel = R × Niveau de Maîtrise',
            'description' => 'Notice officielle pour l’évaluation des risques santé et sécurité au travail par unité de travail, conforme à la démarche INRS ED 833.',
            'gravity_scale' => [
                [
                    'level' => 1,
                    'label' => 'Faible',
                    'consequences' => 'Inconfort, accident ou maladie sans arrêt de travail, soins bénins dispensés sur site.',
                    'lesions' => 'Lésions superficielles sans séquelles.',
                ],
                [
                    'level' => 2,
                    'label' => 'Moyenne',
                    'consequences' => 'Accident ou maladie avec arrêt de travail, réversible à moyen terme.',
                    'lesions' => 'Lésions entraînant un arrêt de travail temporaire, sans séquelles permanentes.',
                ],
                [
                    'level' => 3,
                    'label' => 'Grave',
                    'consequences' => 'Accident ou maladie avec incapacité permanente partielle (IPP), irréversible.',
                    'lesions' => 'Lésions entraînant un arrêt prolongé avec séquelles définitives.',
                ],
                [
                    'level' => 4,
                    'label' => 'Très grave',
                    'consequences' => 'Accident mortel ou incapacité permanente totale / maladie professionnelle incurable.',
                    'lesions' => 'Lésions vitales ou altération définitive irréversible.',
                ],
            ],
            'frequency_scale' => [
                [
                    'level' => 1,
                    'label' => 'Faible / Occasionnelle',
                    'cadence' => 'Au moins 1 fois par an',
                    'description' => 'Le salarié est exposé de façon très ponctuelle ou exceptionnelle au danger.',
                ],
                [
                    'level' => 2,
                    'label' => 'Moyenne / Intermittente',
                    'cadence' => 'Au moins 1 fois par mois',
                    'description' => 'Exposition périodique liée à certaines phases opératoires ou maintenances.',
                ],
                [
                    'level' => 3,
                    'label' => 'Grande / Fréquente',
                    'cadence' => 'Au moins 1 fois par semaine',
                    'description' => 'Exposition habituelle faisant partie de la routine hebdomadaire de travail.',
                ],
                [
                    'level' => 4,
                    'label' => 'Très grande / Permanente',
                    'cadence' => 'Au moins 1 fois par jour / en continu',
                    'description' => 'Exposition constante tout au long de la journée de travail.',
                ],
            ],
            'maitrise_scale' => [
                [
                    'coef' => 1.0,
                    'label' => 'Niveau 1 : Bonne ou forte',
                    'interpretation' => 'Satisfaisant à poursuivre. Mesures de prévention éprouvées, EPI conformes portés, consignes appliquées.',
                ],
                [
                    'coef' => 2.0,
                    'label' => 'Niveau 2 : Moyenne',
                    'interpretation' => 'À améliorer dans le cadre du plan d’action. Dispositifs existants mais insuffisants ou contournables.',
                ],
                [
                    'coef' => 3.0,
                    'label' => 'Niveau 3 : Faible ou absent',
                    'interpretation' => 'À mettre en place ou améliorer urgemment. Aucune protection collective ni consigne formalisée.',
                ],
            ],
            'priorities' => [
                [
                    'code' => 'acceptable',
                    'label' => 'Priorité faible',
                    'color' => '#4caf50',
                    'action_rule' => 'Risque acceptable. Maintien des mesures de prévention existantes.',
                ],
                [
                    'code' => 'tolerable',
                    'label' => 'Priorité moyenne',
                    'color' => '#ff9800',
                    'action_rule' => 'Risque tolérable sous contrôle. Actions correctives à inscrire dans le Programme Annuel de Prévention (PAPRIPACT).',
                ],
                [
                    'code' => 'unacceptable',
                    'label' => 'Priorité maximale',
                    'color' => '#d32f2f',
                    'action_rule' => 'Risque inacceptable. Mesures conservatoires immédiates et action corrective prioritaire sous contrôle du CSSCT/Direction.',
                ],
            ],
            'danger_families' => [
                ['id' => 1, 'name' => 'Circulations et chutes de plain-pied', 'description' => 'Sols glissants, encombrements, inégalités de surface.'],
                ['id' => 2, 'name' => 'Chutes de hauteur', 'description' => 'Escabeaux, échafaudages, toitures, fosses, passerelles.'],
                ['id' => 3, 'name' => 'Circulations internes de véhicules', 'description' => 'Chariots élévateurs, transpalettes, zones piétons/véhicules.'],
                ['id' => 4, 'name' => 'Risque routier en mission', 'description' => 'Déplacements professionnels, véhicules de service, fatigue au volant.'],
                ['id' => 5, 'name' => 'Charge physique et ergonomie', 'description' => 'Manutention manuelle de charges, gestes répétitifs, postures contraignantes.'],
                ['id' => 6, 'name' => 'Manutention mécanique', 'description' => 'Ponts roulants, palans, élingues, défaillance des apparaux de levage.'],
                ['id' => 7, 'name' => 'Produits chimiques, émissions et déchets', 'description' => 'Solvants, acides, fumées, poussières CMR, réactions exothermiques.'],
                ['id' => 8, 'name' => 'Agents biologiques', 'description' => 'Bactéries, virus, légionelles, moisissures, eaux usées.'],
                ['id' => 9, 'name' => 'Équipements et machines', 'description' => 'Organes en mouvement, pièces en rotation, cisaillement, projections.'],
                ['id' => 10, 'name' => 'Effondrements et chutes d’objets', 'description' => 'Racks de stockage instables, empilements, matériaux en dévers.'],
                ['id' => 11, 'name' => 'Bruit et vibrations', 'description' => 'Machines bruyantes, outils percutants, dépassement du seuil de 80 dB(A).'],
                ['id' => 12, 'name' => 'Ambiances thermiques', 'description' => 'Travail au froid, canicule, chambres froides, fours, courants d’air.'],
                ['id' => 13, 'name' => 'Incendie et explosion (ATEX)', 'description' => 'Stockages inflammables, étincelles, poussières explosives.'],
                ['id' => 14, 'name' => 'Électricité', 'description' => 'Armoires sous tension, câbles détériorés, contacts directs/indirects.'],
                ['id' => 15, 'name' => 'Ambiances lumineuses', 'description' => 'Éclairage inadapté, éblouissements, travail sur écran prolongé.'],
                ['id' => 16, 'name' => 'Rayonnements ionisants et non ionisants', 'description' => 'Laser, UV, soudure à l’arc, micro-ondes, sources radioactives.'],
                ['id' => 17, 'name' => 'Risques psychosociaux (RPS)', 'description' => 'Stress, charge mentale, harcèlement, violences internes/externes, conflits de valeurs.'],
            ],
        ];
    }

    /**
     * Grille de cotation des Aspects Environnementaux Significatifs (AES - ISO 14001).
     * Issue de la matrice officielle "BASE AES".
     */
    public function getAesGuide(): array
    {
        return [
            'id' => 'aes',
            'title' => 'Matrice de Cotation des Aspects Environnementaux (AES)',
            'norme' => 'ISO 14001:2015 §6.1.2',
            'formula' => 'Criticité (C) = Fréquence (F) × Gravité (G) × Maîtrise (M) × Sensibilité (S)',
            'description' => 'Système de cotation à 4 dimensions pour identifier objectivement les Aspects Environnementaux Significatifs (AES) nécessitant un plan d’action environnemental obligatoire.',
            'significance_threshold' => 50,
            'significance_rule' => 'Tout aspect dont la cotation C >= 50 (ou impacté par une exigence légale non couverte) est classé Aspect Environnemental Significatif (AES).',
            'frequency_scale' => [
                [
                    'level' => 1,
                    'label' => 'Extrêmement rare',
                    'periodicity' => '1 fois tous les 10 ans',
                    'description' => 'Événement hautement improbable, ne s’est jamais produit sur le site.',
                ],
                [
                    'level' => 3,
                    'label' => 'Rare',
                    'periodicity' => '1 fois par an',
                    'description' => 'Incident environnemental occasionnel (ex: départ de feu circonscrit, fuite annuelle).',
                ],
                [
                    'level' => 5,
                    'label' => 'Peu fréquent',
                    'periodicity' => '1 fois par mois',
                    'description' => 'Survenue intermittente lors de certaines opérations d’exploitation ou de maintenance.',
                ],
                [
                    'level' => 7,
                    'label' => 'Fréquent',
                    'periodicity' => '1 fois par jour à 1 fois par semaine',
                    'description' => 'Événement très probable ou continu lié au fonctionnement quotidien du site.',
                ],
            ],
            'gravity_scale' => [
                [
                    'level' => 1,
                    'label' => 'Faible',
                    'scope' => 'Rayonnement, chaleur, bruit modéré',
                    'description' => 'Effet négligeable ou sans conséquence durable pour l’environnement.',
                ],
                [
                    'level' => 3,
                    'label' => 'Moyen',
                    'scope' => 'Nuisances, déchet banal, eau consommée',
                    'description' => 'Effet réversible à court terme (d’un à six mois). Nuisance olfactive, sonore ou visuelle.',
                ],
                [
                    'level' => 5,
                    'label' => 'Majeur',
                    'scope' => 'Eau, sol, air, déchets dangereux, ressources',
                    'description' => 'Effet réversible à long terme (de six mois à 3 ans). Pollution aux hydrocarbures, CO2, effluents.',
                ],
                [
                    'level' => 7,
                    'label' => 'Critique',
                    'scope' => 'Destruction de milieux, explosion, marée/déversement',
                    'description' => 'Effet destructeur immédiat ou pollution pérenne des sols et nappes phréatiques.',
                ],
            ],
            'maitrise_scale' => [
                [
                    'coef' => 1,
                    'label' => 'Maîtrise forte (M = 1)',
                    'description' => 'Personnel compétent et formé, procédures environnementales documentées et testées, équipements performants avec maintenance préventive.',
                ],
                [
                    'coef' => 3,
                    'label' => 'Maîtrise moyenne (M = 3)',
                    'description' => 'Personnel qualifié, procédures documentées mais maintenance principalement curative ou suivi partiel.',
                ],
                [
                    'coef' => 5,
                    'label' => 'Maîtrise faible / nulle (M = 5)',
                    'description' => 'Aucune formation spécifique, aucune consigne formalisée, matériel vieillissant ou non entretenu.',
                ],
            ],
            'sensibilite_scale' => [
                [
                    'coef' => 1,
                    'label' => 'Sensibilité faible (S = 1)',
                    'description' => '1 seul élément sensible : zone industrielle isolée, nappe profonde non vulnérable, pas de riverain direct.',
                ],
                [
                    'coef' => 3,
                    'label' => 'Sensibilité moyenne (S = 3)',
                    'description' => 'Entre 2 et 3 éléments sensibles : cours d’eau à proximité, zone agricole ou périurbaine.',
                ],
                [
                    'coef' => 5,
                    'label' => 'Sensibilité forte (S = 5)',
                    'description' => '4 éléments sensibles : nappe phréatique captée, zone résidentielle dense ou établissement sensible (école, hôpital) proche.',
                ],
                [
                    'coef' => 7,
                    'label' => 'Sensibilité extrêmement forte (S = 7)',
                    'description' => '5 éléments sensibles ou plus : historique de plaintes avérées de riverains, milieu naturel protégé classé ou nappe vulnérable.',
                ],
            ],
            'operating_modes' => [
                ['code' => 'N', 'label' => 'Mode Normal (N)', 'description' => 'Impact récurrent lié à l’activité en condition normale ou au fonctionnement habituel et prévu.'],
                ['code' => 'A', 'label' => 'Mode Anormal (A)', 'description' => 'Impact potentiel en cas de dysfonctionnement, démarrage, arrêt ou situation exceptionnelle non prévue.'],
                ['code' => 'U', 'label' => 'Mode Urgence (U)', 'description' => 'Situation accidentelle critique (incendie, explosion, fuite massive, inondation).'],
            ],
        ];
    }

    /**
     * Guide de conduite et critères d'évaluation de la Revue de Processus (ISO 9001 §9.1.3 & §9.3).
     */
    public function getProcessReviewGuide(): array
    {
        return [
            'id' => 'process_reviews',
            'title' => 'Guide de conduite et critères d’évaluation de la Revue de Processus',
            'norme' => 'ISO 9001:2015 §4.4, §9.1.3 & §9.3',
            'description' => 'Cadre méthodologique pour piloter les séances annuelles ou périodiques de revue de processus.',
            'sections' => [
                '1. Identification' => 'Cadrage de la séance : RQ, pilote, copilote, présences effectives et dates de couverture.',
                '2. Synthèse PIP' => 'Revue des besoins et attentes des parties intéressées impactant le processus.',
                '3. Risques & Opportunités' => 'Réévaluation de la matrice des risques et des nouvelles opportunités du processus.',
                '4. Objectifs & Projets' => 'Analyse des KPI SMART, taux d’atteinte réel vs cibles et projets de développement.',
                '5. Conformité & NC' => 'Bilan des non-conformités, réclamations clients et enquêtes de satisfaction.',
                '6. Leadership / DUERP & AES' => 'Spécifique au processus Management : synthèse SST et environnementale.',
            ],
            'decision_criteria' => [
                [
                    'code' => 'efficace',
                    'label' => 'Efficace',
                    'description' => 'Le processus atteint ses objectifs (taux >= 80%), les ressources sont maîtrisées, les risques sont sous contrôle.',
                ],
                [
                    'code' => 'partiellement_efficace',
                    'label' => 'Partiellement efficace',
                    'description' => 'Des écarts sont constatés sur certains indicateurs ou des actions sont en retard. Un plan de redressement est requis.',
                ],
                [
                    'code' => 'non_efficace',
                    'label' => 'Non efficace',
                    'description' => 'Dérive critique, objectifs manqués de façon répétée, non-conformités majeures. Plan d’action d’urgence obligatoire.',
                ],
            ],
            'closing_rules' => [
                'rule_replan' => 'REQ-9.2-09 : Impossibilité de clôturer la revue si des actions en retard n’ont pas fait l’objet d’une décision de replanification motivée.',
                'rule_suggestions' => 'REQ-9.2-11 : Transmission directe des propositions d’amélioration au Responsable Qualité (RQ).',
            ],
        ];
    }

    /**
     * Guide méthodologique et gouvernance de la Revue de Direction (ISO 9001:2015 §9.3).
     */
    public function getManagementReviewGuide(): array
    {
        return [
            'id' => 'management_reviews',
            'title' => 'Guide de conduite et exigences de la Revue de Direction globale SMI',
            'norme' => 'ISO 9001:2015 §9.3 / ISO 14001:2015 §9.3 / ISO 45001:2018 §9.3',
            'description' => 'Dispositif annuel ou périodique d’évaluation globale de la pertinence, de l’adéquation et de l’efficacité du système de management intégré.',
            'quadruplet_structure' => [
                'synthese_observations' => 'Analyse des données factuelles consolidées, écarts et constats relevés sur la période.',
                'decision_action' => 'Orientation stratégique, arbitrage managérial ou action corrective arrêtée par la direction.',
                'responsable' => 'Désignation nominative ou fonctionnelle du pilote de mise en œuvre de la décision.',
                'delai' => 'Échéance planifiée pour l’exécution ou la réévaluation de l’action.',
            ],
            'input_elements' => [
                'a' => 'Actions des revues précédentes : avancement et vérification d’efficacité.',
                'b' => 'Changements internes/externes : contexte, parties intéressées, enjeux stratégiques.',
                'c1' => 'Satisfaction client et parties intéressées : retours d’enquêtes et réclamations.',
                'c2' => 'Objectifs qualité / SMI : taux d’atteinte réel vs cibles fixées.',
                'c4' => 'Performance des processus : KPI, conformité des produits et prestations.',
                'c5' => 'Non-conformités : typologie, gravité, coûts d’impact et plans d’actions.',
                'c6' => 'Résultats d’audits : audits internes, audits de certification et audits fournisseurs.',
                'c7' => 'Prestataires externes : évaluations périodiques et conformité des approvisionnements.',
                'e' => 'Risques et opportunités : pertinence et efficacité des actions préventives.',
                'f' => 'Opportunités d’amélioration : innovations, synergies et gains organisationnels.',
            ],
            'sections_sorties' => [
                'resources_data' => [
                    'clause' => 'ISO 9001 §9.3.3.b',
                    'title' => 'Besoins et ressources',
                    'fields' => ['synthese_observations', 'decision_action', 'responsable', 'delai'],
                    'description' => 'Adéquation des ressources humaines, compétences, infrastructures et moyens financiers alloués au SMI.',
                ],
                'system_changes_data' => [
                    'clause' => 'ISO 9001 §9.3.3.c',
                    'title' => 'Modifications du système',
                    'subsections' => [
                        'besoins_changements_systeme' => 'Besoins de changements prioritaires à apporter au système (décision/action, responsable, délai).',
                        'autres_besoins_changements_systeme' => 'Autres ajustements organisationnels ou évolutions SMI (décision/action, responsable, délai).',
                    ],
                ],
            ],
            'governance_rules' => [
                'open_permission' => 'L’ouverture de la revue est strictement réservée au Responsable Qualité (RQ), à la Direction Générale (CEO) ou aux profils habilités avec permission dédiée.',
                'close_permission' => 'La clôture définitive et la validation du rapport DOCX officiel requièrent l’autorité du RQ ou du CEO (Direction Générale).',
            ],
        ];
    }
}

