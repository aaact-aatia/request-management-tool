# Secret Hygiene Validation Workflow

## Purpose

Define a repeatable monthly process to verify that configuration documentation and repository-owned files do not contain committed secret values.

## Scope

In scope:
- Repository-owned application and documentation files
- Configuration-management docs in this folder
- Environment template files such as .env.example

Out of scope:
- Dependency vendor directories and third-party samples
- Runtime secret stores and platform-managed secret values

## Owners and Cadence

- Primary owner: Security and DevOps Owners
- Contributors: Release owner, Application team representative
- Frequency: monthly and pre-release

## Validation Steps

1. Run pattern-based scan against repository-owned content.
2. Exclude vendor and third-party dependency directories from governance findings.
3. Review all matches and classify each as:
   - Placeholder/example
   - Documentation-only sample value
   - Potentially sensitive and requiring remediation
4. Record evidence and disposition in the log below.
5. Create or update finding records in config-audit-checklist.md when needed.
6. Capture owner attestation for monthly completion.

## Recommended Scan Command

```bash
grep -RInE --exclude-dir=vendor "BEGIN RSA|PRIVATE KEY|API[_-]?KEY[[:space:]]*=|TOKEN[[:space:]]*=|PASSWORD[[:space:]]*=|DB_PASS[[:space:]]*=|GCNOTIFY_API_KEY[[:space:]]*=" app docs .env.example
```

## Classification Rules

- Acceptable:
  - Placeholder values in .env.example
  - Explicit examples in future/planning docs that are clearly non-production values
- Not acceptable:
  - Real API keys, passwords, tokens, private keys, or full connection strings committed to repository-owned files

## Evidence Log

| Date | Executor | Scope | Command | Findings | Disposition | Follow-up |
|---|---|---|---|---|---|---|
| 2026-07-08 | Documentation baseline review (Copilot-assisted) | app, docs, .env.example (vendor excluded) | grep pattern scan (see command above) | 3 matches: docs/future/006-gcnotify-integration.md and .env.example placeholder entries | No committed production secrets detected; matches are placeholders/examples | Begin monthly owner attestation starting next cycle |

## Monthly Owner Attestation

| Month | Security Owner | DevOps Owner | Completed Date | Notes |
|---|---|---|---|---|
| 2026-07 | pending | pending | pending | Workflow documented and baseline evidence captured |

## Escalation and Remediation

If a suspected real secret is found:
1. Revoke/rotate the credential immediately in the authoritative secret store.
2. Remove committed secret from source and history if required.
3. Open a security incident or risk record per team policy.
4. Add an audit finding in config-audit-checklist.md and track to closure.
