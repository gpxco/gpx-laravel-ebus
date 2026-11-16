# gpx-laravel-ebus — notes for agents

## Releases always need a git tag

This package is consumed via VCS (`git@github.com:gpxco/gpx-laravel-ebus.git`). Apps pin an exact version (`1.4.2`, `1.5.0`, …). Composer resolves that **only if a matching tag exists on the remote**.

**Every published version must have a tag.** Do not leave a version bump on `master` without pushing the tag.

Tag format (existing convention): `v` + semver from `composer.json` `version`.

| `composer.json` `version` | git tag |
|---|---|
| `1.4.2` | `v1.4.2` |
| `1.5.0` | `v1.5.0` |

## How to ship a new version

1. Work on a branch (e.g. `eng-<N>`). Update `composer.json` `version`, code, tests.
2. Open a PR into `master`. Wait for CI (`composer test`).
3. Merge the PR to `master`.
4. **On `master` after merge**, create and push the tag:

```bash
git checkout master
git pull origin master
# confirm composer.json version matches the release, e.g. 1.5.0
git tag v1.5.0
git push origin v1.5.0
```

5. Only then bump consuming apps (`gpx-engine`, `gpx-device-api`, `gpx-imitator`, `gpx-geocoding`) to that version and remove any path/`packages/` overrides.

Do **not** tag a commit that is only on a feature branch and not yet on `master`. Do **not** tell apps to require `1.5.0` before `v1.5.0` is on `origin`.

## Testing

Use a sibling container from the `gpx-device-api` image (same pattern as device-api worktrees in gpx-docs). See README (Testing). No package test DB.

```bash
composer test
```

`orchestra/testbench` is **require-dev only** — never move it into `require`.
