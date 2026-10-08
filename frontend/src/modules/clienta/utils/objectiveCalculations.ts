/**
 * Règles de calcul des objectifs (§8 du cahier des charges BESTQHSE)
 *
 * Définitions : Pour un objectif O, t(O, m) est le taux saisi pour le mois m (0–100 %), ou N/A, ou vide.
 *
 * 1. Taux d'atteinte actuel de l'objectif (au jour d, mois courant M) :
 *    Taux_actuel(O) = moyenne( t(O, m) ) pour tous les mois m <= M dont la valeur est renseignée et non N/A.
 *    - Mois > M : ignorés (même si saisis).
 *    - Mois N/A : ignorés (« ces mois ne sont pas pris en compte dans le calcul »).
 *    - Mois <= M vides : ignorés mais signalés visuellement « non renseigné ».
 *    - Si aucun mois éligible : null (« — »).
 *    - Résultat plafonné à 100 %.
 *
 * 2. Récap par processus : moyenne( Taux_actuel(O) ) pour les objectifs du processus.
 * 3. Récap par axe stratégique et par norme : moyenne des Taux_actuel des objectifs rattachés.
 * 4. Récap du système : moyenne de tous les Taux_actuel des objectifs.
 */

export type MonthlyRateValue = number | 'NA' | 'N/A' | null | undefined | string

export interface ObjectiveForCalculation {
  id: number | string
  processId?: number | string
  processName?: string
  strategicAxes?: string[]
  strategicAxis?: string
  norms?: string[]
  realisationMensuelle?: MonthlyRateValue[]
  periodRealizations?: MonthlyRateValue[]
  currentAchievementRate?: number | null
}

/**
 * Normalise et plafonne une valeur de taux entre 0 et 100%.
 */
export function clampRate(value: number): number {
  if (Number.isNaN(value)) return 0
  return Math.min(100, Math.max(0, value))
}

/**
 * Vérifie si une valeur mensuelle est N/A.
 */
export function isRateNA(val: MonthlyRateValue): boolean {
  if (val === 'NA' || val === 'N/A') return true
  if (typeof val === 'string' && val.trim().toUpperCase() === 'N/A') return true
  return false
}

/**
 * Vérifie si un mois passé (<= M) est vide / non renseigné.
 */
export function isRateEmpty(val: MonthlyRateValue): boolean {
  return val === null || val === undefined || val === '' || (typeof val === 'number' && Number.isNaN(val))
}

/**
 * Calcule le Taux d'atteinte actuel Taux_actuel(O) d'un objectif selon §8.
 *
 * @param rates Tableau des taux Janvier-Décembre (taille 12 max)
 * @param currentMonthIndex Index du mois courant (0 = Janvier, ..., 11 = Décembre). Par défaut mois système actuel.
 * @returns number entre 0 et 100, ou null si aucun mois éligible.
 */
export function calculateCurrentObjectiveRate(
  rates: MonthlyRateValue[] | undefined | null,
  currentMonthIndex: number = new Date().getMonth(),
): number | null {
  if (!rates || !Array.isArray(rates) || rates.length === 0) {
    return null
  }

  const eligibleValues: number[] = []

  // Ne considérer que les mois m <= M
  const maxM = Math.min(currentMonthIndex, rates.length - 1)

  for (let m = 0; m <= maxM; m++) {
    const val = rates[m]

    // Mois N/A : ignoré
    if (isRateNA(val)) {
      continue
    }

    // Mois vide : ignoré (mais signalé)
    if (isRateEmpty(val)) {
      continue
    }

    const num = Number(val)
    if (!Number.isNaN(num)) {
      eligibleValues.push(clampRate(num))
    }
  }

  if (eligibleValues.length === 0) {
    return null
  }

  const sum = eligibleValues.reduce((acc, curr) => acc + curr, 0)
  const average = Math.round(sum / eligibleValues.length)

  return clampRate(average)
}

/**
 * Calcule la moyenne simple d'une liste de taux actuels (en excluant les null / '—').
 */
export function calculateAverageRate(rates: Array<number | null | undefined>): number | null {
  const valid = rates.filter((r): r is number => typeof r === 'number' && !Number.isNaN(r))
  if (valid.length === 0) return null
  const sum = valid.reduce((acc, r) => acc + r, 0)
  return clampRate(Math.round(sum / valid.length))
}

export interface ProcessRecapSummary {
  processId: number | string
  processName: string
  objectivesCount: number
  averageRate: number | null
}

export interface AxisRecapSummary {
  axisName: string
  objectivesCount: number
  averageRate: number | null
}

export interface NormRecapSummary {
  normName: string
  objectivesCount: number
  averageRate: number | null
}

export interface SystemRecapSummary {
  totalObjectives: number
  systemAverageRate: number | null
  achievedCount: number // >= 80%
  inProgressCount: number // 50% - 79%
  warningCount: number // < 50%
  byProcess: ProcessRecapSummary[]
  byAxis: AxisRecapSummary[]
  byNorm: NormRecapSummary[]
}

/**
 * Calcule tous les récapitulatifs conformément à REQ-6.2-07 et §8 :
 * (a) par processus
 * (b) par axe stratégique et par norme
 * (c) du système (moyenne des objectifs)
 */
export function calculateSystemRecaps(
  objectives: ObjectiveForCalculation[],
  currentMonthIndex: number = new Date().getMonth(),
): SystemRecapSummary {
  // Calculer d'abord Taux_actuel pour chaque objectif
  const withCurrentRates = objectives.map(obj => {
    const rates = obj.realisationMensuelle || obj.periodRealizations || []
    const currentRate = calculateCurrentObjectiveRate(rates, currentMonthIndex)
    return {
      ...obj,
      calculatedRate: currentRate,
    }
  })

  // (c) Récapitulatif du système
  const allRates = withCurrentRates.map(o => o.calculatedRate)
  const systemAverageRate = calculateAverageRate(allRates)

  let achievedCount = 0
  let inProgressCount = 0
  let warningCount = 0

  for (const r of allRates) {
    if (typeof r === 'number') {
      if (r >= 80) achievedCount++
      else if (r >= 50) inProgressCount++
      else warningCount++
    }
  }

  // (a) Récapitulatif par processus
  const processMap = new Map<string, { id: number | string; name: string; rates: Array<number | null> }>()

  for (const obj of withCurrentRates) {
    const pId = String(obj.processId || obj.processName || 'Sans processus')
    const pName = obj.processName || `Processus #${pId}`
    if (!processMap.has(pId)) {
      processMap.set(pId, { id: obj.processId || pId, name: pName, rates: [] })
    }
    processMap.get(pId)!.rates.push(obj.calculatedRate)
  }

  const byProcess: ProcessRecapSummary[] = Array.from(processMap.values()).map(p => ({
    processId: p.id,
    processName: p.name,
    objectivesCount: p.rates.length,
    averageRate: calculateAverageRate(p.rates),
  }))

  // (b.1) Récapitulatif par axe stratégique
  const axisMap = new Map<string, Array<number | null>>()

  for (const obj of withCurrentRates) {
    const axes = (obj.strategicAxes && obj.strategicAxes.length > 0)
      ? obj.strategicAxes
      : (obj.strategicAxis ? [obj.strategicAxis] : ['Non rattaché'])

    for (const axis of axes) {
      const cleanAxis = String(axis || '').trim() || 'Non rattaché'
      if (!axisMap.has(cleanAxis)) {
        axisMap.set(cleanAxis, [])
      }
      axisMap.get(cleanAxis)!.push(obj.calculatedRate)
    }
  }

  const byAxis: AxisRecapSummary[] = Array.from(axisMap.entries()).map(([axisName, rates]) => ({
    axisName,
    objectivesCount: rates.length,
    averageRate: calculateAverageRate(rates),
  }))

  // (b.2) Récapitulatif par norme
  const normMap = new Map<string, Array<number | null>>()

  for (const obj of withCurrentRates) {
    const norms = (obj.norms && obj.norms.length > 0) ? obj.norms : ['Qualité (ISO 9001)']

    for (const norm of norms) {
      const cleanNorm = String(norm || '').trim()
      if (!normMap.has(cleanNorm)) {
        normMap.set(cleanNorm, [])
      }
      normMap.get(cleanNorm)!.push(obj.calculatedRate)
    }
  }

  const byNorm: NormRecapSummary[] = Array.from(normMap.entries()).map(([normName, rates]) => ({
    normName,
    objectivesCount: rates.length,
    averageRate: calculateAverageRate(rates),
  }))

  return {
    totalObjectives: objectives.length,
    systemAverageRate,
    achievedCount,
    inProgressCount,
    warningCount,
    byProcess,
    byAxis,
    byNorm,
  }
}

