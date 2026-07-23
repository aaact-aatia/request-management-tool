# Database Seeds — Configurable Intake Flows

This directory contains optional seed data for configurable intake flows.  Seeds are
loaded explicitly with `scripts/load-intake-seed.sh`; they are **not** part of the
normal Docker initialization that runs on first start.

## Directory layout

```
database/seeds/
├── README.md                     (this file)
├── template/
│   └── intake-flow-template.sql  (copy this to create a new seed)
└── ssc/
    ├── README.md                 (SSC-specific context)
    └── website-testing-v1.sql   (SSC website-testing intake flow, version 1)
```

## Loading seeds

Use `scripts/load-intake-seed.sh` to load a seed explicitly into the running
local database.  Seeds are **not** part of normal Docker initialization.

```bash
./scripts/load-intake-seed.sh list            # show registered seeds
./scripts/load-intake-seed.sh load ssc/website-testing-v1
```

## Full local reset

`scripts/reset-local-db.sh --yes` destroys and rebuilds the entire local
database, including tblusers, and reloads:

1. `database/schema.sql`
2. `database/reference.sql`
3. `database/session_handler.sql`
4. `database/ssc-users-dev.sql` (default demo accounts)
5. `database/ssc-sample-dev.sql`
6. `database/seeds/ssc/website-testing-v1.sql`

**All existing data — including any custom user accounts — is permanently
removed.**  The default demo accounts are restored.  Sign in with the
password documented in `database/ssc-users-dev.sql`.

The website-testing seed is loaded by the reset script because it is intended
to build the complete SSC demonstration environment.  It is *not* loaded during
normal Docker initialization (`docker compose up`); that initializes only files
01–05 above.

## Creating a new seed

Copy `database/seeds/template/intake-flow-template.sql` to
`database/seeds/<org>/<flow-name>-v1.sql`.  Fill in every section marked
`@@CONFIGURE`, register the seed name in `scripts/load-intake-seed.sh`,
then load it with `./scripts/load-intake-seed.sh load <org>/<flow-name>-v1`.

The schema, migration, and loader contain no SSC-specific knowledge. Another
organization can add its own seed without changing any PHP, schema, or SSC files.

## Versioning

A published flow is immutable. To revise: create a new file (e.g.
`website-testing-v2.sql`) and register it separately. Never modify a published
version file.
