#!/usr/bin/env python3
"""Generate one polished technical documentation PDF for BestQHSE."""

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm, mm
from reportlab.platypus import PageBreak, Paragraph, Spacer, Table, TableStyle

from generate_pdf_docs import (
    HEADER_BG,
    PRIMARY_COLOR,
    SECONDARY_COLOR,
    SUCCESS_COLOR,
    PDFDocGenerator,
)


ROOT = Path(__file__).resolve().parent
GENERATED = ROOT / "generated"
OUTPUT = GENERATED / "DOCUMENTATION_TECHNIQUE.pdf"


class CombinedTechnicalDoc(PDFDocGenerator):
    def add_transition_page(self, label, title, subtitle, items, accent_color):
        """Add a polished section divider that follows the project PDF theme."""
        self.story.append(Spacer(1, 1.4 * cm))

        label_box = Table(
            [[Paragraph(f'<font color="white"><b>{label}</b></font>', self.styles["CustomBody"])]],
            colWidths=[4.8 * cm],
            rowHeights=[0.9 * cm],
        )
        label_box.setStyle(
            TableStyle(
                [
                    ("BACKGROUND", (0, 0), (-1, -1), accent_color),
                    ("ALIGN", (0, 0), (-1, -1), "CENTER"),
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("BOX", (0, 0), (-1, -1), 0, accent_color),
                ]
            )
        )
        self.story.append(label_box)
        self.story.append(Spacer(1, 9 * mm))

        self.story.append(Paragraph(title, self.styles["CustomTitle"]))
        self.story.append(Paragraph(subtitle, self.styles["CustomSubtitle"]))
        self.story.append(Spacer(1, 8 * mm))

        rows = []
        for item in items:
            rows.append([Paragraph(f"• {item}", self.styles["CustomBody"])])

        section_card = Table(rows, colWidths=[14 * cm])
        section_card.setStyle(
            TableStyle(
                [
                    ("BACKGROUND", (0, 0), (-1, -1), HEADER_BG),
                    ("BOX", (0, 0), (-1, -1), 1.2, PRIMARY_COLOR),
                    ("LEFTPADDING", (0, 0), (-1, -1), 12),
                    ("RIGHTPADDING", (0, 0), (-1, -1), 12),
                    ("TOPPADDING", (0, 0), (-1, -1), 9),
                    ("BOTTOMPADDING", (0, 0), (-1, -1), 9),
                ]
            )
        )
        self.story.append(section_card)
        self.story.append(Spacer(1, 18 * mm))

        footer = Table(
            [[Paragraph("<b>BestQHSE</b> · Documentation technique", self.styles["CustomBody"])]],
            colWidths=[14 * cm],
        )
        footer.setStyle(
            TableStyle(
                [
                    ("LINEABOVE", (0, 0), (-1, 0), 0.8, SECONDARY_COLOR),
                    ("TEXTCOLOR", (0, 0), (-1, -1), SECONDARY_COLOR),
                    ("TOPPADDING", (0, 0), (-1, -1), 8),
                ]
            )
        )
        self.story.append(footer)
        self.story.append(PageBreak())


def read_md(name):
    content = (GENERATED / name).read_text(encoding="utf-8")
    return content.replace("LOGIQUALI", "BestQHSE").replace("LogiQuali", "BestQHSE")


def main():
    generator = CombinedTechnicalDoc(
        str(OUTPUT),
        "Documentation Technique",
        "Frontend & Backend · Plateforme QHSE BestQHSE",
    )
    generator.add_cover_page()

    generator.add_transition_page(
        "SECTION 01",
        "Partie Frontend",
        "Vue 3 · TypeScript · Vite · Pinia · Vuetify 3",
        [
            "Architecture applicative et organisation des sources",
            "Modules, routes, stores, composants UI et services frontend",
            "Tests, intégration API et conventions d'interface",
        ],
        PRIMARY_COLOR,
    )
    generator.add_architecture_diagram(
        "Architecture Frontend BestQHSE",
        [
            "Framework: Vue 3 (Composition API)",
            "Langage: TypeScript",
            "Build: Vite",
            "State Management: Pinia",
            "UI: Vuetify 3 + Tailwind CSS 4",
            "Routing: Vue Router (3 modules isolés)",
            "HTTP: Axios + Intercepteurs",
            "WebSocket: Laravel Echo + Pusher",
            "Tests: Vitest + Cypress",
        ],
    )
    generator.parse_markdown_content(read_md("DOCUMENTATION_TECHNIQUE_FRONTEND.md"))

    generator.story.append(PageBreak())
    generator.add_transition_page(
        "SECTION 02",
        "Partie Backend",
        "Laravel 12 · PHP 8.2 · PostgreSQL · Redis",
        [
            "Architecture API, sécurité, authentification et permissions",
            "Modèle de données, services métier, événements et exports",
            "Infrastructure applicative, WebSocket, jobs et conformité",
        ],
        SUCCESS_COLOR,
    )
    generator.add_architecture_diagram(
        "Architecture Backend BestQHSE",
        [
            "API REST Laravel 12 (PHP 8.2)",
            "Authentification: Laravel Sanctum + MFA",
            "Base de données: PostgreSQL",
            "Cache & Sessions: Redis",
            "WebSocket: Laravel Reverb",
            "Queue: Laravel Horizon",
            "Permissions: Spatie Permission",
            "Exports: DOCX (PHPWord) + PDF (DomPDF) + Excel (PhpSpreadsheet)",
        ],
    )
    generator.parse_markdown_content(read_md("DOCUMENTATION_TECHNIQUE_BACKEND.md"))
    generator.build()


if __name__ == "__main__":
    main()
