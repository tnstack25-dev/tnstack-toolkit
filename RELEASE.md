# Release Process

TNStack Toolkit updates are distributed through GitHub Releases.

## Create a release

1. Update the version in `tnstack-toolkit.php`:
   - Plugin header: `Version`
   - Constant: `TNSTACK_TOOLKIT_VERSION`
2. Update `Stable tag` and add the changelog entry in `readme.txt`.
3. Commit and push the release changes.
4. Create and push a matching tag:

```bash
git tag v1.0.1
git push origin main
git push origin v1.0.1
```

The GitHub Actions workflow builds the frontend, packages the runtime files as
`tnstack-toolkit.zip`, and attaches that asset to the GitHub Release.

WordPress installations check the public GitHub Releases API and offer an update
when the release version is newer than the installed plugin version.
