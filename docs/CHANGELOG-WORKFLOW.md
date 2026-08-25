# Keeping CHANGELOG.md up to date

[CHANGELOG.md](../CHANGELOG.md) is **generated, not hand-edited**, using
[git-cliff](https://git-cliff.org) (config: [cliff.toml](../cliff.toml)). It
groups commits by [Conventional Commit](https://www.conventionalcommits.org/)
type (`feat`, `fix`, `refactor`, `docs`, `chore`, `ci`, `test`, `remove`,
`db`, `build`, etc.).

## Automatic regeneration

[.github/workflows/changelog.yml](../.github/workflows/changelog.yml) runs on
every push to `main` (i.e. after a PR merges) and regenerates
`CHANGELOG.md`, committing it back to `main` directly if it changed. No
manual step is required for normal merges.

## Write commits that produce a good changelog

Use a conventional prefix on the commit subject:

```
feat: add configurable request subjects
fix: preserve subject types in local seeds
docs: update SLA calculation notes
chore: bump dependency versions
```

Commits without a recognized prefix (and merge commits) still get captured,
but land in an `### Other` section — prefer a proper prefix so the entry is
categorized correctly.

## Manual regeneration (fallback)

Only needed if you want to preview the changelog before merging, or the
workflow run failed. Run this from the repo root:

```bash
npx --yes git-cliff@latest --config cliff.toml --output CHANGELOG.md 4b9ebfa..HEAD
```

- `4b9ebfa` is the initial commit of this fork — keep it as the range start
  so the generated file never re-adds the upstream `aaact-aatia/rmt` history.
- The "Baseline" entry describing the divergence from upstream `v2.0.0` lives
  in the `header` template in `cliff.toml`, so it's regenerated automatically
  every time — no manual re-editing of `CHANGELOG.md` needed.

Commit the regenerated `CHANGELOG.md` alongside your change (or as a small
follow-up commit) so it never drifts far from `main`.
