#!/bin/bash
# Fix deployment conflicts
git config pull.rebase false
git pull origin main --allow-unrelated-histories
git push origin main --force