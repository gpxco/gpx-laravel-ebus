# gpx-laravel-ebus — notes for agents

## Releases always need a git tag

This package is consumed via VCS (`git@github.com:gpxco/gpx-laravel-ebus.git`). Apps pin an exact version (`1.4.2`, `1.5.0`, `1.5.0-rc.1`, …). Composer resolves that **only if a matching tag exists on the remote**.

**Every published version must have a tag.** A tag points at a commit SHA — it does not disappear when the branch merges into `master`.

Tag format: `v` + semver (Composer version without the leading `v`).

| Kind | Example `composer.json` / require | git tag | Where it may be created |
|---|---|---|---|
| Stable | `1.5.0` | `v1.5.0` | Prefer on `master` after merge |
| Release candidate | `1.5.0-rc.1` | `v1.5.0-rc.1` | Allowed on the feature branch |

## How to ship a new version

### 1. Feature branch + release candidate (before trusting master)

Use this while apps still need the previous major line on `master` (e.g. `1.4.x` for older Laravel) and the new line is only ready to try.

1. Work on a branch (e.g. `eng-<N>`). Set `composer.json` `version` to a **pre-release**, e.g. `1.5.0-rc.1`.
2. Push the branch, open a PR into `master`, wait for CI (`composer test`).
3. On that branch commit, create and push the RC tag:

```bash
git checkout eng-3073   # or current feature branch
git pull
git tag v1.5.0-rc.1
git push origin v1.5.0-rc.1
```

4. Point upgrading apps at the RC, e.g. `"gpxco/gpx-laravel-ebus": "1.5.0-rc.1"` (path/`packages/` overrides off). Keep other apps on the previous stable (`1.4.2`) until they upgrade.

Bump RC numbers if needed (`v1.5.0-rc.2`, …). Do **not** move an existing tag.

### 2. Stable release (when it is no longer a candidate)

When the branch is validated and ready as the real release:

1. Set `composer.json` `version` to the stable semver (`1.5.0`).
2. Merge the PR into `master`.
3. **On `master` after merge**, tag and push the stable version:

```bash
git checkout master
git pull origin master
git tag v1.5.0
git push origin v1.5.0
```

4. Bump consuming apps from `1.5.0-rc.*` to `1.5.0`.

The earlier `v1.5.0-rc.1` tag remains on its commit; that is fine. New installs should use the stable tag.

### Parallel lines (optional)

If `master` becomes the new major and older Laravel still needs fixes, cut a maintenance branch from the last `v1.4.*` commit (e.g. `1.4.x`) and only tag `v1.4.*` from there.

## Do not

- Publish a stable `v1.5.0` while the change is still a candidate — use `v1.5.0-rc.N` on the branch instead.
- Tell apps to require `1.5.0` before `v1.5.0` exists on `origin`.
- Retag / force-push an existing version tag.

## Testing

Use a sibling container from the `gpx-device-api` image (same pattern as device-api worktrees in gpx-docs). See README (Testing). No package test DB.

```bash
composer test
```

`orchestra/testbench` is **require-dev only** — never move it into `require`.
