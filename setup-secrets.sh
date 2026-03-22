#!/bin/bash

#########################################################
# ThaiHelp Laravel - GitHub Secrets Setup Script
# รันบน server ที่มี SSH key อยู่
#########################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m'

REPO="xjanova/thaihelp-laravel"

echo -e "${CYAN}╔════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   ThaiHelp - GitHub Secrets Setup         ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════╝${NC}"
echo ""

# Check gh CLI
if ! command -v gh &>/dev/null; then
    echo -e "${RED}✗ GitHub CLI (gh) not found. Installing...${NC}"
    curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
    sudo apt update && sudo apt install gh -y
fi

# Check gh auth
if ! gh auth status &>/dev/null; then
    echo -e "${RED}✗ Not logged in to GitHub CLI${NC}"
    echo "Run: gh auth login"
    exit 1
fi

# Collect info
echo -e "${CYAN}>>> Collecting server info...${NC}"

SSH_HOST=$(hostname -f 2>/dev/null || hostname)
SSH_USER=$(whoami)
SSH_PORT=22
DEPLOY_PATH="/home/${SSH_USER}/domains/thaihelp.xman4289.com/app"
APP_URL="https://thaihelp.xman4289.com"

# Find SSH private key
SSH_KEY_FILE=""
for f in ~/.ssh/id_ed25519 ~/.ssh/id_rsa ~/.ssh/id_ecdsa; do
    if [ -f "$f" ]; then
        SSH_KEY_FILE="$f"
        break
    fi
done

if [ -z "$SSH_KEY_FILE" ]; then
    echo -e "${RED}✗ No SSH private key found in ~/.ssh/${NC}"
    echo "Generate one with: ssh-keygen -t ed25519"
    exit 1
fi

echo ""
echo -e "SSH_HOST:      ${GREEN}${SSH_HOST}${NC}"
echo -e "SSH_USER:      ${GREEN}${SSH_USER}${NC}"
echo -e "SSH_PORT:      ${GREEN}${SSH_PORT}${NC}"
echo -e "DEPLOY_PATH:   ${GREEN}${DEPLOY_PATH}${NC}"
echo -e "APP_URL:       ${GREEN}${APP_URL}${NC}"
echo -e "SSH_KEY_FILE:  ${GREEN}${SSH_KEY_FILE}${NC}"
echo ""

read -p "ข้อมูลถูกต้องไหม? (y/n) แก้ไขก่อนกด n: " CONFIRM
if [ "$CONFIRM" != "y" ]; then
    read -p "SSH_HOST [$SSH_HOST]: " INPUT && [ -n "$INPUT" ] && SSH_HOST="$INPUT"
    read -p "SSH_USER [$SSH_USER]: " INPUT && [ -n "$INPUT" ] && SSH_USER="$INPUT"
    read -p "SSH_PORT [$SSH_PORT]: " INPUT && [ -n "$INPUT" ] && SSH_PORT="$INPUT"
    read -p "DEPLOY_PATH [$DEPLOY_PATH]: " INPUT && [ -n "$INPUT" ] && DEPLOY_PATH="$INPUT"
    read -p "APP_URL [$APP_URL]: " INPUT && [ -n "$INPUT" ] && APP_URL="$INPUT"
    read -p "SSH_KEY_FILE [$SSH_KEY_FILE]: " INPUT && [ -n "$INPUT" ] && SSH_KEY_FILE="$INPUT"
fi

echo ""
echo -e "${CYAN}>>> Setting GitHub Secrets for ${REPO}...${NC}"

# Set secrets
gh secret set SSH_HOST -R "$REPO" -b "$SSH_HOST"
echo -e "${GREEN}✓ SSH_HOST${NC}"

gh secret set SSH_USER -R "$REPO" -b "$SSH_USER"
echo -e "${GREEN}✓ SSH_USER${NC}"

gh secret set SSH_PORT -R "$REPO" -b "$SSH_PORT"
echo -e "${GREEN}✓ SSH_PORT${NC}"

gh secret set SSH_PRIVATE_KEY -R "$REPO" < "$SSH_KEY_FILE"
echo -e "${GREEN}✓ SSH_PRIVATE_KEY${NC}"

gh secret set DEPLOY_PATH -R "$REPO" -b "$DEPLOY_PATH"
echo -e "${GREEN}✓ DEPLOY_PATH${NC}"

gh secret set APP_URL -R "$REPO" -b "$APP_URL"
echo -e "${GREEN}✓ APP_URL${NC}"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ GitHub Secrets ตั้งค่าเรียบร้อย!      ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════╝${NC}"
echo ""
echo "ตรวจสอบ: gh secret list -R $REPO"
echo "ทดสอบ deploy: push commit ไปที่ main branch"
