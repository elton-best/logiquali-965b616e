#!/usr/bin/env python3
"""
Générateur de documentation PDF stylisée pour BestQHSE
Génère des PDFs professionnels avec couleurs, diagrammes et mise en page soignée
"""

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm, mm
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, Image, KeepTogether, Frame, PageTemplate
)
from reportlab.pdfgen import canvas
from reportlab.lib.utils import ImageReader
from datetime import datetime
import re
import os

# Couleurs du thème BestQHSE
PRIMARY_COLOR = colors.HexColor('#1976D2')  # Bleu principal
SECONDARY_COLOR = colors.HexColor('#424242')  # Gris foncé
ACCENT_COLOR = colors.HexColor('#FF6B6B')  # Rouge accent
SUCCESS_COLOR = colors.HexColor('#4CAF50')  # Vert
HEADER_BG = colors.HexColor('#E3F2FD')  # Bleu clair
TABLE_HEADER = colors.HexColor('#1565C0')  # Bleu foncé
CODE_BG = colors.HexColor('#F5F5F5')  # Gris très clair


class PDFDocGenerator:
    def __init__(self, output_path, title, subtitle, footer_label="Documentation Technique"):
        self.output_path = output_path
        self.title = title
        self.subtitle = subtitle
        self.footer_label = footer_label
        self.doc = SimpleDocTemplate(
            output_path,
            pagesize=A4,
            rightMargin=2*cm,
            leftMargin=2*cm,
            topMargin=3*cm,
            bottomMargin=2.5*cm
        )
        self.story = []
        self.styles = self._create_styles()
        
    def _create_styles(self):
        """Crée les styles personnalisés"""
        styles = getSampleStyleSheet()
        
        # Titre principal
        styles.add(ParagraphStyle(
            name='CustomTitle',
            parent=styles['Heading1'],
            fontSize=28,
            textColor=PRIMARY_COLOR,
            spaceAfter=6*mm,
            alignment=TA_CENTER,
            fontName='Helvetica-Bold'
        ))
        
        # Sous-titre
        styles.add(ParagraphStyle(
            name='CustomSubtitle',
            parent=styles['Normal'],
            fontSize=14,
            textColor=SECONDARY_COLOR,
            spaceAfter=12*mm,
            alignment=TA_CENTER,
            fontName='Helvetica'
        ))
        
        # Titre de section (H2)
        styles.add(ParagraphStyle(
            name='SectionTitle',
            parent=styles['Heading2'],
            fontSize=18,
            textColor=PRIMARY_COLOR,
            spaceBefore=8*mm,
            spaceAfter=4*mm,
            fontName='Helvetica-Bold',
            borderWidth=0,
            borderColor=PRIMARY_COLOR,
            borderPadding=2*mm,
            leftIndent=0
        ))
        
        # Titre de sous-section (H3)
        styles.add(ParagraphStyle(
            name='SubsectionTitle',
            parent=styles['Heading3'],
            fontSize=14,
            textColor=SECONDARY_COLOR,
            spaceBefore=6*mm,
            spaceAfter=3*mm,
            fontName='Helvetica-Bold'
        ))
        
        # Paragraphe normal
        styles.add(ParagraphStyle(
            name='CustomBody',
            parent=styles['Normal'],
            fontSize=10,
            textColor=colors.black,
            spaceAfter=3*mm,
            alignment=TA_JUSTIFY,
            fontName='Helvetica'
        ))
        
        # Code/Technique
        styles.add(ParagraphStyle(
            name='CustomCode',
            parent=styles['Code'],
            fontSize=9,
            textColor=colors.HexColor('#D32F2F'),
            fontName='Courier',
            leftIndent=5*mm,
            backColor=CODE_BG,
            borderWidth=1,
            borderColor=colors.HexColor('#BDBDBD'),
            borderPadding=2*mm
        ))
        
        return styles
    
    def _header_footer(self, canvas, doc):
        """En-tête et pied de page personnalisés"""
        canvas.saveState()
        
        # En-tête
        canvas.setFillColor(PRIMARY_COLOR)
        canvas.rect(0, A4[1] - 2*cm, A4[0], 2*cm, fill=True, stroke=False)
        canvas.setFillColor(colors.white)
        canvas.setFont('Helvetica-Bold', 12)
        canvas.drawString(2*cm, A4[1] - 1.3*cm, "BestQHSE")
        canvas.setFont('Helvetica', 9)
        canvas.drawRightString(A4[0] - 2*cm, A4[1] - 1.3*cm, self.subtitle)
        
        # Pied de page
        canvas.setFillColor(SECONDARY_COLOR)
        canvas.setFont('Helvetica', 8)
        canvas.drawString(2*cm, 1.5*cm, self.footer_label)
        canvas.drawRightString(A4[0] - 2*cm, 1.5*cm, f"Page {doc.page}")
        
        # Ligne de séparation
        canvas.setStrokeColor(PRIMARY_COLOR)
        canvas.setLineWidth(0.5)
        canvas.line(2*cm, 2*cm, A4[0] - 2*cm, 2*cm)
        
        canvas.restoreState()
    
    def add_cover_page(self):
        """Page de couverture"""
        # Logo/Titre principal
        title = Paragraph(self.title, self.styles['CustomTitle'])
        self.story.append(Spacer(1, 4*cm))
        self.story.append(title)
        
        # Sous-titre
        subtitle = Paragraph(self.subtitle, self.styles['CustomSubtitle'])
        self.story.append(subtitle)
        
        # Informations
        info_text = f"""
        <para alignment="center" fontSize="11" textColor="#424242">
        <b>Version:</b> 1.0<br/>
        <b>Date:</b> {datetime.now().strftime('%d %B %Y')}<br/>
        <b>Statut:</b> <font color="#4CAF50">Production</font>
        </para>
        """
        self.story.append(Spacer(1, 2*cm))
        self.story.append(Paragraph(info_text, self.styles['CustomBody']))
        
        # Boîte d'information
        self.story.append(Spacer(1, 3*cm))
        info_box = Table(
            [[Paragraph("<b>Documentation Technique Complète</b><br/>Plateforme QHSE BestQHSE", 
                       self.styles['CustomBody'])]],
            colWidths=[14*cm]
        )
        info_box.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, -1), HEADER_BG),
            ('BORDER', (0, 0), (-1, -1), 2, PRIMARY_COLOR),
            ('PADDING', (0, 0), (-1, -1), 10),
            ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ]))
        self.story.append(info_box)
        self.story.append(PageBreak())
    
    def add_section(self, title, level=2):
        """Ajoute un titre de section"""
        style = 'SectionTitle' if level == 2 else 'SubsectionTitle'
        # Nettoyer le titre des marqueurs markdown
        clean_title = re.sub(r'^#+\s*', '', title)
        self.story.append(Paragraph(clean_title, self.styles[style]))
    
    def add_paragraph(self, text):
        """Ajoute un paragraphe"""
        if text.strip():
            # Convertir markdown basique en HTML
            text = self._markdown_to_html(text)
            self.story.append(Paragraph(text, self.styles['CustomBody']))
    
    def add_table(self, data, has_header=True):
        """Ajoute un tableau stylisé"""
        if not data:
            return
        
        # Calculer les largeurs de colonnes
        num_cols = len(data[0])
        col_width = (A4[0] - 4*cm) / num_cols
        
        # Créer le tableau
        table = Table(data, colWidths=[col_width] * num_cols)
        
        # Style de base
        style_commands = [
            ('GRID', (0, 0), (-1, -1), 0.5, colors.grey),
            ('FONTNAME', (0, 0), (-1, -1), 'Helvetica'),
            ('FONTSIZE', (0, 0), (-1, -1), 9),
            ('PADDING', (0, 0), (-1, -1), 6),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ]
        
        # Style de l'en-tête
        if has_header:
            style_commands.extend([
                ('BACKGROUND', (0, 0), (-1, 0), TABLE_HEADER),
                ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
                ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE', (0, 0), (-1, 0), 10),
            ])
            # Alternance de couleurs pour les lignes
            for i in range(1, len(data)):
                if i % 2 == 0:
                    style_commands.append(
                        ('BACKGROUND', (0, i), (-1, i), colors.HexColor('#F5F5F5'))
                    )
        
        table.setStyle(TableStyle(style_commands))
        self.story.append(table)
        self.story.append(Spacer(1, 5*mm))
    
    def add_code_block(self, code):
        """Ajoute un bloc de code"""
        # Nettoyer le code
        code = code.strip()
        if code.startswith('```'):
            code = '\n'.join(code.split('\n')[1:-1])
        
        # Diviser en lignes et créer des paragraphes
        lines = code.split('\n')
        for line in lines:
            if line.strip():
                self.story.append(Paragraph(
                    line.replace(' ', '&nbsp;').replace('<', '&lt;').replace('>', '&gt;'),
                    self.styles['CustomCode']
                ))
        self.story.append(Spacer(1, 3*mm))
    
    def add_architecture_diagram(self, title, items):
        """Ajoute un diagramme d'architecture simplifié"""
        self.story.append(Spacer(1, 5*mm))
        
        # Titre du diagramme
        diagram_title = Paragraph(
            f"<b>{title}</b>",
            self.styles['SubsectionTitle']
        )
        self.story.append(diagram_title)
        
        # Créer une représentation visuelle
        data = [[Paragraph(f"<font color='white'><b>{title}</b></font>", self.styles['CustomBody'])]]
        
        for item in items:
            data.append([Paragraph(f"• {item}", self.styles['CustomBody'])])
        
        diagram = Table(data, colWidths=[14*cm])
        diagram.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (-1, 0), PRIMARY_COLOR),
            ('BACKGROUND', (0, 1), (-1, -1), HEADER_BG),
            ('BORDER', (0, 0), (-1, -1), 2, PRIMARY_COLOR),
            ('PADDING', (0, 0), (-1, -1), 8),
            ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ]))
        
        self.story.append(diagram)
        self.story.append(Spacer(1, 5*mm))
    
    def _markdown_to_html(self, text):
        """Convertit markdown basique en HTML pour ReportLab"""
        # Code inline (doit être traité en premier pour éviter les conflits)
        text = re.sub(r'`([^`]+)`', r'<font face="Courier" color="#D32F2F">\1</font>', text)
        # Gras
        text = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', text)
        text = re.sub(r'__(.+?)__', r'<b>\1</b>', text)
        # Italique (éviter les underscores dans les noms de variables)
        text = re.sub(r'(?<!\w)\*([^*]+?)\*(?!\w)', r'<i>\1</i>', text)
        # Liens
        text = re.sub(r'\[(.+?)\]\((.+?)\)', r'<link href="\2">\1</link>', text)
        return text
    
    def parse_markdown_content(self, md_content):
        """Parse le contenu markdown et génère le PDF"""
        lines = md_content.split('\n')
        i = 0
        
        while i < len(lines):
            line = lines[i].strip()
            
            # Ignorer les lignes vides multiples
            if not line:
                i += 1
                continue
            
            # Titre principal (H1) - Ignorer, déjà dans la couverture
            if line.startswith('# '):
                i += 1
                continue
            
            # Séparateur horizontal
            if line == '---':
                self.story.append(Spacer(1, 3*mm))
                i += 1
                continue
            
            # Titre de section (H2)
            if line.startswith('## '):
                self.add_section(line, level=2)
                i += 1
                continue
            
            # Titre de sous-section (H3)
            if line.startswith('### '):
                self.add_section(line, level=3)
                i += 1
                continue
            
            # Bloc de code
            if line.startswith('```'):
                code_lines = [line]
                i += 1
                while i < len(lines) and not lines[i].strip().startswith('```'):
                    code_lines.append(lines[i])
                    i += 1
                if i < len(lines):
                    code_lines.append(lines[i])
                    i += 1
                self.add_code_block('\n'.join(code_lines))
                continue
            
            # Tableau markdown
            if '|' in line and i + 1 < len(lines) and '|' in lines[i + 1]:
                table_lines = []
                while i < len(lines) and '|' in lines[i]:
                    table_lines.append(lines[i])
                    i += 1
                self._parse_markdown_table(table_lines)
                continue
            
            # Paragraphe normal
            self.add_paragraph(line)
            i += 1
    
    def _parse_markdown_table(self, table_lines):
        """Parse un tableau markdown"""
        if len(table_lines) < 2:
            return
        
        # Extraire les données
        data = []
        for idx, line in enumerate(table_lines):
            # Ignorer la ligne de séparation (---|---|---)
            if idx == 1 and re.match(r'^\|[\s\-:|]+\|$', line):
                continue
            
            cells = [cell.strip() for cell in line.split('|')[1:-1]]
            # Convertir en Paragraphes pour supporter le formatage
            cells = [Paragraph(self._markdown_to_html(cell), self.styles['CustomBody']) 
                    for cell in cells]
            data.append(cells)
        
        if data:
            self.add_table(data, has_header=True)
    
    def build(self):
        """Génère le PDF"""
        self.doc.build(
            self.story,
            onFirstPage=self._header_footer,
            onLaterPages=self._header_footer
        )
        print(f"✅ PDF généré: {self.output_path}")


def generate_backend_pdf():
    """Génère le PDF de la documentation backend"""
    md_path = '/home/asus/Documents/projet/BestQHSE/docs/generated/DOCUMENTATION_TECHNIQUE_BACKEND.md'
    pdf_path = '/home/asus/Documents/projet/BestQHSE/docs/generated/DOCUMENTATION_TECHNIQUE_BACKEND.pdf'
    
    with open(md_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    generator = PDFDocGenerator(
        pdf_path,
        "Documentation Technique Backend",
        "Laravel 12 · PHP 8.2 · PostgreSQL · Redis"
    )
    
    generator.add_cover_page()
    
    # Ajouter un diagramme d'architecture backend
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
            "Exports: DOCX (PHPWord) + PDF (DomPDF) + Excel (PhpSpreadsheet)"
        ]
    )
    
    generator.parse_markdown_content(content)
    generator.build()


def generate_frontend_pdf():
    """Génère le PDF de la documentation frontend"""
    md_path = '/home/asus/Documents/projet/BestQHSE/docs/generated/DOCUMENTATION_TECHNIQUE_FRONTEND.md'
    pdf_path = '/home/asus/Documents/projet/BestQHSE/docs/generated/DOCUMENTATION_TECHNIQUE_FRONTEND.pdf'
    
    with open(md_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    generator = PDFDocGenerator(
        pdf_path,
        "Documentation Technique Frontend",
        "Vue 3 · TypeScript · Vite · Pinia · Vuetify 3"
    )
    
    generator.add_cover_page()
    
    # Ajouter un diagramme d'architecture frontend
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
            "Tests: Vitest + Cypress"
        ]
    )
    
    generator.parse_markdown_content(content)
    generator.build()


def generate_user_manual_pdf():
    """Génère le PDF du manuel utilisateur"""
    md_path = '/home/asus/Documents/projet/BestQHSE/docs/generated/MANUEL_UTILISATEUR_APPLICATION.md'
    pdf_path = '/home/asus/Documents/projet/BestQHSE/docs/generated/MANUEL_UTILISATEUR_APPLICATION.pdf'

    with open(md_path, 'r', encoding='utf-8') as f:
        content = f.read()

    generator = PDFDocGenerator(
        pdf_path,
        "Manuel d'Utilisation",
        "BestQHSE · Guide des fonctionnalités par profil utilisateur",
        footer_label="Manuel Utilisateur"
    )

    generator.add_cover_page()

    generator.add_architecture_diagram(
        "Parcours Utilisateur BestQHSE",
        [
            "Connexion sécurisée (MFA selon configuration)",
            "Sélection du site de travail",
            "Navigation par modules métier QHSE",
            "Saisie, validation et suivi des actions",
            "Exports Excel / PDF / DOCX selon permissions",
            "Pilotage par indicateurs et revues périodiques"
        ]
    )

    generator.parse_markdown_content(content)
    generator.build()


if __name__ == '__main__':
    print("🚀 Génération des documentations PDF BestQHSE...\n")
    
    try:
        generate_backend_pdf()
        generate_frontend_pdf()
        generate_user_manual_pdf()
        print("\n✨ Génération terminée avec succès!")
    except Exception as e:
        print(f"\n❌ Erreur: {e}")
        import traceback
        traceback.print_exc()
