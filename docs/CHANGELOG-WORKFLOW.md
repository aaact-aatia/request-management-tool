# Keeping CHANGELOG.md up to date

[CHANGELOG.md](../CHANGELOG.md) is **generated, not hand-edited**, using
[git-cliff](https://git-cliff.org) (config: [cliff.toml](../cliff.toml)). It
groups commits by [Conventional Commit](https://www.conventionalcommits.org/)
type (`feat`, `fix`, `refactor`, `docs`, `chore`, `ci`, `test`, `remove`,
`db`, `build`, etc.).

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

## Regenerate the changelog

Run this after merging a PR into `main` (or before a release), from the repo
root:

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
