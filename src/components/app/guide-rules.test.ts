import { describe, expect, it } from "vitest";
import { currentRate, isMonthLocked } from "./GuidePages";
import { policyTitle } from "./nav";

describe("taux d'atteinte des objectifs", () => {
  it("ignore les mois N/A et les mois futurs", () => {
    expect(currentRate({ "1": 80, "2": "NA", "3": 100, "11": 0 }, 3)).toBe(90);
  });
  it("renvoie null sans saisie", () => {
    expect(currentRate({ "1": "NA" }, 5)).toBeNull();
  });
});

describe("verrouillage mensuel", () => {
  it("verrouille un mois écoulé et laisse ouvert le mois courant", () => {
    const today = new Date(2026, 9, 8); // 8 octobre 2026
    expect(isMonthLocked(2026, 9, today)).toBe(true); // septembre
    expect(isMonthLocked(2026, 10, today)).toBe(false); // octobre
  });
});

describe("titre de la politique", () => {
  it("qualité seule", () => expect(policyTitle(new Set(["ISO 9001"]))).toBe("Politique Qualité"));
  it("qualité + sécurité + environnement", () => expect(policyTitle(new Set(["ISO 9001", "ISO 45001", "ISO 14001"]))).toBe("Politique QSE"));
  it("avec hygiène", () => expect(policyTitle(new Set(["ISO 9001", "ISO 22000", "ISO 45001", "ISO 14001"]))).toBe("Politique QHSE"));
});
