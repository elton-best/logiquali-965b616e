#!/bin/bash

# Script de génération des documentations PDF BestQHSE
# Auteur: Amazon Q
# Date: 2025

echo "📚 Générateur de Documentation PDF BestQHSE"
echo "=============================================="
echo ""

# Couleurs pour l'affichage
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Répertoire du script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Vérifier si Python3 est installé
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}❌ Python 3 n'est pas installé${NC}"
    echo "Veuillez installer Python 3 pour continuer"
    exit 1
fi

echo -e "${BLUE}🔍 Python 3 détecté: $(python3 --version)${NC}"
echo ""

# Créer un environnement virtuel si nécessaire
if [ ! -d "venv" ]; then
    echo -e "${BLUE}📦 Création de l'environnement virtuel...${NC}"
    python3 -m venv venv
    echo -e "${GREEN}✅ Environnement virtuel créé${NC}"
    echo ""
fi

# Activer l'environnement virtuel
echo -e "${BLUE}🔌 Activation de l'environnement virtuel...${NC}"
source venv/bin/activate

# Installer les dépendances
echo -e "${BLUE}📥 Installation des dépendances...${NC}"
pip install -q --upgrade pip
pip install -q -r requirements.txt

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Dépendances installées${NC}"
    echo ""
else
    echo -e "${RED}❌ Erreur lors de l'installation des dépendances${NC}"
    exit 1
fi

# Générer les PDFs
echo -e "${BLUE}🚀 Génération des PDFs...${NC}"
echo ""
python3 generate_pdf_docs.py

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}=============================================="
    echo -e "✨ PDFs générés avec succès!"
    echo -e "=============================================="
    echo ""
    echo "📄 Fichiers générés:"
    echo "  • generated/DOCUMENTATION_TECHNIQUE_BACKEND.pdf"
    echo "  • generated/DOCUMENTATION_TECHNIQUE_FRONTEND.pdf"
    echo ""
    
    # Afficher la taille des fichiers
    if [ -f "generated/DOCUMENTATION_TECHNIQUE_BACKEND.pdf" ]; then
        SIZE=$(du -h "generated/DOCUMENTATION_TECHNIQUE_BACKEND.pdf" | cut -f1)
        echo -e "  Backend:  ${SIZE}"
    fi
    
    if [ -f "generated/DOCUMENTATION_TECHNIQUE_FRONTEND.pdf" ]; then
        SIZE=$(du -h "generated/DOCUMENTATION_TECHNIQUE_FRONTEND.pdf" | cut -f1)
        echo -e "  Frontend: ${SIZE}"
    fi
    
    echo -e "${NC}"
else
    echo -e "${RED}❌ Erreur lors de la génération des PDFs${NC}"
    exit 1
fi

# Désactiver l'environnement virtuel
deactivate

echo ""
echo -e "${GREEN}🎉 Terminé!${NC}"
