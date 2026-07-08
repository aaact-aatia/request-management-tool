# RMT Documentation

This directory contains comprehensive documentation for the Request Management Tool (RMT).

## Directory Structure

```
docs/
├── README.md                    # This file
├── config-management/           # Lightweight configuration management framework
│   ├── README.md                # Scope, ownership, cadence
│   ├── ci-inventory-standard.md # CI metadata standard and quality rules
│   ├── change-control-sop.md    # Change governance and approval flow
│   ├── config-audit-checklist.md # Monthly/pre-release audit checklist
│   ├── dependency-map-template.md # Service dependency mapping template
│   ├── change-record-template.md # Standard change record template
│   └── kpi-status-accounting.md # KPI baseline and monthly tracking
├── permissions-model.md         # Role and permission source-of-truth (current and target policy)
├── permissions-target-policy.md # Target permission catalog and role matrix
├── permissions-route-access-map.md # Route-by-route access class map
├── migrations/                  # Step-by-step migration guides
│   └── 001-language-files.md   # Language file consolidation guide
└── adr/                         # Architecture Decision Records
    └── 001-language-file-system.md
```

Quick directory links:
- [config-management](config-management/)
- [migrations](migrations/)
- [adr](adr/)

## Documentation Types

### Permissions Documentation

- **[Permissions Model](permissions-model.md)**: Current and target role/permission behavior
  - Status: Active
  - Scope: account types, admin/superadmin flags, capability matrix, rollout phases
- **[Permissions Target Policy](permissions-target-policy.md)**: Target permission vocabulary and role mapping
  - Status: Draft
  - Scope: permission catalog, role matrix, guest policy, environment controls
- **[Permissions Route Access Map](permissions-route-access-map.md)**: Route and endpoint access classes
  - Status: Draft
  - Scope: page-level visibility, endpoint classification, guest-linked view boundaries

### Configuration Management Documentation

- **[Config Management Overview](config-management/README.md)**: Lightweight framework scope, ownership, and cadence
  - Status: Active
  - Scope: CI standards, change control, audits, dependency mapping, KPI tracking
- **[CI Inventory Standard](config-management/ci-inventory-standard.md)**: Minimum CI metadata and quality rules
  - Status: Active
  - Scope: CI classes, required attributes, naming convention, validation rules
- **[Change Control SOP](config-management/change-control-sop.md)**: Change categories, approvals, and execution procedure
  - Status: Active
  - Scope: standard/normal/emergency changes, validation evidence, post-implementation review
- **[Configuration Audit Checklist](config-management/config-audit-checklist.md)**: Repeatable integrity and traceability checks
  - Status: Active
  - Scope: inventory health, drift checks, dependency accuracy, security hygiene
- **[Dependency Map Template](config-management/dependency-map-template.md)**: Service-to-CI relationship template
  - Status: Active
  - Scope: upstream/downstream dependencies, integration points, impact notes
- **[Change Record Template](config-management/change-record-template.md)**: Standardized change evidence capture
  - Status: Active
  - Scope: impacted CIs, risk/rollback, approvals, execution log, outcome
- **[KPI and Status Accounting](config-management/kpi-status-accounting.md)**: Lightweight metrics and trend tracking
  - Status: Active
  - Scope: completeness, verification recency, traceability, drift correction, incident trend

### Migration Guides (`migrations/`)
Step-by-step guides for implementing specific improvements from [IMPROVEMENTS.md](../IMPROVEMENTS.md). Each migration is numbered sequentially and includes:
- Overview and objectives
- Prerequisites
- Detailed implementation steps
- Testing procedures
- Rollback instructions

### Architecture Decision Records (`adr/`)
Documents that capture important architectural decisions, following the [ADR pattern](https://adr.github.io/). Each ADR includes:
- Context and problem statement
- Decision and rationale
- Consequences (positive and negative)
- Status (proposed, accepted, deprecated, superseded)

## Current Active Migrations

- **[Migration 001](migrations/001-language-files.md)**: Language File Consolidation
  - Status: In Progress
  - Branch: `feature/language-file-consolidation`
  - Files Affected: `openrequest.php`, `lang/en.php`, `lang/fr.php`, `switch-lang.php`

## Contributing to Documentation

When making architectural changes:
1. Create a new ADR if the change involves significant architectural decisions
2. Create a migration guide if other developers will need to follow your implementation
3. Update the CHANGELOG.md at the project root
4. Update this README with the new documentation

## Quick Links

- [Main README](../README.md) - Project overview and setup
- [Copilot Instructions](../.github/copilot-instructions.md) - AI agent guidance
- [Improvements Roadmap](../IMPROVEMENTS.md) - Future enhancement plans
