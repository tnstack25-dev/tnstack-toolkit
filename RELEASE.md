# Release Process

TNStack Toolkit updates are distributed through GitHub Releases.

## Requirements

- The GitHub repository must be public. The plugin downloads public release assets without storing a GitHub token on customer websites.
- The release asset must be named exactly `tnstack-toolkit.zip`.
- The ZIP must contain a single top-level directory named `tnstack-toolkit`.

## First installation

1. Open the latest GitHub Release.
2. Download `tnstack-toolkit.zip`.
3. In WordPress, open Plugins > Add New Plugin > Upload Plugin.
4. Upload the ZIP and activate TNStack Toolkit.

After the first installation, WordPress checks GitHub Releases and displays newer versions in the Plugins screen.

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

GitHub draft releases and pre-releases are not returned by the `releases/latest`
endpoint. Publish the release normally when it is ready for WordPress sites.

## Verify an update

1. Install the ZIP from the previous release on a WordPress site.
2. Publish the next release.
3. In WordPress, open Dashboard > Updates and click Check again.
4. Confirm that TNStack Toolkit appears and completes the standard plugin update flow.
