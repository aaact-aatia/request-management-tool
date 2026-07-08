# Configuration Management Docs

## Purpose

This folder provides a lightweight configuration management framework for the Request Management Tool (RMT). It is aligned with IT service management practices while keeping documentation overhead low.

The goal is to establish:
- A minimum configuration item (CI) inventory standard
- A repeatable change control process
- A simple audit and verification cycle
- A clear service dependency mapping pattern
- Basic status accounting metrics

## Scope

In scope:
- Application configuration and release configuration
- Runtime and environment settings by environment, including local, development, and production
- Database and session configuration dependencies
- Authentication and authorization configuration
- External service integrations used by RMT
- Deployment and release process configuration
- Configuration governance records and evidence

Out of scope:
- Full enterprise Configuration Management Database (CMDB) implementation
- Non-RMT infrastructure owned by external teams
- Deep vendor-level operational runbooks
- Storage of secrets, passwords, tokens, private keys, or full connection strings

## Configuration Item Examples

Configuration items may include:
- RMT application
- Hosting environment
- Deployment pipeline
- Environment variable set
- Database configuration
- Session configuration
- Authentication configuration
- External service integration
- Release artifact
- Critical support documentation

Only configuration items that affect deployment, security, availability, support, or service operation need to be tracked.

## Document Set

- ci-inventory-standard.md
- change-control-sop.md
- config-audit-checklist.md
- dependency-map-template.md
- change-record-template.md
- kpi-status-accounting.md

## Source of Truth

These documents provide configuration management records and governance evidence.

They should not duplicate secrets or replace technical sources of truth such as:
- Source code repositories
- Deployment platforms
- Environment-specific application settings
- Approved secret storage locations
- Database administration tools
- Release pipelines

Where possible, documents should reference the authoritative source instead of copying values.

## Suggested Ownership

- Primary owner: Technical lead or release owner
- Contributors: Application developers, deployment owner, database owner
- Reviewers: Product owner, security/compliance representative, and accessibility representative

## Review Cadence

Review this document set:
- Monthly
- Before each production release window
- After major architecture, deployment, database, authentication, or integration changes

## Working Rules

1. Keep documents short and execution-focused.
2. Prefer checklists and tables over long prose.
3. Every change that affects production configuration, deployment, data, security, authentication, external integrations, or service availability should have a change record.
4. Every critical configuration item should have an owner and a last verified date.
5. Do not store passwords, tokens, private keys, full connection strings, or other secrets in these documents.
6. Reference the approved source of truth instead of duplicating technical values where possible.
7. Update configuration records as part of the change process, not after the fact.
