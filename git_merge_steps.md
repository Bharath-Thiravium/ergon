# Git Merge Conflict Resolution Steps

## Current Status
You're merging `main` into `error-fix-process-flow` branch and encountering conflicts.

## Resolution Commands

```bash
# Step 1: Check current status
git status

# Step 2: See which files have conflicts
git diff --name-only --diff-filter=U

# Step 3: Resolve conflicts in each file
# Edit conflicted files manually, removing conflict markers:
# <<<<<<< HEAD
# =======
# >>>>>>> main

# Step 4: After resolving conflicts, add resolved files
git add .

# Step 5: Complete the merge
git commit -m "Resolve merge conflicts between main and error-fix-process-flow"

# Step 6: Push the resolved changes
git push -u origin error-fix-process-flow
```

## Quick Resolution for Common Files

If conflicts are in:
- **Database files**: Keep the unified workflow changes
- **Controllers**: Merge both sets of changes
- **Views**: Keep the modernized task form
- **Routes**: Combine all route definitions

## Auto-resolve Strategy
```bash
# If you want to favor your branch changes:
git checkout --ours conflicted-file.php

# If you want to favor main branch changes:
git checkout --theirs conflicted-file.php

# Then add and commit:
git add conflicted-file.php
git commit -m "Resolve conflict in conflicted-file.php"
```