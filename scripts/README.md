Scripts for local developer workflow.

auto-push.ps1 - PowerShell file watcher that auto-commits and pushes changes to origin/main.

Usage
1. Clone the repo locally and open the plugin directory in your editor.
2. Configure git authentication (SSH key, GCM, or stored PAT) so "git push" works without prompting.
3. From the repo root run:
   powershell -ExecutionPolicy Bypass -File .\scripts\auto-push.ps1
4. Edit files in the repo. The script watches for file changes, debounces 4s, then auto-commits and pushes.

Caveats
- Only use on a development machine. It commits all changed files automatically — do not run on production.
- Keep .gitignore updated to avoid committing secrets or build artifacts.
- If a commit fails (merge conflicts), stop the script and resolve locally.
