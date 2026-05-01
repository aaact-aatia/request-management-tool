# RMT Documentation

This directory contains comprehensive documentation for the Request Management Tool (RMT).

## Directory Structure

```
docs/
├── README.md                    # This file
├── migrations/                  # Step-by-step migration guides
│   └── 001-language-files.md   # Language file consolidation guide
└── adr/                         # Architecture Decision Records
    └── 001-language-file-system.md
```

## Documentation Types

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
