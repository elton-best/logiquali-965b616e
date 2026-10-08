import { describe, expect, it } from 'vitest'
import {
  calculateCurrentObjectiveRate,
  calculateSystemRecaps,
  clampRate,
  type ObjectiveForCalculation,
} from '../objectiveCalculations'

describe('Règles de calcul §8 - Objectifs Qualité', () => {
  it('calcule le taux actuel en ignorant les mois futurs et en excluant les mois N/A', () => {
    // Index mois courant M = 3 (Avril, 0-indexed : Jan=0, Fév=1, Mar=2, Avr=3)
    // Jan: 80, Fév: 'N/A', Mar: 90, Avr: 100, Mai: 50 (futur, index 4), Juin: 70 (futur, index 5)
    const rates = [80, 'N/A', 90, 100, 50, 70]

    // Mois éligibles : Jan (80), Mar (90), Avr (100) -> somme = 270, nb = 3 -> moyenne = 90%
    const rate = calculateCurrentObjectiveRate(rates, 3)
    expect(rate).toBe(90)
  })

  it('plafonne chaque taux mensuel et le taux actuel à 100%', () => {
    // Si la saisie dépasse 100%, chaque valeur et la moyenne sont plafonnées à 100%
    const rates = [120, 110]

    const rate = calculateCurrentObjectiveRate(rates, 1)
    expect(rate).toBe(100)
    expect(clampRate(125)).toBe(100)
  })

  it('retourne null si tous les mois jusqu’à M sont N/A ou non renseignés (affiché « — » selon §8)', () => {
    const rates = ['N/A', null, undefined, '']

    const rate = calculateCurrentObjectiveRate(rates, 3)
    expect(rate).toBeNull()
  })

  it('calcule les 3 récapitulatifs : par processus, par axe/norme, et système', () => {
    // Objectif 1 : Processus 10, Axe 1, Norme ISO 9001 -> Jan = 80%
    // Objectif 2 : Processus 10, Axe 2, Normes [ISO 9001, ISO 14001] -> Jan = 100%
    // Objectif 3 : Processus 20, Axe 1, Norme ISO 14001 -> Jan = 60%
    const objectives: ObjectiveForCalculation[] = [
      {
        id: 1,
        processId: 10,
        processName: 'Management',
        strategicAxis: 'Satisfaction Client',
        norms: ['ISO 9001:2015'],
        realisationMensuelle: [80],
      },
      {
        id: 2,
        processId: 10,
        processName: 'Management',
        strategicAxis: 'Performance Opérationnelle',
        norms: ['ISO 9001:2015', 'ISO 14001:2015'],
        realisationMensuelle: [100],
      },
      {
        id: 3,
        processId: 20,
        processName: 'Réalisation',
        strategicAxis: 'Satisfaction Client',
        norms: ['ISO 14001:2015'],
        realisationMensuelle: [60],
      },
    ]

    const recaps = calculateSystemRecaps(objectives, 0) // Mois Janvier (index 0)

    // (a) Récapitulatif par processus :
    // Processus 10 (Obj 1: 80, Obj 2: 100) -> moyenne = 90%
    // Processus 20 (Obj 3: 60) -> moyenne = 60%
    const p10 = recaps.byProcess.find(p => p.processId === 10)
    const p20 = recaps.byProcess.find(p => p.processId === 20)
    expect(p10?.averageRate).toBe(90)
    expect(p20?.averageRate).toBe(60)

    // (b.1) Récapitulatif par axe stratégique :
    // Axe 'Satisfaction Client' (Obj 1: 80, Obj 3: 60) -> moyenne = 70%
    // Axe 'Performance Opérationnelle' (Obj 2: 100) -> moyenne = 100%
    const ax1 = recaps.byAxis.find(a => a.axisName === 'Satisfaction Client')
    const ax2 = recaps.byAxis.find(a => a.axisName === 'Performance Opérationnelle')
    expect(ax1?.averageRate).toBe(70)
    expect(ax2?.averageRate).toBe(100)

    // (b.2) Récapitulatif par norme (multi-normes) :
    // ISO 9001 (Obj 1: 80, Obj 2: 100) -> moyenne = 90%
    // ISO 14001 (Obj 2: 100, Obj 3: 60) -> moyenne = 80%
    const n9001 = recaps.byNorm.find(n => n.normName === 'ISO 9001:2015')
    const n14001 = recaps.byNorm.find(n => n.normName === 'ISO 14001:2015')
    expect(n9001?.averageRate).toBe(90)
    expect(n14001?.averageRate).toBe(80)

    // (c) Récapitulatif du système :
    // (80 + 100 + 60) / 3 = 80%
    expect(recaps.systemAverageRate).toBe(80)
    expect(recaps.totalObjectives).toBe(3)
  })
})
